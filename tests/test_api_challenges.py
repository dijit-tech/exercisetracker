"""
Comprehensive Test Suite for Challenges Feature
Goal Tracker - Challenges Feature Tests
Date: January 15, 2026
"""

import requests
import json
from datetime import datetime, timedelta
import os

# Base URL (default to localhost for local testing)
BASE_URL = os.environ.get("GOALTRACKER_URL", "http://localhost:8000")

# Test credentials
ADMIN_USER = {"username": "admin", "password": "password123"}
TEST_USER = {"username": "testuser", "password": "password123"}

# Session objects to maintain cookies
headers = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
}
admin_session = requests.Session()
admin_session.headers.update(headers)
test_session = requests.Session()
test_session.headers.update(headers)

def print_test_header(test_name):
    print(f"\n{'='*70}")
    print(f"TEST: {test_name}")
    print(f"{'='*70}")

def print_success(message):
    print(f"[OK] {message}")

def print_error(message):
    print(f"[X] {message}")

def login_user(session, username, password):
    """Login and return success status"""
    url = f"{BASE_URL}/api/login.php"
    try:
        response = session.post(
            url,
            data={"username": username, "password": password}
        )
        if response.status_code not in [200, 302]:
            print(f"[X] Login failed at {url}. Status: {response.status_code}")
            return False
            
        # Check if we were redirected to login page (failure)
        if "index.php" in response.url or "error=" in response.url:
             print(f"[X] Login redirected to {response.url} (Failed)")
             return False
             
        if "login.php" in response.url:
             print(f"[X] Login stayed at {response.url} (Failed). Content start: {response.text[:500]}")
             return False
            
        return True
    except Exception as e:
        print(f"[X] Login exception: {e}")
        return False

def create_goal(session, title, category="Health & Fitness"):
    """Create a goal and return goal ID"""
    response = session.post(
        f"{BASE_URL}/api/create_goal.php",
        json={
            "goal_title": title,
            "goal_category": category,
            "start_date": datetime.now().strftime("%Y-%m-%d"),
            "end_date": (datetime.now() + timedelta(days=30)).strftime("%Y-%m-%d")
        }
    )
    if response.status_code == 200:
        data = response.json()
        return data.get('goal_id')
    else:
        print(f"  Goal creation failed: {response.status_code} - {response.text[:200]}")
    return None

# ============================================
# CATEGORY 1: CHALLENGE CRUD OPERATIONS
# ============================================

def test_create_challenge():
    """Test 1: Create a new challenge"""
    print_test_header("Create Challenge")
    
    # Login as admin
    if not login_user(admin_session, ADMIN_USER["username"], ADMIN_USER["password"]):
        print_error("Failed to login")
        return False
    
    # Create a goal first
    goal_id = create_goal(admin_session, "Daily Workout")
    
    if not goal_id:
        print_error("Could not create goal for challenge")
        return False
        
    print_success(f"Goal created (ID: {goal_id})")

    # Create Challenge
    url = f"{BASE_URL}/api/create_challenge.php"
    challenge_data = {
        "name": f"Test Challenge {datetime.now().strftime('%H%M%S')}",
        "description": "A test challenge created by automation",
        "end_date": (datetime.now() + timedelta(days=30)).strftime("%Y-%m-%d"),
        "privacy": "public",
        "category": "Health & Fitness"
    }
    
    response = admin_session.post(url, json=challenge_data)
    
    if response.status_code == 200:
        data = response.json()
        if data.get('success'):
            challenge_id = data.get('challenge_id')
            print_success(f"Challenge created successfully (ID: {challenge_id})")
            return challenge_id, challenge_data['name']
        else:
            print_error(f"Failed to create challenge: {data.get('error')}")
            print(response.text)
    else:
        print_error(f"HTTP Error: {response.status_code}")
        print(response.text)
    
    return None, None

