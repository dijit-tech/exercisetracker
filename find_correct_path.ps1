# Move files to subdomain root
# This uploads to the likely correct path for a subdomain

$ftpServer = "ftp://ftp.lonkar.in"
$ftpUsername = "dijittechadmin"
$ftpPassword = "Just4DijitTechApps!"
$localPath = "c:\code\exercisetracker_fresh_20260105"

Write-Host "Trying alternative paths..." -ForegroundColor Yellow

# Try uploading to /exercisetracker.dijit.tech/ (common subdomain path)
$altPath = "/exercisetracker.dijit.tech"

Write-Host "`nAttempting to upload to: $altPath" -ForegroundColor Cyan

try {
    $webclient = New-Object System.Net.WebClient
    $webclient.Credentials = New-Object System.Net.NetworkCredential($ftpUsername, $ftpPassword)
    
    # Upload index.php to test
    $uri = "$ftpServer$altPath/index.php"
    $webclient.UploadFile($uri, "$localPath\public\index.php")
    
    Write-Host "Success! Files should go to: $altPath" -ForegroundColor Green
    Write-Host "Now upload all files to this location" -ForegroundColor Yellow
}
catch {
    Write-Host "Path $altPath doesn't work: $_" -ForegroundColor Red
    
    Write-Host "`nPlease check iPage control panel:" -ForegroundColor Yellow
    Write-Host "1. Go to Domains > Subdomains" -ForegroundColor White
    Write-Host "2. Check where 'exercisetracker.dijit.tech' points" -ForegroundColor White
    Write-Host "3. Update document root or upload files to that location" -ForegroundColor White
}
