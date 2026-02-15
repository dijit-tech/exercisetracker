<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

require_once '../includes/session.php';
require_once '../includes/challenges.php';
require_once '../includes/mail.php'; // Mail support

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);
if ($input === null) {
    $input = $_POST;
}

$userId = $_SESSION['user_id'];
$challengeId = $input['challenge_id'] ?? $input['room_id'] ?? 0;
$content = $input['content'] ?? '';

if (!$challengeId || empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Challenge ID and content required']);
    exit;
}

if (!isChallengeMember($challengeId, $userId)) {
    echo json_encode(['success' => false, 'error' => 'Not a member of this challenge']);
    exit;
}

$postId = createChallengePost($challengeId, $userId, $content, 'message');

if ($postId) {
    // Send email notifications to other members
    $challenge = getChallengeById($challengeId);
    if ($challenge) {
        $members = getChallengeMembers($challengeId);
        $recipients = [];
        
        foreach ($members as $member) {
            // Skip the sender and users without email
            if ($member['user_id'] != $userId && !empty($member['email'])) {
                $recipients[] = $member['email'];
            }
        }
        
        if (!empty($recipients)) {
            $senderName = $_SESSION['username'] ?? 'A member';
            $challengeName = $challenge['name'];
            $appUrl = defined('APP_URL') ? APP_URL : 'https://goaltracker.dijit.tech';
            $link = $appUrl . "/challenge.php?id=" . $challengeId;
            
            $subject = "[Goal Tracker] New post in '$challengeName'";
            $body = "
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 8px;'>
                    <h3 style='color: #667eea; margin-top: 0;'>New Activity</h3>
                    <p><strong>$senderName</strong> posted a message in the challenge <strong>$challengeName</strong>:</p>
                    <blockquote style='background: #f9f9f9; border-left: 4px solid #667eea; padding: 15px; margin: 15px 0; color: #555; font-style: italic;'>
                        " . nl2br(htmlspecialchars($content)) . "
                    </blockquote>
                    <p style='text-align: center; margin-top: 25px;'>
                        <a href='$link' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>View Post</a>
                    </p>
                    <p style='font-size: 0.85em; color: #999; text-align: center; margin-top: 30px;'>
                        <a href='$link' style='color: #999; text-decoration: underline;'>Reply on Goal Tracker</a>
                    </p>
                </div>
            ";
            
            // Send to first recipient, BCC the rest
            $to = array_shift($recipients);
            sendEmail($to, $subject, $body, $recipients);
        }
    }

    echo json_encode(['success' => true, 'post_id' => $postId]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to create post']);
}
