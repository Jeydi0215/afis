<?php
// Database connection
include('../../config/config.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$success = false;
$receiptData = null;

// Check if we have a token and ID
if (isset($_GET['token']) && isset($_GET['id'])) {
    $token = $conn->real_escape_string($_GET['token']);
    $id = intval($_GET['id']);
    
    // Verify the token
    $result = $conn->query("SELECT ar.*, pm.method_name FROM acknowledgment_receipt ar 
                           JOIN payment_methods pm ON ar.Payment_Method_Id = pm.id 
                           WHERE ar.Receipt_Id = $id AND ar.Approval_Token = '$token'");
    
    if ($result && $result->num_rows > 0) {
        $receiptData = $result->fetch_assoc();
        
        // Check if already approved
        if ($receiptData['Status'] == 'approved') {
            $message = "This receipt has already been approved. Thank you!";
            $success = true;
        } else {
            // Process approval
            if (isset($_POST['approve'])) {
                // Update receipt status
                $conn->query("UPDATE acknowledgment_receipt SET 
                              Status = 'approved',
                              Customer_Signed = 1,
                              Approval_Date = NOW()
                              WHERE Receipt_Id = $id AND Approval_Token = '$token'");
                
                if ($conn->affected_rows > 0) {
                    $message = "Thank you! The receipt has been successfully approved and signed.";
                    $success = true;
                    // Refresh receipt data
                    $result = $conn->query("SELECT ar.*, pm.method_name FROM acknowledgment_receipt ar 
                                           JOIN payment_methods pm ON ar.Payment_Method_Id = pm.id 
                                           WHERE ar.Receipt_Id = $id");
                    $receiptData = $result->fetch_assoc();
                } else {
                    $message = "Error approving the receipt. Please try again.";
                }
            }
        }
    } else {
        $message = "Invalid approval link. Please check your email for the correct link.";
    }
} else {
    $message = "Missing required information for approval.";
}

// Current date for display
$currentDate = date('F j, Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Approval - Stafffy Inc</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        h1 {
            color: #1F5497;
            margin: 0 0 10px 0;
        }
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .receipt {
            margin-top: 30px;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
        }
        .receipt-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .company-info {
            margin-bottom: 20px;
        }
        .details {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .signature {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature div {
            width: 45%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            padding-top: 5px;
        }
        .btn-approve {
            display: block;
            width: 200px;
            margin: 30px auto;
            padding: 12px 0;
            background-color: #1F5497;
            color: white;
            text-align: center;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        .btn-approve:hover {
            background-color: #164278;
        }
        .signed {
            color: #4CAF50;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .admin-signed {
            color: #1F5497;
            font-weight: bold;
            margin-bottom: 5px;
        }
        @media print {
            .btn-approve, .message {
                display: none;
            }
            .container {
                box-shadow: none;
                padding: 0;
            }
            body {
                background-color: #fff;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Receipt Approval</h1>
            <p>Stafffy Inc - Electronic Receipt System</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($receiptData): ?>
            <div class="receipt">
                <h2 style="color:#1F5497; text-align: center;">ACKNOWLEDGMENT RECEIPT</h2>
                <div class="receipt-header">
                    <div class="company-info">
                        <strong>Stafffy Inc</strong><br>
                        54 Irving Street<br>New Asinan<br>
                        Olongapo City, 2200 ZAMBALES<br>
                        PHILIPPINES<br>
                        staffify@gmail.com
                    </div>
                    <div>
                        <p><strong>Receipt #:</strong> <?php echo $receiptData['Receipt_Id']; ?></p>
                        <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($receiptData['created_at'] ?? 'now')); ?></p>
                        <p><strong>Status:</strong> <span style="color: <?php echo $receiptData['Status'] == 'approved' ? '#4CAF50' : '#FF9800'; ?>; font-weight: bold;">
                            <?php echo ucfirst($receiptData['Status']); ?>
                        </span></p>
                        <?php if ($receiptData['Status'] == 'approved'): ?>
                            <p><strong>Approved On:</strong> <?php echo date('F j, Y', strtotime($receiptData['Approval_Date'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <hr>
                <div>
                    <strong>Received From:</strong><br>
                    <?php echo htmlspecialchars($receiptData['Customer_Name']); ?><br>
                    Email: <?php echo htmlspecialchars($receiptData['Customer_Email']); ?><br>
                    Address: <?php echo htmlspecialchars($receiptData['Address']); ?>
                </div>
                
                <div class="details">
                    <p><strong>Received the sum of:</strong> ₱<?php echo number_format($receiptData['Amount'], 2); ?></p>
                    <p><strong>Payment For:</strong> <?php echo htmlspecialchars($receiptData['Payment_For']); ?></p>
                    <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($receiptData['method_name']); ?></p>
                    <p><strong>Reference Number:</strong> <?php echo htmlspecialchars($receiptData['Reference_Number']); ?></p>
                </div>
                
                <?php if (!empty($receiptData['Notes'])): ?>
                <div style="margin-top: 20px;">
                    <p><strong>Notes:</strong></p>
                    <p><?php echo nl2br(htmlspecialchars($receiptData['Notes'])); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="signature">
                    <div>
                        <?php if ($receiptData['Customer_Signed']): ?>
                            <div class="signed">✓ Customer Signed</div>
                        <?php endif; ?>
                        <div class="signature-line">Customer Signature</div>
                    </div>
                    <div>
                        <?php if ($receiptData['Admin_Signed']): ?>
                            <div class="admin-signed">✓ Digitally Signed</div>
                        <?php endif; ?>
                        <div class="signature-line">Authorized Signature</div>
                    </div>
                </div>
            </div>
            
            <?php if ($receiptData['Status'] != 'approved'): ?>
                <form method="POST">
                    <button type="submit" name="approve" class="btn-approve">APPROVE & SIGN RECEIPT</button>
                </form>
            <?php else: ?>
                <button onclick="window.print()" class="btn-approve">PRINT RECEIPT</button>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>