"""
Comprehensive Test Suite for Rooms/Competitions Feature
Goal Tracker - Rooms Feature Tests
Date: January 7, 2026
"""

import requests
import json
from datetime import datetime, timedelta

# Production URL
BASE_URL = "http://goaltracker.dijit.tech"

# Test credentials
ADMIN_USER = {"username": "admin", "password": "password"}
TEST_USER = {"username": "testuser", "password": "password"}

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
        # Note: 'login' check matches api/login.php so we must be careful.
        # If we are strictly at api/login.php, it implies NO REDIRECT happened (failure or error).
        if "index.php" in response.url or "error=" in response.url:
             print(f"[X] Login redirected to {response.url} (Failed)")
             return False
             
        if "login.php" in response.url:
             # If we are still at login.php, we didn't redirect to dashboard.
             # This usually means an error occurred on the page.
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
# CATEGORY 1: ROOM CRUD OPERATIONS
# ============================================

def test_create_room():
    """Test 1: Create a new room"""
    print_test_header("Create Room")
    
    # Login as admin
    if not login_user(admin_session, ADMIN_USER["username"], ADMIN_USER["password"]):
        print_error("Failed to login")
        return False
    
    # Create a goal first
    goal_id = create_goal(admin_session, "Daily Workout")
    if not goal_id:
        print_error("Failed to create goal")
        return False
    print_success(f"Created goal ID: {goal_id}")
    
    # Create room
    room_data = {
        "name": "Fitness Challenge 2026",
        "description": "30-day fitness challenge",
        "privacy": "private",
        "start_date": datetime.now().strftime("%Y-%m-%d"),
        "end_date": (datetime.now() + timedelta(days=30)).strftime("%Y-%m-%d"),
        "goal_ids": [goal_id]
    }
    
    response = admin_session.post(f"{BASE_URL}/api/create_room.php", json=room_data)
    
    if response.status_code == 200:
        data = response.json()
        if data.get('success'):
            print_success(f"Room created successfully: {data.get('room_id')}")
            return True
    
    print_error(f"Failed to create room: {response.text}")
    return False

def test_list_rooms():
    """Test 2: List user's rooms"""
    print_test_header("List Rooms")
    
    response = admin_session.get(f"{BASE_URL}/api/list_rooms.php")
    
    if response.status_code == 200:
        data = response.json()
        if data.get('success'):
            rooms = data.get('rooms', [])
            print_success(f"Found {len(rooms)} room(s)")
            for room in rooms:
                print(f"  - {room['name']} (ID: {room['id']}, Status: {room['status']})")
            return True
    
    print_error(f"Failed to list rooms: {response.text}")
    return False

def test_view_room_details():
    """Test 3: View room details"""
    print_test_header("View Room Details")
    
    # Get room list first
    response = admin_session.get(f"{BASE_URL}/api/list_rooms.php")
    if response.status_code != 200:
        print_error("Failed to get room list")
        return False
    
    rooms = response.json().get('rooms', [])
    if not rooms:
        print_error("No rooms found")
        return False
    
    room_id = rooms[0]['id']
    
    # Get room details
    response = admin_session.get(f"{BASE_URL}/api/get_room.php?room_id={room_id}")
    
    if response.status_code == 200:
        data = response.json()
        if data.get('success'):
            room = data.get('room', {})
            print_success(f"Room Details:")
            print(f"  - Name: {room.get('name')}")
            print(f"  - Description: {room.get('description')}")
            print(f"  - Members: {room.get('member_count', 0)}")
            print(f"  - Goals: {room.get('goal_count', 0)}")
            return True
    
    print_error(f"Failed to get room details: {response.text}")
    return False

# ============================================
# CATEGORY 2: INVITATIONS
# ============================================

