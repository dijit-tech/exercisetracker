# Goal Tracker - FTP Upload Script
param([switch]$DryRun = $false)

$ftpServer = "ftp://ftp.lonkar.in"
$ftpUser = "dijittechadmin"
$ftpPass = "Just4DijitTechApps!"
$basePath = "/apps/goaltracker"
$localPath = "c:\code\goaltracker"

$script:uploadCount = 0
$script:failCount = 0

function Upload-FileToFTP {
    param([string]$LocalFile, [string]$RemoteFile)
    
    try {
        if (-not (Test-Path $LocalFile)) {
            return $false
        }
        
        if ($DryRun) {
            Write-Host "  [DRY] $RemoteFile" -ForegroundColor Yellow
            return $true
        }
        
        $uri = "$ftpServer$RemoteFile"
        $webclient = New-Object System.Net.WebClient
        $webclient.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $webclient.UploadFile($uri, $LocalFile)
        
        Write-Host "  ✓ $RemoteFile" -ForegroundColor Green
        return $true
    }
    catch {
        Write-Host "  ✗ $RemoteFile - $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

function Create-FTPDir {
    param([string]$RemoteDir)
    
    try {
        if ($DryRun) { return $true }
        
        $uri = "$ftpServer$RemoteDir"
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $response = $request.GetResponse()
        $response.Close()
        return $true
    }
    catch {
        return $true
    }
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Goal Tracker - FTP Upload" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Create directories
Write-Host "Creating directories..." -ForegroundColor Yellow
Create-FTPDir "$basePath/public" | Out-Null
Create-FTPDir "$basePath/public/api" | Out-Null
Create-FTPDir "$basePath/public/includes" | Out-Null
Create-FTPDir "$basePath/database" | Out-Null

# Upload root files
Write-Host "`nUploading root files..." -ForegroundColor Yellow
@("index.php", ".htaccess", ".env") | ForEach-Object {
    $file = Join-Path $localPath $_
    if (Upload-FileToFTP $file "$basePath/$_") { $script:uploadCount++ } else { $script:failCount++ }
}

# Upload public folder
Write-Host "`nUploading public folder..." -ForegroundColor Yellow
$publicFiles = Get-ChildItem "$localPath\public" -Recurse -File
foreach ($file in $publicFiles) {
    $relPath = $file.FullName.Substring("$localPath\public".Length).TrimStart("\").Replace("\", "/")
    if (Upload-FileToFTP $file.FullName "$basePath/public/$relPath") { $script:uploadCount++ } else { $script:failCount++ }
}

# Upload database folder
Write-Host "`nUploading database folder..." -ForegroundColor Yellow
$dbFiles = Get-ChildItem "$localPath\database" -Recurse -File -ErrorAction SilentlyContinue
foreach ($file in $dbFiles) {
    $relPath = $file.FullName.Substring("$localPath\database".Length).TrimStart("\").Replace("\", "/")
    if (Upload-FileToFTP $file.FullName "$basePath/database/$relPath") { $script:uploadCount++ } else { $script:failCount++ }
}

# Summary
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "Upload Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Uploaded: $uploadCount files" -ForegroundColor Green
Write-Host "Failed: $failCount files" -ForegroundColor $(if ($failCount -eq 0) { "Green" } else { "Red" })

if ($failCount -eq 0) {
    Write-Host "`nNext steps:" -ForegroundColor Yellow
    Write-Host "1. Visit: http://goaltracker.dijit.tech" -ForegroundColor White
    Write-Host "2. Login: admin / password" -ForegroundColor White
    Write-Host "3. Change passwords" -ForegroundColor White
}
