# Goal Tracker - Deployment Guide

## Pre-Deployment Checklist

### ✅ Development Complete
- [x] Rooms/Competitions feature fully implemented
- [x] 13/13 tests passing (100% success rate)
- [x] All PHP errors suppressed
- [x] Database schema loaded
- [x] Navigation updated across all pages
- [x] Code committed to git

### 📊 Test Results Summary
```
Total Tests: 13
Passed: 13 ✓
Failed: 0 ✗
Success Rate: 100.0%
```

## Deployment Options

### Option 1: Deploy to Production Server (Recommended)

#### Requirements
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server
- Git installed

#### Steps

1. **Clone Repository to Production Server**
```bash
cd /var/www/
git clone <your-repo-url> goaltracker
cd goaltracker
```

2. **Set Up Environment Variables**
```bash
cp .env.example .env
nano .env
```

Update with production credentials:
```env
DB_HOST=localhost
DB_NAME=goaltracker_prod
DB_USER=goaltracker_user
DB_PASSWORD=<strong-password>
DB_PORT=3306
```

3. **Create Production Database**
```bash
mysql -u root -p
```

```sql
CREATE DATABASE goaltracker_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'goaltracker_user'@'localhost' IDENTIFIED BY '<strong-password>';
GRANT ALL PRIVILEGES ON goaltracker_prod.* TO 'goaltracker_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

4. **Load Database Schema**
```bash
mysql -u goaltracker_user -p goaltracker_prod < database/schema_complete.sql
```

5. **Create Admin User**
```bash
mysql -u goaltracker_user -p goaltracker_prod < database/reset_users.sql
```

This creates:
- Username: `admin`, Password: `password`
- Username: `testuser`, Password: `password`

**⚠️ IMPORTANT**: Change these passwords immediately after first login!

6. **Set Correct Permissions**
```bash
# Create sessions directory if it doesn't exist
mkdir -p public/sessions
chmod 755 public/sessions
chown -R www-data:www-data public/sessions

# Set file permissions
find public -type f -exec chmod 644 {} \;
find public -type d -exec chmod 755 {} \;
```

7. **Configure Apache Virtual Host**

Create `/etc/apache2/sites-available/goaltracker.conf`:
```apache
<VirtualHost *:80>
    ServerName goaltracker.yourdomain.com
    DocumentRoot /var/www/goaltracker/public

    <Directory /var/www/goaltracker/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/goaltracker-error.log
    CustomLog ${APACHE_LOG_DIR}/goaltracker-access.log combined
</VirtualHost>
```

Enable the site:
```bash
sudo a2ensite goaltracker
sudo a2enmod rewrite
sudo systemctl restart apache2
```

8. **Set Up SSL (Recommended)**
```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d goaltracker.yourdomain.com
```

9. **Verify Deployment**
- Visit https://goaltracker.yourdomain.com
- Login with admin/password
- Change the password immediately
- Create a test goal
- Create a test room
- Verify all features work

### Option 2: Deploy with Docker

1. **Use Existing Docker Setup**
```bash
cd /path/to/goaltracker
docker-compose up -d
```

2. **For Production, Update docker-compose.yml**
```yaml
version: '3.8'

services:
  web:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "80:80"
    volumes:
      - ./public:/var/www/html/public:ro  # Read-only in production
    environment:
      - PHP_ENV=production
    depends_on:
      - db
    restart: unless-stopped

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: goaltracker
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql
      - ./database/schema_complete.sql:/docker-entrypoint-initdb.d/01-schema.sql
      - ./database/reset_users.sql:/docker-entrypoint-initdb.d/02-users.sql
    restart: unless-stopped

volumes:
  db_data:
```

### Option 3: Deploy to Cloud Platform

#### Heroku
```bash
# Install Heroku CLI
heroku login
heroku create goaltracker-app

# Add JawsDB MySQL addon
heroku addons:create jawsdb:kitefin

# Get database credentials
heroku config:get JAWSDB_URL

# Update .env with Heroku credentials
# Deploy
git push heroku main

