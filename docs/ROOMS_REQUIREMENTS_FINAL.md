# Rooms/Competitions Feature - Final Requirements Document

## Overview
A room is a **private competition space** where users can compete with friends by tracking their personal goals. Rooms can be permanent or temporary with optional start/end dates.

---

## 1. ROOM CREATION & OWNERSHIP

### Who Can Create
- ✅ Any registered user can create a room

### Room Details Required
- ✅ Room name (required)
- ✅ Description (required)
- ✅ Privacy setting: Private or Invite-only (no public option per interview)
- ✅ Start date (optional)
- ✅ End date (optional)
- ❌ No maximum participant limit
- ❌ No room categories
- ❌ No custom scoring rules (use global system)

### Room Types
- **Permanent rooms**: No end date, always active
- **Temporary rooms**: With start/end dates for challenges

---

## 2. INVITATION SYSTEM

### How Users Are Invited
- ✅ By email address only
- ❌ No username-based invites
- ❌ No shareable links
- ❌ No invite codes

### Invitation Flow
- ✅ Invitations require acceptance (user must approve)
- ✅ Only room creator can send invites
- ❌ Regular members cannot invite others
- ❌ No delegated invite permissions

### Invitation States
1. **Pending** - Sent but not yet responded to
2. **Accepted** - User joined the room
3. **Declined** - User rejected the invite
4. **Expired** - (Optional) After certain period

---

## 3. MEMBERSHIP & VISIBILITY

### Multi-Room Participation
- ✅ Users can join multiple rooms
- ✅ Maximum 10 rooms per user
- System tracks: Total rooms joined, active rooms, archived rooms

### Room Discovery
- ✅ Browse list of all public rooms
- ❌ No search functionality (v1)
- ❌ No recommendations (v1)
- ❌ No trending/popular features (v1)

### Leaderboards
- ✅ **Room-specific leaderboards only**
- ❌ No global leaderboard in room context
- Each room shows race track for its members only
- Dashboard shows aggregated view of all rooms

---

## 4. GOALS & TRACKING

### Goal Strategy
- ✅ **Members bring their own goals** into the room
- Room tracks completion of whatever goals each member has active
- No shared/mandatory room goals
- Competition is based on each person's goal completion rate

### How It Works
1. User creates/has personal goals
2. User joins a room
3. Room tracks that user's goal completion % daily
4. Leaderboard ranks members by their overall completion performance

**Example:**
- Alice has 5 goals (reading, exercise, meditation, coding, journaling)
- Bob has 3 goals (exercise, learning, writing)
- Room leaderboard shows who has better completion % of their respective goals

---

## 5. SCORING & COMPETITION

### Scoring System
- ✅ Use existing global scoring system:
  - 100% completion = 10 points
  - 67-99% completion = 7 points
  - 34-66% completion = 5 points
  - 1-33% completion = 2 points
  - 0% completion = 0 points
- ❌ No custom scoring per room
- ❌ No alternative competition modes (v1)

### Rewards
- ✅ Digital badges/achievements for room winners
- ❌ No physical prizes
- Examples: "Room Champion", "Perfect Week", "Comeback King"

---

## 6. COMMUNICATION

### Communication Features
- ✅ **Forum-style activity feed** showing:
  - Who completed goals
  - Milestones achieved
  - Daily progress updates
- ✅ Public posts/comments visible to all room members
- ❌ No private messages between members
- ❌ No group chat
- ❌ No announcement board

### Notifications
- ❌ **No notifications system** (per interview)
- Users check room page for updates
- Future: Email notifications for invites

---

## 7. ROOM MANAGEMENT

### Creator Permissions (All of the above)
- ✅ Remove members from room
- ✅ Close/delete the room entirely
- ✅ Edit room settings anytime (name, description, dates)
- ✅ Pause/archive the room
- ✅ Transfer ownership to another member

### Member Permissions
- ✅ Leave room voluntarily anytime
- ✅ Post to activity feed
- ✅ View leaderboard and stats
- ❌ Cannot invite others
- ❌ Cannot edit room settings

