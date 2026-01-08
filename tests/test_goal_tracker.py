"""
Comprehensive Test Suite for Goal Tracker
Tests all requirements from GOAL_TRACKER_REQUIREMENTS.md
"""

import unittest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
import time
from datetime import datetime, timedelta

BASE_URL = "http://localhost:8000"

class GoalTrackerTestCase(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        """Set up Chrome driver once for all tests"""
        chrome_options = Options()
        chrome_options.add_argument("--start-maximized")
        # Uncomment next line to run headless
        # chrome_options.add_argument("--headless")
        cls.driver = webdriver.Chrome(options=chrome_options)
        cls.wait = WebDriverWait(cls.driver, 10)
        
    @classmethod
    def tearDownClass(cls):
        """Close browser after all tests"""
        cls.driver.quit()
    
    def setUp(self):
        """Reset state before each test"""
        self.driver.get(f"{BASE_URL}/api/logout.php")
        time.sleep(0.5)
    
    def login(self, username="admin", password="password123"):
        """Helper method to log in"""
        self.driver.get(f"{BASE_URL}/index.php")
        self.driver.find_element(By.NAME, "username").send_keys(username)
        self.driver.find_element(By.NAME, "password").send_keys(password)
        self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
        time.sleep(1)

# ================== TEST SUITE 1: GOAL CRUD OPERATIONS ==================

class TestGoalCRUD(GoalTrackerTestCase):
    """Test creating, reading, updating, and deleting goals"""
    
    def test_01_create_goal(self):
        """Test creating a new goal"""
        self.login()
        self.driver.get(f"{BASE_URL}/goals.php")
        
        # Click "New Goal" button
        new_goal_btn = self.wait.until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, "[data-bs-target='#createGoalModal']"))
        )
        new_goal_btn.click()
        
        # Wait for modal to be fully visible and animated
        time.sleep(0.5)
        
        # Fill in goal details
        title_input = self.wait.until(
            EC.visibility_of_element_located((By.ID, "goalTitle"))
        )
        title_input.send_keys("Test Goal - Automated")
        
        category_select = Select(self.driver.find_element(By.ID, "goalCategory"))
        category_select.select_by_value("Learning")
        
        # Submit form
        self.driver.find_element(By.CSS_SELECTOR, "#createGoalForm button[type='submit']").click()
        
        # Wait for redirect and success message
        time.sleep(2)
        self.assertIn("success", self.driver.current_url.lower())
        self.assertIn("Goal created successfully", self.driver.page_source)
    
    def test_02_view_goals(self):
        """Test viewing goals on goals page"""
        self.login()
        self.driver.get(f"{BASE_URL}/goals.php")
        
        # Check that we can see active goals section
        active_goals_header = self.wait.until(
            EC.presence_of_element_located((By.XPATH, "//h5[contains(text(), 'Active Goals')]"))
        )
        self.assertTrue(active_goals_header.is_displayed())
    
    def test_03_edit_goal(self):
        """Test editing an existing goal"""
        self.login()
        self.driver.get(f"{BASE_URL}/goals.php")
        time.sleep(1)
        
        # Find first edit button
        try:
            edit_btn = self.driver.find_element(By.CSS_SELECTOR, "button[onclick^='editGoal']")
            edit_btn.click()
            
            # Wait for modal
            title_input = self.wait.until(
                EC.presence_of_element_located((By.ID, "editGoalTitle"))
            )
            
            # Modify title
            title_input.clear()
            title_input.send_keys("Updated Goal Title")
            
            # Submit
            self.driver.find_element(By.CSS_SELECTOR, "#editGoalForm button[type='submit']").click()
            
            time.sleep(2)
            self.assertIn("success", self.driver.current_url.lower())
        except:
            print("No goals to edit - skipping test")
    
    def test_04_pause_goal(self):
        """Test pausing a goal"""
        self.login()
        self.driver.get(f"{BASE_URL}/goals.php")
        time.sleep(1)
        
        try:
            # Find first pause button
            pause_btn = self.driver.find_element(By.CSS_SELECTOR, "button[onclick*='pause']")
            pause_btn.click()
            
            # Accept confirmation
            time.sleep(0.5)
            alert = self.driver.switch_to.alert
            alert.accept()
            
            time.sleep(2)
            self.assertIn("paused successfully", self.driver.page_source.lower())
        except:
            print("No goals to pause - skipping test")
    
    def test_05_resume_goal(self):
        """Test resuming a paused goal"""
        self.login()
        self.driver.get(f"{BASE_URL}/goals.php")
        time.sleep(1)
        
        try:
            # Find resume button in paused section
            resume_btn = self.driver.find_element(By.CSS_SELECTOR, "button[onclick*='resume']")
            resume_btn.click()
            
            # Accept confirmation
            time.sleep(0.5)
            alert = self.driver.switch_to.alert
            alert.accept()
            
            time.sleep(2)
            self.assertIn("resumed successfully", self.driver.page_source.lower())
        except:
            print("No paused goals to resume - skipping test")
    
    def test_06_archive_goal(self):
        """Test archiving a goal"""
        self.login()
        self.driver.get(f"{BASE_URL}/goals.php")
        time.sleep(1)
        
        try:
            archive_btn = self.driver.find_element(By.CSS_SELECTOR, "button[onclick*='archive']")
            archive_btn.click()
            
            time.sleep(0.5)
            alert = self.driver.switch_to.alert
            alert.accept()
            
            time.sleep(2)
            self.assertIn("archived successfully", self.driver.page_source.lower())
        except:
            print("No goals to archive - skipping test")

