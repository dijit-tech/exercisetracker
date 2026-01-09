# Debug Script - Rename .htaccess files to disable them
$ftpServer = "ftp://ftp.lonkar.in"
$ftpUser = "dijittechadmin"
$ftpPass = "Just4DijitTechApps!"
$basePath = "/apps/goaltracker"

function Rename-FtpFile {
    param($Path, $NewName)
    try {
        $uri = "$ftpServer$Path"
        $req = [System.Net.FtpWebRequest]::Create($uri)
        $req.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $req.Method = [System.Net.WebRequestMethods+Ftp]::Rename
        $req.RenameTo = $NewName
        $resp = $req.GetResponse()
        $resp.Close()
        Write-Host "Renamed $Path to $NewName" -ForegroundColor Green
    } catch {
        Write-Host "Failed to rename $Path: $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host "Disabling .htaccess files to debug 500 Error..."
Rename-FtpFile "$basePath/.htaccess" ".htaccess.bak"
Rename-FtpFile "$basePath/public/.htaccess" ".htaccess.bak"

Write-Host "Done."
