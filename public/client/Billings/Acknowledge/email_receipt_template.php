<?php
// Ensure receipt data exists
if (!isset($receiptData)) {
    die("Error: Receipt data not found.");
}

// Define base URL for links
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . ($_SERVER['HTTP_HOST'] ?? 'your-domain.com');

// Default values
$tokenExpired = false;
$requestCustomerSignature = false;

// Check if receipt is already signed
$isSigned = isset($receiptData['is_signed']) && (int)$receiptData['is_signed'] === 1;

// Check for token and expiration
$hasToken = isset($receiptData['signature_token']) && !empty($receiptData['signature_token']);
$hasExpiration = isset($receiptData['token_expiration']);
$tokenExpired = $hasExpiration && strtotime($receiptData['token_expiration']) < time();

// Regenerate token if needed and not signed
if (!$isSigned && (!$hasToken || $tokenExpired)) {
    $newToken = bin2hex(random_bytes(32));
    $newExpiration = date("Y-m-d H:i:s", time() + (24 * 60 * 60)); // 24 hours from now

    $receiptData['signature_token'] = $newToken;
    $receiptData['token_expiration'] = $newExpiration;

    // Save to database if connection is available
    if (isset($db) && isset($receiptData['id'])) {
        $stmt = $db->prepare("UPDATE acknowledgment_receipt SET signature_token = ?, token_expiration = ? WHERE id = ?");
        $stmt->bind_param("ssi", $newToken, $newExpiration, $receiptData['id']);
        $stmt->execute();
    }
}

// Determine if signature request should be shown
$requestCustomerSignature = !$isSigned && isset($receiptData['signature_token']) && !$tokenExpired;

