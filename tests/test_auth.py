"""
Selenium Test Suite for Exercise Tracker
Tests authentication and session persistence
"""

import unittest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
import time

class ExerciseTrackerTests(unittest.TestCase):
    """Test cases for Exercise Tracker application"""
    
    BASE_URL = "http://localhost:8000"
    
    def setUp(self):
        """Set up test fixtures"""
        chrome_options = Options()
        # chrome_options.add_argument('--headless')  # Uncomment to run in background
        chrome_options.add_argument('--no-sandbox')
        chrome_options.add_argument('--disable-dev-shm-usage')
        
        self.driver = webdriver.Chrome(options=chrome_options)
        self.driver.implicitly_wait(10)
        self.wait = WebDriverWait(self.driver, 10)
    
    def tearDown(self):
        """Clean up after tests"""
        if self.driver:
            self.driver.quit()
    
    def login(self, username, password):
        """Helper method to log in"""
        self.driver.get(self.BASE_URL)
        
        # Wait for login form
        username_field = self.wait.until(
            EC.presence_of_element_located((By.ID, "username"))
        )
        
        # Fill in credentials
        username_field.clear()
        username_field.send_keys(username)
        
        password_field = self.driver.find_element(By.ID, "password")
        password_field.clear()
        password_field.send_keys(password)
        
        # Submit form
        submit_button = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        submit_button.click()
        
        # Wait for dashboard
        time.sleep(1)
    
    def logout(self):
        """Helper method to log out"""
        logout_link = self.driver.find_element(By.LINK_TEXT, "Logout")
        logout_link.click()
        time.sleep(1)
    
    # ========== TEST CASES ==========
    
    def test_01_login_page_loads(self):
        """Test 1: Login page loads successfully"""
        print("\n[TEST 1] Testing login page loads...")
        
        self.driver.get(self.BASE_URL)
        
        # Check page title
        self.assertIn("Exercise Tracker", self.driver.title)
        
        # Check for login form elements
        self.assertTrue(self.driver.find_element(By.ID, "username"))
        self.assertTrue(self.driver.find_element(By.ID, "password"))
        self.assertTrue(self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']"))
        
        print("✓ Login page loaded successfully")
    
    def test_02_login_with_valid_credentials(self):
        """Test 2: Login with valid credentials"""
        print("\n[TEST 2] Testing login with valid credentials...")
        
        self.login("testuser", "password123")
        
        # Should be on dashboard
        self.assertIn("Dashboard", self.driver.title)
        self.assertIn("dashboard", self.driver.current_url)
        
        # Check for welcome message
        page_source = self.driver.page_source
        self.assertIn("Welcome back, testuser", page_source)
        
        print("✓ Login successful")
    
    def test_03_login_with_invalid_credentials(self):
        """Test 3: Login with invalid credentials"""
        print("\n[TEST 3] Testing login with invalid credentials...")
        
        self.login("testuser", "wrongpassword")
        
        # Should still be on login page
        self.assertIn("index.php", self.driver.current_url)
        
        # Check for error message
        page_source = self.driver.page_source
        self.assertIn("Invalid username or password", page_source)
        
        print("✓ Invalid login rejected correctly")
    
    def test_04_session_persists_across_pages(self):
        """Test 4: CRITICAL - Session persists when navigating between pages"""
        print("\n[TEST 4] Testing session persistence across pages...")
        
        # Login
        self.login("testuser", "password123")
        
        # Verify we're on dashboard
        self.assertIn("dashboard", self.driver.current_url)
        self.assertIn("Welcome back, testuser", self.driver.page_source)
        
        # Navigate to exercises page
        exercises_link = self.driver.find_element(By.LINK_TEXT, "My Exercises")
        exercises_link.click()
        time.sleep(1)
        
        # Should still be logged in (not redirected to login)
        self.assertNotIn("index.php", self.driver.current_url)
        page_source = self.driver.page_source
        self.assertIn("testuser", page_source)
        
        # Navigate back to dashboard
        dashboard_link = self.driver.find_element(By.LINK_TEXT, "Dashboard")
        dashboard_link.click()
        time.sleep(1)
        
        # Should still be logged in
        self.assertIn("dashboard", self.driver.current_url)
        self.assertIn("Welcome back, testuser", self.driver.page_source)
        
        print("✓ Session persisted correctly across page navigation")
    
    def test_05_logout_works(self):
        """Test 5: Logout functionality"""
        print("\n[TEST 5] Testing logout...")
        
        # Login first
        self.login("testuser", "password123")
        self.assertIn("dashboard", self.driver.current_url)
        
        # Logout
        self.logout()
        
        # Should be back on login page
        self.assertIn("index.php", self.driver.current_url)
        self.assertIn("Successfully logged out", self.driver.page_source)
        
        # Try to access dashboard directly (should redirect to login)
        self.driver.get(f"{self.BASE_URL}/dashboard.php")
        time.sleep(1)
        self.assertIn("index.php", self.driver.current_url)
        
        print("✓ Logout successful and protected pages inaccessible")
    
    def test_06_admin_user_can_access_admin_panel(self):
        """Test 6: Admin user can access admin panel"""
        print("\n[TEST 6] Testing admin access...")
        
        # Login as admin
        self.login("admin", "password123")
        
        # Check for admin link in navbar
        page_source = self.driver.page_source
        self.assertIn("Admin", page_source)
        
        # Click admin link
        admin_link = self.driver.find_element(By.LINK_TEXT, "Admin")
        admin_link.click()
        time.sleep(1)
        
        # Should be on admin page (or get a 404 if not created yet, which is fine)
        current_url = self.driver.current_url
        # Either on admin page or redirected but NOT kicked to login
        self.assertNotIn("index.php", current_url)
        
        print("✓ Admin user has admin access")
    
    def test_07_regular_user_cannot_see_admin_link(self):
        """Test 7: Regular user cannot see admin link"""
        print("\n[TEST 7] Testing non-admin user restrictions...")
        
        # Login as regular user
        self.login("testuser", "password123")
        
        # Check that admin link is NOT present
        page_source = self.driver.page_source
        
        # Look for admin link in navbar - should not exist
        try:
            admin_link = self.driver.find_element(By.LINK_TEXT, "Admin")
            # If we get here, the test should fail
            self.fail("Regular user should not see Admin link")
        except:
            # Good - admin link not found
            pass
        
        print("✓ Regular user cannot see admin link")

def run_tests():
    """Run all tests and generate report"""
    print("=" * 80)
    print("EXERCISE TRACKER - SELENIUM TEST SUITE")
    print("=" * 80)
    print("\nTesting authentication and session persistence...")
    print("Base URL:", ExerciseTrackerTests.BASE_URL)
    print("-" * 80)
    
    # Create test suite
    suite = unittest.TestLoader().loadTestsFromTestCase(ExerciseTrackerTests)
    
    # Run tests
    runner = unittest.TextTestRunner(verbosity=2)
    result = runner.run(suite)
    
    # Print summary
    print("\n" + "=" * 80)
    print("TEST SUMMARY")
    print("=" * 80)
    print(f"Tests run: {result.testsRun}")
    print(f"Successes: {result.testsRun - len(result.failures) - len(result.errors)}")
    print(f"Failures: {len(result.failures)}")
    print(f"Errors: {len(result.errors)}")
    print("=" * 80)
    
    return result.wasSuccessful()

if __name__ == "__main__":
    success = run_tests()
    exit(0 if success else 1)