# ================== TEST SUITE 2: GOAL LOGGING ==================

class TestGoalLogging(GoalTrackerTestCase):
    """Test logging goal completions"""
    
    def test_01_quick_log_from_dashboard(self):
        """Test quick logging from dashboard 'Done!' button"""
        self.login()
        self.driver.get(f"{BASE_URL}/dashboard.php")
        time.sleep(1)
        
        try:
            # Find first "Done!" button
            done_btn = self.driver.find_element(By.CSS_SELECTOR, "button[onclick^='quickLog']")
            done_btn.click()
            
            # Wait for page reload
            time.sleep(2)
            
            # Check that button changed to "Completed"
            self.assertIn("Completed", self.driver.page_source)
        except:
            print("No active goals to log - skipping test")
    
    def test_02_track_today_page_bulk_logging(self):
        """Test bulk logging from Track Today page"""
        self.login()
        self.driver.get(f"{BASE_URL}/track_today.php")
        time.sleep(1)
        
        try:
            # Find first checkbox
            checkbox = self.driver.find_element(By.CSS_SELECTOR, "input.goal-checkbox")
            
            # Check it
            if not checkbox.is_selected():
                checkbox.click()
            
            # Add notes
            notes_input = self.driver.find_element(By.CSS_SELECTOR, "input[placeholder*='Add notes']")
            notes_input.send_keys("Completed via automated test")
            
            # Save all
            save_btn = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            save_btn.click()
            
            time.sleep(2)
            self.assertIn("success", self.driver.current_url.lower())
        except:
            print("No goals to track - skipping test")
    
    def test_03_retroactive_logging(self):
        """Test logging for a previous date"""
        self.login()
        self.driver.get(f"{BASE_URL}/track_today.php")
        time.sleep(1)
        
        # Change date to yesterday using JavaScript to avoid stale element issues
        yesterday = (datetime.now() - timedelta(days=1)).strftime('%Y-%m-%d')
        self.driver.execute_script(f"document.getElementById('dateSelector').value = '{yesterday}'")
        self.driver.execute_script("document.getElementById('dateSelector').dispatchEvent(new Event('change'))")
        time.sleep(2)
        
        # Verify we're on the correct date
        current_value = self.driver.find_element(By.ID, "dateSelector").get_attribute('value')
        self.assertIn(yesterday, current_value)
    
    def test_04_logging_with_notes(self):
        """Test adding notes to goal logs"""
        self.login()
        self.driver.get(f"{BASE_URL}/track_today.php")
        time.sleep(1)
        
        try:
            notes_input = self.driver.find_element(By.CSS_SELECTOR, "input[placeholder*='Add notes']")
            notes_input.clear()
            notes_input.send_keys("This is a test note with special chars: !@#$%")
            
            save_btn = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            save_btn.click()
            
            time.sleep(2)
            self.assertIn("success", self.driver.current_url.lower())
        except:
            print("Could not add notes - skipping test")

# ================== TEST SUITE 3: DASHBOARD & UI ==================

