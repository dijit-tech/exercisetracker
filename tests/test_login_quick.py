"""
Quick Login Test - Headless Mode
"""

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
import time

BASE_URL = "http://localhost:8000"

def test_login():
    # Setup Chrome in headless mode
    chrome_options = Options()
    chrome_options.add_argument("--headless")
    chrome_options.add_argument("--disable-gpu")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--window-size=1920,1080")
    
    driver = webdriver.Chrome(options=chrome_options)
    wait = WebDriverWait(driver, 10)
    
    try:
        print("=" * 60)
        print("TESTING LOGIN FLOW")
        print("=" * 60)
        
        # Step 1: Navigate to login page
        print("\n1. Loading login page...")
        driver.get(f"{BASE_URL}/index.php")
        time.sleep(1)
        print(f"   ✓ Current URL: {driver.current_url}")
        
        # Step 2: Fill in credentials
        print("\n2. Entering credentials...")
        username_field = wait.until(EC.presence_of_element_located((By.NAME, "username")))
        password_field = driver.find_element(By.NAME, "password")
        
        username_field.send_keys("admin")
        password_field.send_keys("password123")
        print("   ✓ Credentials entered")
        
        # Step 3: Submit form
        print("\n3. Submitting login form...")
        submit_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        submit_btn.click()
        time.sleep(2)
        
        print(f"   Current URL: {driver.current_url}")
        
        # Step 4: Check if redirected to dashboard
        if "dashboard.php" in driver.current_url:
            print("   ✓ Redirected to dashboard successfully!")
            
            # Step 5: Verify dashboard content
            print("\n4. Verifying dashboard content...")
            page_source = driver.page_source
            
            checks = {
                "Welcome message": "Welcome back" in page_source,
                "Active Goals stat": "Active Goals" in page_source,
                "Navigation bar": "My Goals" in page_source and "Track Today" in page_source,
                "Username display": "admin" in page_source.lower()
            }
            
            for check_name, result in checks.items():
                status = "✓" if result else "✗"
                print(f"   {status} {check_name}")
            
            # Test navigation to goals page
            print("\n5. Testing navigation to Goals page...")
            goals_link = driver.find_element(By.LINK_TEXT, "My Goals")
            goals_link.click()
            time.sleep(1)
            
            if "goals.php" in driver.current_url:
                print("   ✓ Navigation to goals.php successful")
                print(f"   Current URL: {driver.current_url}")
                
                # Check for goals page content
                if "Active Goals" in driver.page_source:
                    print("   ✓ Goals page loaded correctly")
                else:
                    print("   ✗ Goals page missing expected content")
            else:
                print(f"   ✗ Failed to navigate to goals page")
                print(f"   Current URL: {driver.current_url}")
            
            # Test navigation to Track Today
            print("\n6. Testing navigation to Track Today page...")
            driver.get(f"{BASE_URL}/dashboard.php")
            time.sleep(1)
            track_link = driver.find_element(By.LINK_TEXT, "Track Today")
            track_link.click()
            time.sleep(1)
            
            if "track_today.php" in driver.current_url:
                print("   ✓ Navigation to track_today.php successful")
                
                if "Track Your Goals" in driver.page_source or "Your Active Goals" in driver.page_source:
                    print("   ✓ Track Today page loaded correctly")
                else:
                    print("   ✗ Track Today page missing expected content")
            else:
                print(f"   ✗ Failed to navigate to Track Today page")
            
            print("\n" + "=" * 60)
            print("LOGIN TEST: SUCCESS ✓")
            print("=" * 60)
            return True
            
        elif "index.php" in driver.current_url:
            print("   ✗ Login failed - still on login page")
            
            # Check for error messages
            if "error=" in driver.current_url:
                error_msg = driver.current_url.split("error=")[1].split("&")[0]
                print(f"   Error: {error_msg}")
            
            if "alert" in driver.page_source.lower():
                print("   Alert message found on page")
            
            print("\n" + "=" * 60)
            print("LOGIN TEST: FAILED ✗")
            print("=" * 60)
            return False
        else:
            print(f"   ✗ Unexpected redirect to: {driver.current_url}")
            print("\n" + "=" * 60)
            print("LOGIN TEST: FAILED ✗")
            print("=" * 60)
            return False
            
    except Exception as e:
        print(f"\n✗ ERROR: {str(e)}")
        print(f"Current URL: {driver.current_url}")
        print(f"Page title: {driver.title}")
        print("\n" + "=" * 60)
        print("LOGIN TEST: ERROR ✗")
        print("=" * 60)
        return False
        
    finally:
        driver.quit()

if __name__ == '__main__':
    success = test_login()
    exit(0 if success else 1)
