# Email Functionality Implementation Plan

## 1. Overview
The goal is to add transactional email capability to Goal Tracker to support user engagement features like challenge invites, goal reminders, and streak notifications.

## 2. Technical Approach
We will use PHP's native `mail()` function solely for the MVP phase, with an architecture that allows easily swapping in a robust SMTP library (like PHPMailer or Symfony Mailer) later.

## 3. Implementation Steps

### Phase 1: Core Mail Infrastructure
1.  **Create `public/includes/mail.php`**
    *   Define a wrapper function `sendEmail($to, $subject, $htmlBody)`.
    *   Configure default headers (From, Reply-To, Content-Type: HTML).
    *   Handle centralized "From" address configuration.

2.  **Configuration Update**
    *   Add `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME` to `config.php`.
    *   Ensure production config (`config_prod.php`) has valid sender details to avoid spam filters.

### Phase 2: Challenge Invites (High Priority)
1.  **Update `createChallengeInvite` in `includes/challenges.php`**
    *   Trigger `sendEmail` upon successful database insertion of an invite.
    *   Generate a secure link: `https://goaltracker.../challenges.php?action=accept&invite_code=...`.
2.  **Email Template**
    *   Design a simple HTML template for invites.
    *   Include: Inviter Name, Challenge Name, "Join Now" button.

### Phase 3: Notifications (Future)
1.  **Streak Reminders**: Cron job to check users who haven't logged today.
2.  **Weekly Summary**: Sunday email with stats.

## 4. Security & Deliverability Considerations
*   **Spam**: Sending essentially from a shared hosting webserver IP often leads to spam folders.
    *   *Mitigation*: Use a valid "From" address that matches the domain (e.g., `no-reply@goaltracker.dijit.tech`).
    *   *Recommended*: Switch to SMTP (Postmark/SendGrid) ASAP.
*   **Input Sanitation**: Ensure user content (like challenge description) is sanitized before being embedded in email HTML to prevent injection.

## 5. Testing Plan
1.  **Local Dev**: Use MailHog or configure local PHP to write emails to a text file for inspection.
2.  **Production**: Verify headers and HTML rendering in Gmail/Outlook.
