<?php
// Electronic signature handler for petty cash vouchers

// DEBUG SETTINGS (Remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
if (session_status() === PHP_SESSION_NONE) session_start();

// CSRF Token Setup
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}

// Database connection
 include('../../config/config.php');
// Initialize variables
$voucher = null;
$token = '';
$error = '';
$success = '';
$debug = '';

// Check if token is provided
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $error = "Invalid signature link. Please contact the administrator.";
} else {
    $token = $conn->real_escape_string($_GET['token']);
    $debug .= "Looking for token: " . $token . "\n";

    // Find the voucher with this token
    $stmt = $conn->prepare("SELECT * FROM petty_cash_voucher WHERE signature_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    $debug .= "Found " . $result->num_rows . " vouchers with this token\n";

    if ($result->num_rows === 0) {
        // Let's check if the voucher exists but with a different token or already signed
        $debugStmt = $conn->prepare("SELECT voucher_id, signature_token, is_signed FROM petty_cash_voucher ORDER BY voucher_id DESC LIMIT 5");
        $debugStmt->execute();
        $debugResult = $debugStmt->get_result();
        
        $debug .= "Recent vouchers in database:\n";
        while ($debugRow = $debugResult->fetch_assoc()) {
            $debug .= "ID: " . $debugRow['voucher_id'] . ", Token: " . ($debugRow['signature_token'] ? $debugRow['signature_token'] : 'NULL') . ", Signed: " . ($debugRow['is_signed'] ? 'Yes' : 'No') . "\n";
        }
        
        $error = "Invalid or expired signature link. This voucher may have already been signed or the link is invalid.";
    } else {
        $voucher = $result->fetch_assoc();
        
        // Check if already signed
        if ($voucher['is_signed']) {
            $error = "This voucher has already been signed on " . date('F j, Y \a\t g:i A', strtotime($voucher['signature_date'])) . ".";
        }
    }
    $stmt->close();
}

// HANDLE SIGNATURE SAVE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signature_data'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Session expired. Please refresh the page.";
    } else {
        $signatureData = $_POST['signature_data'] ?? '';
        $voucherToken = $_POST['token'] ?? '';
        
        if (!$signatureData) {
            $error = "Please provide a valid signature.";
        } else {
            $clientIP = $_SERVER['REMOTE_ADDR'];
            $signatureDate = date('Y-m-d H:i:s');
            
            // Update the voucher as signed with signature image
            $updateStmt = $conn->prepare("UPDATE petty_cash_voucher SET 
                is_signed = 1, 
                signature_date = ?, 
                signature_ip = ?,
                signature_image = ?
                WHERE signature_token = ?");
            $updateStmt->bind_param("ssss", $signatureDate, $clientIP, $signatureData, $voucherToken);
            
            if ($updateStmt->execute()) {
                $success = "Voucher #{$voucher['voucher_number']} has been successfully signed!";
            } else {
                $error = "Failed to save signature: " . $conn->error;
            }
            $updateStmt->close();
        }
    }
}

