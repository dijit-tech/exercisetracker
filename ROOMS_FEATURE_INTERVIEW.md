# Rooms/Competitions Feature - Requirements Interview

## Interview Date: January 7, 2026
## Feature: User-hosted Rooms/Competitions with Invitations

---

## Question 1: Room Concept and Purpose
**Q: What is the main purpose of a "room" or "competition"?**
- [X] A. Private space for a group of friends to compete
- [ ] B. Public competition anyone can join
- [ ] C. Both private and public options
- [ ] D. Time-limited challenges (e.g., 30-day challenge)
- [ ] E. All of the above

**Q: Should rooms be permanent or temporary?**
- [ ] A. Permanent (always active)
- [ ] B. Temporary with start/end dates
- [X] C. Both options available

**Your answer:**

---

## Question 2: Room Creation and Ownership
**Q: Who can create a room?**
- [X] A. Any registered user
- [ ] B. Only admin users
- [ ] C. Users with certain achievements/criteria

**Q: What details should be collected when creating a room?**
- [X] A. Room name
- [X] B. Description
- [X] C. Privacy setting (public/private/invite-only)
- [X] D. Start and end dates (optional)
- [ ] E. Maximum number of participants
- [ ] F. Room type/category (fitness, learning, work, etc.)
- [ ] G. Custom scoring rules
- [ ] H. Other: _______________

**Your answer:**

---

## Question 3: Invitation System
**Q: How should users be invited to private rooms?**
- [ ] A. By username
- [X] B. By email address
- [ ] C. Via shareable invite link
- [ ] D. Via invite code (like a game lobby code)
- [ ] E. Multiple methods

**Q: Should invitations require acceptance or auto-join?**
- [X] A. Require acceptance (user must approve)
- [ ] B. Auto-join if invited
- [ ] C. Configurable by room creator

**Q: Can room members invite others, or only the creator?**
- [X] A. Only creator can invite
- [ ] B. All members can invite
- [ ] C. Creator can grant invite permissions to specific members

**Your answer:**

---

## Question 4: Room Membership and Visibility
**Q: Can a user be in multiple rooms simultaneously?**
- [ ] A. Yes, unlimited
- [X] B. Yes, but with a limit (e.g., max 10 rooms)
- [ ] C. No, only one room at a time

**Q: How should users discover public rooms?**
- [X] A. Browse list of all public rooms
- [ ] B. Search by name/category
- [ ] C. Recommended rooms based on goals
- [ ] D. Trending/popular rooms
- [ ] E. All of the above

**Q: Should there be a global leaderboard and room-specific leaderboards?**
- [ ] A. Only global (all users)
- [X] B. Only room-specific
- [ ] C. Both - users can switch views

**Your answer:**

---

## Question 5: Room-Specific Goals
**Q: Should rooms have their own set of goals, or use members' existing goals?**
- [ ] A. Room creator defines goals everyone must track (same goals for all)
- [X] B. Members bring their own goals into the room
- [ ] C. Hybrid: Some mandatory room goals + members' personal goals
- [ ] D. Just track whatever goals each member has

**Q: If rooms have shared goals, how are they created?**
- [ ] A. Room creator sets goals when creating room
- [ ] B. Room creator can add/edit goals anytime
- [ ] C. Members vote on which goals to track
- [ ] D. Pre-defined challenge templates (e.g., "30-Day Fitness Challenge")

**Your answer:**

---

## Question 6: Scoring and Competition Rules
**Q: Should rooms have custom scoring rules?**
- [X] A. Use the global scoring system (10/7/5/2/0 points)
- [ ] B. Allow room creator to customize point values
- [ ] C. Different competition modes:
  - [ ] Points-based (current system)
  - [ ] Streak-based (longest streak wins)
  - [ ] Consistency-based (most days completed)
  - [ ] Perfect days (count of 100% days)

**Q: Should there be prizes or rewards for room winners?**
- [ ] A. Just bragging rights (no prizes)
- [X] B. Digital badges/achievements
- [ ] C. Both

**Your answer:**

---

## Question 7: Room Communication
**Q: Should rooms have built-in communication features?**
- [ ] A. No communication - just tracking
- [ ] B. Announcement board (creator posts updates)
- [ ] C. Group chat/discussion
- [X] D. Activity feed (who completed what)
- [ ] E. Multiple features

**Q: Should there be notifications for room activities?**
- [X] A. No notifications
- [ ] B. Email notifications for invites/updates
- [ ] C. In-app notifications
- [ ] D. Both email and in-app

**Your answer:**
Keep a Forum style communication. No private messages at this point
---

## Question 8: Room Management
**Q: What permissions should the room creator have?**
- [ ] A. Remove members
- [ ] B. Close/delete the room
- [ ] C. Edit room settings anytime
- [ ] D. Pause/archive the room
- [ ] E. Transfer ownership to another member
- [X] F. All of the above

**Q: Can members leave a room voluntarily?**
- [X] A. Yes, anytime
- [ ] B. Yes, but with restrictions (e.g., not during active challenge)
- [ ] C. No, only creator can remove them

**Your answer:**

---

## Question 9: Personal vs. Room Mode
**Q: How should users switch between personal tracking and room competitions?**
- [ ] A. Dashboard shows both global and room leaderboards
- [ ] B. Separate page for each room
- [ ] C. Dropdown/tab selector to switch between rooms
- [ ] D. Split screen showing personal and room stats side-by-side

**Q: If a user is in multiple rooms, how do they navigate?**
- [X] A. "My Rooms" page listing all joined rooms
- [ ] B. Room selector in navigation bar
- [X] C. Dashboard shows aggregated stats from all rooms

**Your answer:**
User dashboard is global by default shows all rooms and their activities only in each room. They need to select rooms to see leaderboard in that room
---

## Question 10: Advanced Features (Optional)
**Q: Which additional features would be valuable?**
- [ ] A. Room templates (pre-configured challenges)
- [ ] B. Recurring rooms (e.g., monthly fitness challenge)
- [ ] C. Teams within rooms (2v2, 3v3 competitions)
- [X] D. Room achievements/milestones
- [X] E. Export room results/statistics
- [X] F. Room analytics (participation rate, trends)
- [X] G. Integration with calendar (schedule events)

**Your answer:**

---

## Summary
After completing this interview, we will have clarity on:
1. Room creation and ownership model
2. Invitation and membership system
3. Goal tracking within rooms
4. Competition rules and scoring
5. User experience and navigation
6. Communication and notifications
7. Management and permissions

---

## Next Steps After Interview:
1. ✅ Document final requirements
2. Design database schema (rooms, room_members, room_invites tables)
3. Create mockups/wireframes for UI
4. Plan API endpoints
5. Implement backend functions
6. Build frontend interfaces
7. Create comprehensive tests
8. Deploy to production
