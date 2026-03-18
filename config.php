<?php
// ============================================================
//  config.php  –  Lobby Visitor Notification App
//  All configuration variables live here.
// ============================================================

// --- Database --------------------------------------------------
define('DB_PATH', __DIR__ . '/lobby.sqlite');

// --- Notification message -------------------------------------
// Available placeholder: {visitor}  → visitor name
//                        {employee} → employee button text
define('NOTIFY_MESSAGE', '{visitor} is waiting in the lobby for you.');

// --- Twilio (SMS) ---------------------------------------------
define('TWILIO_ACCOUNT_SID', 'YOUR_TWILIO_ACCOUNT_SID');
define('TWILIO_AUTH_TOKEN',  'YOUR_TWILIO_AUTH_TOKEN');
define('TWILIO_FROM_NUMBER', '+10000000000');   // Your Twilio number

// --- SendGrid (Email) -----------------------------------------
define('SENDGRID_API_KEY',   'YOUR_SENDGRID_API_KEY');
define('EMAIL_FROM_ADDRESS', 'lobby@yourdomain.com');
define('EMAIL_FROM_NAME',    'Lobby Visitor System');
define('EMAIL_SUBJECT',      'Visitor waiting in the lobby');

// --- Admin page password (simple HTTP Basic Auth) -------------
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'changeme');

// --- Photo upload directory (relative to app root) ------------
define('PHOTO_DIR', __DIR__ . '/photos/');
define('PHOTO_URL', 'photos/');           // web-accessible path
