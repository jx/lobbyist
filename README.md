# The Lobbyist - open source visitor notification panel

A simple web page suitable for tablets and phones where people tap, enter their name, and the recipient is notified by email and text instantly.

HTML, CSS, JS, PHP, SQLite. Self contained, no external database required.

## License

Copyright 2026 Grey Hodge
Shared under the GPL v2 license: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

## Requirements

PHP 8.2+ with pdo_sqlite and curl
Apache mod_rewrite for .htaccess security
alter.php should be able to write to lobby.sqlite and to create a folder called photos and read/write access.

## Files

config.php - configuration variables
db.php - SQLite helpers (auto-creates DB on first run)
notify.php - Twilio SMS + SendGrid email functions
send_notification.php - endpoint called by index.php
index.php - Main page
alter.php - Admin page
style.css - CSS
.htaccess - Apache security rules

## Setup

1. **Copy all files** to a single directory under your Apache web root.

2. **Create the photos directory** and make it writable:
   ```bash
   mkdir photos
   chmod 755 photos
   ```

3. **Edit `config.php`**:
   - Set `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_FROM_NUMBER`
   - Set `SENDGRID_API_KEY`, `EMAIL_FROM_ADDRESS`, `EMAIL_FROM_NAME`
   - Set `ADMIN_USER` and `ADMIN_PASS` for the admin page
   - Adjust `NOTIFY_MESSAGE` if desired (use `{visitor}` and `{employee}` placeholders)

4. **Visit `alter.php`** (you'll be prompted for the admin username/password) to add employees.

5. **Visit `index.php`** to see the visitor panel.

## Admin Usage

- Navigate to `alter.php` and log in.
- Click **+ Add New** to create an employee entry.
- **Display Order** this is a unique ID that's also being used for display order on the main page, not currently editable easily.
- Photos are stored in the `photos/` subdirectory and renamed automatically.

## Notification Flow

1. Visitor taps an employee card.
2. A dialog asks for the visitor's name.
3. On submit, `send_notification.php` sends the notifications, an SMS to the employee's cell number and an email.
4. The modal shows a confirmation for 10 seconds, then auto-dismisses.

## Notes

- The SQLite database and all PHP includes are blocked from direct access in `.htaccess`.
- The admin page is protected by HTTP Basic Authentication.
- For production, consider moving `config.php` and `lobby.sqlite` and updating `DB_PATH` accordingly.
- Use HTTPS in production so the admin password is not sent in cleartext.