### Room States
- **Active**: Normal operation
- **Paused**: Creator temporarily stopped competition
- **Archived**: Ended but preserved for history
- **Deleted**: Permanently removed

---

## 8. NAVIGATION & UI

### Dashboard Behavior
- ✅ **Global dashboard by default**
- Shows aggregated stats from all rooms user is in
- Lists all rooms with quick stats:
  - Room name
  - Member count
  - Your current rank
  - Days remaining (if temporary)
  - Recent activity snapshot

### Room Navigation
- ✅ **"My Rooms" page** listing all joined rooms
- Each room card shows:
  - Room name & description
  - Member count
  - Your rank
  - Status (active/paused/ending soon)
  - Quick actions (Enter, Leave)

### Inside a Room
- Dedicated room page with:
  - Room info header
  - Race track leaderboard (room members only)
  - Activity feed
  - Member list
  - Room settings (if creator)

**Navigation Flow:**
```
Dashboard (Global)
  ↓
My Rooms (List all)
  ↓
[Select Room]
  ↓
Room Leaderboard + Activity Feed
```

---

## 9. ADVANCED FEATURES (v1)

### Included in v1
- ✅ **Room achievements/milestones**
  - First to 100 points
  - Perfect week (7/7 days)
  - Comeback (rose from last place)
- ✅ **Export room results/statistics** (CSV/PDF)
- ✅ **Room analytics**
  - Participation rate over time
  - Average completion %
  - Most active days
  - Trend graphs
- ✅ **Calendar integration**
  - Show room start/end dates on calendar
  - Highlight competition days

### Future Versions (v2+)
- Room templates
- Recurring rooms
- Teams within rooms
- Advanced notification system
- Custom scoring rules
- Room categories/tags

---

## 10. DATABASE SCHEMA DESIGN

### New Tables Required

#### `rooms` table
```sql
CREATE TABLE rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    creator_user_id INT NOT NULL,
    privacy ENUM('private', 'invite-only') DEFAULT 'private',
    status ENUM('active', 'paused', 'archived', 'deleted') DEFAULT 'active',
    start_date DATE NULL,
    end_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_creator (creator_user_id),
    INDEX idx_status (status)
);
```

#### `room_members` table
```sql
CREATE TABLE room_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'left') DEFAULT 'active',
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_user (room_id, user_id),
    INDEX idx_user_rooms (user_id, status)
);
```

#### `room_invites` table
```sql
CREATE TABLE room_invites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    inviter_user_id INT NOT NULL,
    invitee_email VARCHAR(255) NOT NULL,
    invitee_user_id INT NULL,
    status ENUM('pending', 'accepted', 'declined', 'expired') DEFAULT 'pending',
    invited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (inviter_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invitee_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_invitee_email (invitee_email),
    INDEX idx_invitee_user (invitee_user_id),
    INDEX idx_status (status)
);
```

#### `room_posts` table (Activity Feed/Forum)
```sql
CREATE TABLE room_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    post_type ENUM('message', 'achievement', 'milestone') DEFAULT 'message',
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_room_posts (room_id, created_at)
);
```

#### `room_achievements` table
```sql
CREATE TABLE room_achievements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    achievement_type VARCHAR(50) NOT NULL,
    achievement_name VARCHAR(255) NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_room_achievements (room_id, user_id)
);
```

---

## 11. API ENDPOINTS

### Room Management
- `POST /api/create_room.php` - Create new room
- `GET /api/get_room.php?id=X` - Get room details
- `POST /api/update_room.php` - Update room settings
- `POST /api/delete_room.php` - Delete/archive room
- `GET /api/list_rooms.php` - List user's rooms
- `GET /api/browse_rooms.php` - Browse public rooms (future)

### Membership
- `POST /api/join_room.php` - Join a room (via invite)
- `POST /api/leave_room.php` - Leave a room
- `POST /api/remove_member.php` - Creator removes member
- `GET /api/room_members.php?room_id=X` - Get room members

