from .base_e2e import E2EBaseTestCase
import requests
import time
from datetime import datetime, timedelta
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC

# Default Admin User
ADMIN_USER = {"username": "admin", "password": "password123"}

class BugFixesE2ETest(E2EBaseTestCase):
    
    @classmethod
    def setUpClass(cls):
        super().setUpClass()
        # Setup Requests Session for data setup
        cls.session = requests.Session()
        cls.login_api()

    @classmethod
    def login_api(cls):
        response = cls.session.post(
            f"{cls.base_url}/api/login.php",
            data=ADMIN_USER
        )
        if response.status_code != 200:
            # Fallback or strict error? 
            # If API login fails, tests can't setup data.
            pass

    def setUp(self):
        # We don't call super setup as Base is just class methods
        # Ensure clean state for UI tests
        self.driver.get(self.get_url("api/logout.php"))
        self.login_selenium()
    
    def login_selenium(self):
        self.driver.get(self.get_url("index.php"))
        self.driver.find_element(By.NAME, "username").send_keys(ADMIN_USER["username"])
        self.driver.find_element(By.NAME, "password").send_keys(ADMIN_USER["password"])
        self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
        # Wait for dashboard
        self.wait.until(EC.url_contains("dashboard.php"))

    # ================= HELPERS (API) =================

    def create_goal_api(self, title, days_offset=30, start_offset=0):
        start_date = (datetime.now() + timedelta(days=start_offset)).strftime("%Y-%m-%d")
        end_date = (datetime.now() + timedelta(days=days_offset)).strftime("%Y-%m-%d")
        
        response = self.session.post(
            f"{self.base_url}/api/create_goal.php",
            json={
                "goal_title": title,
                "goal_category": "Testing",
                "start_date": start_date,
                "end_date": end_date
            }
        )
        return response.json().get('goal_id')

    def create_challenge_api(self, name, days_offset=30, start_offset=0):
        start_date = (datetime.now() + timedelta(days=start_offset)).strftime("%Y-%m-%d")
        end_date = (datetime.now() + timedelta(days=days_offset)).strftime("%Y-%m-%d")
        
        response = self.session.post(
            f"{self.base_url}/api/create_challenge.php",
            json={
                "name": name,
                "description": "Bug fix test",
                "category": "Testing",
                "privacy": "public",
                "start_date": start_date,
                "end_date": end_date
            }
        )
        return response.json().get('challenge_id')

    def add_goal_to_challenge_api(self, challenge_id, goal_id):
        self.session.post(
            f"{self.base_url}/api/add_goal_to_challenge.php",
            data={"challenge_id": challenge_id, "goal_id": goal_id}
        )

    def log_goal_api(self, goal_id, date, completed=True):
        self.session.post(
            f"{self.base_url}/api/log_goal_completion.php",
            json={
                "goal_id": goal_id,
                "date": date,
                "completed": completed,
                "notes": "Automated test"
            }
        )

    # ================= TESTS =================

    def test_bug_success_rate_100_percent(self):
        """Bug Fix: Last 7 days success rate should be 100% (not 114%) for 7/7 completions"""
        # 1. Create Goal
        goal_id = self.create_goal_api(f"Success Rate Test {int(time.time())}")
        
        # 2. Log completion for last 7 days (including today)
        for i in range(7): # 0 to 6
            date_str = (datetime.now() - timedelta(days=i)).strftime("%Y-%m-%d")
            self.log_goal_api(goal_id, date_str, True)
            
        # 3. Refresh Dashboard
        self.driver.get(self.get_url("dashboard.php"))
        
        # 4. Check Stat Card
        stat_cards = self.driver.find_elements(By.CLASS_NAME, "stat-card")
        found = False
        for card in stat_cards:
            if "Success Rate" in card.text:
                rate_text = card.find_element(By.TAG_NAME, "h1").text
                # Should be "100%"
                self.assertIn("100%", rate_text)
                # Should NOT be "114%"
                self.assertNotIn("114%", rate_text)
                found = True
                break
        
        self.assertTrue(found, "Success Rate card not found")

    def test_bug_challenge_auto_archive(self):
        """Bug Fix: Challenges remain open after end date -> Should auto-archive on visit"""
        # 1. Create Challenge ending Yesterday
        c_name = f"Past Challenge {int(time.time())}"
        c_id = self.create_challenge_api(c_name, days_offset=-1, start_offset=-10)
        
        # 2. Visit Challenges Page (triggers update)
        self.driver.get(self.get_url("challenges.php"))
        
        # 3. Verify it is in Archived section
        self.assertIn(c_name, self.driver.page_source)
        try:
            card = self.driver.find_element(By.XPATH, f"//div[contains(@class, 'card-body')][h5[contains(text(), '{c_name}')]]/..")
            badge = card.find_element(By.CLASS_NAME, "challenge-status-badge")
            self.assertEqual(badge.text.strip(), "Archived")
        except Exception as e:
            self.fail(f"Could not find archived status for expired challenge: {e}")

    def test_bug_challenge_month_scrolling(self):
        """Bug Fix: Archived challenges should restrict month scrolling"""
        # 1. Reuse or Create Challenge ending Yesterday
        c_name = f"Scroll Test {int(time.time())}"
        c_id = self.create_challenge_api(c_name, days_offset=-1, start_offset=-32) 
        
        # 2. Go to Challenge Page without month param
        self.driver.get(self.get_url(f"challenge.php?id={c_id}"))
        
        # 3. Check Month displayed (buttons)
        buttons = self.driver.find_elements(By.XPATH, "//button[contains(@onclick, 'month=')]")
        if len(buttons) >= 2:
            next_btn = buttons[1]
            is_disabled = next_btn.get_attribute("disabled")
            self.assertTrue(is_disabled is not None or "disabled" in next_btn.get_attribute("class"), "Next month button should be disabled")

    def test_bug_days_remaining_context(self):
        """Bug Fix: Days left on dashboard should be based on Challenge End Date"""
        # 1. Create Challenge (Ends in 5 days)
        c_name = f"Short Challenge {int(time.time())}"
        c_id = self.create_challenge_api(c_name, days_offset=5)
        
        # 2. Create Goal (Ends in 100 days)
        g_title = f"Long Goal {int(time.time())}"
        g_id = self.create_goal_api(g_title, days_offset=100)
        
        # 3. Add Goal to Challenge
        self.add_goal_to_challenge_api(c_id, g_id)
        
        # 4. Visit Dashboard
        self.driver.get(self.get_url("dashboard.php"))
        
        # 5. Find Goal Card
        card_xpath = f"//div[contains(@class, 'goal-card')]//h6[contains(text(), '{g_title}')]/ancestor::div[contains(@class, 'card-body')]"
        card = self.wait.until(EC.presence_of_element_located((By.XPATH, card_xpath)))
        card_text = card.text
        
        # 6. Assert "5 days left" (matches challenge) NOT "100 days left"
        self.assertIn("5 days left", card_text)
        self.assertNotIn("100 days left", card_text)

    def test_bug_streak_visibility(self):
        """Bug Fix: Streaks displayed only on personal dashboard, not shared"""
        # 1. Create Challenge + Goal (Shared)
        c_id = self.create_challenge_api(f"Shared Streak Test {int(time.time())}", days_offset=30)
        g_shared_title = f"Shared Goal {int(time.time())}"
        g_shared_id = self.create_goal_api(g_shared_title)
        self.add_goal_to_challenge_api(c_id, g_shared_id)
        
        # 2. Create Personal Goal (Not in Challenge)
        g_personal_title = f"Personal Goal {int(time.time())}"
        self.create_goal_api(g_personal_title)
        
        # 3. Visit Dashboard
        self.driver.get(self.get_url("dashboard.php"))
        
        # 4. Check Shared Goal -> NO Streak Badge
        shared_card = self.driver.find_element(By.XPATH, f"//h6[contains(text(), '{g_shared_title}')]/ancestor::div[contains(@class, 'card-body')]")
        try:
            shared_card.find_element(By.CLASS_NAME, "badge-streak")
            found_streak_shared = True
        except:
            found_streak_shared = False
        self.assertFalse(found_streak_shared)
        
        # 5. Check Personal Goal -> YES Streak Badge
        personal_card = self.driver.find_element(By.XPATH, f"//h6[contains(text(), '{g_personal_title}')]/ancestor::div[contains(@class, 'card-body')]")
        try:
            personal_card.find_element(By.CLASS_NAME, "badge-streak")
            found_streak_personal = True
        except:
            found_streak_personal = False
        self.assertTrue(found_streak_personal)
