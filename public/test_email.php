<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';
require_once 'includes/mail.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = $_POST['email'];
    // Allow overriding FROM address for testing
    $fromOverride = !empty($_POST['from_email']) ? $_POST['from_email'] : MAIL_FROM_ADDRESS;
    
    // Create a temporary version of sendEmail logic for this test to support override
    // or just assume standard sendEmail uses the constant. 
    // Let's modify the call to passing from address if possible, but sendEmail function relies on constant.
    // For this debug tool, I'll inline the logic to allow FROM override.
    
    if (!defined('MAIL_CLIENT_ID')) {
         $result = ['success' => false, 'error' => 'Config missing'];
    } else {
        $tokenResult = getGraphAccessToken();
        if (!$tokenResult['success']) {
            $result = ['success' => false, 'error' => 'Auth failed: ' . $tokenResult['error']];
        } else {
            $accessToken = $tokenResult['token'];
            $url = 'https://graph.microsoft.com/v1.0/users/' . $fromOverride . '/sendMail';
            
            $emailData = [
                'message' => [
                    'subject' => "Goal Tracker Test (From $fromOverride)",
                    'body' => [
                        'contentType' => 'HTML',
                        'content' => "Test sent via Graph API from $fromOverride"
                    ],
                    'toRecipients' => [['emailAddress' => ['address' => $to]]]
                ],
                'saveToSentItems' => 'true'
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode === 202) {
                $result = ['success' => true];
            } else {
                $responseObj = json_decode($response, true);
                $errorMsg = $responseObj['error']['message'] ?? ($curlError ?: "HTTP $httpCode: $response");
                $result = ['success' => false, 'error' => $errorMsg];
            }
        }
    }

    if ($result['success']) {
        $message = '<div class="alert alert-success">Email sent successfully from ' . htmlspecialchars($fromOverride) . '!</div>';
    } else {
        $message = '<div class="alert alert-danger">Failed to send from ' . htmlspecialchars($fromOverride) . ': ' . htmlspecialchars($result['error']) . '</div>';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Email - Goal Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h1>Test Email Configuration</h1>
    <p>Default From: <strong><?php echo defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'Not Configured'; ?></strong></p>
    
    <?php echo $message; ?>
    
    <form method="post" class="card p-4 bg-light">
        <div class="mb-3">
            <label class="form-label">Recipient Email (To)</label>
            <input type="email" name="email" class="form-control" required placeholder="Enter destination email">
        </div>
        <div class="mb-3">
             <label class="form-label">Sender Email (From) - Override for testing</label>
             <input type="email" name="from_email" class="form-control" placeholder="Leave empty to use <?php echo defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : ''; ?>" value="<?php echo isset($_POST['from_email']) ? htmlspecialchars($_POST['from_email']) : ''; ?>">
             <small class="text-muted">Must be a valid mailbox in your Microsoft 365 Tenant.</small>
        </div>
        <button type="submit" class="btn btn-primary">Send Test Email</button>
    </form>

    <div class="mt-4">
        <h5>Debug Info:</h5>
        <ul>
            <li>Tenant ID Configured: <?php echo defined('MAIL_TENANT_ID') ? 'Yes' : 'No'; ?></li>
            <li>Client ID Configured: <?php echo defined('MAIL_CLIENT_ID') ? 'Yes' : 'No'; ?></li>
            <li>Client Secret Configured: <?php echo defined('MAIL_CLIENT_SECRET') ? 'Yes' : 'No'; ?></li>
        </ul>
    </div>
</body>
</html>
