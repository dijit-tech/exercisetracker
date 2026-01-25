<?php
/**
 * SQLite Helper for Guest Mode
 * Handles creation, seeding, and cleanup of ephemeral databases
 */

function getGuestDbPath($sessionId) {
    $dir = __DIR__ . '/../sessions/db';
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    // Sanitize ID
    $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $sessionId);
    return "$dir/guest_$safeId.sqlite";
}

function initGuestDb($sessionId) {
    $dbPath = getGuestDbPath($sessionId);
    $cleanupOldStats = cleanupOldGuestDbs(); // Run cleanup
    
    try {
        $db = null;
        // Check for PDO SQLite driver
        if (in_array('sqlite', PDO::getAvailableDrivers())) {
            $db = new PDO("sqlite:$dbPath");
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } 
        // Fallback to SQLite3 Wrapper if native extension exists
        elseif (extension_loaded('sqlite3')) {
            require_once __DIR__ . '/pdo_sqlite3_wrapper.php';
            $db = new PdoSqlite3Wrapper($dbPath);
        } else {
            throw new Exception("No SQLite driver available (pdo_sqlite or sqlite3)");
        }
        
        // 1. Create Schema (SQLite Compatible)
        
        // Users
        $db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                is_admin INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Goals
        $db->exec("
            CREATE TABLE IF NOT EXISTS goals (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                goal_title TEXT NOT NULL,
                goal_category TEXT NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NULL,
                status TEXT DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        
        // Logs
        $db->exec("
            CREATE TABLE IF NOT EXISTS goal_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                goal_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                log_date DATE NOT NULL,
                completed INTEGER DEFAULT 1,
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(goal_id, log_date),
                FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        
        // Challenges (Rooms)
        $db->exec("
            CREATE TABLE IF NOT EXISTS challenges (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                creator_user_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                description TEXT NULL,
                category TEXT NOT NULL,
                privacy TEXT DEFAULT 'private',
                status TEXT DEFAULT 'active',
                is_default INTEGER DEFAULT 0,
                start_date DATE NULL,
                end_date DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (creator_user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        
        // Challenge Members
        $db->exec("
            CREATE TABLE IF NOT EXISTS challenge_members (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                challenge_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                status TEXT DEFAULT 'active',
                joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE(challenge_id, user_id)
            )
        ");
        
        // Challenge Goals
        $db->exec("
            CREATE TABLE IF NOT EXISTS challenge_goals (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                challenge_id INTEGER NOT NULL,
                goal_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
                FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE(challenge_id, goal_id)
            )
        ");

        // Challenge Posts
        $db->exec("
            CREATE TABLE IF NOT EXISTS challenge_posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                challenge_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                post_type TEXT DEFAULT 'message',
                content TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        // Challenge Achievements
        $db->exec("
            CREATE TABLE IF NOT EXISTS challenge_achievements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                challenge_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                achievement_type TEXT NOT NULL,
                achievement_name TEXT NULL,
                achievement_description TEXT NULL,
                unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        // Challenge Invites
        $db->exec("
            CREATE TABLE IF NOT EXISTS challenge_invites (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                challenge_id INTEGER NOT NULL,
                inviter_user_id INTEGER NOT NULL,
                invitee_user_id INTEGER NULL,
                invitee_email TEXT NOT NULL,
                status TEXT DEFAULT 'pending',
                invited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
                FOREIGN KEY (inviter_user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        
        // 2. Seed User
        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, is_admin) VALUES (?, ?, ?, 0)");
        $stmt->execute(['Guest User', 'guest@example.com', 'nopass']);
        $userId = $db->lastInsertId();
        
        // 3. Seed Goals & History
        seedGuestData($db, $userId);
        
        return $userId;
        
    } catch (Exception $e) {
        die("Guest DB Init Error: " . $e->getMessage());
    }
}

function seedGuestData($db, $userId) {
    try {
        // 0. Create "Personal Goals" default challenge for the guest
        $stmt = $db->prepare("
            INSERT INTO challenges (creator_user_id, name, description, category, privacy, status, is_default, created_at)
            VALUES (?, 'Guest''s Personal Goals', 'Default personal workspace', 'Personal', 'private', 'active', 1, ?)
        ");
        $timestamp = date('Y-m-d H:i:s');
        $stmt->execute([$userId, $timestamp]);
        $personalChallengeId = $db->lastInsertId();

        // Add guest to their personal challenge
        $stmt = $db->prepare("INSERT INTO challenge_members (challenge_id, user_id, status) VALUES (?, ?, 'active')");
        $stmt->execute([$personalChallengeId, $userId]);
    } catch (Exception $e) { throw new Exception("Error Seeding Personal Challenge: " . $e->getMessage()); }

    try {
        // 1. Create Dummy Rival "Dusty Crophopper"
        // Reference: Planes (2013) - The crop duster who wanted to race.
        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, is_admin) VALUES (?, ?, ?, 0)");
        $stmt->execute(['DustyCronhopper', 'dusty@propwash.junction', 'flyhigh']);
        $dustyId = $db->lastInsertId();
    } catch (Exception $e) { throw new Exception("Error Seeding Dusty: " . $e->getMessage()); }

    try {
        // 2. Create "Weekly Strength Showdown" Challenge
        $monday = date('Y-m-d', strtotime('monday this week'));
        $sunday = date('Y-m-d', strtotime('sunday this week'));
        
        $stmt = $db->prepare("
            INSERT INTO challenges (creator_user_id, name, description, category, privacy, status, start_date, end_date)
            VALUES (:creator, :name, :desc, :cat, :priv, :stat, :start, :end)
        ");
        $stmt->execute([
            ':creator' => $dustyId, 
            ':name' => 'Weekly Strength Showdown', 
            ':desc' => 'Who can hit the gym consecutively?', 
            ':cat' => 'Fitness', 
            ':priv' => 'public', 
            ':stat' => 'active', 
            ':start' => $monday, 
            ':end' => $sunday
        ]);
        $showdownId = $db->lastInsertId();
    } catch (Exception $e) { throw new Exception("Error Seeding Showdown: " . $e->getMessage()); }

    try {
        // Add both users to the Showdown
        $stmt = $db->prepare("INSERT INTO challenge_members (challenge_id, user_id, status) VALUES (?, ?, 'active')");
        $stmt->execute([$showdownId, $dustyId]);
        $stmt->execute([$showdownId, $userId]);
        
        // Create dusty's goal in the challenge
        $stmt = $db->prepare("INSERT INTO goals (user_id, goal_title, goal_category, start_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$dustyId, 'Strength Training', 'Fitness', $monday]);
        $dustyGoalId = $db->lastInsertId();

        // Link dusty's goal to challenge
        $stmt = $db->prepare("INSERT INTO challenge_goals (challenge_id, goal_id, user_id) VALUES (?, ?, ?)");
        $stmt->execute([$showdownId, $dustyGoalId, $dustyId]);

        // Seed Dusty's activity (He's doing reasonably well, but beatable)
        // He worked out 3 days ago and yesterday
        $daysToLog = [1, 3, 4]; 
        foreach ($daysToLog as $daysAgo) {
            $logDate = date('Y-m-d', strtotime("-$daysAgo days"));
            // Only log if date is >= monday (start of challenge) to act consistent
            if ($logDate >= $monday) {
                 $db->exec("INSERT INTO goal_logs (goal_id, user_id, log_date, completed, notes) VALUES ($dustyGoalId, $dustyId, '$logDate', 1, 'Light weight baby!')");
            }
        }
    } catch (Exception $e) { throw new Exception("Error Seeding Dusty Goals: " . $e->getMessage()); }


    // 3. Create Goal 1 for USER: Drink Water (Consistent) -> Put in Personal Challenge
    $stmt = $db->prepare("INSERT INTO goals (user_id, goal_title, goal_category, start_date) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, 'Drink 2L Water', 'Health', date('Y-m-d', strtotime('-14 days'))]);
    $goal1 = $db->lastInsertId();
    
    // Link to Personal Challenge
    $stmt = $db->prepare("INSERT INTO challenge_goals (challenge_id, goal_id, user_id) VALUES (?, ?, ?)");
    $stmt->execute([$personalChallengeId, $goal1, $userId]);

    // Log previous 14 days (missing a couple for realism)
    for ($i = 14; $i >= 0; $i--) {
        if ($i == 3 || $i == 8) continue; // Missed days
        $date = date('Y-m-d', strtotime("-$i days"));
        $logStmt = $db->prepare("INSERT INTO goal_logs (goal_id, user_id, log_date, completed, notes) VALUES (?, ?, ?, 1, ?)");
        $notes = ($i == 0) ? 'Feeling hydrated!' : '';
        $logStmt->execute([$goal1, $userId, $date, $notes]);
    }
    
    // 4. Create Goal 2 for USER: Strength Training -> Put in Weekly Challenge
    // This connects the user to the social aspect immediately
    $stmt = $db->prepare("INSERT INTO goals (user_id, goal_title, goal_category, start_date) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, 'Strength Training', 'Fitness', $monday]);
    $goal2 = $db->lastInsertId();

    // Link to Weekly Showdown
    $stmt = $db->prepare("INSERT INTO challenge_goals (challenge_id, goal_id, user_id) VALUES (?, ?, ?)");
    $stmt->execute([$showdownId, $goal2, $userId]);
    

    for ($i = 5; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $logStmt = $db->prepare("INSERT INTO goal_logs (goal_id, user_id, log_date, completed) VALUES (?, ?, ?, 1)");
        $logStmt->execute([$goal2, $userId, $date]);
    }
    
    // Goal 3: Code Project (Inconsistent)
    $stmt = $db->prepare("INSERT INTO goals (user_id, goal_title, goal_category, start_date) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, 'Side Project', 'Career', date('Y-m-d', strtotime('-10 days'))]);
    $goal3 = $db->lastInsertId();
    
    $days = [0, 2, 4, 5, 9];
    foreach ($days as $d) {
        $date = date('Y-m-d', strtotime("-$d days"));
        $logStmt = $db->prepare("INSERT INTO goal_logs (goal_id, user_id, log_date, completed) VALUES (?, ?, ?, 1)");
        $logStmt->execute([$goal3, $userId, $date]);
    }
}

function cleanupOldGuestDbs() {
    $dir = __DIR__ . '/../sessions/db';
    if (!file_exists($dir)) return 0;
    
    $files = glob("$dir/guest_*.sqlite");
    $count = 0;
    $now = time();
    $maxAge = 24 * 3600; // 24 hours
    
    foreach ($files as $file) {
        if (file_exists($file) && ($now - filemtime($file) > $maxAge)) {
            unlink($file);
            $count++;
        }
    }
    return $count;
}
?>