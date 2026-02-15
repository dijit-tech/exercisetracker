# Application Architecture

## Core Modules

| Module | Description | Key Files |
| :--- | :--- | :--- |
| **Auth** | Manages user authetnication, registration, and session persistence. | `index.php` (Login), `includes/auth.php`, `includes/session.php` |
| **Dashboard** | Central hub. Aggregates data from Goals and Challenges. | `dashboard.php`, `includes/goals.php`, `includes/challenges.php` |
| **Goals** | Core domain logic. CRUD for goals and daily logging. | `goals.php` (UI), `track_today.php`, `includes/goals.php` |
| **Challenges** | Social/Gamification. Users compete in groups. | `challenges.php`, `challenge.php`, `includes/challenges.php` |
| **System** | Infrastructure. DB connections, config, email. | `includes/db.php`, `includes/config.php`, `includes/mail.php` |

## Interaction Flow
1. **Auth**: Gates all access. `dashboard.php` calls `requireLogin()` immediately.
2. **Dashboard**: 
   - Calls `getGoalStats` (Goals Module)
   - Calls `getUserPendingInvites` (Challenges Module)
3. **Challenges**:
   - Reads available goals from **Goals Module** when a user joins a challenge.
   - Calculates leaderboards based on `goal_logs`.

## Architecture Diagram
![System Architecture](architecture_diagram.drawio)
[Open Architecture Diagram](architecture_diagram.drawio)
