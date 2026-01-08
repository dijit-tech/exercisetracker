# Data Model Update: Room-Specific Goals

## CRITICAL CLARIFICATION (January 7, 2026)

**Original assumption:** Rooms just track ALL of a user's goals  
**NEW requirement:** Users choose WHICH goals to track in WHICH rooms

### Use Case Example:
```
Alice has goals:
├── Exercise daily
├── Read 30min
└── Meditate

Bob has goals:
├── Exercise daily
├── Read 30min
├── Code 1hr
└── Learn Spanish

Scenario:
1. Alice creates "Fitness Room" and invites Bob
   - Alice brings: Exercise goal only
   - Bob brings: Exercise goal only
   - Room leaderboard tracks only fitness goals

2. Bob creates "Reading Room" with work buddies (Charlie, Dave)
   - Bob brings: Read 30min goal
   - Charlie brings: Read books goal
   - Dave brings: Read articles goal
   - Room leaderboard tracks only reading goals

3. Alice could join Reading Room later
   - Selects: Read 30min goal
   - Now tracking in both Fitness Room (exercise) and Reading Room (reading)
```

---

## UPDATED Data Model

### New Relationship: Many-to-Many (Goals ↔ Rooms)

```
┌─────────────┐
│   users     │
└──────┬──────┘
       │ 1:N
       │
┌──────▼──────────────┐
│      goals          │
│  (personal goals    │
│   owned by user)    │
└──────┬──────────────┘
       │
       │ M:N (NEW!)
       │
┌──────▼──────────────┐      ┌─────────────────┐
│   room_goals        │  N:1 │     rooms       │
│  (which goals are   │─────►│  (competition   │
│   tracked in which  │      │   spaces)       │
│   room)             │      └─────────────────┘
└─────────────────────┘
```

### New Table Required: `room_goals`

```sql
CREATE TABLE room_goals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    goal_id INT NOT NULL,
    user_id INT NOT NULL,  -- Denormalized for faster queries
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_goal (room_id, goal_id),
    INDEX idx_room (room_id),
    INDEX idx_goal (goal_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Purpose:** Links goals to rooms. A goal can be in multiple rooms.

---

## Complete Updated Schema

### All Tables in System

1. **users** (existing) - User accounts
2. **goals** (existing) - Personal goals owned by users
3. **goal_logs** (existing) - Daily completion tracking
4. **rooms** (new) - Competition spaces
5. **room_members** (new) - Who's in which room
6. **room_goals** (NEW!) - Which goals are tracked in which room
7. **room_invites** (new) - Email invitation system
8. **room_posts** (new) - Activity feed
9. **room_achievements** (new) - Digital badges

---

## How It Works

### Creating a Room
```
1. Alice creates "Fitness Room"
2. System creates room record
3. Alice is auto-added to room_members
4. Alice selects which goals to track: [Exercise goal]
5. System creates room_goals entry (room_id=1, goal_id=5)
```

### Joining a Room
```
1. Bob receives invite to "Fitness Room"
2. Bob accepts invite
3. System creates room_members entry
4. Bob presented with his goals list
5. Bob selects: [Exercise goal]
6. System creates room_goals entry (room_id=1, goal_id=12)
```

### Room Leaderboard Query
```sql
-- Get leaderboard for "Fitness Room"
SELECT 
    u.username,
    COUNT(DISTINCT gl.log_date) as days_active,
    SUM(
        CASE 
            WHEN daily_pct = 1.0 THEN 10
            WHEN daily_pct >= 0.67 THEN 7
            WHEN daily_pct >= 0.34 THEN 5
            WHEN daily_pct > 0 THEN 2
            ELSE 0
        END
    ) as total_points
FROM room_members rm
JOIN users u ON rm.user_id = u.id
JOIN room_goals rg ON rg.room_id = rm.room_id AND rg.user_id = rm.user_id
JOIN goals g ON g.id = rg.goal_id
LEFT JOIN (
    SELECT 
        user_id,
        log_date,
        -- Calculate daily completion % for THIS ROOM's goals only
        SUM(CASE WHEN completed THEN 1 ELSE 0 END) * 1.0 / COUNT(*) as daily_pct
    FROM goal_logs
    WHERE goal_id IN (
        SELECT goal_id FROM room_goals WHERE room_id = ?
    )
    AND log_date BETWEEN ? AND ?
    GROUP BY user_id, log_date
) gl ON gl.user_id = rm.user_id
WHERE rm.room_id = ?
  AND rm.status = 'active'
  AND g.status = 'active'
GROUP BY u.id, u.username
ORDER BY total_points DESC;
```

**Key:** Only goals in `room_goals` for this room are counted!

---

## User Flows

### Flow 1: Create Room with Goals
```
1. User clicks "Create Room"
2. Fills room details (name, description, dates)
3. Clicks "Next: Select Goals"
4. Sees checklist of their active goals:
   [ ] Exercise daily
   [✓] Read 30min
   [ ] Meditate
   [ ] Code 1hr
5. Selects goals to track in this room
6. Room created with room_goals entries
```

### Flow 2: Join Room and Select Goals
```
1. User accepts invite
2. System: "Which goals do you want to track in this room?"
3. Shows user's active goals:
   [✓] Exercise daily
   [ ] Read 30min
   [✓] Learn Spanish
