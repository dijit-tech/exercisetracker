-- Clickstream Analytics Schema (v3)
-- Based on architecture in docs/CLICKSTREAM_ARCHITECTURE.md

-- 1. Add preferences column to users table for granular privacy settings
-- This uses MySQL 8.0 JSON type for flexibility
ALTER TABLE users ADD COLUMN preferences JSON DEFAULT NULL;
-- Example: {"analytics_enabled": true, "email_frequency": "weekly"}

-- 2. Create the clickstream_events table for high-volume event storage
CREATE TABLE IF NOT EXISTS clickstream_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(100) NOT NULL,
    user_id INT NULL, -- Nullable for anonymous/logged-out users
    session_hash CHAR(64) NOT NULL, -- Hashed session ID to stitch pre-login activity
    occurred_at_utc TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    client_timezone VARCHAR(64) NOT NULL, -- e.g., 'America/New_York'
    client_timestamp DATETIME NOT NULL, -- Local time on user device
    properties JSON, -- Event-specific data (goal_id, category, etc.)
    context JSON, -- Technical context (User-Agent, Screen Size, etc.)
    
    INDEX idx_user_id (user_id),
    INDEX idx_event_name (event_name),
    INDEX idx_occurred_at (occurred_at_utc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create a view for "Smart Nudge" analysis
-- Calculates the most active hour of the day for each user in their local time
CREATE OR REPLACE VIEW view_user_activity_patterns AS
SELECT 
    user_id,
    client_timezone,
    HOUR(client_timestamp) as hour_of_day,
    COUNT(*) as activity_count
FROM clickstream_events
WHERE user_id IS NOT NULL
GROUP BY user_id, client_timezone, HOUR(client_timestamp);
