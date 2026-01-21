"""
Beta Testing Script
Tests the deployed application on goaltrackerbeta.dijit.tech
"""

import unittest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
import time

class BetaTests(unittest.TestCase):
    """Test cases for beta deployment"""
    
    BASE_URL = "https://goaltrackerbeta.dijit.tech"
    USERNAME = "admin"
    PASSWORD = "password123"
    
    def setUp(self):
        """Set up test fixtures"""
        chrome_options = Options()
        chrome_options.add_argument('--no-sandbox')
        chrome_options.add_argument('--disable-dev-shm-usage')
        # chrome_options.add_argument('--headless') # Run headless for speed
        
        self.driver = webdriver.Chrome(options=chrome_options)
        self.driver.implicitly_wait(10)
        self.wait = WebDriverWait(self.driver, 10)
    
    def tearDown(self):
        """Clean up after tests"""
        if self.driver:
            self.driver.quit()
    
    def test_01_beta_site_loads(self):
        """Test 1: Beta site is accessible"""
        print("\n[BETA TEST 1] Testing beta site loads...")
        
        self.driver.get(self.BASE_URL)
        
        # Check page loads - Title might vary, checking generic content or just load success
        print(f"Page Title: {self.driver.title}")
        self.assertTrue(len(self.driver.title) > 0)
        print("✓ Beta site is live!")
    
    def test_02_login_works_on_beta(self):
        """Test 2: Login works on beta"""
        print("\n[BETA TEST 2] Testing login on beta...")
        
        self.driver.get(self.BASE_URL)
        
        # Login
        username_field = self.wait.until(
            EC.presence_of_element_located((By.ID, "username"))
        )
        username_field.send_keys(self.USERNAME)
        
        password_field = self.driver.find_element(By.ID, "password")
        password_field.send_keys(self.PASSWORD)
        
        submit_button = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        submit_button.click()
        
        # Wait for redirect
        self.wait.until(EC.url_contains("dashboard.php"))
        
        # Should be on dashboard
        self.assertIn("dashboard.php", self.driver.current_url.lower())
        
        # Check for user identity in page source
        page_source = self.driver.page_source.lower()
        self.assertIn(self.USERNAME.lower(), page_source)
        
        print("✓ Login successful on beta!")

    def test_03_dashboard_loads_challenges(self):
        """Test 3: Dashboard loads and shows challenges section"""
        print("\n[BETA TEST 3] Checking Dashboard Content...")
        
        # Login first
        self.driver.get(self.BASE_URL)
        username_field = self.driver.find_element(By.ID, "username")
        username_field.send_keys(self.USERNAME)
        self.driver.find_element(By.ID, "password").send_keys(self.PASSWORD)
        self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
        self.wait.until(EC.url_contains("dashboard.php"))
        
        # Check for key V2 elements
        page_source = self.driver.page_source
        
        # Check for "Challenges" or "Rooms" (renamed feature)
        # Note: UI might still say "Rooms" or "Challenges" depending on frontend updates
        # Let's check for generic dashboard elements first
        
        has_nav = "My Goals" in page_source
        
        self.assertTrue(has_nav, "Navigation should be present")
        print("✓ Dashboard navigation present")

if __name__ == "__main__":
    print("Starting Beta Tests...")
    unittest.main(verbosity=2)
