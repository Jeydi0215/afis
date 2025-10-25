<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
include('../../config/config.php');
// include('logout.php');
// Get business tax settings
$taxSettingsQuery = "SELECT tax_type FROM business_settings LIMIT 1";
$taxSettingsResult = $conn->query($taxSettingsQuery);
$isVat = true; // Default to VAT mode
$taxType = 'VAT (12%)'; // Default tax type

if ($taxSettingsResult && $taxSettingsResult->num_rows > 0) {
    $settings = $taxSettingsResult->fetch_assoc();
    $taxType = $settings['tax_type'];
    $isVat = (strpos($settings['tax_type'], 'VAT') !== false && strpos($settings['tax_type'], 'Non-VAT') === false);
}

// PHPMailer includes
require __DIR__ . '/../PHPMailer/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/PHPMailer-master/src/SMTP.php';
require __DIR__ . '/../PHPMailer/PHPMailer-master/src/Exception.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendInvoiceEmail($to, $subject, $bodyText, $htmlBody, $amount) {
    $mail = new PHPMailer();
    $mail->SMTPDebug = 0;
    $mail->Debugoutput = 'html';

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'dimalantajustine8@gmail.com';
    $mail->Password = 'qoss jbhb epku kxel';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('dimalantajustine8@gmail.com', 'Stafify');
    $mail->addAddress($to);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $htmlBody;
    $mail->AltBody = $bodyText;

    if ($mail->send()) {
        echo "✅ Email sent to: <strong>$to</strong><br>";
    } else {
        echo "❌ Failed to send email. Error: " . $mail->ErrorInfo . "<br>";
    }
}

// Create all necessary tables
$createInvoiceTable = "CREATE TABLE IF NOT EXISTS invoice (
    Invoice_Id INT AUTO_INCREMENT PRIMARY KEY,
    Customer_Name VARCHAR(255) NOT NULL,
    Customer_Email VARCHAR(255) NOT NULL,
    Billing_Address TEXT,
    Item_Name TEXT NOT NULL,
    Price DECIMAL(10,2) NOT NULL,
    Quantity INT NOT NULL,
    Discount VARCHAR(50) DEFAULT '0',
    Tax DECIMAL(10,2) DEFAULT 0,
    Terms TEXT,
    tax_id INT DEFAULT NULL,
    discount_id INT DEFAULT NULL,
    invoice_mode VARCHAR(20) DEFAULT 'VAT',
    tax_type_at_creation VARCHAR(50) DEFAULT 'VAT (12%)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$createTaxTable = "CREATE TABLE IF NOT EXISTS tax_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tax_name VARCHAR(100) NOT NULL,
    tax_rate DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$createDiscountTable = "CREATE TABLE IF NOT EXISTS discount_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    discount_name VARCHAR(100) NOT NULL,
    discount_value VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$createBusinessSettingsTable = "CREATE TABLE IF NOT EXISTS business_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tax_type VARCHAR(50) DEFAULT 'VAT (12%)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// Execute table creation
if (!$conn->query($createInvoiceTable)) {
    die("Error creating invoice table: " . $conn->error);
}

if (!$conn->query($createTaxTable)) {
    die("Error creating tax_rates table: " . $conn->error);
}

if (!$conn->query($createDiscountTable)) {
    die("Error creating discount_rates table: " . $conn->error);
}

if (!$conn->query($createBusinessSettingsTable)) {
    die("Error creating business_settings table: " . $conn->error);
}

