import unittest
import os
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait

class E2EBaseTestCase(unittest.TestCase):
    """Base test case for End-to-End Selenium tests"""
    
    @classmethod
    def setUpClass(cls):
        """Set up Chrome driver once for all tests in the class"""
        cls.base_url = os.environ.get("GOALTRACKER_URL", "http://localhost:8000")
        
        chrome_options = Options()
        chrome_options.add_argument("--start-maximized")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        
        if os.environ.get("HEADLESS", "false").lower() == "true":
            chrome_options.add_argument("--headless")
            
        cls.driver = webdriver.Chrome(options=chrome_options)
        cls.wait = WebDriverWait(cls.driver, 10)
        
    @classmethod
    def tearDownClass(cls):
        """Close browser after all tests"""
        if hasattr(cls, 'driver'):
            cls.driver.quit()

    def get_url(self, path):
        """Helper to get full URL"""
        return f"{self.base_url.rstrip('/')}/{path.lstrip('/')}"
        