def test_dashboard_grouping(challenge_id, challenge_name):
    """Test 1.5: Verify Dashboard logic groups goals by challenge"""
    print_test_header("Dashboard Goal Grouping")
    
    # 1. Create a "Personal Goal" (not attached to any challenge)
    personal_goal_title = f"Personal Goal {datetime.now().strftime('%H%M%S')}"
    p_goal_id = create_goal(admin_session, personal_goal_title, "Other")
    if not p_goal_id:
        print_error("Failed to create personal goal for dashboard test")
        return False
        
    print_success(f"Created Personal Goal: {personal_goal_title}")

    # 2. Create a "Challenge Goal" and link it to the challenge
    # Because dashboard only shows challenges that have active goals
    c_goal_title = f"Challenge Goal {datetime.now().strftime('%H%M%S')}"
    # Use "Health & Fitness" because test_create_challenge uses that category
    c_goal_id = create_goal(admin_session, c_goal_title, "Health & Fitness")
    
    if c_goal_id:
        url = f"{BASE_URL}/api/add_goal_to_challenge.php"
        data = { "challenge_id": challenge_id, "goal_id": c_goal_id }
        res = admin_session.post(url, data=data) 
        if res.json().get('success'):
            print_success(f"Linked goal '{c_goal_title}' to challenge '{challenge_name}'")
        else:
            print_error(f"Failed to link goal to challenge: {res.text}")
            return False
    else:
        print_error("Failed to create challenge goal")
        return False

    # 3. Fetch Dashboard
    url = f"{BASE_URL}/dashboard.php"
    response = admin_session.get(url)
    
    if response.status_code != 200:
        print_error(f"Failed to load dashboard. Status: {response.status_code}")
        return False
        
    content = response.text
    
    # 4. Validation
    # Check if Challenge Name header exists
    if challenge_name in content:
        print_success(f"Dashboard contains Challenge Section: '{challenge_name}'")
    else:
        print_error(f"Dashboard MISSING Challenge Section: '{challenge_name}'")
        return False

    # Check for Personal Goals section
    if "Personal Goals" in content:
        print_success("Dashboard contains 'Personal Goals' section")
    else:
        print_error("Dashboard MISSING 'Personal Goals' section")
        return False
        
    # Check if our specific personal goal is rendered
    if personal_goal_title in content:
        print_success(f"Dashboard lists personal goal: '{personal_goal_title}'")
    else:
        print_error(f"Dashboard missing specific personal goal: '{personal_goal_title}'")
        return False

    # 4b. Verify Leaderboard Changes
    # Global Leaderboard should be GONE
    if "Goal Achievement Leaderboard" in content:
        print_error("Global 'Goal Achievement Leaderboard' still present (Should be removed)")
        return False
    else:
        print_success("Global Leaderboard removed successfully")

    # Quick View Leaderboard should be PRESENT for the Challenge section
    # We look for the text "Top Performers" or "Quick View" which I added
    if "Top Performers" in content:
        print_success("Per-Challenge 'Top Performers' leaderboard found")
    else:
        print_error("Per-Challenge 'Top Performers' leaderboard MISSING")
        # print(content[:2000]) # Debug
        return False
        
    # 5. Verify "View Full Board" link points to challenge.php (singular)
    expected_link = f'href="/challenge.php?id={challenge_id}"'
    if expected_link in content:
        print_success(f"Correct 'View Full Board' link found: {expected_link}")
    else:
        print_error(f"Missing or incorrect 'View Full Board' link. Expected: {expected_link}")
        # Search for what it might be
        if f'href="/challenges.php?id={challenge_id}"' in content:
             print_error("Found incorrect link pointing to plural 'challenges.php'")
        return False

    return True

def test_get_challenge(challenge_id):
    """Test 2: Get challenge details"""
    print_test_header("Get Challenge Details")
    
    url = f"{BASE_URL}/api/get_challenge.php?challenge_id={challenge_id}"
    response = admin_session.get(url)
    
    if response.status_code == 200:
        data = response.json()
        if data.get('success'):
            print_success("Challenge details retrieved")
            print(f"  Name: {data['challenge']['name']}")
            print(f"  Status: {data['challenge']['status']}")
            return True
        else:
            print_error(f"API Error: {data.get('error')}")
    else:
        print_error(f"HTTP Error: {response.status_code}")
        
    return False

