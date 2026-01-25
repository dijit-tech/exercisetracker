import requests
import unittest
import os
import glob
import time
from urllib.parse import urlparse

class GuestModeTests(unittest.TestCase):
    BASE_URL = "http://localhost:8000"
    SESSION_DB_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'public', 'sessions', 'db'))

    def setUp(self):
        self.session = requests.Session()

    def test_guest_login_flow(self):
        print(f"\nTargeting: {self.BASE_URL}")

        # 1. Hit Guest Login Endpoint
        # Note: requests follows redirects by default, so we get the dashboard response immediately
        print("1. Logging in as Guest...")
        response = self.session.get(f"{self.BASE_URL}/api/guest_login.php")
        
        # Check if we landed on dashboard or if the redirect happened eventually
        self.assertIn("dashboard.php", response.url)
        self.assertEqual(response.status_code, 200)

        # 2. Verify Session Cookies
        print("2. Verifying Session...")
        cookies = self.session.cookies.get_dict()
        self.assertTrue(len(cookies) > 0, "No cookies found")
        print(f"   Session Cookie: {cookies}")

        # 3. Check Dashboard Content for Guest Banner
        print("3. Checking Dashboard UI...")
        self.assertIn("Guest Mode Active", response.text)
        self.assertIn("Guest User", response.text)
        print("   ✓ Guest Banner found")

        # 4. Check Backend File Creation
        print("4. Checking SQLite File...")
        # We need to find the specific file for this session.
        # Since guest_id is in the session on server side, we can't see it directly here easily 
        # unless we parse it or just look for 'a' new file.
        # But we create a new file with unique ID.
        
        # Simple check: list files in db dir
        files = glob.glob(os.path.join(self.SESSION_DB_DIR, "guest_*.sqlite"))
        self.assertTrue(len(files) > 0, "No guest database files found on disk")
        
        # Get the newest file
        latest_file = max(files, key=os.path.getctime)
        print(f"   ✓ Found Guest DB: {latest_file}")
        
        # 5. Test Write Operation (Create Goal)
        print("5. Creating a Goal (Write Test)...")
        goal_data = {
            "title": "Test Guest Goal",
            "category": "Health & Fitness",
            "start_date": "2023-01-01"
        }
        
        api_resp = self.session.post(
            f"{self.BASE_URL}/api/create_goal.php", 
            json=goal_data,
            headers={"Content-Type": "application/json"}
        )
        
        try:
            json_resp = api_resp.json()
            self.assertTrue(json_resp.get('success'), f"Create goal failed: {api_resp.text}")
            goal_id = json_resp.get('goal_id')
            self.assertIsNotNone(goal_id)
            print(f"   ✓ Goal Created (ID: {goal_id})")
        except Exception as e:
            self.fail(f"Failed to parse API response: {api_resp.text}")

        # 6. Verify Social Seed Data (DustyCronhopper)
        print("6. Verifying Social Features...")
        dash_resp = self.session.get(f"{self.BASE_URL}/dashboard.php")
        
        # Check for Challenge Name
        self.assertIn("Weekly Strength Showdown", dash_resp.text)
        print("   ✓ Weekly Challenge found")
        
        # Check for Rival
        # Note: Leaderboard loads via PHP including challenges.php
        # We look for the rival's username in the HTML
        self.assertIn("DustyCronhopper", dash_resp.text)
        print("   ✓ Rival 'DustyCronhopper' found on dashboard") 

    def tearDown(self):
        # Optional: Logout
        self.session.get(f"{self.BASE_URL}/api/logout.php")

if __name__ == '__main__':
    unittest.main()