// Insert default data if tables are empty
$checkTax = $conn->query("SELECT * FROM tax_rates LIMIT 1");
if ($checkTax->num_rows == 0) {
    $conn->query("INSERT INTO tax_rates (tax_name, tax_rate) VALUES 
        ('No Tax', 0),
        ('Standard VAT', 12),
        ('Reduced VAT', 5)");
}

$checkDiscount = $conn->query("SELECT * FROM discount_rates LIMIT 1");
if ($checkDiscount->num_rows == 0) {
    $conn->query("INSERT INTO discount_rates (discount_name, discount_value) VALUES 
        ('No Discount', '0'),
        ('Standard Discount', '10%'),
        ('Special Offer', '100')");
}

$checkBusinessSettings = $conn->query("SELECT * FROM business_settings LIMIT 1");
if ($checkBusinessSettings->num_rows == 0) {
    $conn->query("INSERT INTO business_settings (tax_type) VALUES ('VAT (12%)')");
}

// Function to calculate invoice totals based on mode
function calculateInvoiceTotals($price, $quantity, $discount, $taxRate, $isVatMode) {
    $subtotal = $price * $quantity;
    
    // Calculate discount
    $discountAmount = 0;
    if (strpos($discount, '%') !== false) {
        $discountAmount = $subtotal * (floatval($discount) / 100);
    } else {
        $discountAmount = floatval($discount);
    }
    
    $taxableAmount = $subtotal - $discountAmount;
    
    if ($isVatMode) {
        // VAT mode: add tax to total
        $tax = $taxableAmount * ($taxRate / 100);
        $total = $taxableAmount + $tax;
    } else {
        // Non-VAT mode: deduct withholding tax (3%)
        $tax = $taxableAmount * 0.03;
        $total = $taxableAmount - $tax;
    }
    
    return [
        'subtotal' => $subtotal,
        'discount' => $discountAmount,
        'tax' => $tax,
        'total' => $total,
        'taxable_amount' => $taxableAmount
    ];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['new_tax'])) {
        $taxName = $conn->real_escape_string($_POST['tax_name']);
        $taxRate = floatval($_POST['tax_rate']);
        
        $result = $conn->query("INSERT INTO tax_rates (tax_name, tax_rate) VALUES ('$taxName', $taxRate)");
        if ($result) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "Error adding tax rate: " . $conn->error;
        }
    }
    
    if (isset($_POST['new_discount'])) {
        $discountName = $conn->real_escape_string($_POST['discount_name']);
        $discountValue = $conn->real_escape_string($_POST['discount_value']);
        
        $result = $conn->query("INSERT INTO discount_rates (discount_name, discount_value) VALUES ('$discountName', '$discountValue')");
        if ($result) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "Error adding discount rate: " . $conn->error;
        }
    }

    if (isset($_POST['send_email'])) {
        $invoiceId = intval($_POST['invoice_id']);
        $recipient = $conn->real_escape_string($_POST['email_recipient']);
        $subject = $conn->real_escape_string($_POST['email_subject']);
        $message = $conn->real_escape_string($_POST['email_message']);
        
        ob_start();
        $result = $conn->query("SELECT * FROM invoice WHERE Invoice_Id = $invoiceId");
        $invoiceData = $result->fetch_assoc();
        include('email_invoice_template.php');
        $invoiceHtml = ob_get_clean();
        
        sendInvoiceEmail($recipient, $subject, $message, $invoiceHtml, $invoiceId);
        
        header("Location: ?view=$invoiceId&email_sent=1");
        exit();
    }

    if (isset($_POST['new_invoice'])) {
        // Validate required fields
        if (empty($_POST['customer_name']) || empty($_POST['customer_email']) || 
            empty($_POST['item_name']) || empty($_POST['price']) || empty($_POST['quantity'])) {
            die("Error: All required fields must be filled.");
        }

        $customer = $conn->real_escape_string($_POST['customer_name']);
        $email = $conn->real_escape_string($_POST['customer_email']);
        $address = $conn->real_escape_string($_POST['billing_address']);
        $item = $conn->real_escape_string($_POST['item_name']);
        $price = floatval($_POST['price']);
        $quantity = intval($_POST['quantity']);
        $terms = $conn->real_escape_string($_POST['terms']);
        
        // Get tax information
        $taxId = intval($_POST['tax_option']);
        $taxResult = $conn->query("SELECT tax_rate FROM tax_rates WHERE id = $taxId");
        
        if (!$taxResult) {
            die("Tax query error: " . $conn->error);
        }
        
        $taxRow = $taxResult->fetch_assoc();
        $tax = $taxRow ? floatval($taxRow['tax_rate']) : 0;
        
        // Get discount information
        $discountId = intval($_POST['discount_option']);
        $discountResult = $conn->query("SELECT discount_value FROM discount_rates WHERE id = $discountId");
        
        if (!$discountResult) {
            die("Discount query error: " . $conn->error);
        }
        
        $discountRow = $discountResult->fetch_assoc();
        $discount = $discountRow ? $discountRow['discount_value'] : '0';
        
        // Store current business tax mode and type with the invoice
        $invoiceMode = $isVat ? 'VAT' : 'NON-VAT';
        $currentTaxType = $conn->real_escape_string($taxType);

        $insertQuery = "INSERT INTO invoice (
            Customer_Name, Customer_Email, Billing_Address, Item_Name,
            Price, Quantity, Discount, Tax, Terms, tax_id, discount_id,
            invoice_mode, tax_type_at_creation
        ) VALUES (
            '$customer', '$email', '$address', '$item',
            $price, $quantity, '$discount', $tax, '$terms', $taxId, $discountId,
            '$invoiceMode', '$currentTaxType'
        )";

        $result = $conn->query($insertQuery);
        
        if ($result) {
            $newId = $conn->insert_id;
            header("Location: ?view=$newId");
            exit();
        } else {
            die("Error creating invoice: " . $conn->error . "<br>Query: " . $insertQuery);
        }
        
    } elseif (isset($_POST['edit_invoice'])) {
        $id = intval($_POST['invoice_id']);
        $customer = $conn->real_escape_string($_POST['customer_name']);
        $email = $conn->real_escape_string($_POST['customer_email']);
        $address = $conn->real_escape_string($_POST['billing_address']);
        $item = $conn->real_escape_string($_POST['item_name']);
        $price = floatval($_POST['price']);
        $quantity = intval($_POST['quantity']);
        $terms = $conn->real_escape_string($_POST['terms']);
        
        $taxId = intval($_POST['tax_option']);
        $taxResult = $conn->query("SELECT tax_rate FROM tax_rates WHERE id = $taxId");
        $taxRow = $taxResult->fetch_assoc();
        $tax = $taxRow ? floatval($taxRow['tax_rate']) : 0;
        
        $discountId = intval($_POST['discount_option']);
        $discountResult = $conn->query("SELECT discount_value FROM discount_rates WHERE id = $discountId");
        $discountRow = $discountResult->fetch_assoc();
        $discount = $discountRow ? $discountRow['discount_value'] : '0';

        // Update invoice mode and tax type to current business settings
        $invoiceMode = $isVat ? 'VAT' : 'NON-VAT';
        $currentTaxType = $conn->real_escape_string($taxType);

        $updateQuery = "UPDATE invoice SET 
            Customer_Name = '$customer',
            Customer_Email = '$email',
            Billing_Address = '$address',
            Item_Name = '$item',
            Price = $price,
            Quantity = $quantity,
            Discount = '$discount',
            Tax = $tax,
            Terms = '$terms',
            tax_id = $taxId,
            discount_id = $discountId,
            invoice_mode = '$invoiceMode',
            tax_type_at_creation = '$currentTaxType'
            WHERE Invoice_Id = $id
        ";

        $result = $conn->query($updateQuery);
        
        if ($result) {
            header("Location: ?view=$id");
            exit();
        } else {
            die("Error updating invoice: " . $conn->error);
        }
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $result = $conn->query("DELETE FROM invoice WHERE Invoice_Id = $id");
    if ($result) {
        header("Location: invoice.php");
        exit();
    } else {
        die("Error deleting invoice: " . $conn->error);
    }
}

