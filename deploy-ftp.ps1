# PowerShell FTP Upload Script
$ftpHost = "ftp.lonkar.in"
$ftpUser = "dijittechadmin"
$ftpPass = "Just4DijitTechApps!"
$ftpPath = "/apps/goaltracker"
$localPath = "C:\code\goaltracker\public"

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "Goal Tracker - FTP Deployment Script" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Create FTP credentials
$ftpCredentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

# Function to upload a file via FTP
function Upload-FtpFile {
    param(
        [string]$LocalFile,
        [string]$RemotePath
    )
    
    try {
        $uri = "ftp://$ftpHost$RemotePath"
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Credentials = $ftpCredentials
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.UseBinary = $true
        $request.KeepAlive = $false
        
        $fileContent = [System.IO.File]::ReadAllBytes($LocalFile)
        $request.ContentLength = $fileContent.Length
        
        $requestStream = $request.GetRequestStream()
        $requestStream.Write($fileContent, 0, $fileContent.Length)
        $requestStream.Close()
        
        $response = $request.GetResponse()
        $response.Close()
        
        return $true
    }
    catch {
        Write-Host "Error uploading $RemotePath : $_" -ForegroundColor Red
        return $false
    }
}

# Function to create FTP directory
function Create-FtpDirectory {
    param([string]$RemotePath)
    
    try {
        $uri = "ftp://$ftpHost$RemotePath"
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Credentials = $ftpCredentials
        $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $request.KeepAlive = $false
        
        $response = $request.GetResponse()
        $response.Close()
        return $true
    }
    catch {
        # Directory might already exist, that's ok
        return $false
    }
}

# Get all files
$files = Get-ChildItem -Path $localPath -Recurse -File | Where-Object { 
    $_.FullName -notmatch '\\sessions\\' -and 
    $_.Name -notmatch '^test_' -and
    $_.Name -ne 'phpinfo.php' -and
    $_.Name -ne 'debug.php'
}

Write-Host "Found $($files.Count) files to upload" -ForegroundColor Green
Write-Host ""

# Create directory structure
$directories = $files | ForEach-Object {
    $relativePath = $_.DirectoryName.Replace($localPath, "").Replace("\", "/")
    if ($relativePath) { $ftpPath + $relativePath }
} | Select-Object -Unique

Write-Host "Creating directory structure..." -ForegroundColor Yellow
foreach ($dir in $directories) {
    Create-FtpDirectory -RemotePath $dir | Out-Null
}

# Upload files
$uploaded = 0
$failed = 0

Write-Host "Uploading files..." -ForegroundColor Yellow
Write-Host ""

foreach ($file in $files) {
    $relativePath = $file.FullName.Replace($localPath, "").Replace("\", "/")
    $remotePath = $ftpPath + $relativePath
    
    Write-Host "Uploading: $relativePath" -NoNewline
    
    if (Upload-FtpFile -LocalFile $file.FullName -RemotePath $remotePath) {
        Write-Host " [OK]" -ForegroundColor Green
        $uploaded++
    }
    else {
        Write-Host " [FAILED]" -ForegroundColor Red
        $failed++
    }
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "Upload Complete!" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "Uploaded: $uploaded files" -ForegroundColor Green
Write-Host "Failed: $failed files" -ForegroundColor Red
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Update .env file on server with database credentials"
Write-Host "2. Verify database tables are created"
Write-Host "3. Test login at: http://goaltracker.lonkar.in"
Write-Host "4. Login with: admin / password"
Write-Host "5. Change passwords immediately!"
Write-Host ""