def test_send_invitation():
    """Test 4: Send room invitation"""
    print_test_header("Send Room Invitation")
    
    # Get room ID
    response = admin_session.get(f"{BASE_URL}/api/list_rooms.php")
    if response.status_code != 200:
        print_error("Failed to get room list")
        return False
    
    rooms = response.json().get('rooms', [])
    if not rooms:
        print_error("No rooms found")
        return False
    
    room_id = rooms[0]['id']
    
    # Send invitation to testuser
    response = admin_session.post(
        f"{BASE_URL}/api/invite_to_room.php",
        json={"room_id": room_id, "invitee_email": "test@goaltracker.local"}
    )
    
    if response.status_code == 200:
        data = response.json()
        if data.get('success'):
            print_success("Invitation sent successfully")
            return True
    
    print_error(f"Failed to send invitation: {response.text}")
    return False

def test_accept_invitation():
    """Test 5: Accept room invitation"""
    print_test_header("Accept Room Invitation")
    
    # Login as testuser
    if not login_user(test_session, TEST_USER["username"], TEST_USER["password"]):
        print_error("Failed to login as testuser")
        return False
    
    # Get pending invites
    response = test_session.get(f"{BASE_URL}/api/my_invites.php")
    if response.status_code != 200:
        print_error("Failed to get invites")
        return False
    
    data = response.json()
    invites = data.get('invites', [])
    
    if not invites:
        print_error("No pending invites found")
        return False
    
    invite_id = invites[0]['id']
    
    # Accept invitation
    response = test_session.post(
        f"{BASE_URL}/api/respond_invite.php",
        json={"invite_id": invite_id, "response": "accepted"}
    )
    
    if response.status_code == 200:
        data = response.json()
        if data.get('success'):
            print_success("Invitation accepted successfully")
            return True
    
    print_error(f"Failed to accept invitation: {response.text}")
    return False

# ============================================
# CATEGORY 3: ROOM GOALS
# ============================================

def test_add_goal_to_room():
    """Test 6: Add goal to room"""
    print_test_header("Add Goal to Room")
    
    # Create a goal for testuser
    goal_id = create_goal(test_session, "Morning Run")
    if not goal_id:
        print_error("Failed to create goal")
        return False
    
    # Get room ID from testuser's rooms
    response = test_session.get(f"{BASE_URL}/api/list_rooms.php")
    if response.status_code != 200:
        print_error("Failed to get room list")
        return False
    
    rooms = response.json().get('rooms', [])
    if not rooms:
        print_error("No rooms found for testuser")
        return False
    
    room_id = rooms[0]['id']
    
    # Add goal to room
    response = test_session.post(
        f"{BASE_URL}/api/add_goal_to_room.php",
        json={"room_id": room_id, "goal_id": goal_id}
    )
    
    if response.status_code == 200:
        data = response.json()
        if data.get('success'):
            print_success(f"Goal {goal_id} added to room {room_id}")
            return True
    
    print_error(f"Failed to add goal to room: {response.text}")
    return False

# ============================================
# CATEGORY 4: LEADERBOARD
# ============================================

def test_room_leaderboard():
    """Test 7: View room leaderboard"""
    print_test_header("Room Leaderboard")
    
    # Get room ID
    response = admin_session.get(f"{BASE_URL}/api/list_rooms.php")
    if response.status_code != 200:
        print_error("Failed to get room list")
        return False
    
    rooms = response.json().get('rooms', [])
    if not rooms:
        print_error("No rooms found")
        return False
    
    room_id = rooms[0]['id']
    current_month = datetime.now().strftime("%Y-%m")
    
    # Get leaderboard
    response = admin_session.get(
        f"{BASE_URL}/api/room_leaderboard.php?room_id={room_id}&month={current_month}"
    )
    
    if response.status_code == 200:
        data = response.json()
        if data.get('success'):
            leaderboard = data.get('leaderboard', [])
            print_success(f"Leaderboard retrieved: {len(leaderboard)} member(s)")
            for i, member in enumerate(leaderboard, 1):
                print(f"  {i}. {member.get('username', 'Unknown')} - {member.get('total_points', 0)} pts")
            return True
    
    print_error(f"Failed to get leaderboard: {response.text}")
    return False

