# Goal Tracker Requirements

## Interview Date: January 6, 2026

---

## Question 1: Dashboard Layout ✓

**Answer:** Option 2 - Compact card grid on a carousel

**Details:**
- Display goals as compact cards (2-3 per row)
- Implement carousel navigation for multiple goals
- Each card shows:
  - Goal title with emoji/icon
  - Category name
  - Current streak with fire emoji
  - Days remaining
  - Progress bar with percentage
  - "Done Today" button

**Visual Layout:**
```
┌──────────────────────────────┐  ┌──────────────────────────────┐
│ 🎯 Eat only 3 times a day   │  │ 📚 Read 30 minutes daily    │
│ Health & Fitness             │  │ Learning                     │
│                              │  │                              │
│ Streak: 65 days 🔥           │  │ Streak: 12 days 🔥           │
│ 300 days left                │  │ 353 days left                │
│                              │  │                              │
│ Progress: ████░░░░░░ 18%     │  │ Progress: █░░░░░░░░░ 3%      │
│                              │  │                              │
│     [✓ Done Today]           │  │     [✓ Done Today]           │
└──────────────────────────────┘  └──────────────────────────────┘
```

---

## Question 2: Daily Tracking ✓

**Answer:** Option C - Both quick dashboard action and dedicated tracking page

**Details:**
- **Quick Action:** "Done Today" button on each goal card on dashboard
  - One-click marking without navigation
  - Immediate visual feedback (button changes to "✓ Completed today")
  
- **Dedicated Track Page:** Separate "Track Goals" page
  - Lists all active goals with checkboxes
  - Allows adding notes for each completion
  - Better for focused daily review session
  
- **Retroactive Tracking:** Users CAN mark goals complete for previous days
  - Useful if they forgot to log
  - Access via dedicated tracking page or goal details
  
- **Future Planning:** Users CANNOT pre-mark future days
  - Goals can only be marked for today or past dates

---

## Question 3: Yearly Calendar ✓

**Answer:** Option C - Heatmap based on percentage completion

**Details:**
- One row per user in the yearly calendar
- Color intensity represents **percentage of goals completed** that day
- Calculation: (completed goals / total active goals) × 100
  
**Color Scale Examples:**
- 0% = Light gray or white (no goals completed)
- 1-33% = Light red (struggling)
- 34-66% = Light orange/yellow (partial completion)
- 67-99% = Light green (good progress)
- 100% = Dark green (perfect day - all goals completed)

**Benefits:**
- Fair comparison regardless of number of goals
- User with 2 goals completing both shows same as user with 5 goals completing all 5
- Visual at-a-glance progress tracking
- Encourages consistency across all goals, not just some

**Example:**
```
User has 4 active goals on Jan 5:
- Completed 3 goals = 75% = Light Green
- Completed 0 goals = 0% = Gray
- Completed 4 goals = 100% = Dark Green
```

---

## Question 4: Streak Calculation ✓

**Answer:** Strict consecutive days approach

**Details:**
- **Current Streak:** Count of consecutive days from most recent completion
  - Missing one day resets streak to 0
  - Starts counting again from next completion
  - Builds discipline and consistency
  
- **Additional Metrics to Track:**
  - **Longest Streak:** Historical best consecutive run
  - **Total Completed Days:** Sum of all days completed (regardless of gaps)
  - **Success Rate:** (Total completed / Total days since start) × 100

**Examples:**
```
Scenario 1: Jan 1-5 complete, Jan 6 missed
- Current Streak: 0 days
- Longest Streak: 5 days
- Total Completed: 5 days

Scenario 2: Jan 1-10 complete, Jan 11 missed, Jan 12-20 complete
- Current Streak: 9 days (Jan 12-20)
- Longest Streak: 10 days (Jan 1-10)
- Total Completed: 19 days
```

---

## Question 5: Goal Management ✓

**Answer:** Full goal management suite

**Details:**

1. **Edit Goal Details**
   - Can modify: Title, description, category, end date
   - Cannot modify: Start date (historical data)
   - Editing does NOT reset streak
   - Shows "Last updated" timestamp

2. **Pause/Resume Goals**
   - Temporarily pause goal (e.g., vacation, illness)
   - Paused days don't count as missed
   - Streak freezes during pause
   - Clear visual indicator on card (grayed out or "Paused" badge)
   - Resume anytime to continue tracking

3. **Archive Completed Goals**
   - Mark goal as "Achieved" or "Completed"
   - Removes from active dashboard
   - Keeps all historical data and stats
   - Accessible in "Archived Goals" section
   - Can view past achievements and stats

4. **Delete Goals**
   - Permanently remove goal and ALL tracking data
   - Requires confirmation dialog
   - Warning: "This will delete all history for this goal"
   - Cannot be undone

**Goal States:**
- Active (default)
- Paused
- Archived (completed)
- Deleted (removed permanently)

---

## Question 6: Progress Tracking ✓

**Answer:** Option C - Checkbox + Optional Notes

**Details:**
- **Primary Tracking:** Simple checkbox (Yes/No completion)
  - Fast, one-click marking
  - Binary: Goal completed or not completed
  
- **Optional Notes Field:** Text area for additional context
  - Users can add notes if desired
  - Not required for daily tracking
  - Useful for:
    - Recording challenges ("Tempted at 3pm but stayed strong")
    - Contextual details ("Read 25 mins instead of 30, but still counts")
    - Reflections and patterns
    - Reasons for missing goal
  
- **Benefits:**
  - Speed: Just checkbox for busy users
  - Flexibility: Notes when useful for reflection
  - Pattern identification: Review notes to spot triggers/obstacles
  - Accountability: Document the journey

**UI Implementation:**
```
✓ Goal completed today
[Optional: Add notes...] (expandable text area)
```

---

# Requirements Complete! ✅

All questions answered. Ready to proceed with implementation.