// Get current date for invoice
$currentDate = date('F j, Y');
$dueDate = date('F j, Y', strtotime('+7 days'));

// Check if we should show the modal
$showModal = isset($_GET['show']) ? true : false;

// Check if we're viewing or editing an invoice
$viewId = isset($_GET['view']) ? intval($_GET['view']) : null;
$editId = isset($_GET['edit']) ? intval($_GET['edit']) : null;

// Check if we're showing tax or discount modal
$showTaxModal = isset($_GET['add_tax']) ? true : false;
$showDiscountModal = isset($_GET['add_discount']) ? true : false;

// Fetch invoice data if viewing or editing
$invoiceData = null;
$invoiceIsVat = $isVat; // Default to current business setting
$invoiceTaxType = $taxType; // Default to current tax type

if ($viewId || $editId) {
    $id = $viewId ? $viewId : $editId;
    $result = $conn->query("SELECT * FROM invoice WHERE Invoice_Id = $id");
    if ($result && $result->num_rows > 0) {
        $invoiceData = $result->fetch_assoc();
        
        // Use the invoice's stored mode and tax type if available
        if (isset($invoiceData['invoice_mode']) && !empty($invoiceData['invoice_mode'])) {
            $invoiceIsVat = ($invoiceData['invoice_mode'] === 'VAT');
        }
        if (isset($invoiceData['tax_type_at_creation']) && !empty($invoiceData['tax_type_at_creation'])) {
            $invoiceTaxType = $invoiceData['tax_type_at_creation'];
        }
    }
}

// Fetch all tax rates
$taxRates = $conn->query("SELECT * FROM tax_rates ORDER BY tax_name");

// Fetch all discount rates
$discountRates = $conn->query("SELECT * FROM discount_rates ORDER BY discount_name");

