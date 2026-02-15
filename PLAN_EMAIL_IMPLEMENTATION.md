# Email Functionality Implementation Plan

## 1. Overview
The goal is to add transactional email capability to Goal Tracker to support user engagement features like challenge invites, goal reminders, and streak notifications.

## 2. Technical Approach
We are using **Microsoft Graph API** for secure, authenticated email delivery (replacing the original plan for `mail()`). Secure credentials (Client ID, Secret, Tenant ID) are stored in `config.php`.

## 3. Implementation Steps

### Phase 1: Core Mail Infrastructure (Completed)
1.  **Create `public/includes/mail.php`** - *Done*
    *   Implemented `sendEmail($to, $subject, $htmlBody)` using MS Graph API `users/{id}/sendMail`.
    *   Added `getGraphAccessToken()` with session caching.

2.  **Configuration Update** - *Done*
    *   Added `MAIL_TENANT_ID`, `MAIL_CLIENT_ID`, `MAIL_CLIENT_SECRET`, `MAIL_FROM_ADDRESS` to `config.php` and `config_prod.php`.

### Phase 2: Challenge Invites (Completed)
1.  **Update `createChallengeInvite` in `includes/challenges.php`** - *Done*
    *   Trigger `sendEmail` upon successful database insertion of an invite.
2.  **Email Template** - *Done*
    *   HTML template implemented directly in the function with "Join Now" button.


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