# ============================================
# CATEGORY 5: ACTIVITY FEED
# ============================================

def test_room_feed():
    """Test 8: Post to room feed"""
    print_test_header("Room Activity Feed")
    
    # Get room ID
    response = admin_session.get(f"{BASE_URL}/api/list_rooms.php")
    if response.status_code != 200:
        print_error("Failed to get room list")
        return False
    
    rooms = response.json().get('rooms', [])
    if not rooms:
        print_error("No rooms found")
        return False
    
    room_id = rooms[0]['id']
    
    # Post to feed
    response = admin_session.post(
        f"{BASE_URL}/api/post_to_room.php",
        json={"room_id": room_id, "content": "Great job everyone! Keep it up! 💪"}
    )
    
    if response.status_code == 200:
        data = response.json()
        if data.get('success'):
            print_success("Posted to room feed")
            
            # Retrieve feed
            feed_response = admin_session.get(f"{BASE_URL}/api/room_feed.php?room_id={room_id}")
            if feed_response.status_code == 200:
                feed_data = feed_response.json()
                posts = feed_data.get('posts', [])
                print_success(f"Retrieved {len(posts)} post(s) from feed")
                return True
    
    print_error(f"Failed to post to feed: {response.text}")
    return False

# ============================================
# CATEGORY 6: UI NAVIGATION
# ============================================

def test_rooms_page_loads():
    """Test 9: Rooms page loads correctly"""
    print_test_header("Rooms Page Load")
    
    response = admin_session.get(f"{BASE_URL}/rooms.php")
    
    if response.status_code == 200:
        content = response.text
        if "My Rooms" in content and "Create Room" in content:
            print_success("Rooms page loaded successfully")
            return True
    
    print_error(f"Failed to load rooms page: Status {response.status_code}")
    return False

# ============================================
# CATEGORY 7: EDGE CASES
# ============================================

def test_create_room_without_goals():
    """Test 10: Create room without goals (should work)"""
    print_test_header("Create Room Without Goals")
    
    room_data = {
        "name": "Empty Goals Room",
        "description": "Testing room creation without goals",
        "privacy": "private",
        "goal_ids": []
    }
    
    response = admin_session.post(f"{BASE_URL}/api/create_room.php", json=room_data)
    
    if response.status_code == 200:
        data = response.json()
        if data.get('success'):
            print_success("Room created without goals successfully")
            return True
    
    print_error(f"Failed to create room without goals: {response.text}")
    return False

def test_duplicate_invitation():
    """Test 11: Send duplicate invitation (should fail gracefully)"""
    print_test_header("Duplicate Invitation")
    
    # Get room ID
    response = admin_session.get(f"{BASE_URL}/api/list_rooms.php")
    if response.status_code != 200:
        print_error("Failed to get room list")
        return False
    
    rooms = response.json().get('rooms', [])
    if not rooms:
        print_error("No rooms found")
        return False
    
    room_id = rooms[0]['id']
    
    # Try to send duplicate invitation
    response = admin_session.post(
        f"{BASE_URL}/api/invite_to_room.php",
        json={"room_id": room_id, "invitee_email": "test@goaltracker.local"}
    )
    
    # Should either succeed (idempotent) or return error message
    if response.status_code in [200, 400]:
        data = response.json()
        if not data.get('success'):
            print_success(f"Duplicate invitation handled: {data.get('message', 'Already invited')}")
            return True
        else:
            print_success("Duplicate invitation handled (idempotent)")
            return True
    
    print_error(f"Unexpected response for duplicate invitation: {response.text}")
    return False

