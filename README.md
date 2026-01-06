# Exercise Tracker

A comprehensive web application for tracking daily exercises with multi-user support, admin panel, and yearly activity calendar visualization.

🌐 **Live Demo:** [https://exercisetracker.dijit.tech](https://exercisetracker.dijit.tech)

## Features

### User Features
- **Exercise Management**: Add, view, and delete exercises with type, duration, and notes
- **Dashboard**: Personal statistics showing weekly exercises, total minutes, and active days
- **Exercise History**: View all logged exercises with filtering and search
- **Responsive Design**: Beautiful Bootstrap 5 UI that works on all devices

### Admin Features
- **User Management**: Create, edit, and delete user accounts
- **Password Management**: Change passwords for any user
- **Yearly Activity Calendar**: Visual grid showing all users' exercise activity for 2026
  - Daily checkmarks (✓) for exercise completion
  - Monthly grouping for easy navigation
  - Sticky columns for better scrolling experience
- **Role Management**: Grant or revoke admin privileges

### Technical Features
- **Secure Authentication**: Session-based authentication with custom session storage
- **Database**: MySQL with PDO for secure queries
- **Docker Support**: Complete local development environment
- **Production Ready**: Deployment scripts for FTP upload
- **Testing**: Selenium test suite for authentication flows

## Technology Stack

- **Backend**: PHP 8.4
- **Database**: MySQL 8.0
- **Frontend**: Bootstrap 5, Vanilla JavaScript
- **Development**: Docker, Docker Compose
- **Testing**: Python, Selenium WebDriver
- **Deployment**: FTP via PowerShell scripts

## Quick Start

### Local Development with Docker

1. **Clone the repository**
   ```bash
   git clone https://github.com/dijit-tech/exercisetracker.git
   cd exercisetracker
   ```

2. **Start Docker containers**
   ```bash
   ./start.bat
   # Or manually:
   docker-compose up -d
   ```

3. **Access the application**
   - App: http://localhost:8000
   - phpMyAdmin: http://localhost:8080

4. **Login with test accounts**
   - Admin: `admin` / `password123`
   - User: `testuser` / `password123`

### Project Structure

```
exercisetracker/
├── config/
│   ├── database.php              # Database configuration
│   ├── database_production.php   # Production config
│   └── database_local.php        # Local config backup
├── database/
│   └── init.sql                  # Database schema and test data
├── public/
│   ├── api/                      # API endpoints
│   │   ├── login.php
│   │   ├── logout.php
│   │   ├── add_exercise.php
│   │   ├── delete_exercise.php
│   │   ├── admin_create_user.php
│   │   ├── admin_update_user.php
│   │   └── admin_delete_user.php
│   ├── includes/                 # Core PHP modules
│   │   ├── session.php           # Session management
│   │   ├── db.php                # Database connection
│   │   ├── auth.php              # Authentication functions
│   │   └── exercises.php         # Exercise CRUD functions
│   ├── index.php                 # Login page
│   ├── dashboard.php             # Main dashboard with yearly calendar
│   ├── exercises.php             # Exercise management page
│   └── admin.php                 # Admin panel
├── tests/
│   ├── test_auth.py              # Selenium authentication tests
│   └── test_production.py        # Production smoke tests
├── docker-compose.yml            # Docker services configuration
├── Dockerfile                    # PHP Apache image
├── deploy_via_ftp.ps1            # Automated deployment script
└── start.bat                     # Quick Docker startup
```

## Database Schema

### Users Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);
```

### Exercises Table
```sql
CREATE TABLE exercises (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    exercise_date DATE NOT NULL,
    exercise_type VARCHAR(50) NOT NULL,
    duration_minutes INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Configuration

### Local Development
Edit `config/database.php`:
```php
define('DB_HOST', 'db');              // Docker service name
define('DB_NAME', 'exercisetracker');
define('DB_USER', 'root');
define('DB_PASS', 'rootpassword');
define('APP_URL', 'http://localhost:8000');
```

### Production
Edit `config/database_production.php`:
```php
define('DB_HOST', 'your-mysql-host');
define('DB_NAME', 'your-database');
define('DB_USER', 'your-username');
define('DB_PASS', 'your-password');
define('APP_URL', 'https://your-domain.com');
```

## Deployment

### Production Deployment via FTP

1. **Update production config**
   - Edit `config/database_production.php` with production credentials

2. **Run deployment script**
   ```powershell
   ./deploy_via_ftp.ps1
   ```

3. **Import database**
   - Login to phpMyAdmin
   - Import `database/init.sql`

4. **Test the deployment**
   - Visit your production URL
   - Login with admin credentials

### Key Differences: Local vs Production
- **Paths**: Local uses `../../config`, Production uses `../config`
- **Session Storage**: Custom writable directory for shared hosting
- **Error Display**: Disabled in production, enabled in development

## Testing

### Run Selenium Tests
```bash
# Activate virtual environment
.venv\Scripts\Activate.ps1

# Run authentication tests
python tests/test_auth.py

# Run production tests
python tests/test_production.py
```

### Test Coverage
- ✓ Login with valid credentials
- ✓ Login with invalid credentials
- ✓ Session persistence across pages
- ✓ Logout functionality
- ✓ Admin access control
- ✓ Protected pages redirect

## Features in Detail

### Yearly Exercise Calendar
The dashboard displays a comprehensive yearly calendar showing exercise activity for all users:
- **365-day grid** starting from January 1, 2026
- **Month headers** for easy navigation
- **Visual indicators**: ✓ (exercised) or ✕ (no exercise)
- **Sticky columns**: User names stay visible while scrolling
- **Hover tooltips**: Date and user information

### Admin User Management
Complete user administration interface:
- **Create users** with username, email, password, and role
- **Edit users** including password changes (optional)
- **Delete users** with protection for last admin
- **View activity** including last login timestamps

### Session Management
Robust session handling optimized for shared hosting:
- Custom session save path for write permissions
- Session timeout management (2 hours)
- Automatic session cleanup
- Secure cookie settings

## Security Features

- **Password Hashing**: BCrypt with PHP's `password_hash()`
- **SQL Injection Protection**: PDO prepared statements
- **XSS Prevention**: `htmlspecialchars()` for all output
- **CSRF Protection**: Form validation and proper HTTP methods
- **Session Security**: HTTP-only cookies, strict mode
- **Admin Protection**: Cannot delete self or last admin

## Troubleshooting

### Session Issues on Shared Hosting
If sessions aren't persisting:
1. Ensure `sessions/` directory exists and is writable (0700)
2. Check that custom session path is set in `session.php`
3. Verify no output before `session_start()`

### Database Connection Errors
1. Verify credentials in `config/database.php`
2. Check database server is accessible
3. Ensure database and tables exist
4. Confirm user has proper permissions

### 500 Internal Server Errors
1. Check PHP error logs
2. Verify all file paths are correct for environment
3. Ensure proper file permissions
4. Test with `test_db_connection.php`

## Development Notes

### Session Management Evolution
The session handling went through several iterations to solve persistence issues on shared hosting:
- Initially used `session_write_close()` + `session_start()` (caused empty sessions)
- Removed `session_regenerate_id()` (was destroying session data)
- Added custom writable session path (solved iPage hosting issues)

### Path Handling
Due to different directory structures between local Docker and production:
- Local: Files in `public/` subfolder
- Production: Files at root level
- Solution: Separate `*_prod.php` versions with correct paths

## License

This project is private and proprietary.

## Support

For issues or questions, please open an issue on GitHub or contact the development team.

## Changelog

### Version 1.0.0 (January 2026)
- Initial release
- User authentication and authorization
- Exercise tracking (CRUD operations)
- Yearly activity calendar visualization
- Admin panel with user management
- Docker development environment
- Production deployment scripts
- Selenium test suite
