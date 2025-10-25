<?php
include('../../config/config.php');

require __DIR__ . '/../PHPMailer/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/PHPMailer-master/src/SMTP.php';
require __DIR__ . '/../PHPMailer/PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to check and update database schema if needed
function checkAndUpdateSchema($conn) {
    // Check if contact_number column exists
    $result = $conn->query("SHOW COLUMNS FROM acknowledgment_receipt LIKE 'contact_number'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE acknowledgment_receipt ADD COLUMN contact_number VARCHAR(20) DEFAULT NULL AFTER Customer_Email");
    }
    
    // Check if purpose_type column exists
    $result = $conn->query("SHOW COLUMNS FROM acknowledgment_receipt LIKE 'purpose_type'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE acknowledgment_receipt ADD COLUMN purpose_type ENUM('payment', 'items') DEFAULT 'payment' AFTER Address");
    }
    
    // Check if items_received column exists
    $result = $conn->query("SHOW COLUMNS FROM acknowledgment_receipt LIKE 'items_received'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE acknowledgment_receipt ADD COLUMN items_received TEXT DEFAULT NULL AFTER Payment_For");
    }
    
    // Check if location column exists
    $result = $conn->query("SHOW COLUMNS FROM acknowledgment_receipt LIKE 'location'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE acknowledgment_receipt ADD COLUMN location VARCHAR(255) DEFAULT NULL AFTER items_received");
    }
    
    // Check if payment_status column exists
    $result = $conn->query("SHOW COLUMNS FROM acknowledgment_receipt LIKE 'payment_status'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE acknowledgment_receipt ADD COLUMN payment_status ENUM('full', 'partial' 'down') DEFAULT 'full' AFTER Amount");
    }
}

function sendReceiptEmail($to, $subject, $bodyText, $htmlBody, $amount, $receiptId) {
    $mail = new PHPMailer();
    $mail->SMTPDebug = 0; // Show detailed debug output (set to 0 to hide)
    $mail->Debugoutput = 'html';

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'dimalantajustine8@gmail.com'; // Replace with your Gmail
    $mail->Password = 'qoss jbhb epku kxel';    // Replace with your App Password
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
        
        // Generate a unique signature token
        global $conn;
        $signatureToken = md5($receiptId . time() . mt_rand(1000, 9999));
        
        // Update the database with the signature token
        $stmt = $conn->prepare("UPDATE acknowledgment_receipt SET signature_token = ? WHERE Receipt_Id = ?");
        $stmt->bind_param("si", $signatureToken, $receiptId);
        $stmt->execute();
        $stmt->close();
        
    } else {
        echo "❌ Failed to send email. Error: " . $mail->ErrorInfo . "<br>";
    }
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check and update database schema if needed
checkAndUpdateSchema($conn);

// Create table for payment methods if it doesn't exist
$createPaymentMethodsTable = "CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$conn->query($createPaymentMethodsTable);

// Create table for acknowledgment receipts if it doesn't exist
$createReceiptTable = "CREATE TABLE IF NOT EXISTS acknowledgment_receipt (
    Receipt_Id INT AUTO_INCREMENT PRIMARY KEY,
    Customer_Name VARCHAR(255) NOT NULL,
    Customer_Email VARCHAR(255) NOT NULL,
    contact_number VARCHAR(20) DEFAULT NULL,
    Address VARCHAR(500) NOT NULL,
    purpose_type ENUM('payment', 'items') DEFAULT 'payment',
    Payment_For VARCHAR(500) DEFAULT NULL,
    items_received TEXT DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    Amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('full', 'partial' , 'down') DEFAULT 'full',
    Payment_Method_Id INT NOT NULL,
    Reference_Number VARCHAR(100),
    Notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_signed TINYINT(1) DEFAULT 0,
    signature_token VARCHAR(255) DEFAULT NULL,
    signature_date DATETIME DEFAULT NULL,
    signature_ip VARCHAR(45) DEFAULT NULL,
    FOREIGN KEY (Payment_Method_Id) REFERENCES payment_methods(id)
)";

$conn->query($createReceiptTable);

