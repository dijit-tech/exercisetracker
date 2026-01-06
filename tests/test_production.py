"""
Production Testing Script
Tests the deployed application on exercisetracker.dijit.tech
"""

import unittest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
import time

class ProductionTests(unittest.TestCase):
    """Test cases for production deployment"""
    
    BASE_URL = "https://exercisetracker.dijit.tech"
    
    def setUp(self):
        """Set up test fixtures"""
        chrome_options = Options()
        chrome_options.add_argument('--no-sandbox')
        chrome_options.add_argument('--disable-dev-shm-usage')
        
        self.driver = webdriver.Chrome(options=chrome_options)
        self.driver.implicitly_wait(10)
        self.wait = WebDriverWait(self.driver, 10)
    
    def tearDown(self):
        """Clean up after tests"""
        if self.driver:
            self.driver.quit()
    
    def test_01_production_site_loads(self):
        """Test 1: Production site is accessible"""
        print("\n[PROD TEST 1] Testing production site loads...")
        
        self.driver.get(self.BASE_URL)
        
        # Check page loads
        self.assertIn("Exercise Tracker", self.driver.title)
        print("✓ Production site is live!")
    
    def test_02_login_works_on_production(self):
        """Test 2: Login works on production"""
        print("\n[PROD TEST 2] Testing login on production...")
        
        self.driver.get(self.BASE_URL)
        
        # Login
        username_field = self.wait.until(
            EC.presence_of_element_located((By.ID, "username"))
        )
        username_field.send_keys("testuser")
        
        password_field = self.driver.find_element(By.ID, "password")
        password_field.send_keys("password123")
        
        submit_button = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        submit_button.click()
        
        time.sleep(2)
        
        # Should be on dashboard
        self.assertIn("dashboard", self.driver.current_url.lower())
        self.assertIn("testuser", self.driver.page_source)
        
        print("✓ Login successful on production!")
    
    def test_03_https_enabled(self):
        """Test 3: HTTPS is enabled"""
        print("\n[PROD TEST 3] Testing HTTPS...")
        
        self.driver.get(self.BASE_URL)
        
        current_url = self.driver.current_url
        self.assertTrue(current_url.startswith("https://"), 
                       f"Site should use HTTPS, got: {current_url}")
        
        print("✓ HTTPS is enabled!")

def run_production_tests():
    """Run production tests"""
    print("=" * 80)
    print("PRODUCTION DEPLOYMENT - TEST SUITE")
    print("=" * 80)
    print(f"\nTesting: {ProductionTests.BASE_URL}")
    print("-" * 80)
    
    # Create test suite
    suite = unittest.TestLoader().loadTestsFromTestCase(ProductionTests)
    
    # Run tests
    runner = unittest.TextTestRunner(verbosity=2)
    result = runner.run(suite)
    
    # Print summary
    print("\n" * 2)
    print("=" * 80)
    print("PRODUCTION TEST SUMMARY")
    print("=" * 80)
    print(f"Tests run: {result.testsRun}")
    print(f"Successes: {result.testsRun - len(result.failures) - len(result.errors)}")
    print(f"Failures: {len(result.failures)}")
    print(f"Errors: {len(result.errors)}")
    
    if result.wasSuccessful():
        print("\n🎉 ALL PRODUCTION TESTS PASSED!")
        print("✓ Site is live and working correctly")
    else:
        print("\n⚠️  Some tests failed. Check the output above.")
    
    print("=" * 80)
    
    return result.wasSuccessful()

if __name__ == "__main__":
    success = run_production_tests()
    exit(0 if success else 1)
