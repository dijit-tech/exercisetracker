-- Rename tables
RENAME TABLE rooms TO challenges;
RENAME TABLE room_members TO challenge_members;
RENAME TABLE room_goals TO challenge_goals;
RENAME TABLE room_invites TO challenge_invites;
RENAME TABLE room_posts TO challenge_posts;
RENAME TABLE room_achievements TO challenge_achievements;

-- Rename columns (standardizing on challenge_id)
-- Using MySQL 8.0 RENAME COLUMN syntax which handles Foreign Keys automatically
ALTER TABLE challenge_members RENAME COLUMN room_id TO challenge_id;
ALTER TABLE challenge_goals RENAME COLUMN room_id TO challenge_id;
ALTER TABLE challenge_invites RENAME COLUMN room_id TO challenge_id;
ALTER TABLE challenge_posts RENAME COLUMN room_id TO challenge_id;
ALTER TABLE challenge_achievements RENAME COLUMN room_id TO challenge_id;

-- Update indexes (optional but good practice to keep names consistent)
-- Note: MySQL might not rename indexes automatically when columns change, or it might preserve old names.
-- Dropping and re-creating indexes is safer to ensure correct naming, but arguably not strictly required for functionality if the FKs still hold.
-- For simplicity in this script, we'll assume the column rename handles the FK constraint updates (InnoDB usually handles this).

-- However, if constraints are named explicitly, they might need adjustment. 
-- We'll assume default naming or that functional correctness is primary.