// Insert default payment methods if they don't exist
$checkPaymentMethods = $conn->query("SELECT * FROM payment_methods LIMIT 1");
if ($checkPaymentMethods->num_rows == 0) {
    $conn->query("INSERT INTO payment_methods (method_name) VALUES 
        ('Cash'),
        ('Check'),
        ('Bank Transfer'),
        ('Credit Card'),
        ('Debit Card'),
        ('Mobile Payment')");
}

// Handle form submission for new payment method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['new_payment_method'])) {
        $methodName = $conn->real_escape_string($_POST['method_name']);
        
        $conn->query("INSERT INTO payment_methods (method_name) VALUES ('$methodName')");
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    if (isset($_POST['send_email'])) {
        $receiptId = intval($_POST['receipt_id']);
        $recipient = $conn->real_escape_string($_POST['email_recipient']);
        $subject = $conn->real_escape_string($_POST['email_subject']);
        $message = $conn->real_escape_string($_POST['email_message']);
        $adminSignature = isset($_POST['admin_signature']) ? 1 : 0;
        $requestSignature = isset($_POST['request_signature']) ? 1 : 0;
        
        // Generate receipt HTML from your existing view code
        ob_start();
        $result = $conn->query("SELECT ar.*, pm.method_name FROM acknowledgment_receipt ar 
                               JOIN payment_methods pm ON ar.Payment_Method_Id = pm.id 
                               WHERE ar.Receipt_Id = $receiptId");
        $receiptData = $result->fetch_assoc();
        
        // Pass admin signature status to the template
        $hasAdminSignature = $adminSignature;
        $requestCustomerSignature = $requestSignature;
        
        // Include receipt HTML generation
        include('email_receipt_template.php');
        $receiptHtml = ob_get_clean();
        
        sendReceiptEmail($recipient, $subject, $message, $receiptHtml, $receiptData['Amount'], $receiptId);
        
        header("Location: ?view=$receiptId&email_sent=1");
        exit();
    }

    if (isset($_POST['new_receipt'])) {
        // Create new receipt
        $customer = $conn->real_escape_string($_POST['customer_name']);
        $email = $conn->real_escape_string($_POST['customer_email']);
        $contact = $conn->real_escape_string($_POST['contact_number']);
        $address = $conn->real_escape_string($_POST['address']);
        $purpose_type = $conn->real_escape_string($_POST['purpose_type']);
        $payment_for = isset($_POST['payment_for']) ? $conn->real_escape_string($_POST['payment_for']) : NULL;
        $items_received = isset($_POST['items_received']) ? $conn->real_escape_string($_POST['items_received']) : NULL;
        $location = $conn->real_escape_string($_POST['location']);
        $amount = floatval($_POST['amount']);
        $payment_status = $conn->real_escape_string($_POST['payment_status']);
        $payment_method = intval($_POST['payment_method']);
        $reference = $conn->real_escape_string($_POST['reference_number']);
        $notes = $conn->real_escape_string($_POST['notes']);

        $conn->query("INSERT INTO acknowledgment_receipt (
            Customer_Name, Customer_Email, contact_number, Address, 
            purpose_type, Payment_For, items_received, location,
            Amount, payment_status, Payment_Method_Id, Reference_Number, Notes
        ) VALUES (
            '$customer', '$email', '$contact', '$address',
            '$purpose_type', " . ($payment_for ? "'$payment_for'" : "NULL") . ", " . ($items_received ? "'$items_received'" : "NULL") . ", '$location',
            $amount, '$payment_status', $payment_method, '$reference', '$notes'
        )");

        $newId = $conn->insert_id;
        header("Location: ?view=$newId");
        exit();
    } elseif (isset($_POST['edit_receipt'])) {
        // Update existing receipt
        $id = intval($_POST['receipt_id']);
        $customer = $conn->real_escape_string($_POST['customer_name']);
        $email = $conn->real_escape_string($_POST['customer_email']);
        $contact = $conn->real_escape_string($_POST['contact_number']);
        $address = $conn->real_escape_string($_POST['address']);
        $purpose_type = $conn->real_escape_string($_POST['purpose_type']);
        $payment_for = isset($_POST['payment_for']) ? $conn->real_escape_string($_POST['payment_for']) : NULL;
        $items_received = isset($_POST['items_received']) ? $conn->real_escape_string($_POST['items_received']) : NULL;
        $location = $conn->real_escape_string($_POST['location']);
        $amount = floatval($_POST['amount']);
        $payment_status = $conn->real_escape_string($_POST['payment_status']);
        $payment_method = intval($_POST['payment_method']);
        $reference = $conn->real_escape_string($_POST['reference_number']);
        $notes = $conn->real_escape_string($_POST['notes']);

        $conn->query("UPDATE acknowledgment_receipt SET 
            Customer_Name = '$customer',
            Customer_Email = '$email',
            contact_number = '$contact',
            Address = '$address',
            purpose_type = '$purpose_type',
            Payment_For = " . ($payment_for ? "'$payment_for'" : "NULL") . ",
            items_received = " . ($items_received ? "'$items_received'" : "NULL") . ",
            location = '$location',
            Amount = $amount,
            payment_status = '$payment_status',
            Payment_Method_Id = $payment_method,
            Reference_Number = '$reference',
            Notes = '$notes'
            WHERE Receipt_Id = $id
        ");

        header("Location: ?view=$id");
        exit();
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM acknowledgment_receipt WHERE Receipt_Id = $id");
    header("Location: acknowledge.php");
    exit();
}

// Get current date for receipt
$currentDate = date('F j, Y');

// Check if we should show the modal
$showModal = isset($_GET['show']) ? true : false;

// Check if we're viewing or editing a receipt
$viewId = isset($_GET['view']) ? intval($_GET['view']) : null;
$editId = isset($_GET['edit']) ? intval($_GET['edit']) : null;

// Check if we're showing payment method modal
$showPaymentMethodModal = isset($_GET['add_payment_method']) ? true : false;

// Fetch receipt data if viewing or editing
$receiptData = null;
if ($viewId || $editId) {
    $id = $viewId ? $viewId : $editId;
    $result = $conn->query("SELECT ar.*, pm.method_name FROM acknowledgment_receipt ar 
                           JOIN payment_methods pm ON ar.Payment_Method_Id = pm.id 
                           WHERE ar.Receipt_Id = $id");
    if ($result && $result->num_rows > 0) {
        $receiptData = $result->fetch_assoc();
    }
}

// Fetch all payment methods
$paymentMethods = $conn->query("SELECT * FROM payment_methods ORDER BY method_name");

include('../../../components/sidebar.php');
?>
<link rel="stylesheet" href="acknowledge.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<div class="modal-overlay" id="paymentMethodModal" style="<?php echo $showPaymentMethodModal ? 'display: flex;' : 'display: none;' ?>">
  <div class="small-modal">
    <button class="close-modal" onclick="window.location.href='acknowledge.php'">X</button>
    <h2>Add New Payment Method</h2>
    <form method="POST">
      <input type="hidden" name="new_payment_method" value="1">
      <label>Method Name</label>
      <input type="text" name="method_name" required>
      <button type="submit" class="btn-save">Save Payment Method</button>
      <div class="spinner" id="emailSpinner"></div>
    </form>
  </div>
</div>

<!-- Email Modal -->
<div class="modal-overlay" id="emailModal" style="display: none;">
  <div class="email-modal">
    <button class="close-modal" onclick="document.getElementById('emailModal').style.display='none'">X</button>
    <h2>Send Receipt via Email</h2>
    <form method="POST">
      <input type="hidden" name="send_email" value="1">
      <input type="hidden" name="receipt_id" id="email_receipt_id" value="">
      
      <label>Recipient Email</label>
      <input type="email" name="email_recipient" id="email_recipient" required>
      
      <label>Subject</label>
      <input type="text" name="email_subject" value="Receipt from Stafffy Inc" required>
      
      <label>Message</label>
      <textarea name="email_message" rows="4" required>Dear customer,

Please find attached your acknowledgment receipt. For any queries, please don't hesitate to contact us.

Best regards,
Stafffy Inc</textarea>
      
      <div style="margin-top: 15px;">
        <label>
          <input type="checkbox" name="admin_signature" checked> 
          Include authorized signature in the receipt
        </label>
      </div>
      
      <div style="margin-top: 15px;">
        <label>
          <input type="checkbox" name="request_signature" checked> 
          Request customer to sign this receipt
        </label>
      </div>
      
      <button type="submit" class="btn-save">Send Email</button>
      <div class="spinner" id="emailSpinner"></div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="receiptModal" style="<?php echo ($showModal || $editId) ? 'display: flex;' : 'display: none;' ?>">
  <div class="invoice-modal">
    <button class="close-modal" onclick="closeModal()">X</button>
    <!-- Left: Form -->
    <form method="POST" class="edit-form">
      <h2><?php echo $editId ? 'Edit Receipt' : 'Create Receipt'; ?></h2>
      
      <?php if ($editId): ?>
        <input type="hidden" name="edit_receipt" value="1">
        <input type="hidden" name="receipt_id" value="<?php echo $editId; ?>">
      <?php else: ?>
        <input type="hidden" name="new_receipt" value="1">
      <?php endif; ?>

      <label>Full Name</label>
      <input type="text" name="customer_name" id="customerName" required 
             value="<?php echo $receiptData ? htmlspecialchars($receiptData['Customer_Name']) : ''; ?>">

      <label>Email</label>
      <input type="email" name="customer_email" id="customerEmail" required
             value="<?php echo $receiptData ? htmlspecialchars($receiptData['Customer_Email']) : ''; ?>">
             
      <label>Contact Number</label>
      <input type="text" name="contact_number" id="contactNumber" required
             value="<?php echo $receiptData ? htmlspecialchars($receiptData['contact_number'] ?? '') : ''; ?>">

      <label>Address</label>
      <textarea name="address" id="address" required><?php 
          echo $receiptData ? htmlspecialchars($receiptData['Address']) : ''; 
      ?></textarea>

      <label>Purpose</label>
      <select name="purpose_type" id="purposeType" onchange="togglePurposeFields()">
        <option value="payment" <?php echo ($receiptData && isset($receiptData['purpose_type']) && $receiptData['purpose_type'] == 'payment') ? 'selected' : ''; ?>>Payment For</option>
        <option value="items" <?php echo ($receiptData && isset($receiptData['purpose_type']) && $receiptData['purpose_type'] == 'items') ? 'selected' : ''; ?>>Received Items</option>
      </select>

      <div id="paymentFields" style="display: <?php echo (!$receiptData || !isset($receiptData['purpose_type']) || $receiptData['purpose_type'] == 'payment') ? 'block' : 'none'; ?>">
        <label>Payment Description</label>
        <textarea name="payment_for" id="paymentFor"><?php 
            echo $receiptData ? htmlspecialchars($receiptData['Payment_For']) : ''; 
        ?></textarea>
      </div>

      <div id="itemsFields" style="display: <?php echo ($receiptData && isset($receiptData['purpose_type']) && $receiptData['purpose_type'] == 'items') ? 'block' : 'none'; ?>">
        <label>Items Received</label>
        <textarea name="items_received" id="itemsReceived"><?php 
            echo $receiptData ? htmlspecialchars($receiptData['items_received'] ?? '') : ''; 
        ?></textarea>
      </div>

      <label>Location</label>
      <input type="text" name="location" id="location" 
             value="<?php echo $receiptData ? htmlspecialchars($receiptData['location'] ?? '') : ''; ?>">

      <label>Amount (₱)</label>
      <div style="display: flex; gap: 10px;">
        <input type="number" name="amount" id="amount" step="0.01" required style="width: 70%"
               value="<?php echo $receiptData ? htmlspecialchars($receiptData['Amount']) : ''; ?>">
        <select name="payment_status" id="paymentStatus" style="width: 30%">
        <option value="down" <?php echo ($receiptData && isset($receiptData['payment_status']) && $receiptData['payment_status'] == 'down') ? 'selected' : ''; ?>>Down Payment</option>
          <option value="partial" <?php echo ($receiptData && isset($receiptData['payment_status']) && $receiptData['payment_status'] == 'partial') ? 'selected' : ''; ?>>Partial Payment</option>
          <option value="full" <?php echo ($receiptData && isset($receiptData['payment_status']) && $receiptData['payment_status'] == 'full') ? 'selected' : ''; ?>>Full Payment</option>
        </select>
      </div>

      <div style="display: flex; justify-content: space-between;">
        <div style="width: 100%;">
          <label>Payment Method</label>
          <div style="display: flex;">
            <select name="payment_method" id="paymentMethod" style="width: 85%" onchange="updatePreview()">
              <?php while ($method = $paymentMethods->fetch_assoc()): ?>
                <option value="<?php echo $method['id']; ?>" <?php 
                  if ($receiptData && $receiptData['Payment_Method_Id'] == $method['id']) echo 'selected'; 
                ?>>
                  <?php echo htmlspecialchars($method['method_name']); ?>
                </option>
              <?php endwhile; ?>
            </select>
            <button type="button" class="btn-add" onclick="window.location.href='?add_payment_method=1'" style="width: 15%">+</button>
          </div>
        </div>
      </div>

      <label>Reference Number (Check/Transaction ID)</label>
      <input type="text" name="reference_number" id="referenceNumber"
             value="<?php echo $receiptData ? htmlspecialchars($receiptData['Reference_Number']) : ''; ?>">

      <label>Notes</label>
      <textarea name="notes" id="notes"><?php 
          echo $receiptData ? htmlspecialchars($receiptData['Notes']) : ''; 
      ?></textarea>

      <!-- Save button inside the form -->
      <div class="modal-actions">
        <button type="submit" class="btn-save"><?php echo $editId ? 'Update Receipt' : 'Save Receipt'; ?></button>
        <button type="button" class="btn-print" onclick="generatePDF()">Print Receipt</button>
      </div>
    </form>

    <!-- Right: Preview -->
    <div class="preview-pane">
      <h2 style="color:#1F5497;">ACKNOWLEDGMENT RECEIPT</h2>
      <div style="display: flex; justify-content: space-between;">
        <div>
          <strong>Stafffy Inc</strong><br>
          54 Irving Street<br>New Asinan<br>Olongapo City, 2200 ZAMBALES<br>PHILIPPINES<br>staffify@gmail.com
        </div>
        <div>
          <p><strong>Receipt #:</strong> <?php echo $receiptData ? $receiptData['Receipt_Id'] : 'New'; ?></p>
          <p><strong>Date:</strong> <?php echo $currentDate; ?></p>
        </div>
      </div>
      <hr>
      
      <div class="receipt-statement">
        <p>I, <span id="previewCustomerName" class="highlight"><?php 
            echo $receiptData ? htmlspecialchars($receiptData['Customer_Name']) : 'Full Name'; 
        ?></span>, 
        <span id="purposeStatementPreview">
          received from Stafffy Inc the amount of ₱<span id="previewAmount" class="highlight"><?php 
            echo $receiptData ? number_format($receiptData['Amount'], 2) : '0.00'; 
          ?></span> 
          <span id="paymentStatusPreview"><?php 
            echo $receiptData && isset($receiptData['payment_status']) && $receiptData['payment_status'] == 'partial' ? '(Partial Payment)' : '(Full Payment)'; 
          ?></span> 
          for <span id="previewPurpose" class="highlight"><?php 
            echo $receiptData ? htmlspecialchars($receiptData['Payment_For'] ?? 'payment purpose') : 'payment purpose'; 
          ?></span>.
        </span>
        <span id="itemsStatementPreview" style="display: <?php echo ($receiptData && isset($receiptData['purpose_type']) && $receiptData['purpose_type'] == 'items') ? 'inline' : 'none'; ?>">
          received from Stafffy Inc the following items: <span id="previewItems" class="highlight"><?php 
            echo $receiptData ? htmlspecialchars($receiptData['items_received'] ?? 'items') : 'items'; 
          ?></span>.
        </span>
        </p>
        
        <p>Done, this <?php echo date('jS \d\a\y \of F Y'); ?>, at <span id="previewLocation" class="highlight"><?php 
            echo $receiptData ? htmlspecialchars($receiptData['location'] ?? 'Olongapo City') : 'Olongapo City'; 
        ?></span>.</p>
      </div>

      <div class="contact-info" style="margin-top: 20px;">
        <div>
          <strong>Contact Information:</strong><br>
          Email: <span id="previewCustomerEmail"><?php 
              echo $receiptData ? htmlspecialchars($receiptData['Customer_Email']) : '-'; 
          ?></span><br>
          Phone: <span id="previewCustomerPhone"><?php 
              echo $receiptData ? htmlspecialchars($receiptData['contact_number'] ?? '-') : '-'; 
          ?></span><br>
          Address: <span id="previewCustomerAddress"><?php 
              echo $receiptData ? htmlspecialchars($receiptData['Address']) : '-'; 
          ?></span>
        </div>
      </div>
      
      <div class="receipt-details" style="margin-top: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
        <p><strong>Payment Method:</strong> <span id="previewPaymentMethod"><?php 
            echo $receiptData ? htmlspecialchars($receiptData['method_name']) : '-'; 
        ?></span></p>
        
        <p><strong>Reference Number:</strong> <span id="previewReferenceNumber"><?php 
            echo $receiptData ? htmlspecialchars($receiptData['Reference_Number']) : '-'; 
        ?></span></p>
      </div>

      <div class="notes" style="margin-top: 20px;">
        <p><strong>Notes:</strong></p>
        <p id="previewNotes"><?php 
            echo $receiptData ? nl2br(htmlspecialchars($receiptData['Notes'])) : ''; 
        ?></p>
      </div>

      <div class="signature" style="margin-top: 40px; display: flex; justify-content: space-between;">
        <div style="width: 45%; text-align: center;">
          <div style="border-top: 1px solid #000; padding-top: 5px;">Customer Signature</div>
        </div>
        <div style="width: 45%; text-align: center;">
          <div style="border-top: 1px solid #000; padding-top: 5px;">Authorized Signature</div>
        </div>
      </div>
    </div>
 

  </div>
</div>
  <h1 class="page-title">
             Acknowledgment Receipts
            </h1>
<div class="container" style="padding: 20px;">

 <?php if (!$viewId || !$receiptData): ?>
  <button onclick="openModal()" class="btn-create"> + Create New Receipt</button>
<?php endif; ?>
  


  <?php if (isset($_GET['email_sent']) && $_GET['email_sent'] == 1): ?>
    <div class="success-message">Receipt has been sent successfully via email!</div>
  <?php endif; ?>

  <?php if ($viewId && $receiptData): ?>
    <!-- View Receipt Section -->
    <div class="invoice-view">
      <div class="invoice-actions">
        <button onclick="window.location.href='acknowledge.php'" class="btn-back">Back to List</button>
        <button onclick="window.location.href='?edit=<?php echo $viewId; ?>'" class="btn-edit">Edit Receipt</button>
        <button onclick="if(confirm('Delete this receipt?')) window.location.href='?delete=<?php echo $viewId; ?>'" class="btn-delete">Delete Receipt</button>
         <button type="button" class="btn-print" onclick="generatePDF()">Print Receipt</button>
        <button onclick="openEmailModal(<?php echo $viewId; ?>, '<?php echo htmlspecialchars($receiptData['Customer_Email']); ?>')" class="btn-email">Email Receipt</button>
      </div>
      
      <div class="invoice-document">
        <h2 style="color:#1F5497;">ACKNOWLEDGMENT RECEIPT</h2>
        <div style="display: flex; justify-content: space-between;">
          <div>
            <strong>Stafffy Inc</strong><br>
            54 Irving Street<br>New Asinan<br>Olongapo City, 2200 ZAMBALES<br>PHILIPPINES<br>staffify@gmail.com
          </div>
          <div>
            <p><strong>Receipt #:</strong> <?php echo $receiptData['Receipt_Id']; ?></p>
            <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($receiptData['created_at'] ?? 'now')); ?></p>
            <?php if($receiptData['is_signed']): ?>
            <p class="receipt-signed">✓ Signed by customer on <?php echo date('F j, Y', strtotime($receiptData['signature_date'])); ?></p>
            <?php endif; ?>
          </div>
        </div>
        <hr>
        
        <div class="receipt-statement">
          <p>I, <strong><?php echo htmlspecialchars($receiptData['Customer_Name']); ?></strong>, 
          <?php if(!isset($receiptData['purpose_type']) || $receiptData['purpose_type'] == 'payment'): ?>
            received from Stafffy Inc the amount of <strong>₱<?php echo number_format($receiptData['Amount'], 2); ?></strong>
            <?php echo (isset($receiptData['payment_status']) && $receiptData['payment_status'] == 'partial') ? '(Partial Payment)' : '(Full Payment)'; ?>
            for <strong><?php echo htmlspecialchars($receiptData['Payment_For'] ?? ''); ?></strong>.
          <?php else: ?>
            received from Stafffy Inc the following items: <strong><?php echo htmlspecialchars($receiptData['items_received'] ?? ''); ?></strong>.
          <?php endif; ?>
          </p>
          
          <p>Done, this <?php echo date('jS \d\a\y \of F Y', strtotime($receiptData['created_at'] ?? 'now')); ?>, at <strong><?php echo htmlspecialchars($receiptData['location'] ?? 'Olongapo City'); ?></strong>.</p>
        </div>

        <div class="contact-info" style="margin-top: 20px;">
          <div>
            <strong>Contact Information:</strong><br>
            Email: <?php echo htmlspecialchars($receiptData['Customer_Email']); ?><br>
            Phone: <?php echo htmlspecialchars($receiptData['contact_number'] ?? '-'); ?><br>
            Address: <?php echo htmlspecialchars($receiptData['Address']); ?>
          </div>
        </div>

        <div class="receipt-details" style="margin-top: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
          <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($receiptData['method_name']); ?></p>
          
          <p><strong>Reference Number:</strong> <?php echo htmlspecialchars($receiptData['Reference_Number']); ?></p>
        </div>

        <div class="notes" style="margin-top: 20px;">
          <p><strong>Notes:</strong></p>
          <p><?php echo nl2br(htmlspecialchars($receiptData['Notes'])); ?></p>
        </div>

        <div class="signature" style="margin-top: 40px; display: flex; justify-content: space-between;">
  <div style="width: 45%; text-align: center;">
    <?php if(isset($receiptData['is_signed']) && $receiptData['is_signed']): ?>
      <div class="customer-signature">
        <?php if(isset($receiptData['signature_image']) && !empty($receiptData['signature_image'])): ?>
          <!-- Display the actual signature image -->
          <img src="<?php echo $receiptData['signature_image']; ?>" alt="Customer Signature" style="max-width: 100%; height: 60px; margin-bottom: 5px;">
        <?php else: ?>
          <!-- Text fallback if no image available -->
          <span style="color: #4CAF50; font-weight: bold;">✓ Electronically Signed</span>
        <?php endif; ?>
        <div style="font-size: 12px; color: #666;">
          <?php if(isset($receiptData['signature_date'])): ?>
            Signed on <?php echo date('F j, Y g:i A', strtotime($receiptData['signature_date'])); ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
    <div style="border-top: 1px solid #000; padding-top: 5px;">Customer Signature</div>
  </div>
  <div style="width: 45%; text-align: center;">
    <?php if(isset($hasAdminSignature) && $hasAdminSignature): ?>
      <div style="margin-bottom: 5px;">
        <span style="color: #1F5497; font-weight: bold;">✓ Digitally Signed</span>
      </div>
    <?php endif; ?>
    <div style="border-top: 1px solid #000; padding-top: 5px;">Authorized Signature</div>
  </div>
</div>
  <?php else: ?>
    <!-- Receipt List Section -->
    <?php
    // Fetch existing receipts
    $sql = "SELECT ar.*, pm.method_name FROM acknowledgment_receipt ar 
            JOIN payment_methods pm ON ar.Payment_Method_Id = pm.id 
            ORDER BY ar.Receipt_Id DESC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0): ?>
      <table class="invoices-table">
        <thead>
          <tr>
            <th>Receipt #</th>
            <th>Full Name</th>
            <th>Contact</th>
            <th>Purpose</th>
            <th>Amount</th>
            <th>Payment</th>
            <th>Location</th>
            <th>Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while($receipt = $result->fetch_assoc()):
            $date = date('M j, Y', strtotime($receipt['created_at'] ?? 'now'));
            $purpose = '';
            if (isset($receipt['purpose_type']) && $receipt['purpose_type'] == 'items') {
                $purpose = 'Items: ' . htmlspecialchars(substr($receipt['items_received'] ?? '', 0, 30)) . (strlen($receipt['items_received'] ?? '') > 30 ? '...' : '');
            } else {
                $purpose = 'Payment: ' . htmlspecialchars(substr($receipt['Payment_For'] ?? '', 0, 30)) . (strlen($receipt['Payment_For'] ?? '') > 30 ? '...' : '');
            }
          ?>
            <tr>
              <td><?php echo htmlspecialchars($receipt['Receipt_Id']); ?></td>
              <td><?php echo htmlspecialchars($receipt['Customer_Name']); ?></td>
              <td><?php echo htmlspecialchars($receipt['contact_number'] ?? 'N/A'); ?></td>
              <td><?php echo $purpose; ?></td>
              <td>₱<?php echo number_format($receipt['Amount'], 2); ?></td>
              <td>
  <?php 
    echo isset($receipt['payment_status']) 
      ? ($receipt['payment_status'] == 'partial' 
          ? 'Partial' 
          : ($receipt['payment_status'] == 'down' 
              ? 'Down' 
              : 'Full')) 
      : '-'; 
  ?>
</td>
              <td><?php echo htmlspecialchars($receipt['location'] ?? 'N/A'); ?></td>
              <td><?php echo $date; ?></td>
              <td>
              <?php if(isset($receipt['is_signed']) && $receipt['is_signed']): ?>
                <span class="status-signed">Signed</span>
                <?php else: ?>
                <span class="status-pending">Pending</span>
                <?php endif; ?>
              </td>
              <td>
              <div class="action-buttons">
              <button class="action-icon-btn" onclick="window.location.href='?view=<?php echo $receipt['Receipt_Id']; ?>'">
              <svg class="w-[12px] h-[12px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
  <path stroke="currentColor" stroke-width="2" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/>
  <path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
</svg>


                </button>
                <button class="action-icon-btn" onclick="window.location.href='?edit=<?php echo $receipt['Receipt_Id']; ?>'">
                <svg class="w-[12px] h-[12px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
</svg>

                </button>
                <button class="action-icon-btn" onclick="if(confirm('Delete this receipt?')) window.location.href='?delete=<?php echo $receipt['Receipt_Id']; ?>'">
                <svg class="w-[12px] h-[12px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
</svg>

                </button>
                <button class="action-icon-btn" onclick="openEmailModal(<?php echo $receipt['Receipt_Id']; ?>, '<?php echo htmlspecialchars($receipt['Customer_Email']); ?>')">
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
      <p>No receipts found. Create your first receipt.</p>
    <?php endif; ?>
  <?php endif; ?>
</div>

<style>
.btn-email {
  background-color: #4A4A4A;
  color: white;
  border: none;
  padding: 8px 16px;
  cursor: pointer;
  border-radius: 4px;
  margin-left: 10px;
}

.email {
  background-color: #4A4A4A;
  color: white;
  border: none;
  padding: 5px 8px;
  cursor: pointer;
  border-radius: 4px;
  margin-left: 2px;
}

.btn-add {
  background-color: #4CAF50;
  color: white;
  border: none;
  padding: 8px;
  cursor: pointer;
  border-radius: 4px;
}

.small-modal {
  background: white;
  padding: 20px 25px;
  border-radius: 12px;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
  width: 100%;
  max-width: 450px;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
  animation: modalFadeIn 0.3s ease-out;
  font-family: Arial, sans-serif;
}

.small-modal h3 {
  margin-top: 0;
  color: #1F5497;
  font-size: 20px;
  font-family:
}

.small-modal label {
  display: block;
  margin: 12px 0 6px;
  font-weight: bold;
  color: #333;
}

.small-modal input[type="text"],
.small-modal input[type="email"],
.small-modal textarea,
.small-modal select {
  width: 100%;
  padding: 8px 10px;
  border: 1px solid #ccc;
  border-radius: 6px;
  font-size: 14px;
  box-sizing: border-box;
}

.small-modal .btn-group {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.small-modal .btn {
  padding: 10px 18px;
  border: none;
  border-radius: 6px;
  font-weight: bold;
  cursor: pointer;
  transition: background-color 0.2s;
}

.small-modal .btn-primary {
  background-color: #1F5497;
  color: white;
}

.small-modal .btn-primary:hover {
  background-color: #153e6b;
}

.small-modal .btn-secondary {
  background-color: #eee;
  color: #333;
}

.small-modal .btn-secondary:hover {
  background-color: #ddd;
}

.close-small {
  position: absolute;
  top: 12px;
  right: 14px;
  background: transparent;
  border: none;
  font-size: 16px;
  color: #888;
  cursor: pointer;
}

.close-small:hover {
  color: #000;
}
.email-modal {
  background: white;
  padding: 30px;
  border-radius: var(--border-radius);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  max-width: 600px;
  width: 95%;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
  animation: modalFadeIn 0.3s ease-out;
}

.email-modal h2 {
  margin-top: 0;
  color: var(--primary-color);
}

.email-modal label {
  display: block;
  margin-top: 15px;
  margin-bottom: 5px;
  font-weight: bold;
  color: #333;
}

.email-modal input[type="text"],
.email-modal input[type="email"],
.email-modal textarea {
  width: 100%;
  padding: 10px 12px;
  border-radius: 6px;
  border: 1px solid #ccc;
  font-size: 14px;
  box-sizing: border-box;
}

.email-modal input[type="checkbox"] {
  margin-right: 8px;
}

.email-modal .btn-save {
  margin-top: 20px;
  background-color: var(--primary-color);
  color: white;
  padding: 12px 20px;
  border: none;
  border-radius: 8px;
  font-weight: bold;
  cursor: pointer;
  width: 100%;
  transition: background-color 0.3s;
}

.email-modal .btn-save:hover {
  background-color: #163f73;
}

.close-modal {
  position: absolute;
  top: 15px;
  right: 15px;
  background: transparent;
  border: none;
  font-size: 18px;
  font-weight: bold;
  cursor: pointer;
  color: #888;
}

.close-modal:hover {
  color: #000;
}

@keyframes modalFadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}


.email-modal .spinner {
  display: none;
  width: 40px;
  height: 40px;
  margin: 20px auto 0;
  border: 4px solid #ccc;
  border-top-color: var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
.success-message {
  background-color: #d4edda;
  color: #155724;
  padding: 10px;
  margin: 10px 0;
  border-radius: 4px;
  border: 1px solid #c3e6cb;
}

/* Make the scrollable area specific to the invoice modal */
.invoice-modal {
  max-height: 90vh;
  overflow-y: auto;
}

/* Receipt specific styles */
.receipt-details {
  background-color: #f9f9f9;
  margin-top: 20px;
  padding: 15px;
  border: 1px solid #ddd;
  border-radius: 5px;
}

/* Navigation button container */
.button-container {
  display: flex;
  margin-top: 15px;
  gap: 15px;
}

/* Styled navigation buttons */
.nav-button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 10px 20px;
  border-radius: 5px;
  border: none;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.customers-btn {
  background-color:#4A4A4A;
  color: white;
}

.customers-btn:hover {
  background-color: #A1A1A1;
}

.invoices-btn {
  background-color:#4A4A4A;
  color: white;
}

.invoices-btn:hover {
  background-color:#A1A1A1;
}

/* Receipt statement styling */
.receipt-statement {
  line-height: 1.6;
  margin: 25px 0;
  padding: 15px;
  background: #f9f9f9;
  border-radius: 5px;
  border-left: 4px solid #1F5497;
}

.highlight {
  font-weight: bold;
  color: #1F5497;
}

/* Status indicators */
.status-signed {
  display: inline-block;
  padding: 3px 8px;
  background-color: #4CAF50;
  color: white;
  border-radius: 4px;
  font-size: 0.85em;
}

.status-pending {
  display: inline-block;
  padding: 3px 8px;
  background-color: #FFC107;
  color: #333;
  border-radius: 4px;
  font-size: 0.85em;
}

.receipt-signed {
  color: #4CAF50;
  font-weight: bold;
}

.customer-signature {
  color: #4CAF50;
  font-weight: bold;
  margin-bottom: 5px;
}

@media print {
  .btn-back, .btn-edit, .btn-delete, .btn-print, .btn-email, 
  .edit-form, .modal-actions, button, .container > h1, .container > button,
  .button-container {
    display: none !important;
  }
  

  .invoice-document {
    width: 100% !important;
    box-shadow: none !important;
    border: none !important;
  }
  
  .container {
    margin: 0 !important;
    padding: 0 !important;
  }
  
  .invoice-view {
    padding: 0 !important;
  }
}

.spinner {
  display: none;
  width: 40px;
  height: 40px;
  margin: 20px auto 0;
  border: 4px solid #ccc;
  border-top-color: #1F5497;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}

</style>

<script>
// Make modal functions globally available
window.openModal = function() {
  const modal = document.getElementById("receiptModal");
  if (modal) {
    modal.style.display = "flex";
  }
};

window.closeModal = function() {
  const modal = document.getElementById('receiptModal');
  if (modal) {
    modal.style.display = 'none';
  }
  <?php if ($editId): ?>
    window.location.href = 'acknowledge.php';
  <?php endif; ?>
};

function generatePDF() {
    // Show loading indicator
    const loader = document.createElement('div');
    loader.className = 'pdf-loading';
    loader.innerHTML = '<div class="pdf-loading-spinner"></div>';
    document.body.appendChild(loader);
    loader.style.display = 'flex';

    // Initialize jsPDF with better page size options
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: 'a4'
    });

    // Get the receipt element
    let receiptElement;
    if (document.querySelector('.invoice-document')) {
        receiptElement = document.querySelector('.invoice-document');
    } else if (document.querySelector('.preview-pane')) {
        receiptElement = document.querySelector('.preview-pane');
    } else {
        alert('Could not find receipt content');
        loader.remove();
        return;
    }

    // Clone the element to avoid affecting the original
    const elementClone = receiptElement.cloneNode(true);
    elementClone.style.width = '190mm'; // Set a fixed width that fits A4
    elementClone.style.padding = '20px';
    elementClone.style.boxSizing = 'border-box';
    elementClone.style.backgroundColor = 'white';
    document.body.appendChild(elementClone);

    // Options for html2canvas with better scaling
    const options = {
        scale: 2, // Higher quality
        useCORS: true,
        allowTaint: true,
        scrollX: 0,
        scrollY: 0,
        width: elementClone.scrollWidth,
        height: elementClone.scrollHeight,
        windowWidth: elementClone.scrollWidth,
        windowHeight: elementClone.scrollHeight
    };

    // Convert HTML to canvas then to PDF
    html2canvas(elementClone, options).then(canvas => {
        // Calculate dimensions to fit A4 page with margins
        const pageWidth = doc.internal.pageSize.getWidth() - 20; // 10mm margins on each side
        const pageHeight = doc.internal.pageSize.getHeight() - 20;
        
        // Calculate aspect ratio
        const imgWidth = canvas.width;
        const imgHeight = canvas.height;
        const ratio = imgWidth / imgHeight;
        
        // Calculate final dimensions to fit page
        let finalWidth = pageWidth;
        let finalHeight = pageWidth / ratio;
        
        // If still too tall, scale down to fit height
        if (finalHeight > pageHeight) {
            finalHeight = pageHeight;
            finalWidth = pageHeight * ratio;
        }
        
        // Add image to PDF
        const xPos = (doc.internal.pageSize.getWidth() - finalWidth) / 2;
        const yPos = (doc.internal.pageSize.getHeight() - finalHeight) / 2;
        
        doc.addImage(canvas, 'PNG', xPos, yPos, finalWidth, finalHeight);
        
        // Save the PDF
        doc.save('receipt_' + (new Date().getTime()) + '.pdf');
        
        // Clean up
        elementClone.remove();
        loader.remove();
    }).catch(err => {
        console.error('Error generating PDF:', err);
        alert('Error generating PDF. Please try again.');
        elementClone.remove();
        loader.remove();
    });
}
// Function to open email modal
document.addEventListener("DOMContentLoaded", function () {
  window.openEmailModal = function(receiptId, email) {
    document.getElementById('email_receipt_id').value = receiptId;
    document.getElementById('email_recipient').value = email;
    document.getElementById('emailModal').style.display = 'flex';
  };
  
  // Close email modal function
  window.closeEmailModal = function() {
    document.getElementById('emailModal').style.display = 'none';
  };
});

// Function to toggle between payment and items fields
window.togglePurposeFields = function() {
  const purposeType = document.getElementById('purposeType').value;
  const paymentFields = document.getElementById('paymentFields');
  const itemsFields = document.getElementById('itemsFields');
  const purposeStatementPreview = document.getElementById('purposeStatementPreview');
  const itemsStatementPreview = document.getElementById('itemsStatementPreview');
  
  if (purposeType === 'payment') {
    paymentFields.style.display = 'block';
    itemsFields.style.display = 'none';
    purposeStatementPreview.style.display = 'inline';
    itemsStatementPreview.style.display = 'none';
  } else {
    paymentFields.style.display = 'none';
    itemsFields.style.display = 'block';
    purposeStatementPreview.style.display = 'none';
    itemsStatementPreview.style.display = 'inline';
  }
  
  updatePreview();
};

// Function to update all preview fields
function updatePreview() {
  // Update customer info
  document.getElementById('previewCustomerName').textContent = 
    document.getElementById('customerName').value || "Full Name";
  document.getElementById('previewCustomerEmail').textContent = 
    document.getElementById('customerEmail').value || "-";
  document.getElementById('previewCustomerPhone').textContent = 
    document.getElementById('contactNumber').value || "-";
  document.getElementById('previewCustomerAddress').textContent = 
    document.getElementById('address').value || "-";
  
  // Update purpose based on selection
  const purposeType = document.getElementById('purposeType').value;
  
  if (purposeType === 'payment') {
    document.getElementById('previewPurpose').textContent = 
      document.getElementById('paymentFor').value || "payment purpose";
  } else {
    document.getElementById('previewItems').textContent = 
      document.getElementById('itemsReceived').value || "items";
  }
  
  // Update location
  document.getElementById('previewLocation').textContent = 
    document.getElementById('location').value || "Olongapo City";
  
  // Update payment info
  document.getElementById('previewAmount').textContent = 
    (parseFloat(document.getElementById('amount').value) || 0).toFixed(2);
  
  // Update payment status
  const paymentStatus = document.getElementById('paymentStatus').value;
  document.getElementById('paymentStatusPreview').textContent = 
    paymentStatus === 'partial' ? '(Partial Payment)' : 
    paymentStatus === 'full' ? '(Full Payment)' : '(Down Payment)';
  
  // Update payment method
  const paymentMethodSelect = document.getElementById('paymentMethod');
  const selectedPaymentMethod = paymentMethodSelect.options[paymentMethodSelect.selectedIndex].text;
  document.getElementById('previewPaymentMethod').textContent = selectedPaymentMethod;
  
  // Update reference number
  document.getElementById('previewReferenceNumber').textContent = 
    document.getElementById('referenceNumber').value || "-";
  
  // Update notes
  document.getElementById('previewNotes').innerHTML = 
    document.getElementById('notes').value.replace(/\n/g, '<br>') || "";
}

// Add event listeners to all input fields
document.addEventListener("DOMContentLoaded", function() {
  ['customerName', 'customerEmail', 'contactNumber', 'address', 'purposeType', 'paymentFor', 'itemsReceived', 
   'location', 'amount', 'paymentStatus', 'paymentMethod', 'referenceNumber', 'notes'].forEach(function(id) {
    const element = document.getElementById(id);
    if (element) {
      element.addEventListener('input', updatePreview);
      element.addEventListener('change', updatePreview);
    }
  });

  // Initialize the preview on page load
  if (document.getElementById('purposeType')) {
    togglePurposeFields();
    updatePreview();
  }
  
  // Add event listener for email form submission
  const emailForm = document.querySelector(".email-modal form");
  if (emailForm) {
    emailForm.addEventListener("submit", function () {
      const btn = document.getElementById("sendEmailBtn");
      const spinner = document.getElementById("emailSpinner");
      
      if (btn) btn.style.display = "none";
      if (spinner) spinner.style.display = "block";
    });
  }
});

// If we're in edit mode, focus the modal
<?php if (isset($editId) && $editId): ?>
  document.addEventListener("DOMContentLoaded", function() {
    const modal = document.getElementById('receiptModal');
    if (modal) {
      modal.style.display = 'flex';
    }
  });
<?php endif; ?>
</script>