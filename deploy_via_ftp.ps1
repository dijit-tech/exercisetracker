# Automated FTP Upload Script
# Uses native .NET FTP client

param(
    [switch]$DryRun = $false
)

$ftpServer = "ftp://ftp.lonkar.in"
$ftpUsername = "dijittechadmin"
$ftpPassword = "Just4DijitTechApps!"
$ftpBasePath = "/apps/exercisetracker"
$localPath = "c:\code\exercisetracker_fresh_20260105"

function Upload-File {
    param(
        [string]$LocalFile,
        [string]$RemotePath
    )
    
    try {
        $uri = "$ftpServer$RemotePath"
        Write-Host "Uploading: $LocalFile -> $RemotePath" -ForegroundColor Gray
        
        if ($DryRun) {
            Write-Host "  [DRY RUN] Would upload file" -ForegroundColor Yellow
            return $true
        }
        
        $webclient = New-Object System.Net.WebClient
        $webclient.Credentials = New-Object System.Net.NetworkCredential($ftpUsername, $ftpPassword)
        $webclient.UploadFile($uri, $LocalFile)
        
        Write-Host "  Uploaded" -ForegroundColor Green
        return $true
    }
    catch {
        Write-Host "  Failed: $_" -ForegroundColor Red
        return $false
    }
}

function Create-FtpDirectory {
    param([string]$RemotePath)
    
    try {
        $uri = "$ftpServer$RemotePath"
        
        if ($DryRun) {
            Write-Host "  [DRY RUN] Would create directory: $RemotePath" -ForegroundColor Yellow
            return $true
        }
        
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Credentials = New-Object System.Net.NetworkCredential($ftpUsername, $ftpPassword)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $response = $request.GetResponse()
        $response.Close()
        return $true
    }
    catch {
        # Directory might already exist
        return $true
    }
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "FTP Upload to Production" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

if ($DryRun) {
    Write-Host "[DRY RUN MODE - No files will be uploaded]" -ForegroundColor Yellow
}

# Ensure production config is active
Write-Host "`nStep 1: Activating production config..." -ForegroundColor Yellow
Copy-Item "$localPath\config\database_production.php" "$localPath\config\database.php" -Force
Write-Host "Production config active" -ForegroundColor Green

# Create directories
Write-Host "`nStep 2: Creating remote directories..." -ForegroundColor Yellow
Create-FtpDirectory "$ftpBasePath/config"
Create-FtpDirectory "$ftpBasePath/includes"
Create-FtpDirectory "$ftpBasePath/api"
Write-Host "Directories created" -ForegroundColor Green

# Upload public files
Write-Host "`nStep 3: Uploading public files..." -ForegroundColor Yellow
Get-ChildItem "$localPath\public" -File | ForEach-Object {
    Upload-File $_.FullName "$ftpBasePath/$($_.Name)"
}

# Upload includes
Write-Host "`nStep 4: Uploading includes..." -ForegroundColor Yellow
Get-ChildItem "$localPath\public\includes" -File | ForEach-Object {
    Upload-File $_.FullName "$ftpBasePath/includes/$($_.Name)"
}

# Upload API files
Write-Host "`nStep 5: Uploading API files..." -ForegroundColor Yellow
Get-ChildItem "$localPath\public\api" -File | ForEach-Object {
    Upload-File $_.FullName "$ftpBasePath/api/$($_.Name)"
}

# Upload config
Write-Host "`nStep 6: Uploading configuration..." -ForegroundColor Yellow
Upload-File "$localPath\config\database.php" "$ftpBasePath/config/database.php"

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "Upload Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Create database 'apps_exercisetracker' on ipage MySQL" -ForegroundColor White
Write-Host "2. Import database/init.sql via phpMyAdmin" -ForegroundColor White
Write-Host "3. Test at: https://exercisetracker.dijit.tech" -ForegroundColor White
Write-Host ""

# Restore local config
Write-Host "Restoring local config..." -ForegroundColor Yellow
Copy-Item "$localPath\config\database_local_backup.php" "$localPath\config\database.php" -Force
Write-Host "Local config restored" -ForegroundColor Green
