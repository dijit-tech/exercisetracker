# Check what files exist on the server

$ftpServer = "ftp://ftp.lonkar.in"
$ftpUsername = "dijittechadmin"
$ftpPassword = "Just4DijitTechApps!"
$remotePath = "/apps/exercisetracker"

try {
    $request = [System.Net.FtpWebRequest]::Create("$ftpServer$remotePath/")
    $request.Credentials = New-Object System.Net.NetworkCredential($ftpUsername, $ftpPassword)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    
    $response = $request.GetResponse()
    $reader = New-Object System.IO.StreamReader($response.GetResponseStream())
    
    Write-Host "Files in $remotePath :" -ForegroundColor Cyan
    while (-not $reader.EndOfStream) {
        Write-Host "  - $($reader.ReadLine())" -ForegroundColor Gray
    }
    
    $reader.Close()
    $response.Close()
}
catch {
    Write-Host "Error: $_" -ForegroundColor Red
}
