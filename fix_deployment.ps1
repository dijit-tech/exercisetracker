# Fix Deployment Script
$ftpServer = "ftp://ftp.lonkar.in"
$ftpUser = "dijittechadmin"
$ftpPass = "Just4DijitTechApps!"
$basePath = "/apps/goaltracker"
$localPath = "c:\code\goaltracker"

function Upload-FileToFTP {
    param($LocalFile, $RemoteFile)
    try {
        if (-not (Test-Path $LocalFile)) { return $false }
        $uri = "$ftpServer$RemoteFile"
        $wc = New-Object System.Net.WebClient
        $wc.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $wc.UploadFile($uri, $LocalFile)
        Write-Host "  ✓ $RemoteFile" -ForegroundColor Green
        return $true
    } catch {
        Write-Host "  ✗ $RemoteFile - $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

function Create-FTPDir {
    param($RemoteDir)
    try {
        $uri = "$ftpServer$RemoteDir"
        $req = [System.Net.FtpWebRequest]::Create($uri)
        $req.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $req.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $resp = $req.GetResponse()
        $resp.Close()
    } catch {}
}

Write-Host "Fixing Deployment..." -ForegroundColor Cyan

# 1. Upload Configuration
Write-Host "`n1. Uploading Configuration..." -ForegroundColor Yellow
Create-FTPDir "$basePath/config"
Upload-FileToFTP "$localPath\config\database.php" "$basePath/config/database.php"
Create-FTPDir "$basePath/sessions"
Write-Host "  ✓ Created sessions directory" -ForegroundColor Green


# 2. Fix and Upload Root .htaccess
Write-Host "`n2. Fixing Root .htaccess..." -ForegroundColor Yellow
$content = Get-Content "$localPath\.htaccess" -Raw
$content = $content.Replace("Options -Indexes", "#Options -Indexes")
$content = $content.Replace("Order allow,deny", "#Order allow,deny")
$content = $content.Replace("Deny from all", "#Deny from all")
Set-Content "$localPath\.htaccess_fixed" $content
Upload-FileToFTP "$localPath\.htaccess_fixed" "$basePath/.htaccess"
Remove-Item "$localPath\.htaccess_fixed"

# 3. Fix and Upload Public .htaccess
Write-Host "`n3. Fixing Public .htaccess..." -ForegroundColor Yellow
$content = Get-Content "$localPath\public\.htaccess" -Raw
$content = $content.Replace("Options -Indexes", "#Options -Indexes")
Set-Content "$localPath\public\.htaccess_fixed" $content
Upload-FileToFTP "$localPath\public\.htaccess_fixed" "$basePath/public/.htaccess"
Remove-Item "$localPath\public\.htaccess_fixed"

Write-Host "`n4. Restoring public/index.php production settings..." -ForegroundColor Yellow
# Restore error reporting to 0 (production safe) but maybe keep it 1 for a moment if user still sees blank page?
# User complained about blank page. Let's keep it 1 for now to debug.
# I will upload the local public/index.php which I modified to have display_errors=1 earlier.
Upload-FileToFTP "$localPath\public\index.php" "$basePath/public/index.php"

Write-Host "`nDone." -ForegroundColor Green