def test_join_challenge(challenge_id):
    """Test 3: Another user joins the challenge"""
    print_test_header("Join Challenge (Add Goal to Challenge)")
    
    # Login as test user
    if not login_user(test_session, TEST_USER["username"], TEST_USER["password"]):
        print_error("Test user login failed")
        return False
        
    # Create goal for test user
    goal_id = create_goal(test_session, "Test User Goal")
    if not goal_id:
        return False
        
    # Join challenge (by adding goal to it)
    url = f"{BASE_URL}/api/add_goal_to_challenge.php"
    data = {
        "challenge_id": challenge_id,
        "goal_id": goal_id
    }
    
    response = test_session.post(url, data=data) # Using form-data as per API
    
    try:
        res_data = response.json()
        if res_data.get('success'):
            print_success("User joined challenge successfully")
            return goal_id
        else:
            print_error(f"Failed to join: {res_data.get('error')}")
            return False
    except Exception as e:
        print_error(f"Invalid JSON: {response.text[:200]}")
        return False

def test_leaderboard(challenge_id):
    """Test 4: View Leaderboard"""
    print_test_header("View Leaderboard")
    
    url = f"{BASE_URL}/api/challenge_leaderboard.php?challenge_id={challenge_id}"
    response = admin_session.get(url)
    
    if response.status_code == 200:
        data = response.json()
        if data.get('success'):
            count = len(data.get('leaderboard', []))
            print_success(f"Leaderboard retrieved ({count} entries)")
            return True
        else:
            print_error(f"Failed to get leaderboard: {data.get('error')}")
    else:
        print_error(f"HTTP Error: {response.status_code}")
    
    return False

def test_feed(challenge_id):
    """Test 5: Post to feed"""
    print_test_header("Post to Feed")
    
    url = f"{BASE_URL}/api/post_to_challenge.php"
    data = {
        "challenge_id": challenge_id,
        "content": "Hello everyone! This is a test post."
    }
    
    response = admin_session.post(url, data=data)
    
    try:
        res_data = response.json()
        if res_data.get('success'):
            print_success("Posted to feed successfully")
            
            # Verify feed
            feed_url = f"{BASE_URL}/api/challenge_feed.php?challenge_id={challenge_id}"
            feed_res = admin_session.get(feed_url)
            feed_data = feed_res.json()
            if len(feed_data.get('posts', [])) > 0:
                print_success("Feed post verified")
                return True
        else:
            print_error(f"Failed to post: {res_data.get('error')}")
    except:
        print_error("Failed to parse response")
        
    return False

def test_delete_challenge(challenge_id):
    """Test 6: Delete a challenge (Soft delete)"""
    print_test_header("Delete Challenge")
    
    url = f"{BASE_URL}/api/delete_challenge.php"
    data = {"challenge_id": challenge_id}
    
    response = admin_session.post(url, json=data)
    
    try:
        res_data = response.json()
        if res_data.get('success'):
            print_success("Challenge deleted successfully")
            
            # Verify deletion by trying to get it
            get_url = f"{BASE_URL}/api/get_challenge.php?challenge_id={challenge_id}"
            get_res = admin_session.get(get_url)
            get_data = get_res.json()
            
            # Should either return 404, error, or status 'deleted'
            if not get_data.get('success') or get_data.get('challenge', {}).get('status') == 'deleted':
                print_success("Deletion verification passed (Challenge not found or status deleted)")
                return True
            else:
                 print_error(f"Challenge still Active after delete! Status: {get_data.get('challenge', {}).get('status')}")
                 return False
        else:
            print_error(f"Failed to delete: {res_data.get('error')}")
            return False
    except Exception as e:
        print_error(f"Error during delete test: {e}")
        return False

