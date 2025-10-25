<?php
// email_invoice_template.php
// This file generates the HTML for the invoice email

// Calculate invoice values
$subtotal = $invoiceData['Price'] * $invoiceData['Quantity'];
$discountInput = $invoiceData['Discount'];
$discount = 0;

if (strpos($discountInput, '%') !== false) {
    $discount = $subtotal * (floatval($discountInput) / 100);
} else {
    $discount = floatval($discountInput);
}

$taxRate = $invoiceData['Tax'];
$taxableAmount = $subtotal - $discount;
$tax = $taxableAmount * ($taxRate / 100);
$total = $taxableAmount + $tax;

// Format the invoice date
$invoiceDate = date('F j, Y', strtotime($invoiceData['created_at'] ?? 'now'));
$dueDate = date('F j, Y', strtotime(($invoiceData['created_at'] ?? 'now') . ' +7 days'));
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #<?php echo $invoiceData['Invoice_Id']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .invoice-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .company-details, .invoice-details {
            width: 48%;
        }
        .invoice-title {
            font-size: 24px;
            color: #1F5497;
            margin-bottom: 20px;
        }
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .client-details, .payment-details {
            width: 48%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f5f5f5;
        }
        .totals {
            margin-top: 20px;
            text-align: right;
        }
        .totals p {
            margin: 5px 0;
        }
        .total {
            font-size: 18px;
            font-weight: bold;
        }
        .terms {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-title">INVOICE</div>
        
        <div class="invoice-header">
            <div class="company-details">
                <strong>Stafffy Inc</strong><br>
                54 Irving Street<br>
                New Asinan<br>
                Olongapo City, 2200 ZAMBALES<br>
                PHILIPPINES<br>
                staffify@gmail.com
            </div>
            <div class="invoice-details">
                <p><strong>Invoice #:</strong> <?php echo $invoiceData['Invoice_Id']; ?></p>
                <p><strong>Date:</strong> <?php echo $invoiceDate; ?></p>
                <p><strong>Due Date:</strong> <?php echo $dueDate; ?></p>
            </div>
        </div>
        
        <hr>
        
        <div class="invoice-info">
            <div class="client-details">
                <strong>Bill To:</strong><br>
                <?php echo htmlspecialchars($invoiceData['Customer_Name']); ?><br>
                Email: <?php echo htmlspecialchars($invoiceData['Customer_Email']); ?><br>
                Address: <?php echo htmlspecialchars($invoiceData['Billing_Address']); ?>
            </div>
            <div class="payment-details">
                <strong>Payment Details:</strong><br>
                Status: Pending<br>
                Due: Upon receipt<br>
                Method: Bank Transfer
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($invoiceData['Item_Name']); ?></td>
                    <td>₱<?php echo number_format($invoiceData['Price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($invoiceData['Quantity']); ?></td>
                    <td>₱<?php echo number_format($subtotal, 2); ?></td>
                </tr>
            </tbody>
        </table>
        
        <div class="totals">
            <p>Subtotal: ₱<?php echo number_format($subtotal, 2); ?></p>
            <p>Discount: ₱<?php echo number_format($discount, 2); ?></p>
            <p>Tax (<?php echo $taxRate; ?>%): ₱<?php echo number_format($tax, 2); ?></p>
            <p class="total">Total: ₱<?php echo number_format($total, 2); ?></p>
        </div>
        
        <?php if (!empty($invoiceData['Terms'])): ?>
        <div class="terms">
            <h4>Terms & Conditions</h4>
            <p><?php echo nl2br(htmlspecialchars($invoiceData['Terms'])); ?></p>
        </div>
        <?php endif; ?>
        
        <p style="text-align: center; margin-top: 40px;">
            Thank you for your business!
        </p>
    </div>
</body>
</html>