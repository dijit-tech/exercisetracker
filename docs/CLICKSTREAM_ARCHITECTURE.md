# Clickstream & Analytics Architecture

## Overview
This document outlines the architecture for the "Goal Tracker" behavioral analytics system. The system uses a **Hybrid Ingestion** model (Client-side JS + Server-side PHP) to capture high-fidelity user events while respecting privacy preferences.

![Clickstream Diagram](clickstream_diagram.drawio)
[Open Clickstream Diagram](clickstream_diagram.drawio)

The goal is to move beyond generic pageviews and capture **User Intent** (e.g., "Goal Logged", "Streak Broken", "Leaderboard Scrolled") to power features like:
- **Smart Nudges**: Reminding users at their usual activity time.
- **Abandonment Prevention**: Detecting when a user is slipping.
- **Viral Coefficient Analysis**: Tracking challenge invites.

---

## 1. Data Schema (MySQL 8.0)

We leverage MySQL 8.0's native `JSON` column type to allow for a flexible, schema-less event structure while maintaining relational integrity for the core metadata.

### `clickstream_events` Table
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | `BIGINT` | Primary Key. |
| `event_name` | `VARCHAR(100)` | High-level action (e.g., `goal_completed`, `page_view`). |
| `user_id` | `INT (Nullable)` | Linked to `users.id`. NULL if anonymous/logged out. |
| `session_hash` | `CHAR(64)` | The PHP Session ID. critical for stitching pre-login activity. |
| `occurred_at_utc` | `TIMESTAMP` | When the event happened (UTC). |
| `client_timezone` | `VARCHAR(64)` | e.g. "America/New_York". Vital for "Local Time" analysis. |
| `client_timestamp` | `DATETIME` | The local time on the user's device. |
| `properties` | **`JSON`** | Event-specific data (e.g., `{"goal_id": 42, "category": "Health"}`). |
| `context` | **`JSON`** | Technical metadata (User Agent, Screen Size, Network Type). |

### `users` Table Update
We add a `preferences` JSON column to the existing `users` table to store granular privacy settings without schema migrations.
```sql
ALTER TABLE users ADD COLUMN preferences JSON DEFAULT NULL;
-- Example: {"analytics_enabled": true, "email_frequency": "weekly"}
```

---

## 2. Ingestion Pipeline

### Client-Side: `startSession();` (JS)
A lightweight library (`public/js/tracker.js`) handles event buffering and transmission.
- **Auto-Capture**: Listens for clicks on elements with `data-track-event="name"`.
- **Context Awareness**: Automatically captures `window.innerWidth`, `referrer`, and `timezone`.
- **Privacy Check**: Reads `window.currentUserPrefs` to decide whether to scrub sensitive device data.
- **Transmission**: Uses `navigator.sendBeacon` for reliability (even if the tab is closed immediately).

### Server-Side: `/api/ingest.php`
A write-only endpoint that acts as the gatekeeper.
1.  **Authentication**: Checks `$_SESSION['user_id']`.
2.  **Privacy Enforcement**: 
    - Checks `$_SESSION['preferences']['analytics_enabled']`.
    - If **Opted-Out**, it still records the event but:
        - Hashes the IP address.
        - Nullifies the User-Agent.
        - User ID is recorded as NULL (Anonymous).
3.  **Storage**: Validates and inserts the JSON payload directly into `clickstream_events`.

---

## 3. Domain Event Taxonomy

We track events categorized by User Intent:

### A. Core Loop (Habit Formation)
these events drive the "Streak" logic.
- `goal_log_submitted`: User checked a box.
    - *Props*: `goal_id`, `category`, `is_backfill`.
- `goal_log_undo`: User unchecked a box.
- `streak_milestone`: Server-side event when a streak hits 7, 30, 100 days.

### B. Social & Gamification
- `challenge_joined`: User accepted an invite.
- `leaderboard_scrolled`: User is engaging with the social proof.
    - *Props*: `room_id`, `depth_percent`.
- `invite_sent`: Viral growth metric.

### C. Friction (Growth Hacking)
- `goal_creation_started`: User opened the "New Goal" form.
- `goal_creation_abandoned`: User closed the form without saving.

---

## 4. Analytics & Value

### The "Smart Nudge" Engine
By comparing `client_timestamp` with `client_timezone`, we can determine the user's "Prime Time".

**SQL View: `view_user_activity_patterns`**
```sql
CREATE OR REPLACE VIEW view_user_activity_patterns AS
SELECT 
    user_id,
    client_timezone,
    HOUR(client_timestamp) as hour_of_day, -- The User's Local Hour (0-23)
    COUNT(*) as activity_count
FROM clickstream_events
WHERE user_id IS NOT NULL
GROUP BY user_id, client_timezone, HOUR(client_timestamp);
```

**Usage**:
To find the best time to send a push notification to User #123:
```sql
SELECT hour_of_day FROM view_user_activity_patterns 
WHERE user_id = 123 
ORDER BY activity_count DESC 
LIMIT 1;
-- Result: 19 (7 PM is their most active time)
```

---

## 5. Security & Privacy

1.  **Consent**: Users can toggle "Allow Analytics" in `/settings.php`. This is respected by both Client (JS) and Server (PHP).
2.  **Data Minimization**: We only store raw IP addresses for fraud detection (rate limiting) unless the user has opted in.
3.  **Retention**: A scheduled job (cron) will purge raw `clickstream_events` older than 90 days, retaining only aggregated stats.

## Architecture Diagram

![Clickstream Diagram](clickstream_diagram.drawio)
[Open Clickstream Diagram](clickstream_diagram.drawio)