# Load schema
heroku run bash
mysql -h <host> -u <user> -p<password> <database> < database/schema_complete.sql
```

#### AWS EC2
1. Launch Ubuntu 20.04 instance
2. Install LAMP stack
3. Follow "Option 1" steps above
4. Configure security groups (ports 80, 443, 22)
5. Set up Elastic IP for static address

#### DigitalOcean
1. Create Ubuntu Droplet
2. Follow "Option 1" steps above
3. Use DigitalOcean's one-click LAMP stack for easier setup

## Post-Deployment Tasks

### 1. Security Hardening

**Change Default Passwords**
```sql
UPDATE users SET password_hash = PASSWORD('new-secure-password') WHERE username = 'admin';
```

**Restrict File Permissions**
```bash
chmod 600 .env
chmod 600 database/*.sql
```

**Update php.ini for Production**
```ini
display_errors = Off
display_startup_errors = Off
error_reporting = E_ALL
log_errors = On
error_log = /var/log/php/error.log
```

**Enable HTTPS Only**
Add to .htaccess:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 2. Configure Backups

**Daily Database Backup Script** (`/usr/local/bin/backup-goaltracker.sh`):
```bash
#!/bin/bash
BACKUP_DIR="/var/backups/goaltracker"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

mysqldump -u goaltracker_user -p<password> goaltracker_prod > \
  $BACKUP_DIR/goaltracker_$DATE.sql

# Keep only last 7 days
find $BACKUP_DIR -name "goaltracker_*.sql" -mtime +7 -delete
```

**Add to crontab**:
```bash
0 2 * * * /usr/local/bin/backup-goaltracker.sh
```

### 3. Monitoring Setup

**Install Server Monitoring**
```bash
# Install htop for resource monitoring
sudo apt install htop

# Check PHP error logs
tail -f /var/log/php/error.log

# Check Apache logs
tail -f /var/log/apache2/goaltracker-error.log
```

**Set Up Uptime Monitoring**
- Use UptimeRobot (free): https://uptimerobot.com
- Monitor: https://goaltracker.yourdomain.com
- Alert via email/SMS on downtime

### 4. Performance Optimization

**Enable PHP OPcache** (php.ini):
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

**Enable MySQL Query Cache** (my.cnf):
```ini
query_cache_type = 1
query_cache_size = 64M
query_cache_limit = 2M
```

**Enable Apache Compression**:
```bash
sudo a2enmod deflate
sudo systemctl restart apache2
```

## Testing Deployment

### Smoke Tests
1. ✅ Login page loads
2. ✅ Can login with admin credentials
3. ✅ Dashboard displays without errors
4. ✅ Can create a new goal
5. ✅ Can track today's goals
6. ✅ Can create a room
7. ✅ Can send room invitation
8. ✅ Leaderboard displays correctly
9. ✅ Activity feed works
10. ✅ Navigation works on all pages

### Load Testing (Optional)
```bash
# Install Apache Bench
sudo apt install apache2-utils

# Test with 100 concurrent requests
ab -n 1000 -c 100 https://goaltracker.yourdomain.com/
```

## Rollback Plan

If deployment fails:

1. **Restore Previous Code**
```bash
git checkout <previous-commit-hash>
```

2. **Restore Database**
```bash
mysql -u goaltracker_user -p goaltracker_prod < /var/backups/goaltracker/goaltracker_<date>.sql
```

3. **Clear Cache**
```bash
sudo systemctl restart apache2
php -r "opcache_reset();"
```

## Support & Troubleshooting

### Common Issues

**Issue: Database connection failed**
- Check .env credentials
- Verify MySQL service is running: `sudo systemctl status mysql`
- Check firewall allows MySQL port: `sudo ufw status`

**Issue: PHP errors displaying**
- Verify display_errors = Off in php.ini
- Check error_reporting is set correctly
- Restart Apache: `sudo systemctl restart apache2`

**Issue: Session errors**
- Check sessions/ directory exists and is writable
- Verify session.save_path in php.ini
- Check disk space: `df -h`

**Issue: Slow performance**
- Enable OPcache
- Check database indexes: Run `EXPLAIN` on slow queries
- Monitor with `htop`

## Maintenance Schedule

### Daily
- Monitor error logs
- Check uptime status

### Weekly
- Review backup logs
- Check disk space usage
- Review user activity

### Monthly
- Update dependencies: `composer update`
- Review and optimize database indexes
- Test backup restoration

## Environment-Specific Configuration

### Development
```env
APP_ENV=development
DEBUG=true
DB_HOST=localhost
DB_PORT=3307
```

### Staging
```env
APP_ENV=staging
DEBUG=true
DB_HOST=staging-db.internal
DB_PORT=3306
```

### Production
```env
APP_ENV=production
DEBUG=false
DB_HOST=prod-db.internal
DB_PORT=3306
```

## Deployment Checklist

- [ ] Code committed and pushed to repository
- [ ] All tests passing (13/13)
- [ ] Database schema loaded
- [ ] Admin credentials changed
- [ ] SSL certificate installed
- [ ] Backups configured
- [ ] Monitoring set up
- [ ] Error logging enabled
- [ ] Performance optimizations applied
- [ ] Smoke tests completed
- [ ] Documentation updated
- [ ] Team notified of deployment

## Contact

For deployment issues or questions:
- Review logs: `/var/log/apache2/goaltracker-error.log`
- Check PHP logs: `/var/log/php/error.log`
- Database logs: `/var/log/mysql/error.log`

---

**Deployment Date**: January 7, 2026
**Version**: 2.0.0 (Rooms/Competitions Feature)
**Status**: ✅ Ready for Production
