# FTP Deployment Script for Windows
# Deploy Exercise Tracker to ipage.com production

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Exercise Tracker - Production Deployment" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$ftpServer = "ftp.lonkar.in"
$ftpUsername = "dijittechadmin"
$ftpPassword = "Just4DijitTechApps!"
$ftpBasePath = "/apps/exercisetracker"
$localPath = "c:\code\exercisetracker_fresh_20260105"

Write-Host "Step 1: Backing up production config..." -ForegroundColor Yellow
Copy-Item "$localPath\config\database.php" "$localPath\config\database_local_backup.php" -Force
Write-Host "✓ Local config backed up" -ForegroundColor Green

Write-Host "`nStep 2: Switching to production config..." -ForegroundColor Yellow
Copy-Item "$localPath\config\database_production.php" "$localPath\config\database.php" -Force
Write-Host "✓ Production config active" -ForegroundColor Green

Write-Host "`nStep 3: Creating deployment package..." -ForegroundColor Yellow
$deployFiles = @(
    "public\*",
    "config\database.php"
)

Write-Host "✓ Files ready for deployment" -ForegroundColor Green

Write-Host "`nStep 4: Connecting to FTP server..." -ForegroundColor Yellow
Write-Host "Server: $ftpServer" -ForegroundColor Gray
Write-Host "Path: $ftpBasePath" -ForegroundColor Gray

# Install WinSCP if needed for easier FTP
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "MANUAL DEPLOYMENT STEPS:" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "1. Use FileZilla or WinSCP to connect:" -ForegroundColor White
Write-Host "   Host: ftp.lonkar.in" -ForegroundColor Gray
Write-Host "   User: dijittechadmin" -ForegroundColor Gray
Write-Host "   Pass: Just4DijitTechApps!" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Navigate to: /apps/exercisetracker/" -ForegroundColor White
Write-Host ""
Write-Host "3. Upload these folders:" -ForegroundColor White
Write-Host "   - public/* -> /apps/exercisetracker/" -ForegroundColor Gray
Write-Host "   - config/database.php -> /apps/exercisetracker/config/" -ForegroundColor Gray
Write-Host ""
Write-Host "4. Set up database on phpMyAdmin:" -ForegroundColor White
Write-Host "   URL: https://www.ipage.com/" -ForegroundColor Gray
Write-Host "   Database: apps_exercisetracker" -ForegroundColor Gray
Write-Host "   Import: database/init.sql" -ForegroundColor Gray
Write-Host ""
Write-Host "5. Test at: https://exercisetracker.dijit.tech" -ForegroundColor White
Write-Host ""

Write-Host "`nAlternatively, run automated FTP upload:" -ForegroundColor Yellow
Write-Host ".\deploy_via_ftp.ps1" -ForegroundColor Gray

Write-Host "`n========================================" -ForegroundColor Cyan