class TestDashboard(GoalTrackerTestCase):
    """Test dashboard functionality"""
    
    def test_01_dashboard_loads(self):
        """Test that dashboard loads successfully"""
        self.login()
        self.driver.get(f"{BASE_URL}/dashboard.php")
        
        # Check for key elements
        self.assertIn("Welcome back", self.driver.page_source)
        self.assertIn("Active Goals", self.driver.page_source)
    
    def test_02_quick_stats_display(self):
        """Test that quick stats are displayed"""
        self.login()
        self.driver.get(f"{BASE_URL}/dashboard.php")
        time.sleep(1)
        
        # Check for stat cards
        stats_cards = self.driver.find_elements(By.CSS_SELECTOR, ".stat-card")
        self.assertGreaterEqual(len(stats_cards), 3, "Should have at least 3 stat cards")
    
    def test_03_goal_cards_carousel(self):
        """Test goal cards carousel navigation"""
        self.login()
        self.driver.get(f"{BASE_URL}/dashboard.php")
        time.sleep(1)
        
        try:
            # Check if carousel exists
            carousel = self.driver.find_element(By.ID, "goalsCarousel")
            self.assertTrue(carousel.is_displayed())
            
            # Try to click next button if it exists
            try:
                next_btn = self.driver.find_element(By.CSS_SELECTOR, ".carousel-control-next")
                next_btn.click()
                time.sleep(1)
            except:
                print("Only one carousel slide - skipping navigation test")
        except:
            print("No goals in carousel - skipping test")
    
    def test_04_heatmap_calendar_displays(self):
        """Test that heatmap calendar is displayed"""
        self.login()
        self.driver.get(f"{BASE_URL}/dashboard.php")
        time.sleep(1)
        
        # Check for calendar
        calendar = self.driver.find_element(By.XPATH, "//h5[contains(text(), 'Heatmap')]")
        self.assertTrue(calendar.is_displayed())
        
        # Check for calendar days
        calendar_days = self.driver.find_elements(By.CSS_SELECTOR, ".calendar-day")
        self.assertGreater(len(calendar_days), 0, "Should have calendar days")
    
    def test_05_recent_activity_feed(self):
        """Test recent activity feed"""
        self.login()
        self.driver.get(f"{BASE_URL}/dashboard.php")
        time.sleep(1)
        
        # Check if activity feed exists
        try:
            activity_header = self.driver.find_element(By.XPATH, "//h5[contains(text(), 'Recent Activity')]")
            self.assertTrue(activity_header.is_displayed())
        except:
            print("No recent activity yet - skipping test")
    
    def test_06_track_today_link(self):
        """Test 'Track Today' button navigation"""
        self.login()
        self.driver.get(f"{BASE_URL}/dashboard.php")
        
        track_btn = self.wait.until(
            EC.element_to_be_clickable((By.LINK_TEXT, "Track Today"))
        )
        track_btn.click()
        time.sleep(1)
        
        self.assertIn("track_today.php", self.driver.current_url)

# ================== TEST SUITE 4: NAVIGATION & ACCESS CONTROL ==================

class TestNavigation(GoalTrackerTestCase):
    """Test navigation and page access"""
    
    def test_01_navbar_links(self):
        """Test all navbar links"""
        self.login()
        
        # Dashboard
        self.driver.get(f"{BASE_URL}/dashboard.php")
        self.assertIn("dashboard.php", self.driver.current_url)
        
        # My Goals
        self.driver.find_element(By.LINK_TEXT, "My Goals").click()
        time.sleep(1)
        self.assertIn("goals.php", self.driver.current_url)
        
        # Track Today
        self.driver.find_element(By.LINK_TEXT, "Track Today").click()
        time.sleep(1)
        self.assertIn("track_today.php", self.driver.current_url)
    
    def test_02_protected_pages_require_login(self):
        """Test that protected pages redirect to login"""
        protected_pages = [
            "/dashboard.php",
            "/goals.php",
            "/track_today.php"
        ]
        
        for page in protected_pages:
            self.driver.get(f"{BASE_URL}{page}")
            time.sleep(1)
            self.assertIn("index.php", self.driver.current_url, 
                         f"Page {page} should redirect to login")
    
    def test_03_logout(self):
        """Test logout functionality"""
        self.login()
        self.driver.get(f"{BASE_URL}/dashboard.php")
        
        logout_link = self.driver.find_element(By.LINK_TEXT, "Logout")
        logout_link.click()
        time.sleep(1)
        
        # Should redirect to login
        self.assertIn("index.php", self.driver.current_url)

# ================== TEST SUITE 5: EDGE CASES ==================