4. User selects relevant goals
5. room_goals entries created
6. User appears on room leaderboard
```

### Flow 3: Manage Room Goals
```
1. User views room
2. Clicks "My Goals in This Room"
3. Sees currently tracked goals with option to:
   - Add more goals
   - Remove goals (affects future scoring)
4. Updates room_goals entries
```

---

## Updated Room Leaderboard Logic

### Before (Wrong):
- Query ALL of user's goals
- Calculate completion % across all goals
- Problem: Unfair if users have different numbers of goals

### After (Correct):
- Query only goals linked to this room (via room_goals)
- Calculate completion % only for room-specific goals
- Fair: Everyone judged on their chosen goals for this competition

### Example:
```
Fitness Room:
- Alice tracking: Exercise goal (1 goal)
- Bob tracking: Exercise goal, Diet goal (2 goals)

Jan 7 scoring:
- Alice: Exercise ✓ → 1/1 goals = 100% = 10 pts
- Bob: Exercise ✓, Diet ✗ → 1/2 goals = 50% = 5 pts

This is FAIR because:
- Alice committed to 1 goal for this room
- Bob committed to 2 goals for this room
- Each is judged on their commitment
```

---

## Migration Path

### Option 1: Auto-assign all goals to existing rooms (if any)
```sql
-- If rooms already exist, auto-include all user goals
INSERT INTO room_goals (room_id, goal_id, user_id)
SELECT rm.room_id, g.id, rm.user_id
FROM room_members rm
JOIN goals g ON g.user_id = rm.user_id
WHERE g.status = 'active'
  AND rm.status = 'active';
```

### Option 2: Require explicit goal selection
- Existing room members prompted to select goals
- Until goals selected, user not on leaderboard

---

## API Changes

### New Endpoints Needed:

```
POST /api/add_goal_to_room.php
Body: { room_id, goal_id }
- Adds user's goal to room tracking

POST /api/remove_goal_from_room.php
Body: { room_id, goal_id }
- Removes goal from room tracking

GET /api/room_goals.php?room_id=X&user_id=Y
- Gets goals user is tracking in specific room

GET /api/available_goals_for_room.php?room_id=X
- Gets user's goals not yet in this room (for selection)
```

### Updated Endpoints:

```
POST /api/join_room.php
- NOW: After accepting invite, redirect to goal selection
- Creates room_members entry
- User must then select goals via add_goal_to_room

GET /api/room_leaderboard.php?room_id=X&month=YYYY-MM
- NOW: Filters by room_goals, not all user goals
```

---

## UI Changes

### My Goals Page (`goals.php`)
Add column showing "Tracked in Rooms":
```
┌─────────────────────────────────────────────┐
│ Goal Title        │ Category  │ Rooms       │
├─────────────────────────────────────────────┤
│ Exercise daily    │ Fitness   │ 2 rooms     │
│ Read 30min        │ Learning  │ 1 room      │
│ Meditate          │ Health    │ Not tracked │
└─────────────────────────────────────────────┘
```

### Room View Page (`room.php`)
Add "Goals Tracked in This Room" section:
```
┌────────────────────────────────────────────┐
│ Your Goals in Fitness Room:                │
│ ✓ Exercise daily                [Remove]   │
│ ✓ Track calories               [Remove]   │
│                                             │
│ [+ Add More Goals]                         │
└────────────────────────────────────────────┘
```

### Join Room Flow
New step after accepting invite:
```
┌────────────────────────────────────────────┐
│ Welcome to Fitness Room!                   │
│                                             │
│ Select which goals to track:               │
│ [ ] Exercise daily                         │
│ [✓] Track calories                         │
│ [ ] Sleep 8hrs                             │
│                                             │
│ [Start Competing]                          │
└────────────────────────────────────────────┘
```

---

## Benefits of This Approach

1. **Flexibility:** Users control what they compete on per room
2. **Privacy:** Don't have to expose all goals to all rooms
3. **Fairness:** Competition based on relevant goals only
4. **Multi-context:** Same goal can be in multiple rooms
5. **Focus:** Rooms stay focused on their theme (fitness, reading, etc.)

---

## Summary of Changes

| Aspect | Before | After |
|--------|--------|-------|
| Goals ↔ Rooms | No link | Many-to-many via `room_goals` |
| Room scoring | All user goals | Only room-selected goals |
| Joining room | Auto-compete | Select goals first |
| Goal visibility | All visible | User-controlled per room |
| Tables | 4 new tables | 5 new tables (+room_goals) |

---

## Implementation Priority Update

### Phase 1a: Core Tables
1. Create rooms table
2. Create room_members table
3. Create **room_goals table** ← NEW!
4. Create room_invites table

### Phase 1b: Goal Selection
5. UI for selecting goals when creating room
6. UI for selecting goals when joining room
7. API endpoints for managing room goals

### Phase 1c: Leaderboard
8. Update leaderboard query to filter by room_goals
9. Room-specific scoring logic

### Phase 2: Enhanced Features
10. Activity feed (room_posts)
11. Achievements (room_achievements)
12. Analytics and export

---

**Status:** Data model updated, ready for implementation  
**Next Step:** Create database migration script with `room_goals` table
