<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; }
        .receipt { padding: 20px; border: 1px solid #ddd; }
        .header { display: flex; justify-content: space-between; }
        .company-info { margin-bottom: 20px; }
        .receipt-statement { margin: 25px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; border-left: 4px solid #1F5497; line-height: 1.6; }
        .highlight { font-weight: bold; color: #1F5497; }
        .details { margin-top: 20px; background-color: #f9f9f9; padding: 15px; border-radius: 5px; }
        .signature { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature div { width: 45%; text-align: center; }
        .signature-line { border-top: 1px solid #000; padding-top: 5px; }
        .authorized-signed { color: #1F5497; font-weight: bold; }
        .signed-img { margin-bottom: 5px; height: 40px; }
        .sign-button { display: block; background-color: #4CAF50; color: white; text-align: center; padding: 12px 20px; 
                       margin: 20px auto; text-decoration: none; border-radius: 4px; font-weight: bold; width: 200px; }
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
                <p><strong>Receipt #:</strong> <?php echo $receiptData["Receipt_Id"]; ?></p>
                <p><strong>Date:</strong> <?php echo date("F j, Y", strtotime($receiptData["created_at"] ?? "now")); ?></p>
            </div>
        </div>
        <hr>
        
        <div class="receipt-statement">
            <p>I, <span class="highlight"><?php echo htmlspecialchars($receiptData["Customer_Name"]); ?></span>, 
            <?php if(!isset($receiptData["purpose_type"]) || $receiptData["purpose_type"] == 'payment'): ?>
                received from Stafffy Inc the amount of <span class="highlight">₱<?php echo number_format($receiptData["Amount"], 2); ?></span>
                <?php echo (isset($receiptData["payment_status"]) && $receiptData["payment_status"] == 'partial') ? '(Partial Payment)' : '(Full Payment)'; ?>
                for <span class="highlight"><?php echo htmlspecialchars($receiptData["Payment_For"] ?? ''); ?></span>.
            <?php else: ?>
                received from Stafffy Inc the following items: <span class="highlight"><?php echo htmlspecialchars($receiptData["items_received"] ?? ''); ?></span>.
            <?php endif; ?>
            </p>
            
            <p>Done, this <?php echo date("jS \d\a\y \of F Y", strtotime($receiptData["created_at"] ?? "now")); ?>, at <span class="highlight"><?php echo htmlspecialchars($receiptData["location"] ?? "Olongapo City"); ?></span>.</p>
        </div>
        
        <div class="contact-info" style="margin-top: 20px;">
            <div>
                <strong>Contact Information:</strong><br>
                Email: <?php echo htmlspecialchars($receiptData["Customer_Email"]); ?><br>
                Phone: <?php echo htmlspecialchars($receiptData["contact_number"] ?? 'N/A'); ?><br>
                Address: <?php echo htmlspecialchars($receiptData["Address"]); ?>
            </div>
        </div>

        <div class="details">
            <p><strong>Payment Method:</strong> <?php echo isset($receiptData["method_name"]) ? htmlspecialchars($receiptData["method_name"]) : "N/A"; ?></p>
            <p><strong>Reference Number:</strong> <?php echo htmlspecialchars($receiptData["Reference_Number"] ?? ''); ?></p>
        </div>
        
        <?php if (!empty($receiptData["Notes"])): ?>
        <div style="margin-top: 20px;">
            <p><strong>Notes:</strong></p>
            <p><?php echo nl2br(htmlspecialchars($receiptData["Notes"])); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (isset($requestCustomerSignature) && $requestCustomerSignature): ?>
        <div style="margin-top: 30px; text-align: center;">
            <p>Please sign this receipt by clicking the button below:</p>
            <a href="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/sign_receipt.php?token=' . $receiptData['signature_token']; ?>" class="sign-button">
                SIGN RECEIPT
            </a>
        </div>
        <?php endif; ?>
        
        <div class="signature">
            <div>
                <?php if(isset($receiptData["is_signed"]) && $receiptData["is_signed"]): ?>
                <div class="signed-img">
                    <span class="authorized-signed">✓ Electronically Signed</span>
                </div>
                <?php endif; ?>
                <div class="signature-line">Customer Signature</div>
            </div>
            <div>
                <?php if (isset($hasAdminSignature) && $hasAdminSignature): ?>
                <div class="signed-img">
                    <span class="authorized-signed">✓ Digitally Signed</span>
                </div>
                <?php endif; ?>
                <div class="signature-line">Authorized Signature</div>
            </div>
        </div>
    </div>
</body>
</html>