include('../../../components/sidebar.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Management</title>
    <link rel="stylesheet" href="Invoice.css">
    <style>
        .business-tax-info {
            background-color: #e8f4f8;
            padding: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #1F5497;
            border-radius: 4px;
        }
        .business-tax-info strong {
            color: #1F5497;
        }
        .non-vat-warning {
            color: #d9534f;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .invoice-mode-indicator {
            background-color: #f0f0f0;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            margin-left: 10px;
        }
        .invoice-mode-vat {
            background-color: #d4edda;
            color: #155724;
        }
        .invoice-mode-nonvat {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
  <h1 class="page-title">
              Invoice
            </h1>
<!-- Tax Modal -->
<div class="modal-overlay" id="taxModal" style="<?php echo $showTaxModal ? 'display: flex;' : 'display: none;' ?>">
  <div class="small-modal">
    <button class="close-modal" onclick="window.location.href='invoice.php'">X</button>
    <h2>Add New Tax Rate</h2>
    <form method="POST">
      <input type="hidden" name="new_tax" value="1">
      <label>Tax Name</label>
      <input type="text" name="tax_name" required>
      <label>Tax Rate (%)</label>
      <input type="number" name="tax_rate" step="0.01" required>
      <button type="submit" class="btn-save">Save Tax Rate</button>
    </form>
  </div>
</div>

<!-- Discount Modal -->
<div class="modal-overlay" id="discountModal" style="<?php echo $showDiscountModal ? 'display: flex;' : 'display: none;' ?>">
  <div class="small-modal">
    <button class="close-modal" onclick="window.location.href='invoice.php'">X</button>
    <h2>Add New Discount</h2>
    <form method="POST">
      <input type="hidden" name="new_discount" value="1">
      <label>Discount Name</label>
      <input type="text" name="discount_name" required>
      <label>Discount Value (use number for fixed amount or add % for percentage)</label>
      <input type="text" name="discount_value" required placeholder="e.g., 100 or 10%">
      <button type="submit" class="btn-save">Save Discount</button>
    </form>
  </div>
</div>

<!-- Email Modal -->
<div class="modal-overlay" id="emailModal" style="display: none;">
  <div class="email-modal">
    <button class="close-modal" onclick="document.getElementById('emailModal').style.display='none'">X</button>
    <h2>Send Invoice via Email</h2>
    <form method="POST">
      <input type="hidden" name="send_email" value="1">
      <input type="hidden" name="invoice_id" id="email_invoice_id" value="">
      
      <label>Recipient Email</label>
      <input type="email" name="email_recipient" id="email_recipient" required>
      
      <label>Subject</label>
      <input type="text" name="email_subject" value="Invoice from Stafffy Inc" required>
      
      <label>Message</label>
      <textarea name="email_message" rows="4" required>Dear customer,

Please find attached your invoice. For any queries, please don't hesitate to contact us.

Best regards,
Stafffy Inc</textarea>
      
      <button type="submit" class="btn-save">Send Email</button>
    </form>
  </div>
</div>

<div class="modal-overlay" id="invoiceModal" style="<?php echo ($showModal || $editId) ? 'display: flex;' : 'display: none;' ?>">
  <div class="invoice-modal">
    <button class="close-modal" onclick="closeModal()">X</button>
    <!-- Left: Form -->
    <form method="POST" class="edit-form">
      <h2><?php echo $editId ? 'Edit Invoice' : 'Create Invoice'; ?></h2>
      
      <!-- Business Tax Mode Display -->
      <div class="business-tax-info">
        <strong>Current Business Tax Mode:</strong> <?php echo htmlspecialchars($taxType); ?>
        <?php if (!$isVat): ?>
          <br><small style="color: #d9534f;">New invoices will use NON-VAT calculations with 3% withholding tax.</small>
        <?php endif; ?>
      </div>
      
      <?php if ($editId): ?>
        <input type="hidden" name="edit_invoice" value="1">
        <input type="hidden" name="invoice_id" value="<?php echo $editId; ?>">
        <!-- Show original invoice mode when editing -->
        <div class="business-tax-info" style="background-color: #fff3cd; border-left-color: #ffc107;">
          <strong>Original Invoice Mode:</strong> <?php echo htmlspecialchars($invoiceTaxType); ?>
          <br><small>This invoice will be updated to current business settings when saved.</small>
        </div>
      <?php else: ?>
        <input type="hidden" name="new_invoice" value="1">
      <?php endif; ?>

      <label>Customer Name</label>
      <input type="text" name="customer_name" id="customerName" required 
             value="<?php echo $invoiceData ? htmlspecialchars($invoiceData['Customer_Name']) : ''; ?>">

      <label>Email</label>
      <input type="email" name="customer_email" id="customerEmail" required
             value="<?php echo $invoiceData ? htmlspecialchars($invoiceData['Customer_Email']) : ''; ?>">

      <label>Billing Address</label>
      <textarea name="billing_address" id="billingAddress" required><?php 
          echo $invoiceData ? htmlspecialchars($invoiceData['Billing_Address']) : ''; 
      ?></textarea>

      <!-- Item Description, Price, and Quantity Section -->
      <div style="display: flex; gap: 10px; width: 100%; margin-bottom: 15px;">
        <!-- Item Description -->
        <div style="flex: 2;">
          <label>Item Description</label>
          <textarea name="item_name" id="itemName" required><?php 
              echo $invoiceData ? htmlspecialchars($invoiceData['Item_Name']) : ''; 
          ?></textarea>
        </div>

        <!-- Price and Quantity -->
        <div style="flex: 1;">
          <div style="margin-bottom: 10px;">
            <label>Price (₱)</label>
            <input type="number" name="price" id="price" step="0.01" required
                   value="<?php echo $invoiceData ? htmlspecialchars($invoiceData['Price']) : ''; ?>">
          </div>
          <div>
            <label>Quantity</label>
            <input type="number" name="quantity" id="quantity" required
                   value="<?php echo $invoiceData ? htmlspecialchars($invoiceData['Quantity']) : ''; ?>">
          </div>
        </div>
      </div>

      <div style="display: flex; justify-content: space-between;">
        <div style="width: 48%;">
          <label>Tax</label>
          <div style="display: flex;">
            <select name="tax_option" id="taxOption" style="width: 85%" onchange="updatePreviewFromSelect()">
              <?php 
              $taxRatesForForm = $conn->query("SELECT * FROM tax_rates ORDER BY tax_name");
              while ($tax = $taxRatesForForm->fetch_assoc()): ?>
                <option value="<?php echo $tax['id']; ?>" <?php 
                  if ($invoiceData && isset($invoiceData['tax_id']) && $invoiceData['tax_id'] == $tax['id']) echo 'selected'; 
                  elseif ($invoiceData && !isset($invoiceData['tax_id']) && $invoiceData['Tax'] == $tax['tax_rate']) echo 'selected';
                ?>>
                  <?php echo htmlspecialchars($tax['tax_name']) . ' (' . $tax['tax_rate'] . '%)'; ?>
                </option>
              <?php endwhile; ?>
            </select>
            <button type="button" class="btn-add" onclick="window.location.href='?add_tax=1'" style="width: 15%">+</button>
          </div>
        </div>
        
        <div style="width: 48%;">
          <label>Discount</label>
          <div style="display: flex;">
            <select name="discount_option" id="discountOption" style="width: 85%" onchange="updatePreviewFromSelect()">
              <?php 
              $discountRatesForForm = $conn->query("SELECT * FROM discount_rates ORDER BY discount_name");
              while ($discount = $discountRatesForForm->fetch_assoc()): ?>
                <option value="<?php echo $discount['id']; ?>" <?php 
                  if ($invoiceData && isset($invoiceData['discount_id']) && $invoiceData['discount_id'] == $discount['id']) echo 'selected';
                  elseif ($invoiceData && !isset($invoiceData['discount_id']) && $invoiceData['Discount'] == $discount['discount_value']) echo 'selected';
                ?>>
                  <?php echo htmlspecialchars($discount['discount_name']) . ' (' . $discount['discount_value'] . ')'; ?>
                </option>
              <?php endwhile; ?>
            </select>
            <button type="button" class="btn-add" onclick="window.location.href='?add_discount=1'" style="width: 15%">+</button>
          </div>
        </div>
      </div>

      <label>Terms & Conditions</label>
      <textarea name="terms" id="terms"><?php 
          echo $invoiceData ? htmlspecialchars($invoiceData['Terms']) : ''; 
      ?></textarea>

      <!-- Save button inside the form -->
      <div class="modal-actions">
        <button type="submit" class="btn-save"><?php echo $editId ? 'Update Invoice' : 'Save Invoice'; ?></button>
        <button type="button" class="btn-print" onclick="window.print()">Print Invoice</button>
      </div>
    </form>

    <!-- Right: Preview -->
    <div class="preview-pane">
      <h2 style="color:#1F5497;">INVOICE</h2>
      <div style="display: flex; justify-content: space-between;">
        <div>
          <strong>Stafffy Inc</strong><br>
          54 Irving Street<br>New Asinan<br>Olongapo City, 2200 ZAMBALES<br>PHILIPPINES<br>staffify@gmail.com
        </div>
        <div>
          <p><strong>Invoice #:</strong> <?php echo $invoiceData ? $invoiceData['Invoice_Id'] : 'New'; ?></p>
          <p><strong>Date:</strong> <?php echo $currentDate; ?></p>
          <p><strong>Due Date:</strong> <?php echo $dueDate; ?></p>
        </div>
      </div>
      <hr>
      <div style="display: flex; justify-content: space-between;">
        <div>
          <strong>Bill To:</strong><br>
          <span id="previewCustomerName"><?php 
              echo $invoiceData ? htmlspecialchars($invoiceData['Customer_Name']) : 'Customer Name'; 
          ?></span><br>
          Email: <span id="previewCustomerEmail"><?php 
              echo $invoiceData ? htmlspecialchars($invoiceData['Customer_Email']) : '-'; 
          ?></span><br>
          Address: <span id="previewCustomerAddress"><?php 
              echo $invoiceData ? htmlspecialchars($invoiceData['Billing_Address']) : '-'; 
          ?></span>
        </div>
        <div>
          <strong>Payment Details:</strong><br>
          Status: Pending<br>
          Due: Upon receipt<br>
          Method: Bank Transfer
        </div>
      </div>

      <table class="invoice-table">
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
            <td id="previewItem"><?php 
                echo $invoiceData ? htmlspecialchars($invoiceData['Item_Name']) : '-'; 
            ?></td>
            <td>₱<span id="previewPrice"><?php 
                echo $invoiceData ? number_format($invoiceData['Price'], 2) : '0.00'; 
            ?></span></td>
            <td id="previewQty"><?php 
                echo $invoiceData ? htmlspecialchars($invoiceData['Quantity']) : '0'; 
            ?></td>
            <td>₱<span id="previewSubtotal"><?php 
                if ($invoiceData) {
                    echo number_format($invoiceData['Price'] * $invoiceData['Quantity'], 2);
                } else {
                    echo '0.00';
                }
            ?></span></td>
          </tr>
        </tbody>
      </table>

      <div class="totals">
        <!-- VAT Mode Fields -->
        <div id="vatFields" style="display: <?php echo $isVat ? 'block' : 'none'; ?>">
          <div class="totals-row">Subtotal: <span id="subtotal">₱0.00</span></div>
          <div class="totals-row">Discount: <span id="discount">₱0.00</span></div>
          <div class="totals-row">Tax (12%): <span id="tax">₱0.00</span></div>
          <div class="totals-row"><strong>Total: <span id="total">₱0.00</span></strong></div>
        </div>

        <!-- NON-VAT Mode Fields -->
        <div id="nonVatFields" style="display: <?php echo $isVat ? 'none' : 'block'; ?>">
          <div class="non-vat-warning">THIS DOCUMENT IS NOT VALID FOR CLAIM OF INPUT TAX.</div>
          <div class="totals-row">Subtotal: <span id="subtotal_nonvat">₱0.00</span></div>
          <div class="totals-row">Discount: <span id="discount_nonvat">₱0.00</span></div>
          <div class="totals-row">Withholding Tax (3%): <span id="wht_nonvat">₱0.00</span></div>
          <div class="totals-row"><strong>Total: <span id="total_nonvat">₱0.00</span></strong></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="container" style="padding: 20px;">

  <?php if (!$viewId || !$invoiceData): ?>
    <button onclick="openModal()" class="btn-create"> + Create New Invoice</button>
  <?php endif; ?>

  <?php if (isset($_GET['email_sent']) && $_GET['email_sent'] == 1): ?>
    <div class="success-message">Invoice has been sent successfully via email!</div>
  <?php endif; ?>

  <?php if ($viewId && $invoiceData): ?>
    <!-- View Invoice Section -->
    <div class="invoice-view">
      <div class="invoice-actions">
        <button onclick="window.location.href='invoice.php'" class="btn-back">Back to List</button>
        <button onclick="window.location.href='?edit=<?php echo $viewId; ?>'" class="btn-edit">Edit Invoice</button>
        <button onclick="if(confirm('Delete this invoice?')) window.location.href='?delete=<?php echo $viewId; ?>'" class="btn-delete">Delete Invoice</button>
        <button onclick="window.print()" class="btn-print">Print Invoice</button>
        <button onclick="openEmailModal(<?php echo $viewId; ?>, '<?php echo htmlspecialchars($invoiceData['Customer_Email']); ?>')" class="btn-email">Email Invoice</button>
      </div>
      
      <!-- Business Tax Mode Display in view -->
      <div class="business-tax-info">
        <strong>Invoice Tax Mode:</strong> <?php echo htmlspecialchars($invoiceTaxType); ?>
        <span class="invoice-mode-indicator <?php echo $invoiceIsVat ? 'invoice-mode-vat' : 'invoice-mode-nonvat'; ?>">
          <?php echo $invoiceIsVat ? 'VAT' : 'NON-VAT'; ?>
        </span>
      </div>
      
      <div class="invoice-document">
        <div style="display: flex; justify-content: space-between;">
          <div>
            <strong class="company">Stafffy Inc</strong><br>
            54 Irving Street<br>New Asinan<br>Olongapo City, 2200 ZAMBALES<br>PHILIPPINES<br>staffify@gmail.com
          </div>
          <div>
            <p><strong>Invoice #:</strong> <?php echo $invoiceData['Invoice_Id']; ?></p>
            <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($invoiceData['created_at'] ?? 'now')); ?></p>
            <p><strong>Due Date:</strong> <?php echo date('F j, Y', strtotime(($invoiceData['created_at'] ?? 'now') . ' +7 days')); ?></p>
          </div>
        </div>
        <hr>
        <div style="display: flex; justify-content: space-between;">
          <div>
            <strong>Bill To:</strong><br>
            <?php echo htmlspecialchars($invoiceData['Customer_Name']); ?><br>
            Email: <?php echo htmlspecialchars($invoiceData['Customer_Email']); ?><br>
            Address: <?php echo htmlspecialchars($invoiceData['Billing_Address']); ?>
          </div>
          <div>
            <strong>Payment Details:</strong><br>
            Status: Pending<br>
            Due: Upon receipt<br>
            Method: Bank Transfer
          </div>
        </div>

        <table class="invoice-table">
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
              <td>₱<?php echo number_format($invoiceData['Price'] * $invoiceData['Quantity'], 2); ?></td>
            </tr>
          </tbody>
        </table>

        <div class="totals">
          <?php
          // Calculate totals using the stored invoice mode
          $calculations = calculateInvoiceTotals(
              $invoiceData['Price'], 
              $invoiceData['Quantity'], 
              $invoiceData['Discount'], 
              $invoiceData['Tax'], 
              $invoiceIsVat
          );
          ?>

          <!-- VAT Mode Fields -->
          <div id="viewVatFields" style="display: <?php echo $invoiceIsVat ? 'block' : 'none'; ?>">
            <div class="totals-row">Subtotal: ₱<?php echo number_format($calculations['subtotal'], 2); ?></div>
            <div class="totals-row">Discount: ₱<?php echo number_format($calculations['discount'], 2); ?></div>
            <div class="totals-row">Tax (<?php echo $invoiceData['Tax']; ?>%): ₱<?php echo number_format($calculations['tax'], 2); ?></div>
            <div class="totals-row"><strong>Total: ₱<?php echo number_format($calculations['total'], 2); ?></strong></div>
          </div>

          <!-- NON-VAT Mode Fields -->
          <div id="viewNonVatFields" style="display: <?php echo $invoiceIsVat ? 'none' : 'block'; ?>">
            <div class="non-vat-warning">THIS DOCUMENT IS NOT VALID FOR CLAIM OF INPUT TAX.</div>
            <div class="totals-row">Subtotal: ₱<?php echo number_format($calculations['subtotal'], 2); ?></div>
            <div class="totals-row">Discount: ₱<?php echo number_format($calculations['discount'], 2); ?></div>
            <div class="totals-row">Withholding Tax (3%): ₱<?php echo number_format($calculations['tax'], 2); ?></div>
            <div class="totals-row"><strong>Total: ₱<?php echo number_format($calculations['total'], 2); ?></strong></div>
          </div>
        </div>
        
        <?php if (!empty($invoiceData['Terms'])): ?>
          <div class="terms">
            <h4>Terms & Conditions</h4>
            <p><?php echo nl2br(htmlspecialchars($invoiceData['Terms'])); ?></p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>
    <!-- Invoice List Section -->
    <?php
    $sql = "SELECT * FROM invoice ORDER BY Invoice_Id DESC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0): ?>
      <table class="invoices-table">
        <thead>
          <tr>
            <th>Invoice #</th>
            <th>Customer</th>
            <th>Email</th>
            <th>Item</th>
            <th>Price</th>
            <th>Qty</th>
            <th>Total</th>
            <th>Tax Mode</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while($invoice = $result->fetch_assoc()):
            // Determine invoice mode for display
            $listInvoiceIsVat = true;
            $listInvoiceTaxType = 'VAT';
            
            if (isset($invoice['invoice_mode']) && !empty($invoice['invoice_mode'])) {
                $listInvoiceIsVat = ($invoice['invoice_mode'] === 'VAT');
                $listInvoiceTaxType = $invoice['invoice_mode'];
            } elseif (isset($invoice['tax_type_at_creation']) && !empty($invoice['tax_type_at_creation'])) {
                $listInvoiceIsVat = (strpos($invoice['tax_type_at_creation'], 'Non-VAT') === false);
                $listInvoiceTaxType = $listInvoiceIsVat ? 'VAT' : 'NON-VAT';
            }
            
            // Calculate total using the invoice's original mode
            $listCalculations = calculateInvoiceTotals(
                $invoice['Price'], 
                $invoice['Quantity'], 
                $invoice['Discount'], 
                $invoice['Tax'], 
                $listInvoiceIsVat
            );
            
            $date = date('M j, Y', strtotime($invoice['created_at'] ?? 'now'));
          ?>
            <tr>
              <td><?php echo htmlspecialchars($invoice['Invoice_Id']); ?></td>
              <td><?php echo htmlspecialchars($invoice['Customer_Name']); ?></td>
              <td><?php echo htmlspecialchars($invoice['Customer_Email']); ?></td>
              <td><?php echo htmlspecialchars($invoice['Item_Name']); ?></td>
              <td>₱<?php echo number_format($invoice['Price'], 2); ?></td>
              <td><?php echo htmlspecialchars($invoice['Quantity']); ?></td>
              <td>₱<?php echo number_format($listCalculations['total'], 2); ?></td>
              <td>
                <span class="invoice-mode-indicator <?php echo $listInvoiceIsVat ? 'invoice-mode-vat' : 'invoice-mode-nonvat'; ?>">
                  <?php echo $listInvoiceTaxType; ?>
                </span>
              </td>
              <td><?php echo $date; ?></td>
              <td>
                <div class="action-buttons">
                  <button class="action-icon-btn" onclick="window.location.href='?view=<?php echo $invoice['Invoice_Id']; ?>'">
                    <svg class="w-[12px] h-[12px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                      <path stroke="currentColor" stroke-width="2" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/>
                      <path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>
                  </button>
                  <button class="action-icon-btn" onclick="window.location.href='?edit=<?php echo $invoice['Invoice_Id']; ?>'">
                    <svg class="w-[12px] h-[12px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
                    </svg>
                  </button>
                  <button class="action-icon-btn" onclick="if(confirm('Delete this invoice?')) window.location.href='?delete=<?php echo $invoice['Invoice_Id']; ?>'">
                    <svg class="w-[12px] h-[12px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                    </svg>
                  </button>
                  <button class="action-icon-btn" onclick="openEmailModal(<?php echo $invoice['Invoice_Id']; ?>, '<?php echo htmlspecialchars($invoice['Customer_Email']); ?>')">
                    <svg class="w-[12px] h-[12px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                      <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m3.5 5.5 7.893 6.036a1 1 0 0 0 1.214 0L20.5 5.5M4 19h16a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Z"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No invoices found. Create your first invoice.</p>
    <?php endif; ?>
  <?php endif; ?>
</div>

<script>
// Pass PHP values to JavaScript
const businessIsVat = <?php echo $isVat ? 'true' : 'false'; ?>;
const businessTaxType = '<?php echo addslashes($taxType); ?>';

// Function to calculate all values for the preview
function calculatePreview() {
  const price = parseFloat(document.getElementById('price').value) || 0;
  const qty = parseInt(document.getElementById('quantity').value) || 0;
  const subtotal = price * qty;

  // Get the selected tax option
  const taxSelect = document.getElementById('taxOption');
  const selectedTaxOption = taxSelect.options[taxSelect.selectedIndex];
  const taxText = selectedTaxOption.text;
  const taxRate = parseFloat(taxText.match(/\(([^)]+)\)/)[1]) || 0;

  // Get the selected discount option
  const discountSelect = document.getElementById('discountOption');
  const selectedDiscountOption = discountSelect.options[discountSelect.selectedIndex];
  const discountText = selectedDiscountOption.text;
  const discountMatch = discountText.match(/\(([^)]+)\)/);
  const discountValue = discountMatch ? discountMatch[1] : '0';
  
  let discount = 0;
  // Check if discount is percentage or fixed amount
  if (discountValue.includes('%')) {
    discount = subtotal * (parseFloat(discountValue) / 100);
  } else {
    discount = parseFloat(discountValue) || 0;
  }

  const taxableAmount = subtotal - discount;
  
  // Use business settings to determine VAT/NON-VAT mode
  let tax = 0;
  let total = taxableAmount;
  
  if (businessIsVat) {
    // VAT mode calculations
    tax = taxableAmount * (taxRate / 100);
    total = taxableAmount + tax;
  } else {
    // NON-VAT mode calculations (3% withholding tax)
    tax = taxableAmount * 0.03;
    total = taxableAmount - tax;
  }

  // Update all display fields
  document.getElementById('previewSubtotal').textContent = subtotal.toFixed(2);
  
  // VAT fields
  document.getElementById('subtotal').textContent = '₱' + subtotal.toFixed(2);
  document.getElementById('discount').textContent = '₱' + discount.toFixed(2);
  document.getElementById('tax').textContent = '₱' + tax.toFixed(2);
  document.getElementById('total').textContent = '₱' + total.toFixed(2);
  
  // NON-VAT fields
  document.getElementById('subtotal_nonvat').textContent = '₱' + subtotal.toFixed(2);
  document.getElementById('discount_nonvat').textContent = '₱' + discount.toFixed(2);
  document.getElementById('wht_nonvat').textContent = '₱' + tax.toFixed(2);
  document.getElementById('total_nonvat').textContent = '₱' + total.toFixed(2);
  
  // Show/hide appropriate fields based on business mode
  document.getElementById('vatFields').style.display = businessIsVat ? 'block' : 'none';
  document.getElementById('nonVatFields').style.display = businessIsVat ? 'none' : 'block';
}

