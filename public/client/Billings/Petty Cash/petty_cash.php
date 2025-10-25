<?php
// Database connection
include('../../config/config.php');  
require __DIR__ . '/../PHPMailer/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/PHPMailer-master/src/SMTP.php';
require __DIR__ . '/../PHPMailer/PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_GET['voucher_id'])) {
    $id = intval($_GET['voucher_id']);
    $stmt = $conn->prepare("SELECT * FROM petty_cash_voucher WHERE voucher_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        echo json_encode(['success' => true, 'voucher' => $res->fetch_assoc()]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// Function to check and create database tables if needed
function checkAndCreateTables($conn) {
    // Create table for expense categories if it doesn't exist
    $createCategoriesTable = "CREATE TABLE IF NOT EXISTS expense_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($createCategoriesTable);

    // Insert default categories if they don't exist
    $checkCategories = $conn->query("SELECT * FROM expense_categories LIMIT 1");
    if ($checkCategories->num_rows == 0) {
        $conn->query("INSERT INTO expense_categories (category_name) VALUES 
            ('Office Supplies'),
            ('Transportation'),
            ('Meals and Entertainment'),
            ('Utilities'),
            ('Postage and Shipping'),
            ('Repairs and Maintenance'),
            ('Miscellaneous')");
    }

    // Create table for petty cash vouchers if it doesn't exist
    $createVoucherTable = "CREATE TABLE IF NOT EXISTS petty_cash_voucher (
        voucher_id INT AUTO_INCREMENT PRIMARY KEY,
        voucher_number VARCHAR(50) NOT NULL,
        date_issued DATE NOT NULL,
        payee_name VARCHAR(255) NOT NULL,
        payee_email VARCHAR(255) NOT NULL,
        contact_number VARCHAR(20) DEFAULT NULL,
        department VARCHAR(100) DEFAULT NULL,
        position VARCHAR(100) DEFAULT NULL,
        purpose TEXT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        category_id INT NOT NULL,
        payment_method VARCHAR(50) DEFAULT 'Cash',
        approved_by VARCHAR(255) DEFAULT NULL,
        received_by VARCHAR(255) DEFAULT NULL,
        receipt_attached BOOLEAN DEFAULT FALSE,
        notes TEXT,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_signed TINYINT(1) DEFAULT 0,
        signature_token VARCHAR(255) DEFAULT NULL,
        signature_date DATETIME DEFAULT NULL,
        signature_ip VARCHAR(45) DEFAULT NULL,
        signature_image LONGTEXT DEFAULT NULL,
        FOREIGN KEY (category_id) REFERENCES expense_categories(id)
    )";
    $conn->query($createVoucherTable);
    
    // Add signature_image column if it doesn't exist
    $checkColumn = $conn->query("SHOW COLUMNS FROM petty_cash_voucher LIKE 'signature_image'");
    if ($checkColumn->num_rows == 0) {
        $conn->query("ALTER TABLE petty_cash_voucher ADD COLUMN signature_image LONGTEXT DEFAULT NULL AFTER signature_ip");
    }
}

function sendVoucherEmail($to, $subject, $bodyText, $htmlBody, $amount, $voucherId) {
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
    } else {
        echo "❌ Failed to send email. Error: " . $mail->ErrorInfo . "<br>";
    }
}

// Generate a new voucher number based on the current date and a sequential number
function generateVoucherNumber($conn) {
    $today = date('Ymd');
    
    // Get the latest voucher number that starts with today's date
    $stmt = $conn->prepare("SELECT voucher_number FROM petty_cash_voucher WHERE voucher_number LIKE ? ORDER BY voucher_id DESC LIMIT 1");
    $pattern = $today . '%';
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastNumber = $row['voucher_number'];
        
        // Extract the sequential part and increment it
        $sequentialPart = intval(substr($lastNumber, 8));
        $newSequentialPart = $sequentialPart + 1;
    } else {
        // No vouchers today yet, start with 1
        $newSequentialPart = 1;
    }
    
    // Format the new voucher number: YYYYMMDD-XXX
    $newVoucherNumber = $today . '-' . str_pad($newSequentialPart, 3, '0', STR_PAD_LEFT);
    
    $stmt->close();
    return $newVoucherNumber;
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check and create database tables if needed
checkAndCreateTables($conn);

