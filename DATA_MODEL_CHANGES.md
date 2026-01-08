# Data Model Changes for Rooms Feature

## Current Data Model (Before Rooms)

```
┌─────────────┐
│   users     │
│  (id, name) │
└──────┬──────┘
       │
       │ 1:N
       │
┌──────▼──────────────────┐
│       goals             │
│  (id, user_id, title,   │
│   category, status)     │
└──────┬──────────────────┘
       │
       │ 1:N
       │
┌──────▼──────────────────┐
│     goal_logs           │
│  (id, goal_id, user_id, │
│   log_date, completed)  │
└─────────────────────────┘
```

**Key characteristics:**
- Users create personal goals
- Goal logs track daily completion
- No concept of groups or competitions
- Leaderboard shows ALL users globally

---

## NEW Data Model (With Rooms)

```
┌─────────────┐
│   users     │◄────────────────┐
│  (id, name) │                 │
└──────┬──────┘                 │
       │                        │
       │ 1:N                    │ creator
       │                        │
┌──────▼──────────────────┐     │
│       goals             │     │
│  (id, user_id, title,   │     │
│   category, status)     │     │
└──────┬──────────────────┘     │
       │                        │
       │ 1:N              ┌─────┴──────────────┐
       │                  │      rooms         │
┌──────▼──────────────┐   │  (id, name, desc,  │
│     goal_logs       │   │   creator_user_id, │
│  (id, goal_id,      │   │   privacy, status) │
│   user_id,          │   └─────┬──────────────┘
│   log_date,         │         │
│   completed)        │         │ 1:N
└─────────────────────┘         │
                          ┌─────▼────────────────┐
                          │   room_members       │
                          │  (id, room_id,       │
                          │   user_id, joined_at)│
                          └──────────────────────┘
                          
                          ┌──────────────────────┐
                          │   room_invites       │
                          │  (id, room_id,       │
                          │   invitee_email,     │
                          │   status)            │
                          └──────────────────────┘
                          
                          ┌──────────────────────┐
                          │   room_posts         │
                          │  (id, room_id,       │
                          │   user_id, content)  │
                          └──────────────────────┘
                          
                          ┌──────────────────────┐
                          │  room_achievements   │
                          │  (id, room_id,       │
                          │   user_id, type)     │
                          └──────────────────────┘
```

---

## CRITICAL: No Changes to Existing Tables! 🎯

### What DOESN'T Change:
- ✅ `users` table - **NO CHANGES**
- ✅ `goals` table - **NO CHANGES**
- ✅ `goal_logs` table - **NO CHANGES**

### Why No Changes?
**Goals remain personal and independent of rooms!**

- A user's goals are their own
- When a user joins a room, the room simply **tracks their existing goal completion**
- No foreign keys from `goals` to `rooms`
- No new columns in existing tables

---

## How Rooms Work with Existing Data

### Conceptual Model

```
User A has personal goals:
├── Read 30min daily
├── Exercise
└── Meditate

User A joins "Friends Challenge" room

Room queries:
SELECT 
    COUNT(*) as total_goals,
    COUNT(CASE WHEN gl.completed THEN 1 END) as completed_goals
FROM goals g
LEFT JOIN goal_logs gl ON g.id = gl.goal_id AND gl.log_date = '2026-01-07'
WHERE g.user_id = 1  -- User A
  AND g.status = 'active';

Result: User A completed 2/3 goals = 67% = 7 points
```

### Room Leaderboard Calculation

**Query Pattern:**
```sql
-- Get all members in a room
SELECT user_id FROM room_members 
WHERE room_id = ? AND status = 'active';

-- For each member, calculate daily completion %
-- Then sum up points for the month
-- Rank by total points
```

**Key Insight:** Room leaderboard is a **VIEW** of existing data filtered by room membership!

---

## Data Relationships

### No Direct Link Between Goals and Rooms
```
❌ BAD (What we're NOT doing):
goals table:
- room_id (foreign key) ← NO!

❓ Problem: What if user joins multiple rooms?
❓ Problem: Goals become tied to rooms, lose personal ownership
```

```
✅ GOOD (What we ARE doing):
rooms ↔ room_members ↔ users ↔ goals ↔ goal_logs

Room leaderboard = Query goal_logs WHERE user_id IN (room members)
```

---

## New Tables Detailed

### 1. `rooms`
```sql
CREATE TABLE rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    creator_user_id INT NOT NULL,  -- Links to users.id
    privacy ENUM('private', 'invite-only') DEFAULT 'private',
    status ENUM('active', 'paused', 'archived', 'deleted') DEFAULT 'active',
    start_date DATE NULL,
    end_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_user_id) REFERENCES users(id) ON DELETE CASCADE
);
```
**Purpose:** Store room metadata and settings

---

### 2. `room_members`
```sql
CREATE TABLE room_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,      -- Which room
    user_id INT NOT NULL,      -- Which user (links to users.id)
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'left') DEFAULT 'active',
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_user (room_id, user_id)
);
```
**Purpose:** Track who's in which room  
**Key Query:** `SELECT user_id FROM room_members WHERE room_id = ? AND status = 'active'`