// Function to update all preview fields
function updatePreview() {
  // Update customer info
  document.getElementById('previewCustomerName').textContent = 
    document.getElementById('customerName').value || "Customer Name";
  document.getElementById('previewCustomerEmail').textContent = 
    document.getElementById('customerEmail').value || "-";
  document.getElementById('previewCustomerAddress').textContent = 
    document.getElementById('billingAddress').value || "-";
  
  // Update item info
  document.getElementById('previewItem').textContent = 
    document.getElementById('itemName').value || "-";
  document.getElementById('previewPrice').textContent = 
    (parseFloat(document.getElementById('price').value) || 0).toFixed(2);
  document.getElementById('previewQty').textContent = 
    document.getElementById('quantity').value || "0";
  
  // Calculate all values
  calculatePreview();
}

// Function to update preview based on select changes
function updatePreviewFromSelect() {
  calculatePreview();
}

// JavaScript functions to handle modal open/close
function openModal() {
  document.getElementById('invoiceModal').style.display = 'flex';
  // Reset preview to current business mode
  calculatePreview();
}

function closeModal() {
  document.getElementById('invoiceModal').style.display = 'none';
  <?php if ($editId): ?>
    window.location.href = 'invoice.php';
  <?php endif; ?>
}

// Function to open email modal
function openEmailModal(invoiceId, email) {
  document.getElementById('email_invoice_id').value = invoiceId;
  document.getElementById('email_recipient').value = email;
  document.getElementById('emailModal').style.display = 'flex';
}

// Initialize when DOM loads
document.addEventListener("DOMContentLoaded", function() {
  // Add event listeners to all input fields
  const inputFields = ['customerName', 'customerEmail', 'billingAddress', 'itemName', 'price', 'quantity'];
  
  inputFields.forEach(function(id) {
    const element = document.getElementById(id);
    if (element) {
      element.addEventListener("input", updatePreview);
    }
  });
  
  // Initialize the preview on page load
  updatePreview();
  
  // Show a message about business tax settings
  console.log('Business Tax Mode:', businessTaxType);
  console.log('VAT Mode:', businessIsVat ? 'Enabled' : 'Disabled');
});

// If we're in edit mode, focus the modal
<?php if ($editId): ?>
  document.getElementById('invoiceModal').style.display = 'flex';
<?php endif; ?>
</script>

<?php
$conn->close();
?>