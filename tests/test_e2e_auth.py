from .base_e2e import E2EBaseTestCase
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
import time

class AuthE2ETests(E2EBaseTestCase):
    """Test cases for Exercise Tracker Authentication"""
    
    def test_login_logout_flow(self):
        """Test complete login and logout flow"""
        driver = self.driver
        base_url = self.base_url
        
        # 1. Login
        driver.get(f"{base_url}/index.php")
        driver.find_element(By.NAME, "username").send_keys("admin")
        driver.find_element(By.NAME, "password").send_keys("password123")
        driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
        
        # Verify dashboard
        self.wait.until(EC.url_contains("dashboard.php"))
        self.assertIn("Dashboard", driver.title)
        
        # 2. Verify Session Persistence (Refresh)
        driver.refresh()
        self.assertIn("dashboard.php", driver.current_url)
        
        # 3. Logout
        driver.get(f"{base_url}/api/logout.php")
        self.wait.until(EC.url_contains("index.php"))
        
        # 4. Verify Auth Restriction (Try accessing dashboard)
        driver.get(f"{base_url}/dashboard.php")
        self.wait.until(EC.url_contains("index.php"))
        
    def test_invalid_login(self):
        """Test login with invalid credentials"""
        driver = self.driver
        base_url = self.base_url
        
        driver.get(f"{base_url}/index.php")
        driver.find_element(By.NAME, "username").send_keys("wronguser")
        driver.find_element(By.NAME, "password").send_keys("wrongpass")
        driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
        
        # Should stay on index.php and show error
        self.wait.until(EC.presence_of_element_located((By.CLASS_NAME, "alert-danger")))
        self.assertIn("index.php", driver.current_url)