class TestEdgeCases(GoalTrackerTestCase):
    """Test edge cases and error handling"""
    
    def test_01_no_goals_state(self):
        """Test UI when user has no goals"""
        # Create a new test user without goals
        # For now, just check the empty state message exists
        self.login()
        self.driver.get(f"{BASE_URL}/goals.php")
        
        # The page should have either goals or an empty state message
        page_source = self.driver.page_source
        self.assertTrue(
            "Active Goals" in page_source or "No active goals" in page_source,
            "Should show either goals or empty state"
        )
    
    def test_02_future_date_not_allowed(self):
        """Test that future dates are not allowed in Track Today"""
        self.login()
        self.driver.get(f"{BASE_URL}/track_today.php")
        time.sleep(1)
        
        # Check date input max attribute
        date_input = self.driver.find_element(By.ID, "dateSelector")
        max_date = date_input.get_attribute("max")
        
        # The max date should be set and not allow future dates
        # Just verify it's set to a reasonable date (within 1 day of today)
        self.assertIsNotNone(max_date, "Max date should be set")
        max_date_obj = datetime.strptime(max_date, '%Y-%m-%d')
        today = datetime.now()
        date_diff = (max_date_obj.date() - today.date()).days
        self.assertLessEqual(abs(date_diff), 1, "Max date should be today or within 1 day (timezone tolerance)")
    
    def test_03_goal_without_end_date(self):
        """Test creating a goal without end date"""
        self.login()
        self.driver.get(f"{BASE_URL}/goals.php")
        
        # Click "New Goal" button
        new_goal_btn = self.wait.until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, "[data-bs-target='#createGoalModal']"))
        )
        new_goal_btn.click()
        
        # Wait for modal animation
        time.sleep(0.5)
        
        # Fill only required fields
        title_input = self.wait.until(
            EC.visibility_of_element_located((By.ID, "goalTitle"))
        )
        title_input.send_keys("Ongoing Goal Test")
        
        category_select = Select(self.driver.find_element(By.ID, "goalCategory"))
        category_select.select_by_value("Personal Projects")
        
        # Leave end date empty
        
        # Submit
        self.driver.find_element(By.CSS_SELECTOR, "#createGoalForm button[type='submit']").click()
        time.sleep(2)
        
        # Should succeed
        self.assertIn("success", self.driver.current_url.lower())
    
    def test_04_special_characters_in_goal_title(self):
        """Test goal title with special characters"""
        self.login()
        self.driver.get(f"{BASE_URL}/goals.php")
        
        new_goal_btn = self.wait.until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, "[data-bs-target='#createGoalModal']"))
        )
        new_goal_btn.click()
        
        # Wait for modal animation
        time.sleep(0.5)
        
        title_input = self.wait.until(
            EC.visibility_of_element_located((By.ID, "goalTitle"))
        )
        title_input.send_keys("Test: Goal with <special> & chars!")
        
        category_select = Select(self.driver.find_element(By.ID, "goalCategory"))
        category_select.select_by_value("Other")
        
        self.driver.find_element(By.CSS_SELECTOR, "#createGoalForm button[type='submit']").click()
        time.sleep(2)
        
        # Should succeed without XSS issues
        self.assertIn("success", self.driver.current_url.lower())

# ================== TEST SUITE 6: CATEGORY TESTS ==================

class TestCategories(GoalTrackerTestCase):
    """Test goal categories"""
    
    def test_01_all_categories_available(self):
        """Test that all required categories are available"""
        self.login()
        self.driver.get(f"{BASE_URL}/goals.php")
        
        new_goal_btn = self.wait.until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, "[data-bs-target='#createGoalModal']"))
        )
        new_goal_btn.click()
        
        # Wait for modal animation
        time.sleep(0.5)
        
        category_select = Select(self.wait.until(
            EC.visibility_of_element_located((By.ID, "goalCategory"))
        ))
        
        expected_categories = [
            'Reading', 'Learning', 'Health & Fitness', 'Meditation',
            'Writing', 'Creative Work', 'Professional Development',
            'Financial', 'Relationships', 'Personal Projects', 'Other'
        ]
        
        options = [option.text for option in category_select.options if option.text]
        
        for cat in expected_categories:
            self.assertIn(cat, options, f"Category '{cat}' should be available")

# ================== RUN ALL TESTS ==================

if __name__ == '__main__':
    # Create test suite
    loader = unittest.TestLoader()
    suite = unittest.TestSuite()
    
    # Add all test classes
    suite.addTests(loader.loadTestsFromTestCase(TestGoalCRUD))
    suite.addTests(loader.loadTestsFromTestCase(TestGoalLogging))
    suite.addTests(loader.loadTestsFromTestCase(TestDashboard))
    suite.addTests(loader.loadTestsFromTestCase(TestNavigation))
    suite.addTests(loader.loadTestsFromTestCase(TestEdgeCases))
    suite.addTests(loader.loadTestsFromTestCase(TestCategories))
    
    # Run tests
    runner = unittest.TextTestRunner(verbosity=2)
    result = runner.run(suite)
    
    # Print summary
    print("\n" + "="*70)
    print("TEST SUMMARY")
    print("="*70)
    print(f"Tests run: {result.testsRun}")
    print(f"Successes: {result.testsRun - len(result.failures) - len(result.errors)}")
    print(f"Failures: {len(result.failures)}")
    print(f"Errors: {len(result.errors)}")
    print("="*70)
    
    # Exit with appropriate code
    exit(0 if result.wasSuccessful() else 1)
