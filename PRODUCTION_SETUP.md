# Production Database Setup Instructions

## Step 1: Log into iPage Control Panel
1. Go to https://www.ipage.com/
2. Login with your credentials
3. Navigate to MySQL Databases

## Step 2: Create Database User (if not exists)
- Username: apps_exercise
- Password: ExerciseTracker2026!
- Save credentials

## Step 3: Create Database
- Database name: apps_exercisetracker
- Assign user: apps_exercise
- Grant ALL PRIVILEGES

## Step 4: Import Schema via phpMyAdmin
1. Click phpMyAdmin link for apps_exercisetracker
2. Make sure apps_exercisetracker database is selected (top left dropdown)
3. Click "Import" tab
4. Upload file: database/init.sql
5. Click "Go"

**Note:** The init.sql file will create tables in whatever database is currently selected. Make sure apps_exercisetracker is selected before importing!

## Step 5: Verify Tables Created
Run this query to verify:
```sql
SHOW TABLES;
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM exercises;
```

You should see:
- 2 tables: users, exercises
- 2 users: admin, testuser
- 4 sample exercises

## Step 6: Test Application
1. Visit: https://exercisetracker.dijit.tech
2. Login with: testuser / password123
3. Verify dashboard loads
4. Test navigation between pages
5. Test logout

## Step 7: Update Passwords
After testing, change the default passwords:
1. Login as admin / password123
2. Navigate to admin panel (when implemented)
3. Change both admin and testuser passwords

## Rollback Plan
If anything goes wrong:
1. FTP back to: ftp.lonkar.in
2. Delete /apps/exercisetracker/* files
3. Restore from backup (if available)
4. Or redeploy from local

## Production URL
https://exercisetracker.dijit.tech

## Database Connection Details
- Host: anandlonkar.ipagemysql.com
- Database: apps_exercisetracker
- User: apps_exercise
- Password: ExerciseTracker2026!
