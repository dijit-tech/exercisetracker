param($Path)
$ftpServer = "ftp://ftp.lonkar.in"
$ftpUser = "dijittechadmin"
$ftpPass = "Just4DijitTechApps!"
$uri = "$ftpServer$Path/"
$req = [System.Net.FtpWebRequest]::Create($uri)
$req.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
$req.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectoryDetails
try {
    $resp = $req.GetResponse()
    $reader = New-Object System.IO.StreamReader($resp.GetResponseStream())
    echo $reader.ReadToEnd()
    $reader.Close()
    $resp.Close()
} catch {
    echo "Error: $($_.Exception.Message)"
}
