<?php
// Email voucher template
// This file generates the HTML content for vouchers sent via email

// Check if we have voucher data
if (!isset($voucherData) || !$voucherData) {
    echo "Error: Voucher data not available";
    return;
}

// Check if we need to include signature link
$includeSignatureLink = isset($requestPayeeSignature) && $requestPayeeSignature;
$showAdminSignature = isset($hasAdminSignature) && $hasAdminSignature;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petty Cash Voucher</title>
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
        
        .voucher-container {
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
        
        .company-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .company-details {
            flex: 1;
            min-width: 200px;
        }
        
        .voucher-info {
            flex: 1;
            text-align: right;
            min-width: 200px;
        }
        
        .voucher-info p {
            margin: 5px 0;
        }
        
        .voucher-details {
            margin: 30px 0;
        }
        
        .detail-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-label {
            width: 30%;
            font-weight: bold;
            color: #555;
        }
        
        .detail-value {
            width: 70%;
            color: #333;
        }
        
        .amount-highlight {
            font-weight: bold;
            color: #1F5497;
            font-size: 18px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #FFC107;
            color: #333;
        }
        
        .status-approved {
            background-color: #4CAF50;
            color: white;
        }
        
        .status-rejected {
            background-color: #F44336;
            color: white;
        }
        
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        .signature-box {
            width: 30%;
            text-align: center;
            min-width: 150px;
            margin-bottom: 20px;
        }
        
        .signature-line {
            border-top: 2px solid #333;
            margin-top: 50px;
            padding-top: 10px;
            font-weight: bold;
        }
        
        .notes-section {
            margin: 30px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .signature-link {
            background-color: #1F5497;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
            font-weight: bold;
        }
        
        .signature-link:hover {
            background-color: #163f73;
        }
        
        .admin-signature {
            color: #4CAF50;
            font-weight: bold;
            font-style: italic;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        
        @media (max-width: 600px) {
            .company-info {
                flex-direction: column;
            }
            
            .voucher-info {
                text-align: left;
                margin-top: 20px;
            }
            
            .signature-section {
                flex-direction: column;
            }
            
            .signature-box {
                width: 100%;
            }
            
            .detail-row {
                flex-direction: column;
            }
            
            .detail-label {
                width: 100%;
                margin-bottom: 5px;
            }
            
            .detail-value {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="voucher-container">
        <div class="header">
            <h1>PETTY CASH VOUCHER</h1>
        </div>
        
        <div class="company-info">
            <div class="company-details">
                <strong>Stafffy Inc</strong><br>
                54 Irving Street<br>
                New Asinan<br>
                Olongapo City, 2200 ZAMBALES<br>
                PHILIPPINES<br>
                staffify@gmail.com
            </div>
            <div class="voucher-info">
                <p><strong>Voucher #:</strong> <?php echo htmlspecialchars($voucherData['voucher_number']); ?></p>
                <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($voucherData['date_issued'])); ?></p>
                <p><strong>Status:</strong> 
                    <span class="status-badge status-<?php echo $voucherData['status']; ?>">
                        <?php echo ucfirst($voucherData['status']); ?>
                    </span>
                </p>
                <?php if($voucherData['is_signed']): ?>
                <p style="color: #4CAF50; font-weight: bold;">✓ Electronically Signed</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="voucher-details">
            <div class="detail-row">
                <div class="detail-label">Payee:</div>
                <div class="detail-value"><?php echo htmlspecialchars($voucherData['payee_name']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Email:</div>
                <div class="detail-value"><?php echo htmlspecialchars($voucherData['payee_email']); ?></div>
            </div>
            
            <?php if($voucherData['contact_number']): ?>
            <div class="detail-row">
                <div class="detail-label">Contact:</div>
                <div class="detail-value"><?php echo htmlspecialchars($voucherData['contact_number']); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if($voucherData['department']): ?>
            <div class="detail-row">
                <div class="detail-label">Department:</div>
                <div class="detail-value"><?php echo htmlspecialchars($voucherData['department']); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if($voucherData['position']): ?>
            <div class="detail-row">
                <div class="detail-label">Position:</div>
                <div class="detail-value"><?php echo htmlspecialchars($voucherData['position']); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="detail-row">
                <div class="detail-label">Purpose:</div>
                <div class="detail-value"><?php echo htmlspecialchars($voucherData['purpose']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Amount:</div>
                <div class="detail-value amount-highlight">₱<?php echo number_format($voucherData['amount'], 2); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Category:</div>
                <div class="detail-value"><?php echo htmlspecialchars($voucherData['category_name']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Payment Method:</div>
                <div class="detail-value"><?php echo htmlspecialchars($voucherData['payment_method']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Receipt Attached:</div>
                <div class="detail-value"><?php echo $voucherData['receipt_attached'] ? 'Yes' : 'No'; ?></div>
            </div>
        </div>
        
        <?php if($voucherData['notes']): ?>
        <div class="notes-section">
            <h3>Notes:</h3>
            <p><?php echo nl2br(htmlspecialchars($voucherData['notes'])); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="signature-section">
            <div class="signature-box">
                <?php if($showAdminSignature): ?>
                    <div class="admin-signature">Authorized Signature</div>
                    <div><?php echo htmlspecialchars($voucherData['approved_by'] ?: 'Stafffy Inc'); ?></div>
                <?php endif; ?>
                <div class="signature-line">Approved By</div>
            </div>
            
            <div class="signature-box">
                <div><?php echo htmlspecialchars($voucherData['received_by'] ?: ''); ?></div>
                <div class="signature-line">Received By</div>
            </div>
            
            <div class="signature-box">
                <?php if($voucherData['is_signed']): ?>
                    <div style="color: #4CAF50; font-weight: bold;">Electronically Signed</div>
                    <?php if($voucherData['signature_image']): ?>
                        <img src="<?php echo htmlspecialchars($voucherData['signature_image']); ?>" 
                             alt="Digital Signature" 
                             style="max-width: 200px; max-height: 60px; border: 1px solid #ccc; background: white; margin: 5px 0;">
                    <?php endif; ?>
                    <div><?php echo htmlspecialchars($voucherData['payee_name']); ?></div>
                    <div><small><?php echo date('F j, Y g:i A', strtotime($voucherData['signature_date'])); ?></small></div>
                <?php elseif($includeSignatureLink && isset($voucherData['signature_token'])): ?>
                    <p>Please click the link below to sign this voucher electronically:</p>
                    <a href="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/sign_voucher.php?token=' . $voucherData['signature_token']; ?>" 
                       class="signature-link">Sign Voucher</a>
                <?php else: ?>
                    <div style="height: 50px;"></div>
                <?php endif; ?>
                <div class="signature-line">Payee Signature</div>
            </div>
        </div>
        
        <div class="footer">
            <p>This is an electronically generated voucher from Stafffy Inc.<br>
            For any queries, please contact us at staffify@gmail.com</p>
        </div>
    </div>
</body>
</html>