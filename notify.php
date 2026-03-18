<?php
// ============================================================
//  notify.php  –  Send SMS via Twilio + email via SendGrid
// ============================================================
require_once __DIR__ . '/config.php';

/**
 * Replace placeholders in the configured message template.
 */
function build_message(string $visitor, string $employee): string {
    return str_replace(
        ['{visitor}', '{employee}'],
        [$visitor,    $employee],
        NOTIFY_MESSAGE
    );
}

/**
 * Send an SMS via Twilio REST API.
 * Returns ['ok' => bool, 'error' => string].
 */
function send_sms(string $to, string $body): array {
    if (empty($to)) return ['ok' => false, 'error' => 'No phone number'];

    $url  = 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_ACCOUNT_SID . '/Messages.json';
    $data = http_build_query(['To' => $to, 'From' => TWILIO_FROM_NUMBER, 'Body' => $body]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($response, true);
    if ($httpCode >= 200 && $httpCode < 300) {
        return ['ok' => true, 'error' => ''];
    }
    return ['ok' => false, 'error' => $json['message'] ?? 'Twilio error ' . $httpCode];
}

/**
 * Send an email via SendGrid v3 API.
 * Returns ['ok' => bool, 'error' => string].
 */
function send_email(string $to, string $subject, string $body): array {
    if (empty($to)) return ['ok' => false, 'error' => 'No email address'];

    $payload = json_encode([
        'personalizations' => [['to' => [['email' => $to]]]],
        'from'             => ['email' => EMAIL_FROM_ADDRESS, 'name' => EMAIL_FROM_NAME],
        'subject'          => $subject,
        'content'          => [['type' => 'text/plain', 'value' => $body]],
    ]);

    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . SENDGRID_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        return ['ok' => true, 'error' => ''];
    }
    $json = json_decode($response, true);
    $msg  = $json['errors'][0]['message'] ?? 'SendGrid error ' . $httpCode;
    return ['ok' => false, 'error' => $msg];
}

/**
 * Notify a single employee by both SMS and email.
 * Returns an array of result messages.
 */
function notify_employee(array $employee, string $visitor_name): array {
    $msg     = build_message($visitor_name, $employee['name']);
    $results = [];

    $sms = send_sms($employee['phone'], $msg);
    $results[] = 'SMS: '   . ($sms['ok']   ? 'sent' : 'FAILED – ' . $sms['error']);

    $mail = send_email($employee['email'], EMAIL_SUBJECT, $msg);
    $results[] = 'Email: ' . ($mail['ok'] ? 'sent' : 'FAILED – ' . $mail['error']);

    return $results;
}
