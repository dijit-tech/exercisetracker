<?php
require_once 'config.php';

/**
 * Send email using Microsoft Graph API
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $htmlBody Email body (HTML)
 * @param array $bcc Array of BCC email addresses (optional)
 * @return array ['success' => bool, 'error' => string|null]
 */
function sendEmail($to, $subject, $htmlBody, $bcc = []) {
    if (!defined('MAIL_CLIENT_ID') || !defined('MAIL_CLIENT_SECRET')) {
        return ['success' => false, 'error' => 'Mail configuration missing'];
    }

    $tokenResult = getGraphAccessToken();
    if (!$tokenResult['success']) {
        return ['success' => false, 'error' => 'Auth failed: ' . $tokenResult['error']];
    }
    $accessToken = $tokenResult['token'];

    $url = 'https://graph.microsoft.com/v1.0/users/' . MAIL_FROM_ADDRESS . '/sendMail';

    // Format TO recipients
    $toRecipients = [
        [
             'emailAddress' => [
                 'address' => $to
             ]
        ]
    ];

    // Format BCC recipients
    $bccRecipients = [];
    foreach ($bcc as $bccEmail) {
        if (filter_var($bccEmail, FILTER_VALIDATE_EMAIL)) {
            $bccRecipients[] = [
                'emailAddress' => [
                    'address' => $bccEmail
                ]
            ];
        }
    }

    $message = [
        'subject' => $subject,
        'body' => [
            'contentType' => 'HTML',
            'content' => $htmlBody
        ],
        'toRecipients' => $toRecipients,
        'from' => [
            'emailAddress' => [
                'address' => MAIL_FROM_ADDRESS,
                'name' => defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Goal Tracker'
            ]
        ]
    ];

    if (!empty($bccRecipients)) {
        $message['bccRecipients'] = $bccRecipients;
    }

    $emailData = [
        'message' => $message,
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
        return ['success' => true, 'error' => null];
    } else {
        // Parse error response
        $responseObj = json_decode($response, true);
        $errorMsg = $responseObj['error']['message'] ?? ($curlError ?: "HTTP $httpCode: $response");
        error_log("Graph Mail Error: $errorMsg");
        return ['success' => false, 'error' => $errorMsg];
    }
}

/**
 * Get Access Token for MS Graph
 */
function getGraphAccessToken() {
    // Basic session caching to avoid hitting token endpoint on every call within same session
    if (session_status() === PHP_SESSION_NONE) {
        // If no session, we can't cache effectively in memory easily without a static, 
        // but typically this is called in context of a logged user.
        // For CLI or Cron, we fetch every time.
    } elseif (isset($_SESSION['graph_access_token']) && isset($_SESSION['graph_token_expires']) && $_SESSION['graph_token_expires'] > time()) {
        return ['success' => true, 'token' => $_SESSION['graph_access_token'], 'error' => null];
    }

    $url = 'https://login.microsoftonline.com/' . MAIL_TENANT_ID . '/oauth2/v2.0/token';
    
    $postData = [
        'client_id' => MAIL_CLIENT_ID,
        'client_secret' => MAIL_CLIENT_SECRET,
        'scope' => 'https://graph.microsoft.com/.default',
        'grant_type' => 'client_credentials'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode === 200 && isset($data['access_token'])) {
        // Cache if session exists
        if (session_status() !== PHP_SESSION_NONE) {
            $_SESSION['graph_access_token'] = $data['access_token'];
            $_SESSION['graph_token_expires'] = time() + $data['expires_in'] - 60; // Buffer
        }
        return ['success' => true, 'token' => $data['access_token'], 'error' => null];
    } else {
        $errorMsg = $data['error_description'] ?? 'Failed to retrieve access token';
        error_log("Graph Auth Error: $errorMsg");
        return ['success' => false, 'error' => $errorMsg];
    }
}
?>