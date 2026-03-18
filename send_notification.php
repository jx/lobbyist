<?php
// ============================================================
//  send_notification.php  –  AJAX endpoint
//  POST: display_order (int), visitor_name (string)
//  Returns JSON { success: bool, message: string }
// ============================================================
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/notify.php';

$order        = (int)  ($_POST['display_order'] ?? 0);
$visitor_name = trim(  $_POST['visitor_name']   ?? '');

if ($order <= 0 || $visitor_name === '') {
    echo json_encode(['success' => false, 'message' => 'Missing required data.']);
    exit;
}

$employee = get_employee($order);
if (!$employee || !$employee['active']) {
    echo json_encode(['success' => false, 'message' => 'Employee not found.']);
    exit;
}

$results = notify_employee($employee, $visitor_name);

echo json_encode([
    'success' => true,
    'message' => $employee['name'] . ' has been notified.',
    'details' => $results,
]);