// Add signature_image column if it doesn't exist
$checkColumn = $conn->query("SHOW COLUMNS FROM petty_cash_voucher LIKE 'signature_image'");
if ($checkColumn->num_rows == 0) {
    $conn->query("ALTER TABLE petty_cash_voucher ADD COLUMN signature_image LONGTEXT DEFAULT NULL AFTER signature_ip");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Petty Cash Voucher - Stafffy Inc</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #1F5497;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #1F5497;
            margin: 0;
            font-size: 28px;
        }
        
        .voucher-summary {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            flex-wrap: wrap;
        }
        
        .summary-label {
            font-weight: bold;
            color: #555;
        }
        
        .summary-value {
            color: #333;
        }
        
        .amount-highlight {
            font-size: 18px;
            color: #1F5497;
            font-weight: bold;
        }
        
        .signature-container {
            border: 2px dashed #1F5497;
            border-radius: 8px;
            margin: 20px 0;
            background: #fafafa;
            padding: 10px;
        }
        
        #signature-pad {
            width: 100%;
            height: 300px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: white;
        }
        
        .button {
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin: 10px 0;
            border: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .primary {
            background: #1F5497;
            color: white;
        }
        
        .primary:hover {
            background: #163f73;
        }
        
        .secondary {
            background: #eee;
            color: #333;
        }
        
        .secondary:hover {
            background: #ddd;
        }
        
        .full-width {
            width: 100%;
        }
        
        .buttons-container {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        
        .buttons-container button {
            flex: 1;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            margin: 20px 0;
            border: 1px solid #c3e6cb;
        }
        
        .success-message h2 {
            color: #155724;
            margin-top: 0;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            margin: 20px 0;
            border: 1px solid #f5c6cb;
        }
        
        .legal-text {
            font-size: 12px;
            color: #666;
            margin: 20px 0;
            line-height: 1.4;
        }
        
        .signature-info {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .debug-info {
            font-family: monospace;
            background: #f5f5f5;
            padding: 10px;
            border: 1px solid #ccc;
            white-space: pre-wrap;
            margin-top: 20px;
            font-size: 12px;
            border-radius: 4px;
        }
        
        .token-display {
            padding: 10px;
            background: #f0f0f0;
            font-family: monospace;
            border-radius: 4px;
            margin: 10px 0;
            word-break: break-all;
            font-size: 12px;
        }
        
        @media (max-width: 600px) {
            .summary-row {
                flex-direction: column;
            }
            
            .summary-label {
                margin-bottom: 5px;
            }
            
            .buttons-container {
                flex-direction: column;
            }
            
            .buttons-container button {
                margin-bottom: 10px;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Electronic Signature</h1>
            <p>Stafffy Inc - Petty Cash Voucher</p>
        </div>
        
        <?php if ($success): ?>
            <div class="success-message">
                <h2>✓ Voucher Successfully Signed!</h2>
                <p><?= htmlspecialchars($success) ?></p>
                <p>Thank you for signing the voucher electronically. Your signature has been recorded.</p>
                <p><strong>Signed on:</strong> <?php echo date('F j, Y \a\t g:i A'); ?></p>
                <p><strong>IP Address:</strong> <?php echo $_SERVER['REMOTE_ADDR']; ?></p>
                <p>You may now close this window. A confirmation will be sent to the administrator.</p>
            </div>
        <?php elseif ($error): ?>
            <div class="error-message">
                <h3>Error</h3>
                <p><?= htmlspecialchars($error) ?></p>
                <?php if ($debug): ?>
                    <div class="debug-info"><?= htmlspecialchars($debug) ?></div>
                <?php endif; ?>
            </div>
        <?php elseif ($voucher): ?>
            <div class="voucher-summary">
                <h3>Voucher Details</h3>
                <div class="summary-row">
                    <span class="summary-label">Voucher Number:</span>
                    <span class="summary-value"><?php echo htmlspecialchars($voucher['voucher_number']); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Date Issued:</span>
                    <span class="summary-value"><?php echo date('F j, Y', strtotime($voucher['date_issued'])); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Payee Name:</span>
                    <span class="summary-value"><?php echo htmlspecialchars($voucher['payee_name']); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Purpose:</span>
                    <span class="summary-value"><?php echo htmlspecialchars($voucher['purpose']); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Amount:</span>
                    <span class="summary-value amount-highlight">₱<?php echo number_format($voucher['amount'], 2); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Token:</span>
                    <span class="summary-value"><div class="token-display"><?= htmlspecialchars($token) ?></div></span>
                </div>
            </div>
            
            <div class="signature-info">
                <h4>About Electronic Signatures</h4>
                <p>By drawing your signature below, you are providing your electronic signature to acknowledge receipt of the petty cash amount specified in this voucher. This electronic signature has the same legal effect as a handwritten signature.</p>
            </div>
            
            <p><strong>Please draw your signature below:</strong></p>
            
            <div class="signature-container">
                <canvas id="signature-pad"></canvas>
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <input type="hidden" name="signature_data" id="signature-data">
                
                <div class="buttons-container">
                    <button type="button" id="clear-button" class="button secondary">Clear Signature</button>
                    <button type="button" id="save-button" class="button primary">Save Signature</button>
                </div>
            </form>
            
            <div class="legal-text">
                <p><strong>Legal Notice:</strong> By providing your electronic signature, you acknowledge that:</p>
                <ul>
                    <li>You have received the amount specified in this voucher</li>
                    <li>The information in this voucher is accurate</li>
                    <li>This electronic signature is legally binding</li>
                    <li>Your IP address and timestamp will be recorded for security purposes</li>
                </ul>
            </div>
            
            <?php if ($debug): ?>
                <div class="debug-info"><?= htmlspecialchars($debug) ?></div>
            <?php endif; ?>
            
            <script>
                const canvas = document.getElementById('signature-pad');
                const signaturePad = new SignaturePad(canvas, { 
                    minWidth: 1, 
                    maxWidth: 2.5, 
                    penColor: "black",
                    backgroundColor: "white"
                });

                function resizeCanvas() {
                    const ratio = Math.max(window.devicePixelRatio || 1, 1);
                    canvas.width = canvas.offsetWidth * ratio;
                    canvas.height = canvas.offsetHeight * ratio;
                    canvas.getContext("2d").scale(ratio, ratio);
                    signaturePad.clear();
                }

                window.addEventListener("resize", resizeCanvas);
                resizeCanvas();

                document.getElementById('clear-button').addEventListener('click', function() {
                    signaturePad.clear();
                });
                
                document.getElementById('save-button').addEventListener('click', function() {
                    if (signaturePad.isEmpty()) {
                        alert("Please provide a signature first.");
                        return;
                    }
                    
                    if (confirm('Are you sure you want to sign this voucher? This action cannot be undone.')) {
                        document.getElementById('signature-data').value = signaturePad.toDataURL();
                        document.forms[0].submit();
                    }
                });
            </script>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 12px;">
            <p>Stafffy Inc - Electronic Signature System<br>
            For support, contact: staffify@gmail.com</p>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>