<?php
// DEBUG SETTINGS (Remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
if (session_status() === PHP_SESSION_NONE) session_start();

// Base URL
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'];

// CSRF Token Setup
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}

// Database connection
include('../../config/config.php');

// Initialize variables
$receiptData = null;
$token = '';
$error = '';
$success = '';
$debug = "Checking database for tokens with is_signed = 0\n";

// Check if the database has any tokens with is_signed = 0
$stmt = $mysqli->prepare("SELECT * FROM acknowledgment_receipt WHERE is_signed = 0 LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $receiptData = $result->fetch_assoc();
    // If the record has a token, use it
    if (!empty($receiptData['signature_token'])) {
        $token = $receiptData['signature_token'];
        $debug .= "Found token: $token\n";
    } 
    // If no token but is_signed = 0, generate one
    else {
        $token = bin2hex(random_bytes(16)); // Generate a new token
        $debug .= "No token found, generated new token: $token\n";
        
        // Update the receipt with the new token
        $update_stmt = $mysqli->prepare("UPDATE acknowledgment_receipt SET signature_token = ? WHERE Receipt_Id = ? AND is_signed = 0");
        $update_stmt->bind_param("ss", $token, $receiptData['Receipt_Id']);
        if ($update_stmt->execute()) {
            $debug .= "Updated record with new token\n";
            $receiptData['signature_token'] = $token;
        } else {
            $debug .= "Failed to update record with new token: " . $mysqli->error . "\n";
            $error = "Failed to generate token.";
        }
    }
} else {
    $debug .= "No receipts found with is_signed = 0\n";
    $error = "No receipts available for signing.";
}

// Process signature submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sign_receipt'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Session expired. Please refresh the page.";
    } else {
        $submitted_token = $_POST['token'];
        $receipt_id = $_POST['receipt_id'];
        
        // Create simple signature image (text-based)
        $name = $_POST['customer_name'];
        $date = date('Y-m-d H:i:s');
        $signatureData = "data:image/svg+xml;base64," . base64_encode("<svg xmlns='http://www.w3.org/2000/svg' width='300' height='100'><text x='10' y='50' font-family='cursive' font-size='24' fill='blue'>{$name}</text><text x='10' y='80' font-family='sans-serif' font-size='12' fill='gray'>Signed: {$date}</text></svg>");
        
        // Update receipt
        $update_stmt = $mysqli->prepare("UPDATE acknowledgment_receipt SET is_signed = 1, signature_image = ?, signature_date = NOW() WHERE Receipt_Id = ?");
        $update_stmt->bind_param("ss", $signatureData, $receipt_id);
        
        if ($update_stmt->execute()) {
            // DISPLAY SUCCESS MESSAGE INSTEAD OF REDIRECTING
            $success = "Receipt #{$receipt_id} has been successfully signed!";
        } else {
            $error = "Failed to save signature: " . $mysqli->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Sign Acknowledgment Receipt</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .receipt-container { padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { text-align: center; color: #1F5497; margin-bottom: 20px; }
        .receipt-summary { background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .receipt-detail { margin: 5px 0; }
        .highlight { font-weight: bold; color: #1F5497; }
        .button { padding: 12px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; margin: 10px 0; border: none; }
        .primary { background: #1F5497; color: white; }
        .secondary { background: #eee; }
        .full-width { width: 100%; }
        .success-message { padding: 15px; background-color: #e8f5e9; border-left: 4px solid #4caf50; margin: 20px 0; }
        .error-message { padding: 15px; background-color: #ffebee; border-left: 4px solid #f44336; margin: 20px 0; }
        .signature-box { text-align: center; padding: 20px; background: #f5f5f5; border-radius: 5px; margin: 20px 0; }
        .token-display { padding: 10px; background: #f0f0f0; font-family: monospace; border-radius: 4px; margin: 10px 0; word-break: break-all; }
        .debug-info { font-family: monospace; background: #f5f5f5; padding: 10px; border: 1px solid #ccc; white-space: pre-wrap; margin-top: 20px; font-size: 12px; }
    </style>
</head>
<body>

<div class="receipt-container">
    <div class="header">
        <h2>Quick Sign Acknowledgment Receipt</h2>
    </div>
    
    <?php if ($success): ?>
        <div class="success-message">
            <h3>Thank You!</h3>
            <p><?= htmlspecialchars($success) ?></p>
            <p>Your receipt has been successfully signed and recorded in our system.</p>
            <p><a href="<?= $baseUrl ?>/stafifycmdorig/afis" class="button primary full-width">Return to Home</a></p>
        </div>
    <?php elseif ($error): ?>
        <div class="error-message">
            <h3>Error</h3>
            <p><?= htmlspecialchars($error) ?></p>
            <p><a href="<?= $baseUrl ?>/stafifycmdorig/afis" class="button secondary full-width">Return Home</a></p>
            <div class="debug-info"><?= htmlspecialchars($debug) ?></div>
        </div>
    <?php elseif ($receiptData): ?>
        <div class="receipt-summary">
            <div class="receipt-detail"><strong>Receipt #:</strong> <?= htmlspecialchars($receiptData['Receipt_Id']) ?></div>
            <div class="receipt-detail"><strong>Customer:</strong> <?= htmlspecialchars($receiptData['Customer_Name']) ?></div>
            <div class="receipt-detail"><strong>Amount:</strong> ₱<?= number_format($receiptData['Amount'] ?? 0, 2) ?></div>
            <div class="receipt-detail"><strong>Date:</strong> <?= date("F j, Y", strtotime($receiptData['created_at'] ?? "now")) ?></div>
            <div class="receipt-detail"><strong>Purpose:</strong> <?= htmlspecialchars($receiptData['Payment_For'] ?? $receiptData['items_received'] ?? 'N/A') ?></div>
            <div class="receipt-detail"><strong>Token:</strong> <div class="token-display"><?= htmlspecialchars($token) ?></div></div>
        </div>
        
        <div class="signature-box">
            <h3>Quick Signature Confirmation</h3>
            <p>By clicking "Sign Now", you confirm that you, <span class="highlight"><?= htmlspecialchars($receiptData['Customer_Name']) ?></span>, 
            acknowledge receipt of the items or payment detailed above.</p>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <input type="hidden" name="receipt_id" value="<?= htmlspecialchars($receiptData['Receipt_Id']) ?>">
                <input type="hidden" name="customer_name" value="<?= htmlspecialchars($receiptData['Customer_Name']) ?>">
                <button type="submit" name="sign_receipt" class="button primary full-width">Sign Now</button>
            </form>
            
            <p>If you prefer to draw your signature, <a href="<?= $baseUrl ?>/stafifycmdorig/afis/sign_receipt.php">click here</a>.</p>
        </div>
        
        <div class="debug-info"><?= htmlspecialchars($debug) ?></div>
    <?php endif; ?>
</div>

</body>
</html>