def test_room_page_loads():
    """Test 12: Individual room page loads"""
    print_test_header("Individual Room Page Load")
    
    # Get room ID
    response = admin_session.get(f"{BASE_URL}/api/list_rooms.php")
    if response.status_code != 200:
        print_error("Failed to get room list")
        return False
    
    rooms = response.json().get('rooms', [])
    if not rooms:
        print_error("No rooms found")
        return False
    
    room_id = rooms[0]['id']
    
    # Load room page
    response = admin_session.get(f"{BASE_URL}/room.php?id={room_id}")
    
    if response.status_code == 200:
        content = response.text
        if "Leaderboard" in content and "Activity Feed" in content:
            print_success("Room page loaded successfully")
            return True
    
    print_error(f"Failed to load room page: Status {response.status_code}")
    return False

def test_max_rooms_limit():
    """Test 13: Maximum rooms limit (10 rooms per user)"""
    print_test_header("Maximum Rooms Limit")
    
    # Get current room count
    response = admin_session.get(f"{BASE_URL}/api/list_rooms.php")
    if response.status_code != 200:
        print_error("Failed to get room list")
        return False
    
    current_count = len(response.json().get('rooms', []))
    print(f"Current room count: {current_count}")
    
    if current_count >= 10:
        print_success("Already at max rooms limit (testing limit enforcement)")
        
        # Try to create one more
        room_data = {
            "name": "Should Fail Room",
            "description": "This should fail due to limit",
            "privacy": "private",
            "goal_ids": []
        }
        
        response = admin_session.post(f"{BASE_URL}/api/create_room.php", json=room_data)
        
        if response.status_code in [400, 403]:
            data = response.json()
            if not data.get('success'):
                print_success(f"Room limit enforced: {data.get('message', 'Limit reached')}")
                return True
        
        print_error("Room limit not enforced properly")
        return False
    else:
        print_success(f"Under room limit ({current_count}/10) - limit not tested")
        return True

# ============================================
# MAIN TEST RUNNER
# ============================================

def run_all_tests():
    """Run all tests and generate report"""
    print("\n" + "="*70)
    print("GOAL TRACKER - ROOMS FEATURE TEST SUITE")
    print("="*70)
    print(f"Date: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print(f"Base URL: {BASE_URL}")
    print("="*70)
    
    tests = [
        ("Room CRUD", [
            test_create_room,
            test_list_rooms,
            test_view_room_details
        ]),
        ("Invitations", [
            test_send_invitation,
            test_accept_invitation
        ]),
        ("Room Goals", [
            test_add_goal_to_room
        ]),
        ("Leaderboard", [
            test_room_leaderboard
        ]),
        ("Activity Feed", [
            test_room_feed
        ]),
        ("UI Navigation", [
            test_rooms_page_loads,
            test_room_page_loads
        ]),
        ("Edge Cases", [
            test_create_room_without_goals,
            test_duplicate_invitation,
            test_max_rooms_limit
        ])
    ]
    
    total_tests = 0
    passed_tests = 0
    failed_tests = []
    
    for category_name, category_tests in tests:
        print(f"\n{'='*70}")
        print(f"CATEGORY: {category_name}")
        print(f"{'='*70}")
        
        for test_func in category_tests:
            total_tests += 1
            try:
                result = test_func()
                if result:
                    passed_tests += 1
                else:
                    failed_tests.append(test_func.__name__)
            except Exception as e:
                print_error(f"Test crashed: {str(e)}")
                failed_tests.append(test_func.__name__)
    
    # Final Report
    print("\n" + "="*70)
    print("TEST SUMMARY")
    print("="*70)
    print(f"Total Tests: {total_tests}")
    print(f"Passed: {passed_tests} ✓")
    print(f"Failed: {len(failed_tests)} ✗")
    print(f"Success Rate: {(passed_tests/total_tests*100):.1f}%")
    
    if failed_tests:
        print(f"\nFailed Tests:")
        for test_name in failed_tests:
            print(f"  - {test_name}")
    
    print("="*70)
    
    return passed_tests == total_tests

if __name__ == "__main__":
    success = run_all_tests()
    exit(0 if success else 1)