// Check for admin signature
$hasAdminSignature = isset($receiptData['admin_signed']) && $receiptData['admin_signed'] == 1;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acknowledgment Receipt</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .receipt { padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; flex-wrap: wrap; }
        .company-info { margin-bottom: 20px; }
        .receipt-statement { margin: 25px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; border-left: 4px solid #1F5497; line-height: 1.6; }
        .highlight { font-weight: bold; color: #1F5497; }
        .details { margin-top: 20px; background-color: #f9f9f9; padding: 15px; border-radius: 5px; }
        .signature { margin-top: 40px; display: flex; justify-content: space-between; flex-wrap: wrap; }
        .signature div { width: 45%; text-align: center; margin-bottom: 20px; }
        .signature-line { border-top: 1px solid #000; padding-top: 5px; }
        .authorized-signed { color: #1F5497; font-weight: bold; }
        .signed-img { margin-bottom: 5px; height: 40px; }
        .error-message { padding: 15px; background-color: #ffebee; border-left: 4px solid #f44336; margin: 20px 0; }
        .success-message { padding: 15px; background-color: #e8f5e9; border-left: 4px solid #4caf50; margin: 20px 0; }

        .email-button {
            display: inline-block;
            padding: 12px 25px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            margin: 10px;
        }
        .email-button.draw {
            background-color: #2196F3;
        }

        @media (max-width: 600px) {
            .header { flex-direction: column; }
            .signature div { width: 100%; }
            .email-button { display: block; margin: 10px auto; text-align: center; max-width: 200px; }
        }
    </style>
</head>
<body>
<div class="receipt">
    <h2 style="color:#1F5497;">ACKNOWLEDGMENT RECEIPT</h2>
    <div class="header">
        <div class="company-info">
            <strong>Stafffy Inc</strong><br>
            54 Irving Street<br>New Asinan<br>
            Olongapo City, 2200 ZAMBALES<br>
            PHILIPPINES<br>
            staffify@gmail.com
        </div>
        <div>
            <p><strong>Receipt #:</strong> <?= htmlspecialchars($receiptData["Receipt_Id"] ?? 'N/A') ?></p>
            <p><strong>Date:</strong> <?= date("F j, Y", strtotime($receiptData["created_at"] ?? "now")) ?></p>
        </div>
    </div>
    <hr>

    <div class="receipt-statement">
        <p>I, <span class="highlight"><?= htmlspecialchars($receiptData["Customer_Name"] ?? 'Customer') ?></span>,
        <?php if (!isset($receiptData["purpose_type"]) || $receiptData["purpose_type"] == 'payment'): ?>
            received from Stafffy Inc the amount of <span class="highlight">₱<?= number_format($receiptData["Amount"] ?? 0, 2) ?></span>
            <?= (isset($receiptData["payment_status"]) && $receiptData["payment_status"] == 'partial') ? '(Partial Payment)' : '(Full Payment)' ?>
            for <span class="highlight"><?= htmlspecialchars($receiptData["Payment_For"] ?? '') ?></span>.
        <?php else: ?>
            received from Stafffy Inc the following items: <span class="highlight"><?= htmlspecialchars($receiptData["items_received"] ?? '') ?></span>.
        <?php endif; ?>
        </p>
        <p>Done, this <?= date("jS \d\a\y \of F Y", strtotime($receiptData["created_at"] ?? "now")) ?>, at <span class="highlight"><?= htmlspecialchars($receiptData["location"] ?? "Olongapo City") ?></span>.</p>
    </div>

    <div class="contact-info" style="margin-top: 20px;">
        <div>
            <strong>Contact Information:</strong><br>
            Email: <?= htmlspecialchars($receiptData["Customer_Email"] ?? 'N/A') ?><br>
            Phone: <?= htmlspecialchars($receiptData["contact_number"] ?? 'N/A') ?><br>
            Address: <?= htmlspecialchars($receiptData["Address"] ?? 'N/A') ?>
        </div>
    </div>

    <div class="details">
        <p><strong>Payment Method:</strong> <?= htmlspecialchars($receiptData["method_name"] ?? 'N/A') ?></p>
        <p><strong>Reference Number:</strong> <?= htmlspecialchars($receiptData["Reference_Number"] ?? '') ?></p>
    </div>

    <?php if (!empty($receiptData["Notes"])): ?>
    <div style="margin-top: 20px;">
        <p><strong>Notes:</strong></p>
        <p><?= nl2br(htmlspecialchars($receiptData["Notes"])) ?></p>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] == 'token'): ?>
    <div class="error-message">
        <strong>Invalid signature token.</strong> This link may have expired or been used already.
        <p><a href="<?= $baseUrl ?>/afis/receipts">Return to Receipts</a></p>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['success']) && $_GET['success'] == 'signed'): ?>
    <div class="success-message">
        <strong>Thank you!</strong> Your signature has been successfully recorded.
    </div>
    <?php endif; ?>

    <?php if ($requestCustomerSignature): ?>
    <div style="margin-top: 30px; text-align: center; padding: 15px; background-color: #f8f9fa; border-radius: 8px; border: 1px solid #ddd;">
        <div style="font-size: 18px; font-weight: bold; color: #1F5497; margin-bottom: 15px;">
            Your Signature is Required
        </div>
        <p>Please sign this receipt to acknowledge that you have received the payment or items described above.</p>
        <div style="margin-top: 15px;">
            <a href="<?= $baseUrl ?>/afis/easy_sign.php?token=<?= urlencode($receiptData['signature_token']) ?>&receipt_id=<?= urlencode($receiptData['id'] ?? $receiptData['Receipt_Id'] ?? '0') ?>" 
               class="email-button">QUICK SIGN</a>
            <a href="<?= $baseUrl ?>/afis/sign_receipt.php?token=<?= urlencode($receiptData['signature_token']) ?>&receipt_id=<?= urlencode($receiptData['id'] ?? $receiptData['Receipt_Id'] ?? '0') ?>" 
               class="email-button draw">DRAW SIGNATURE</a>
        </div>
        <p style="font-size: 0.9em; color: #555; margin-top: 15px;">
            Your signature will be securely stored with this receipt for record-keeping purposes. This signature link will expire in 24 hours.
        </p>
    </div>
    <?php elseif (!$requestCustomerSignature && $tokenExpired): ?>
    <div class="error-message">
        <strong>Signature link expired.</strong> This signature request has expired. Please contact Stafffy Inc for a new signature link.
    </div>
    <?php endif; ?>

    <div class="signature">
        <div>
            <?php if ($isSigned): ?>
            <div class="signed-img">
                <?php if (!empty($receiptData["signature_image"])): ?>
                    <img src="<?= htmlspecialchars($receiptData["signature_image"]) ?>" alt="Customer Signature" style="max-width: 100%; height: 60px;">
                <?php else: ?>
                    <span class="authorized-signed">✓ Electronically Signed</span>
                <?php endif; ?>
            </div>
            <p style="font-size: 0.8em; color: #666;">
                Signed on: <?= date("F j, Y, g:i a", strtotime($receiptData["signed_at"] ?? $receiptData["created_at"] ?? "now")) ?>
            </p>
            <?php endif; ?>
            <div class="signature-line">Customer Signature</div>
        </div>

        <div>
            <?php if ($hasAdminSignature): ?>
            <div class="signed-img">
                <?php if (!empty($receiptData["admin_signature_image"])): ?>
                    <img src="<?= htmlspecialchars($receiptData["admin_signature_image"]) ?>" alt="Authorized Signature" style="max-width: 100%; height: 60px;">
                <?php else: ?>
                    <span class="authorized-signed">✓ Digitally Signed</span>
                <?php endif; ?>
            </div>
            <p style="font-size: 0.8em; color: #666;">
                Signed on: <?= date("F j, Y, g:i a", strtotime($receiptData["admin_signed_at"] ?? $receiptData["created_at"] ?? "now")) ?>
            </p>
            <?php endif; ?>
            <div class="signature-line">Authorized Signature</div>
        </div>
    </div>

    <div style="margin-top: 30px; font-size: 0.8em; color: #666; text-align: center; border-top: 1px solid #eee; padding-top: 15px;">
        This is an electronically generated receipt. For verification, please contact Stafffy Inc.
    </div>
</div>
</body>
</html>