### Invitations
- `POST /api/invite_to_room.php` - Send invitation
- `POST /api/respond_invite.php` - Accept/decline invite
- `GET /api/my_invites.php` - Get pending invites
- `GET /api/room_invites.php?room_id=X` - Get room's invitations (creator only)

### Leaderboard & Stats
- `GET /api/room_leaderboard.php?room_id=X&month=YYYY-MM` - Room-specific leaderboard
- `GET /api/room_stats.php?room_id=X` - Room analytics
- `GET /api/export_room_stats.php?room_id=X` - Export CSV/PDF

### Activity Feed
- `POST /api/post_to_room.php` - Create post in room
- `GET /api/room_feed.php?room_id=X` - Get activity feed
- `DELETE /api/delete_room_post.php` - Delete own post

### Achievements
- `GET /api/room_achievements.php?room_id=X` - Get room achievements
- System automatically awards achievements based on performance

---

## 12. UI PAGES NEEDED

### New Pages
1. **My Rooms** (`/rooms.php`)
   - List all joined rooms
   - Quick stats per room
   - "Create Room" button
   - Pending invites section

2. **Create/Edit Room** (`/room_create.php`, `/room_edit.php`)
   - Form with room details
   - Privacy settings
   - Date range picker

3. **Room View** (`/room.php?id=X`)
   - Room info header
   - Race track leaderboard (room members only)
   - Activity feed/forum
   - Member list
   - Room settings (if creator)
   - Export stats button

4. **Room Invites** (`/room_invites.php`)
   - Send invitations by email
   - View pending/sent invites
   - Resend/cancel options

### Modified Pages
- **Dashboard** (`/dashboard.php`)
  - Add "My Rooms" section
  - Show aggregated room stats
  - Recent room activities widget

---

## 13. IMPLEMENTATION PRIORITY

### Phase 1 (Core Functionality)
1. Database schema creation
2. Backend functions (rooms.php)
3. Room CRUD operations
4. Invitation system
5. Room-specific leaderboard
6. Basic activity feed

### Phase 2 (Enhanced Features)
7. Room analytics
8. Export functionality
9. Achievement system
10. Calendar integration

### Phase 3 (Polish & Testing)
11. UI/UX refinements
12. Comprehensive tests
13. Performance optimization
14. Production deployment

---

## 14. SUCCESS METRICS

### User Engagement
- Number of rooms created per week
- Average room membership
- Invitation acceptance rate
- Daily active rooms
- Posts per room per week

### Competition Quality
- Average completion rate in rooms vs. solo
- Member retention in rooms (30-day, 90-day)
- Achievement distribution
- Export usage

---

## 15. CONSTRAINTS & LIMITATIONS

### v1 Limitations
- Max 10 rooms per user
- Email invites only (must be registered user)
- No public rooms
- No search/filtering (browse all only)
- No notifications
- No private messaging
- No custom scoring
- No teams
- English language only

### Technical Constraints
- Room leaderboard calculates from members' personal goals
- Archive rooms after 90 days of inactivity (creator can extend)
- Delete expired invites after 30 days
- Limit activity feed to last 100 posts (pagination)

---

## APPENDIX: User Stories

**As a user**, I want to create a private room so that I can compete with my friends.

**As a room creator**, I want to invite people by email so that they can join my competition.

**As an invitee**, I want to accept or decline room invitations so that I control which competitions I join.

**As a room member**, I want to see how I rank against others so that I stay motivated.

**As a room member**, I want to post updates so that I can share my progress with the group.

**As a room creator**, I want to manage members so that I can maintain a positive competition environment.

**As a user in multiple rooms**, I want to see all my rooms in one place so that I can easily navigate between them.

**As a competitive user**, I want to earn achievements so that my efforts are recognized.

**As a room creator**, I want to export stats so that I can share results after the competition ends.

---

**Document Version:** 1.0  
**Last Updated:** January 7, 2026  
**Status:** Ready for Implementation
