<?php
require_once 'db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['token']) || empty($data['signature'])) {
    die(json_encode(['success' => false, 'message' => 'Invalid data']));
}

// Verify token again
$stmt = $pdo->prepare("SELECT receipt_id FROM receipts WHERE signature_token = ?");
$stmt->execute([$data['token']]);
$receipt = $stmt->fetch();

if (!$receipt) {
    die(json_encode(['success' => false, 'message' => 'Invalid token']));
}

// Save signature
$imageData = str_replace('data:image/png;base64,', '', $data['signature']);
$imageData = base64_decode($imageData);
$filename = 'signatures/' . uniqid() . '.png';
file_put_contents($filename, $imageData);

// Update database
$update = $pdo->prepare("UPDATE receipts SET 
    signature_image = ?,
    is_signed = 1,
    signed_at = NOW(),
    signature_token = NULL
    WHERE receipt_id = ?");
$update->execute([$filename, $receipt['receipt_id']]);

echo json_encode(['success' => true]);