---

### 3. `room_invites`
```sql
CREATE TABLE room_invites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    inviter_user_id INT NOT NULL,    -- Who sent invite
    invitee_email VARCHAR(255) NOT NULL,  -- Email address
    invitee_user_id INT NULL,        -- Matched user (if registered)
    status ENUM('pending', 'accepted', 'declined', 'expired') DEFAULT 'pending',
    invited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (inviter_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invitee_user_id) REFERENCES users(id) ON DELETE SET NULL
);
```
**Purpose:** Manage email-based invitations  
**Flow:** 
1. Creator invites "friend@example.com"
2. System checks if email exists in users table
3. If yes, links `invitee_user_id`
4. User sees pending invite, accepts → creates `room_members` entry

---

### 4. `room_posts`
```sql
CREATE TABLE room_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    user_id INT NOT NULL,        -- Who posted
    post_type ENUM('message', 'achievement', 'milestone') DEFAULT 'message',
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```
**Purpose:** Activity feed / forum-style communication  
**Example posts:**
- "Alice completed all goals today! 🎉"
- "Bob: Let's keep the momentum going!"
- "System: Charlie earned 'Perfect Week' achievement"

---

### 5. `room_achievements`
```sql
CREATE TABLE room_achievements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    user_id INT NOT NULL,        -- Who earned it
    achievement_type VARCHAR(50) NOT NULL,  -- 'first_100_points', 'perfect_week'
    achievement_name VARCHAR(255) NOT NULL,  -- Display name
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```
**Purpose:** Track digital badges/achievements per room  
**Examples:**
- First to 100 points in room
- Perfect week (7/7 days 100%)
- Comeback king (rose from last place)

---

## Query Examples

### Get Room Leaderboard for January 2026
```sql
SELECT 
    u.username,
    COUNT(DISTINCT gl.log_date) as days_tracked,
    AVG(
        CASE 
            WHEN daily_completion = 1.0 THEN 10
            WHEN daily_completion >= 0.67 THEN 7
            WHEN daily_completion >= 0.34 THEN 5
            WHEN daily_completion > 0 THEN 2
            ELSE 0
        END
    ) * COUNT(DISTINCT gl.log_date) as total_points
FROM room_members rm
JOIN users u ON rm.user_id = u.id
LEFT JOIN goals g ON g.user_id = rm.user_id AND g.status = 'active'
LEFT JOIN (
    SELECT 
        user_id,
        log_date,
        COUNT(*) as total_goals,
        SUM(CASE WHEN completed THEN 1 ELSE 0 END) as completed_goals,
        SUM(CASE WHEN completed THEN 1 ELSE 0 END) / COUNT(*) as daily_completion
    FROM goal_logs
    WHERE log_date BETWEEN '2026-01-01' AND '2026-01-31'
    GROUP BY user_id, log_date
) gl ON gl.user_id = rm.user_id
WHERE rm.room_id = ?
  AND rm.status = 'active'
GROUP BY u.id, u.username
ORDER BY total_points DESC;
```

### Get User's Rooms
```sql
SELECT 
    r.id,
    r.name,
    r.description,
    r.status,
    r.start_date,
    r.end_date,
    COUNT(rm2.id) as member_count,
    RANK() OVER (ORDER BY points DESC) as my_rank
FROM rooms r
JOIN room_members rm ON rm.room_id = r.id
LEFT JOIN room_members rm2 ON rm2.room_id = r.id AND rm2.status = 'active'
WHERE rm.user_id = ?  -- Current user
  AND rm.status = 'active'
GROUP BY r.id;
```

---

## Migration Impact

### For Existing Users
- ✅ No changes to their goals
- ✅ No changes to their goal_logs
- ✅ Leaderboard continues to work as before (global view)
- ✅ Can optionally join rooms to compete with friends

### For Database
- ✅ Add 5 new tables (no modifications to existing)
- ✅ No data migration required
- ✅ Backward compatible

### For Queries
**Before Rooms:**
```sql
-- Global leaderboard
SELECT ... FROM goal_logs WHERE log_date = ?;
```

**After Rooms:**
```sql
-- Global leaderboard (unchanged)
SELECT ... FROM goal_logs WHERE log_date = ?;

-- Room-specific leaderboard (NEW)
SELECT ... FROM goal_logs gl
JOIN room_members rm ON rm.user_id = gl.user_id
WHERE rm.room_id = ? AND gl.log_date = ?;
```

---

## Summary: Zero Breaking Changes! 🎉

| Aspect | Impact |
|--------|--------|
| Existing tables | No changes |
| Existing queries | Still work |
| Existing features | Unaffected |
| User data | Preserved |
| Goals | Remain personal |
| Global leaderboard | Still available |
| **New capability** | **Room-specific competitions** |

**The rooms feature is purely additive** - it creates a parallel system for competition while leaving the core goal tracking unchanged!