// Handle form submission for new category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['new_category'])) {
        $categoryName = $conn->real_escape_string($_POST['category_name']);
        
        $conn->query("INSERT INTO expense_categories (category_name) VALUES ('$categoryName')");
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    if (isset($_POST['send_email'])) {
        $voucherId = intval($_POST['voucher_id']);
        $recipient = $conn->real_escape_string($_POST['email_recipient']);
        $subject = $conn->real_escape_string($_POST['email_subject']);
        $message = $conn->real_escape_string($_POST['email_message']);
        $adminSignature = isset($_POST['admin_signature']) ? 1 : 0;
        $requestSignature = isset($_POST['request_signature']) ? 1 : 0;
        
        // First, generate signature token if needed BEFORE generating the email
        if ($requestSignature) {
            $signatureToken = md5($voucherId . time() . mt_rand(1000, 9999));
            $stmt = $conn->prepare("UPDATE petty_cash_voucher SET signature_token = ? WHERE voucher_id = ?");
            $stmt->bind_param("si", $signatureToken, $voucherId);
            $stmt->execute();
            $stmt->close();
        }
        
        // Generate voucher HTML from your existing view code
        ob_start();
        $result = $conn->query("SELECT pcv.*, ec.category_name FROM petty_cash_voucher pcv 
                               JOIN expense_categories ec ON pcv.category_id = ec.id 
                               WHERE pcv.voucher_id = $voucherId");
        $voucherData = $result->fetch_assoc();
        
        // Pass admin signature status to the template
        $hasAdminSignature = $adminSignature;
        $requestPayeeSignature = $requestSignature;
        
        // Include voucher HTML generation
        if (file_exists('email_voucher_template.php')) {
            include('email_voucher_template.php');
            $voucherHtml = ob_get_clean();
            
            sendVoucherEmail($recipient, $subject, $message, $voucherHtml, $voucherData['amount'], $voucherId);
            
            header("Location: ?view=$voucherId&email_sent=1");
            exit();
        } else {
            ob_end_clean();
            echo "❌ Error: email_voucher_template.php file not found. Please make sure the file exists in the same directory.";
        }
    }

    if (isset($_POST['new_voucher'])) {
        // Create new voucher
        $voucherNumber = generateVoucherNumber($conn);
        $dateIssued = $conn->real_escape_string($_POST['date_issued']);
        $payeeName = $conn->real_escape_string($_POST['payee_name']);
        $payeeEmail = $conn->real_escape_string($_POST['payee_email']);
        $contactNumber = $conn->real_escape_string($_POST['contact_number']);
        $department = $conn->real_escape_string($_POST['department']);
        $position = $conn->real_escape_string($_POST['position']);
        $purpose = $conn->real_escape_string($_POST['purpose']);
        $amount = floatval($_POST['amount']);
        $categoryId = intval($_POST['category_id']);
        $paymentMethod = $conn->real_escape_string($_POST['payment_method']);
        $approvedBy = $conn->real_escape_string($_POST['approved_by']);
        $receivedBy = $conn->real_escape_string($_POST['received_by']);
        $receiptAttached = isset($_POST['receipt_attached']) ? 1 : 0;
        $notes = $conn->real_escape_string($_POST['notes']);
        $status = $conn->real_escape_string($_POST['status']);

        $conn->query("INSERT INTO petty_cash_voucher (
            voucher_number, date_issued, payee_name, payee_email, contact_number, 
            department, position, purpose, amount, category_id, payment_method, 
            approved_by, received_by, receipt_attached, notes, status
        ) VALUES (
            '$voucherNumber', '$dateIssued', '$payeeName', '$payeeEmail', '$contactNumber',
            '$department', '$position', '$purpose', $amount, $categoryId, '$paymentMethod',
            '$approvedBy', '$receivedBy', $receiptAttached, '$notes', '$status'
        )");

        $newId = $conn->insert_id;
        header("Location: ?view=$newId");
        exit();
    } elseif (isset($_POST['edit_voucher'])) {
        // Update existing voucher
        $voucherId = intval($_POST['voucher_id']);
        $dateIssued = $conn->real_escape_string($_POST['date_issued']);
        $payeeName = $conn->real_escape_string($_POST['payee_name']);
        $payeeEmail = $conn->real_escape_string($_POST['payee_email']);
        $contactNumber = $conn->real_escape_string($_POST['contact_number']);
        $department = $conn->real_escape_string($_POST['department']);
        $position = $conn->real_escape_string($_POST['position']);
        $purpose = $conn->real_escape_string($_POST['purpose']);
        $amount = floatval($_POST['amount']);
        $categoryId = intval($_POST['category_id']);
        $paymentMethod = $conn->real_escape_string($_POST['payment_method']);
        $approvedBy = $conn->real_escape_string($_POST['approved_by']);
        $receivedBy = $conn->real_escape_string($_POST['received_by']);
        $receiptAttached = isset($_POST['receipt_attached']) ? 1 : 0;
        $notes = $conn->real_escape_string($_POST['notes']);
        $status = $conn->real_escape_string($_POST['status']);

        $conn->query("UPDATE petty_cash_voucher SET 
            date_issued = '$dateIssued',
            payee_name = '$payeeName',
            payee_email = '$payeeEmail',
            contact_number = '$contactNumber',
            department = '$department',
            position = '$position',
            purpose = '$purpose',
            amount = $amount,
            category_id = $categoryId,
            payment_method = '$paymentMethod',
            approved_by = '$approvedBy',
            received_by = '$receivedBy',
            receipt_attached = $receiptAttached,
            notes = '$notes',
            status = '$status'
            WHERE voucher_id = $voucherId
        ");

        header("Location: ?view=$voucherId");
        exit();
    }
    
    // Handle voucher status update
    if (isset($_POST['update_status'])) {
        $voucherId = intval($_POST['voucher_id']);
        $newStatus = $conn->real_escape_string($_POST['new_status']);
        
        $conn->query("UPDATE petty_cash_voucher SET 
            status = '$newStatus'
            WHERE voucher_id = $voucherId
        ");
        
        header("Location: ?view=$voucherId&status_updated=1");
        exit();
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $voucherId = intval($_GET['delete']);
    $conn->query("DELETE FROM petty_cash_voucher WHERE voucher_id = $voucherId");
    header("Location: petty_cash.php");
    exit();
}

// Check if we should show the modal
$showModal = isset($_GET['show']) ? true : false;

// Check if we're viewing or editing a voucher
$viewId = isset($_GET['view']) ? intval($_GET['view']) : null;
$editId = isset($_GET['edit']) ? intval($_GET['edit']) : null;

// Check if we're showing category modal
$showCategoryModal = isset($_GET['add_category']) ? true : false;

// Fetch voucher data if viewing or editing
$voucherData = null;
if ($viewId || $editId) {
    $id = $viewId ? $viewId : $editId;
    $result = $conn->query("SELECT pcv.*, ec.category_name FROM petty_cash_voucher pcv 
                           JOIN expense_categories ec ON pcv.category_id = ec.id 
                           WHERE pcv.voucher_id = $id");
    if ($result && $result->num_rows > 0) {
        $voucherData = $result->fetch_assoc();
    }
}

// Fetch all expense categories
$categories = $conn->query("SELECT * FROM expense_categories ORDER BY category_name");


include('../../../components/sidebar.php');
// include('logout.php');
?>
<link rel="stylesheet" href="Invoice.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <h1 class="page-title">
              Petty Cash Voucher
            </h1>
<!-- Category Modal -->
<div class="modal-overlay" id="categoryModal" style="<?php echo $showCategoryModal ? 'display: flex;' : 'display: none;' ?>">
  <div class="small-modal">
    <button class="close-modal" onclick="window.location.href='petty_cash.php'">X</button>
    <h2>Add New Expense Category</h2>
    <form method="POST">
      <input type="hidden" name="new_category" value="1">
      <label>Category Name</label>
      <input type="text" name="category_name" required>
      <button type="submit" class="btn-save">Save Category</button>
      <div class="spinner" id="categorySpinner"></div>
    </form>
  </div>
</div>

<!-- Email Modal -->
<div class="modal-overlay" id="emailModal" style="display: none;">
  <div class="email-modal">
    <button class="close-modal" onclick="document.getElementById('emailModal').style.display='none'">X</button>
    <h2>Send Voucher via Email</h2>
    <form method="POST">
      <input type="hidden" name="send_email" value="1">
      <input type="hidden" name="voucher_id" id="email_voucher_id" value="">
      
      <label>Recipient Email</label>
      <input type="email" name="email_recipient" id="email_recipient" required>
      
      <label>Subject</label>
      <input type="text" name="email_subject" value="Petty Cash Voucher from Stafify Inc" required>
      
      <label>Message</label>
      <textarea name="email_message" rows="4" required>Dear colleague,

Please find attached your petty cash voucher. For any queries, please don't hesitate to contact us.

Best regards,
Stafffy Inc</textarea>
      
      <div style="margin-top: 15px;">
        <label>
          <input type="checkbox" name="admin_signature" checked> 
          Include authorized signature in the voucher
        </label>
      </div>
      
      <div style="margin-top: 15px;">
        <label>
          <input type="checkbox" name="request_signature" checked> 
          Request payee to sign this voucher
        </label>
      </div>
      
      <button type="submit" class="btn-save">Send Email</button>
      <div class="spinner" id="emailSpinner"></div>
    </form>
  </div>
</div>

<!-- Status Update Modal -->
<div class="modal-overlay" id="statusModal" style="display: none;">
  <div class="small-modal">
    <button class="close-modal" onclick="document.getElementById('statusModal').style.display='none'">X</button>
    <h2>Update Voucher Status</h2>
    <form method="POST">
      <input type="hidden" name="update_status" value="1">
      <input type="hidden" name="voucher_id" id="status_voucher_id" value="">
      <label>New Status</label>
      <select name="new_status" id="new_status" required>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
      </select>
      <button type="submit" class="btn-save">Update Status</button>
    </form>
  </div>
</div>

<!-- Voucher Modal -->
<div class="modal-overlay" id="voucherModal" style="<?php echo ($showModal || $editId) ? 'display: flex;' : 'display: none;' ?>">
  <div class="invoice-modal">
    <button class="close-modal" onclick="closeModal()">X</button>
    
    <!-- Left: Form -->
    <form method="POST" class="edit-form">
      <h2><?php echo $editId ? 'Edit Voucher' : 'Create Voucher'; ?></h2>
      
      <?php if ($editId): ?>
        <input type="hidden" name="edit_voucher" value="1">
        <input type="hidden" name="voucher_id" value="<?php echo $editId; ?>">
      <?php else: ?>
        <input type="hidden" name="new_voucher" value="1">
      <?php endif; ?>

      <label>Date Issued</label>
      <input type="date" name="date_issued" id="dateIssued" required 
             value="<?php echo $voucherData ? $voucherData['date_issued'] : date('Y-m-d'); ?>">

      <label>Payee Name</label>
      <input type="text" name="payee_name" id="payeeName" required 
             value="<?php echo $voucherData ? htmlspecialchars($voucherData['payee_name']) : ''; ?>">

      <label>Payee Email</label>
      <input type="email" name="payee_email" id="payeeEmail" required
             value="<?php echo $voucherData ? htmlspecialchars($voucherData['payee_email']) : ''; ?>">
             
      <label>Contact Number</label>
      <input type="text" name="contact_number" id="contactNumber"
             value="<?php echo $voucherData ? htmlspecialchars($voucherData['contact_number']) : ''; ?>">

      <div style="display: flex; gap: 10px;">
        <div style="width: 50%;">
          <label>Department</label>
          <input type="text" name="department" id="department"
                 value="<?php echo $voucherData ? htmlspecialchars($voucherData['department']) : ''; ?>">
        </div>
        <div style="width: 50%;">
          <label>Position</label>
          <input type="text" name="position" id="position"
                 value="<?php echo $voucherData ? htmlspecialchars($voucherData['position']) : ''; ?>">
        </div>
      </div>

      <label>Purpose/Description</label>
      <textarea name="purpose" id="purpose" required><?php 
          echo $voucherData ? htmlspecialchars($voucherData['purpose']) : ''; 
      ?></textarea>

      <label>Amount (₱)</label>
      <input type="number" name="amount" id="amount" step="0.01" required
             value="<?php echo $voucherData ? htmlspecialchars($voucherData['amount']) : ''; ?>">

      <div style="display: flex; justify-content: space-between;">
        <div style="width: 100%;">
          <label>Expense Category</label>
          <div style="display: flex;">
            <select name="category_id" id="categoryId" style="width: 85%">
              <?php while ($category = $categories->fetch_assoc()): ?>
                <option value="<?php echo $category['id']; ?>" <?php 
                  if ($voucherData && $voucherData['category_id'] == $category['id']) echo 'selected'; 
                ?>>
                  <?php echo htmlspecialchars($category['category_name']); ?>
                </option>
              <?php endwhile; ?>
            </select>
            <button type="button" class="btn-add" onclick="window.location.href='?add_category=1'" style="width: 15%">+</button>
          </div>
        </div>
      </div>

      <label>Payment Method</label>
      <select name="payment_method" id="paymentMethod">
        <option value="Cash" <?php echo ($voucherData && $voucherData['payment_method'] == 'Cash') ? 'selected' : ''; ?>>Cash</option>
        <option value="Check" <?php echo ($voucherData && $voucherData['payment_method'] == 'Check') ? 'selected' : ''; ?>>Check</option>
        <option value="Bank Transfer" <?php echo ($voucherData && $voucherData['payment_method'] == 'Bank Transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
        <option value="Mobile Payment" <?php echo ($voucherData && $voucherData['payment_method'] == 'Mobile Payment') ? 'selected' : ''; ?>>Mobile Payment</option>
      </select>

      <div style="display: flex; gap: 10px;">
        <div style="width: 50%;">
          <label>Approved By</label>
          <input type="text" name="approved_by" id="approvedBy"
                 value="<?php echo $voucherData ? htmlspecialchars($voucherData['approved_by']) : ''; ?>">
        </div>
        <div style="width: 50%;">
          <label>Received By</label>
          <input type="text" name="received_by" id="receivedBy"
                 value="<?php echo $voucherData ? htmlspecialchars($voucherData['received_by']) : ''; ?>">
        </div>
      </div>

      <div style="margin-top: 15px;">
        <label>
          <input type="checkbox" name="receipt_attached" id="receiptAttached" 
                 <?php echo ($voucherData && $voucherData['receipt_attached']) ? 'checked' : ''; ?>> 
          Receipt Attached
        </label>
      </div>

      <label>Notes</label>
      <textarea name="notes" id="notes"><?php 
          echo $voucherData ? htmlspecialchars($voucherData['notes']) : ''; 
      ?></textarea>

      <label>Status</label>
      <select name="status" id="status">
        <option value="pending" <?php echo (!$voucherData || $voucherData['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
        <option value="approved" <?php echo ($voucherData && $voucherData['status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
        <option value="rejected" <?php echo ($voucherData && $voucherData['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
      </select>

      <!-- Save button inside the form -->
      <div class="modal-actions">
        <button type="submit" class="btn-save"><?php echo $editId ? 'Update Voucher' : 'Save Voucher'; ?></button>
        <button type="button" class="btn-print" onclick="generatePDF()">Print Voucher</button>
      </div>
    </form>

    <!-- Right: Preview -->
    <div class="preview-pane">
      <h2 style="color:#1F5497;">PETTY CASH VOUCHER</h2>
      <div style="display: flex; justify-content: space-between;">
        <div>
          <strong>Stafify Inc</strong><br>
          54 Irving Street<br>New Asinan<br>Olongapo City, 2200 ZAMBALES<br>PHILIPPINES<br>staffify@gmail.com
        </div>
        <div>
          <p><strong>Voucher #:</strong> <span id="previewVoucherNumber"><?php echo $voucherData ? $voucherData['voucher_number'] : 'New'; ?></span></p>
          <p><strong>Date:</strong> <span id="previewDateIssued"><?php echo $voucherData ? date('F j, Y', strtotime($voucherData['date_issued'])) : date('F j, Y'); ?></span></p>
          <p><strong>Status:</strong> <span class="status-<?php echo $voucherData ? $voucherData['status'] : 'pending'; ?>" id="previewStatus"><?php echo $voucherData ? ucfirst($voucherData['status']) : 'Pending'; ?></span></p>
        </div>
      </div>
      <hr>
      
      <div class="voucher-details">
        <div class="detail-row">
          <div class="detail-label">Payee:</div>
          <div class="detail-value" id="previewPayeeName"><?php echo $voucherData ? htmlspecialchars($voucherData['payee_name']) : 'Full Name'; ?></div>
        </div>
        
        <div class="detail-row">
          <div class="detail-label">Department:</div>
          <div class="detail-value" id="previewDepartment"><?php echo $voucherData ? htmlspecialchars($voucherData['department']) : '-'; ?></div>
        </div>
        
        <div class="detail-row">
          <div class="detail-label">Position:</div>
          <div class="detail-value" id="previewPosition"><?php echo $voucherData ? htmlspecialchars($voucherData['position']) : '-'; ?></div>
        </div>
        
        <div class="detail-row">
          <div class="detail-label">Purpose:</div>
          <div class="detail-value" id="previewPurpose"><?php echo $voucherData ? htmlspecialchars($voucherData['purpose']) : '-'; ?></div>
        </div>
        
        <div class="detail-row">
          <div class="detail-label">Amount:</div>
          <div class="detail-value highlight" id="previewAmount">₱<?php echo $voucherData ? number_format($voucherData['amount'], 2) : '0.00'; ?></div>
        </div>
        
        <div class="detail-row">
          <div class="detail-label">Category:</div>
          <div class="detail-value" id="previewCategory"><?php echo $voucherData ? htmlspecialchars($voucherData['category_name']) : '-'; ?></div>
        </div>
        
        <div class="detail-row">
          <div class="detail-label">Payment Method:</div>
          <div class="detail-value" id="previewPaymentMethod"><?php echo $voucherData ? htmlspecialchars($voucherData['payment_method']) : 'Cash'; ?></div>
        </div>
        
        <div class="detail-row">
          <div class="detail-label">Receipt Attached:</div>
          <div class="detail-value" id="previewReceiptAttached"><?php echo ($voucherData && $voucherData['receipt_attached']) ? 'Yes' : 'No'; ?></div>
        </div>
      </div>

      <div class="contact-info" style="margin-top: 20px;">
        <div>
          <strong>Contact Information:</strong><br>
          Email: <span id="previewPayeeEmail"><?php echo $voucherData ? htmlspecialchars($voucherData['payee_email']) : '-'; ?></span><br>
          Phone: <span id="previewContactNumber"><?php echo $voucherData ? htmlspecialchars($voucherData['contact_number']) : '-'; ?></span>
        </div>
      </div>

      <div class="notes" style="margin-top: 20px;">
        <p><strong>Notes:</strong></p>
        <p id="previewNotes"><?php echo $voucherData ? nl2br(htmlspecialchars($voucherData['notes'])) : ''; ?></p>
      </div>

      <div class="signature" style="margin-top: 40px; display: flex; justify-content: space-between;">
        <div style="width: 30%; text-align: center;">
          <div id="previewApprovedBy"><?php echo $voucherData ? htmlspecialchars($voucherData['approved_by']) : ''; ?></div>
          <div style="border-top: 1px solid #000; padding-top: 5px;">Approved By</div>
        </div>
        
        <div style="width: 30%; text-align: center;">
          <div id="previewReceivedBy"><?php echo $voucherData ? htmlspecialchars($voucherData['received_by']) : ''; ?></div>
          <div style="border-top: 1px solid #000; padding-top: 5px;">Received By</div>
        </div>
        
        <div style="width: 30%; text-align: center;">
          <?php if ($voucherData && $voucherData['is_signed']): ?>
            <div class="signed-badge">Electronically Signed</div>
            <?php if($voucherData['signature_image']): ?>
              <img src="<?php echo htmlspecialchars($voucherData['signature_image']); ?>" 
                   alt="Digital Signature" 
                   style="max-width: 180px; max-height: 50px; border: 1px solid #ccc; background: white; margin: 5px 0;">
            <?php endif; ?>
            <div><?php echo htmlspecialchars($voucherData['payee_name']); ?></div>
            <div><small><?php echo date('F j, Y g:i A', strtotime($voucherData['signature_date'])); ?></small></div>
          <?php else: ?>
            <div id="previewSignature">___________________</div>
          <?php endif; ?>
          <div style="border-top: 1px solid #000; padding-top: 5px;">Payee Signature</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="container" style="padding: 20px;">
  <?php if (!$viewId || !$voucherData): ?>
    <button onclick="openModal()" class="btn-create"> + Create New Voucher</button>
  <?php endif; ?>
  

  <?php if (isset($_GET['email_sent']) && $_GET['email_sent'] == 1): ?>
    <div class="success-message">Voucher has been sent successfully via email!</div>
  <?php endif; ?>

  <?php if (isset($_GET['status_updated']) && $_GET['status_updated'] == 1): ?>
    <div class="success-message">Voucher status has been updated successfully!</div>
  <?php endif; ?>

  <?php if ($viewId && $voucherData): ?>
    <!-- View Voucher Section -->
    <div class="invoice-view">
      <div class="invoice-actions">
        <button onclick="window.location.href='petty_cash.php'" class="btn-back">Back to List</button>
        <button onclick="window.location.href='?edit=<?php echo $viewId; ?>'" class="btn-edit">Edit Voucher</button>
        <button onclick="if(confirm('Delete this voucher?')) window.location.href='?delete=<?php echo $viewId; ?>'" class="btn-delete">Delete Voucher</button>
        <button onclick="generatePDF()" class="btn-print">Print Voucher</button>
        <button onclick="openEmailModal(<?php echo $viewId; ?>, '<?php echo htmlspecialchars($voucherData['payee_email']); ?>')" class="btn-email">Email Voucher</button>
        <button onclick="openStatusModal(<?php echo $viewId; ?>, '<?php echo $voucherData['status']; ?>')" class="btn-status">Update Status</button>
      </div>
      
      <div class="invoice-document">
        <h2 style="color:#1F5497;">PETTY CASH VOUCHER</h2>
        <div style="display: flex; justify-content: space-between;">
          <div>
            <strong>Stafify Inc</strong><br>
            54 Irving Street<br>New Asinan<br>Olongapo City, 2200 ZAMBALES<br>PHILIPPINES<br>staffify@gmail.com
          </div>
          <div>
            <p><strong>Voucher #:</strong> <?php echo $voucherData['voucher_number']; ?></p>
            <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($voucherData['date_issued'])); ?></p>
            <p><strong>Status:</strong> <span class="status-<?php echo $voucherData['status']; ?>"><?php echo ucfirst($voucherData['status']); ?></span></p>
            <?php if($voucherData['is_signed']): ?>
            <p class="receipt-signed">✓ Signed by payee on <?php echo date('F j, Y', strtotime($voucherData['signature_date'])); ?></p>
            <?php endif; ?>
          </div>
        </div>
        <hr>
        
        <div class="voucher-details">
          <div class="detail-row">
            <div class="detail-label">Payee:</div>
            <div class="detail-value"><?php echo htmlspecialchars($voucherData['payee_name']); ?></div>
          </div>
          
          <div class="detail-row">
            <div class="detail-label">Department:</div>
            <div class="detail-value"><?php echo htmlspecialchars($voucherData['department'] ?: '-'); ?></div>
          </div>
          
          <div class="detail-row">
            <div class="detail-label">Position:</div>
            <div class="detail-value"><?php echo htmlspecialchars($voucherData['position'] ?: '-'); ?></div>
          </div>
          
          <div class="detail-row">
            <div class="detail-label">Purpose:</div>
            <div class="detail-value"><?php echo htmlspecialchars($voucherData['purpose']); ?></div>
          </div>
          
          <div class="detail-row">
            <div class="detail-label">Amount:</div>
            <div class="detail-value highlight">₱<?php echo number_format($voucherData['amount'], 2); ?></div>
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

        <div class="contact-info" style="margin-top: 20px;">
          <div>
            <strong>Contact Information:</strong><br>
            Email: <?php echo htmlspecialchars($voucherData['payee_email']); ?><br>
            Phone: <?php echo htmlspecialchars($voucherData['contact_number'] ?: '-'); ?>
          </div>
        </div>

        <div class="notes" style="margin-top: 20px;">
          <p><strong>Notes:</strong></p>
          <p><?php echo nl2br(htmlspecialchars($voucherData['notes'])); ?></p>
        </div>

        <div class="signature" style="margin-top: 40px; display: flex; justify-content: space-between;">
          <div style="width: 30%; text-align: center;">
            <div><?php echo htmlspecialchars($voucherData['approved_by'] ?: ''); ?></div>
            <div style="border-top: 1px solid #000; padding-top: 5px;">Approved By</div>
          </div>
          
          <div style="width: 30%; text-align: center;">
            <div><?php echo htmlspecialchars($voucherData['received_by'] ?: ''); ?></div>
            <div style="border-top: 1px solid #000; padding-top: 5px;">Received By</div>
          </div>
          
          <div style="width: 30%; text-align: center;">
            <?php if($voucherData['is_signed']): ?>
              <div class="signed-badge">Electronically Signed</div>
              <?php if($voucherData['signature_image']): ?>
                <img src="<?php echo htmlspecialchars($voucherData['signature_image']); ?>" 
                     alt="Digital Signature" 
                     style="max-width: 200px; max-height: 60px; border: 1px solid #ccc; background: white; margin: 5px 0;">
              <?php endif; ?>
              <div><?php echo htmlspecialchars($voucherData['payee_name']); ?></div>
              <div><small><?php echo date('F j, Y g:i A', strtotime($voucherData['signature_date'])); ?></small></div>
            <?php endif; ?>
            <div style="border-top: 1px solid #000; padding-top: 5px;">Payee Signature</div>
          </div>
        </div>
      </div>
    </div>
  <?php else: ?>
    <!-- Voucher List Section -->
    <?php
    $sql = "SELECT pcv.*, ec.category_name FROM petty_cash_voucher pcv 
            JOIN expense_categories ec ON pcv.category_id = ec.id 
            ORDER BY pcv.voucher_id DESC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0): ?>
      <table class="invoices-table">
        <thead>
          <tr>
            <th>Voucher #</th>
            <th>Date</th>
            <th>Payee</th>
            <th>Department</th>
            <th>Purpose</th>
            <th>Amount</th>
            <th>Category</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while($voucher = $result->fetch_assoc()):
            $date = date('M j, Y', strtotime($voucher['date_issued']));
            $purpose = htmlspecialchars(substr($voucher['purpose'], 0, 30)) . (strlen($voucher['purpose']) > 30 ? '...' : '');
          ?>
            <tr>
              <td><?php echo htmlspecialchars($voucher['voucher_number']); ?></td>
              <td><?php echo $date; ?></td>
              <td><?php echo htmlspecialchars($voucher['payee_name']); ?></td>
              <td><?php echo htmlspecialchars($voucher['department'] ?: '-'); ?></td>
              <td><?php echo $purpose; ?></td>
              <td>₱<?php echo number_format($voucher['amount'], 2); ?></td>
              <td><?php echo htmlspecialchars($voucher['category_name']); ?></td>
              <td>
                <span class="status-<?php echo $voucher['status']; ?>"><?php echo ucfirst($voucher['status']); ?></span>
                <?php if($voucher['is_signed']): ?>
                <span class="badge-signed">✓</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="action-buttons">
                  <button class="action-icon-btn" onclick="window.location.href='?view=<?php echo $voucher['voucher_id']; ?>'">
                    <svg class="w-[12px] h-[12px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                      <path stroke="currentColor" stroke-width="2" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/>
                      <path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>
                  </button>
                  <button class="action-icon-btn" onclick="window.location.href='?edit=<?php echo $voucher['voucher_id']; ?>'">
                    <svg class="w-[12px] h-[12px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
                    </svg>
                  </button>
                  <button class="action-icon-btn" onclick="if(confirm('Delete this voucher?')) window.location.href='?delete=<?php echo $voucher['voucher_id']; ?>'">
                    <svg class="w-[12px] h-[12px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                    </svg>
                  </button>
                  <button class="action-icon-btn" onclick="openEmailModal(<?php echo $voucher['voucher_id']; ?>, '<?php echo htmlspecialchars($voucher['payee_email']); ?>')">
                    <svg class="w-[12px] h-[12px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                      <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m3.5 5.5 7.893 6.036a1 1 0 0 0 1.214 0L20.5 5.5M4 19h16a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Z"/>
                    </svg>
                  </button>
                  <button class="action-icon-btn" onclick="openStatusModal(<?php echo $voucher['voucher_id']; ?>, '<?php echo $voucher['status']; ?>')">
                    <svg class="w-[12px] h-[12px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No vouchers found. Create your first voucher.</p>
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

.btn-status {
  background-color: #6c757d;
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

/* Voucher specific styles */
.voucher-details {
  margin: 20px 0;
}

.detail-row {
  display: flex;
  padding: 8px 0;
  border-bottom: 1px solid #eee;
}

.detail-label {
  width: 30%;
  font-weight: bold;
  color: #555;
}

.detail-value {
  width: 70%;
}

.highlight {
  font-weight: bold;
  color: #1F5497;
}

/* Status indicators */
.status-pending {
  display: inline-block;
  padding: 3px 8px;
  background-color: #FFC107;
  color: #333;
  border-radius: 4px;
  font-size: 0.85em;
}

.status-approved {
  display: inline-block;
  padding: 3px 8px;
  background-color: #4CAF50;
  color: white;
  border-radius: 4px;
  font-size: 0.85em;
}

.status-rejected {
  display: inline-block;
  padding: 3px 8px;
  background-color: #F44336;
  color: white;
  border-radius: 4px;
  font-size: 0.85em;
}

.badge-signed {
  display: inline-block;
  margin-left: 5px;
  color: #4CAF50;
  font-weight: bold;
}

.signed-badge {
  color: #4CAF50;
  font-weight: bold;
  margin-bottom: 5px;
}

.receipt-signed {
  color: #4CAF50;
  font-weight: bold;
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

.action-buttons {
  display: flex;
  gap: 6px;
  margin-right: 29%;
  
}
.action-buttons button{
border-radius:100%;
 box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.action-icon-btn {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: background-color 0.2s;
  
}

.action-icon-btn:hover {
  background-color: #f0f0f0;
  color:black;
}

@media print {
  .btn-back, .btn-edit, .btn-delete, .btn-print, .btn-email, .btn-status,
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

.btn-create {
  background-color: #4A4A4A;
  color: white;
  border: none;
  padding: 12px 24px;
  font-size: 16px;
  border-radius: 5px;
  cursor: pointer;
  position: absolute;
  right:2.5%;
  top: 14% !important;
  transform: translateY(-50%);
}

</style>

<script>
function generatePDF() {
    // Initialize jsPDF
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'pt', 'a4');
    
    // Get the voucher element - try different selectors based on context
    let voucherElement;
    if (document.querySelector('.invoice-document')) {
        // View mode
        voucherElement = document.querySelector('.invoice-document');
    } else if (document.querySelector('.preview-pane')) {
        // Edit mode preview
        voucherElement = document.querySelector('.preview-pane');
    } else {
        alert('Could not find voucher content');
        return;
    }
    
    // Create a clone of the element to avoid affecting the original
    const elementClone = voucherElement.cloneNode(true);
    elementClone.style.width = '700px'; // Set a fixed width that fits A4
    elementClone.style.padding = '20px';
    elementClone.style.boxSizing = 'border-box';
    elementClone.style.backgroundColor = 'white';
    document.body.appendChild(elementClone);
    
    // Options for html2canvas
    const options = {
        scale: 2, // Higher quality
        useCORS: true, // Handle external resources
        allowTaint: true, // Handle tainted canvas
        scrollX: 0,
        scrollY: 0,
        width: elementClone.scrollWidth,
        height: elementClone.scrollHeight,
        windowWidth: elementClone.scrollWidth,
        windowHeight: elementClone.scrollHeight
    };
    
    // Show loading indicator
    const loader = document.createElement('div');
    loader.className = 'pdf-loading';
    loader.innerHTML = '<div class="pdf-loading-spinner"></div>';
    document.body.appendChild(loader);
    loader.style.display = 'flex';
    
    // Convert HTML to canvas then to PDF
    html2canvas(elementClone, options).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const imgWidth = doc.internal.pageSize.getWidth() - 40; // Margin
        const imgHeight = (canvas.height * imgWidth) / canvas.width;
        
        // Center the image on the page
        const xPos = (doc.internal.pageSize.getWidth() - imgWidth) / 2;
        const yPos = 20; // Top margin
        
        doc.addImage(imgData, 'PNG', xPos, yPos, imgWidth, imgHeight);
        doc.save('voucher_' + (new Date().getTime()) + '.pdf');
        
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
window.openModal = function() {
  const modal = document.getElementById("voucherModal");
  if (modal) {
    modal.style.display = "flex";
  }
};

window.closeModal = function() {
  window.location.href = 'petty_cash.php';
};

// Function to open email modal
document.addEventListener("DOMContentLoaded", function () {
  window.openEmailModal = function(voucherId, email) {
    document.getElementById('email_voucher_id').value = voucherId;
    document.getElementById('email_recipient').value = email;
    document.getElementById('emailModal').style.display = 'flex';
  };
  
  // Close email modal function
  window.closeEmailModal = function() {
    document.getElementById('emailModal').style.display = 'none';
  };

  // Function to open status update modal
  window.openStatusModal = function(voucherId, currentStatus) {
    document.getElementById('status_voucher_id').value = voucherId;
    const statusSelect = document.getElementById('new_status');
    if (statusSelect) {
      for (let i = 0; i < statusSelect.options.length; i++) {
        if (statusSelect.options[i].value === currentStatus) {
          statusSelect.selectedIndex = i;
          break;
        }
      }
    }
    document.getElementById('statusModal').style.display = 'flex';
  };
  
  // Close status modal function
  window.closeStatusModal = function() {
    document.getElementById('statusModal').style.display = 'none';
  };

  // Add event listeners to update preview when form fields change
  function updatePreview() {
    // Update date
    const dateIssued = document.getElementById('dateIssued');
    if (dateIssued) {
      const dateObj = new Date(dateIssued.value);
      const options = { year: 'numeric', month: 'long', day: 'numeric' };
      document.getElementById('previewDateIssued').textContent = dateObj.toLocaleDateString('en-US', options);
    }

    // Update payee name
    const payeeName = document.getElementById('payeeName');
    if (payeeName) {
      document.getElementById('previewPayeeName').textContent = payeeName.value || 'Full Name';
    }

    // Update payee email
    const payeeEmail = document.getElementById('payeeEmail');
    if (payeeEmail) {
      document.getElementById('previewPayeeEmail').textContent = payeeEmail.value || '-';
    }

    // Update contact number
    const contactNumber = document.getElementById('contactNumber');
    if (contactNumber) {
      document.getElementById('previewContactNumber').textContent = contactNumber.value || '-';
    }

    // Update department
    const department = document.getElementById('department');
    if (department) {
      document.getElementById('previewDepartment').textContent = department.value || '-';
    }

    // Update position
    const position = document.getElementById('position');
    if (position) {
      document.getElementById('previewPosition').textContent = position.value || '-';
    }

    // Update purpose
    const purpose = document.getElementById('purpose');
    if (purpose) {
      document.getElementById('previewPurpose').textContent = purpose.value || '-';
    }

    // Update amount
    const amount = document.getElementById('amount');
    if (amount) {
      document.getElementById('previewAmount').textContent = '₱' + (parseFloat(amount.value) || 0).toFixed(2);
    }

    // Update category
    const categoryId = document.getElementById('categoryId');
    if (categoryId) {
      document.getElementById('previewCategory').textContent = categoryId.options[categoryId.selectedIndex].text;
    }

    // Update payment method
    const paymentMethod = document.getElementById('paymentMethod');
    if (paymentMethod) {
      document.getElementById('previewPaymentMethod').textContent = paymentMethod.value;
    }

    // Update receipt attached
    const receiptAttached = document.getElementById('receiptAttached');
    if (receiptAttached) {
      document.getElementById('previewReceiptAttached').textContent = receiptAttached.checked ? 'Yes' : 'No';
    }

    // Update notes
    const notes = document.getElementById('notes');
    if (notes) {
      document.getElementById('previewNotes').innerHTML = notes.value.replace(/\n/g, '<br>') || '';
    }

    // Update status
    const status = document.getElementById('status');
    if (status) {
      const statusElem = document.getElementById('previewStatus');
      statusElem.textContent = status.options[status.selectedIndex].text;
      statusElem.className = 'status-' + status.value;
    }

    // Update approved by
    const approvedBy = document.getElementById('approvedBy');
    if (approvedBy) {
      document.getElementById('previewApprovedBy').textContent = approvedBy.value || '';
    }

    // Update received by
    const receivedBy = document.getElementById('receivedBy');
    if (receivedBy) {
      document.getElementById('previewReceivedBy').textContent = receivedBy.value || '';
    }
  }

  // Add event listeners to all form fields
  const formFields = [
    'dateIssued', 'payeeName', 'payeeEmail', 'contactNumber', 'department', 
    'position', 'purpose', 'amount', 'categoryId', 'paymentMethod', 
    'receiptAttached', 'notes', 'status', 'approvedBy', 'receivedBy'
  ];

  formFields.forEach(function(id) {
    const field = document.getElementById(id);
    if (field) {
      field.addEventListener('input', updatePreview);
      field.addEventListener('change', updatePreview);
    }
  });

  // Initialize preview when page loads
  updatePreview();

  // If in edit mode, show the modal
  <?php if (isset($editId) && $editId): ?>
    const modal = document.getElementById('voucherModal');
    if (modal) {
      modal.style.display = 'flex';
    }
  <?php endif; ?>
});
</script>