def test_invite_flow(challenge_id):
    """Test 7: Invite -> Respond Flow"""
    print_test_header("Invite & Join Flow")
    
    # Needs two users: Admin (Creator) and TestUser (Invitee)
    
    # 1. Admin sends invite to TestUser
    # We need TestUser's email. Based on login func, it is probably hardcoded in DB seed?
    # Actually, we can just guess/use "test@exercisetracker.local" based on db_init logs seen earlier
    invitee_email = "test@exercisetracker.local"
    
    invite_url = f"{BASE_URL}/api/invite_to_challenge.php"
    invite_data = {
        "challenge_id": challenge_id,
        "invitee_email": invitee_email
    }
    
    print(f"Sending invite to {invitee_email}...")
    res = admin_session.post(invite_url, json=invite_data)
    
    invite_id = None
    try:
        data = res.json()
        if data.get('success'):
            invite_id = data.get('invite_id')
            print_success(f"Invite sent successfully (ID: {invite_id})")
        else:
            print_error(f"Failed to send invite: {data.get('error')}")
            # If invite already exists, we might need a workaround or just continue?
            # But normally for a new challenge it should be clean.
            return False
    except:
        print_error(f"Invite API failed. Status: {res.status_code}")
        return False

    # 2. TestUser checks for invites
    # We need an endpoint to list invites. `my_invites.php`?
    my_invites_url = f"{BASE_URL}/api/my_invites.php"
    
    # Use test_session (TestUser)
    print("Checking invites for TestUser...")
    res = test_session.get(my_invites_url)
    
    found_invite = False
    try:
        data = res.json()
        if data.get('success'):
            invites = data.get('invites', [])
            print(f"Found {len(invites)} pending invites")
            for inv in invites:
                if str(inv.get('id')) == str(invite_id):
                    found_invite = True
                    print_success("Invite verified in recipient's list")
                    break
        else:
             print_error("Failed to fetch invites")
    except:
         print_error("Failed to parse my_invites response")
    
    if not found_invite:
        print_error("Invite NOT found in recipient list")
        return False

    # 3. TestUser accepts invite AND links a goal
    # First create a goal for TestUser to link
    goal_id = create_goal(test_session, "Challenge Goal for Invite")
    if not goal_id:
        print_error("Failed to create goal for invite acceptance")
        return False
        
    respond_url = f"{BASE_URL}/api/respond_invite.php"
    respond_data = {
        "invite_id": invite_id,
        "response": "accepted",
        "goal_id": goal_id
    }
    
    print(f"Accepting invite and linking Goal ID: {goal_id}...")
    res = test_session.post(respond_url, json=respond_data)
    
    try:
        data = res.json()
        if data.get('success'):
             print_success("Invite accepted and goal linked successfully")
             return True
        else:
             print_error(f"Failed to accept invite: {data.get('error')}")
             return False
    except:
        print_error("Failed to parse respond_invite response")
        return False

def run_all_tests():
    print("Starting Challenge Feature Tests...")
    print(f"Target: {BASE_URL}")
    
    # Prerequisite: Ensure DB is reachable (simple check)
    try:
        requests.get(BASE_URL, timeout=5)
    except:
        print_error(f"Cannot reach {BASE_URL}. Is server running?")
        return

    challenge_id, challenge_name = test_create_challenge()
    if challenge_id:
        test_dashboard_grouping(challenge_id, challenge_name)
        test_get_challenge(challenge_id)
        test_join_challenge(challenge_id)
        test_leaderboard(challenge_id)
        test_feed(challenge_id)
        
        # Invite Flow check
        # We need another challenge for this because the main one has test_user joined already via 'add_goal' (if public)
        # Or we can just use the same one if 'join' didn't fully work or if we want to test invite mechanics specifically.
        # However, earlier test_join_challenge let testuser join. You can't invite a member.
        # So let's create a PRIVATE challenge for invite testing.
        
        print_test_header("Test Invite Logic with Private Challenge")
        url = f"{BASE_URL}/api/create_challenge.php"
        p_data = {
            "name": f"Private Challenge {datetime.now().strftime('%H%M%S')}",
            "description": "Invite only test",
            "end_date": (datetime.now() + timedelta(days=30)).strftime("%Y-%m-%d"),
            "privacy": "private"
        }
        res = admin_session.post(url, json=p_data)
        if res.status_code == 200 and res.json().get('success'):
            priv_id = res.json().get('challenge_id')
            print_success(f"Private Challenge Created (ID: {priv_id})")
            test_invite_flow(priv_id)
            # Cleanup
            test_delete_challenge(priv_id)
        else:
            print_error("Failed to create private challenge for invite test")

        test_delete_challenge(challenge_id)
    else:
        print_error("Skipping dependent tests due to creation failure")

if __name__ == "__main__":
    run_all_tests()
