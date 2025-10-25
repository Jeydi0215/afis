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

// Connect to database
$mysqli = new mysqli('localhost', 'root', '', 'afis');
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}

// Initialize variables
$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$receiptData = null;

// Process token submission form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_token'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Session expired. Please refresh the page.";
    } else {
        $submitted_token = trim($_POST['token'] ?? '');
        
        if (empty($submitted_token)) {
            $error = "Please enter your token.";
        } else {
            // Look up the token in the database
            $stmt = $mysqli->prepare("SELECT * FROM acknowledgment_receipt WHERE signature_token = ? AND is_signed = 0");
            $stmt->bind_param("s", $submitted_token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $error = "Invalid or expired token. Please check and try again.";
            } else {
                $receiptData = $result->fetch_assoc();
                $token = $submitted_token; // Set token for later use
            }
        }
    }
}

// Process signature submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_signature'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Session expired. Please refresh the page.";
    } else {
        $submitted_token = trim($_POST['signature_token'] ?? '');
        $signature_data = $_POST['signature_data'] ?? '';
        $signature_type = $_POST['signature_type'] ?? 'text';
        
        if (empty($submitted_token)) {
            $error = "Token is missing. Please try again.";
        } elseif (empty($signature_data) && $signature_type === 'draw') {
            $error = "Please provide a signature.";
        } else {
            // Verify token is valid
            $stmt = $mysqli->prepare("SELECT Receipt_Id, Customer_Name FROM acknowledgment_receipt WHERE signature_token = ? AND is_signed = 0");
            $stmt->bind_param("s", $submitted_token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $error = "Invalid or expired token. Please check and try again.";
            } else {
                $receipt_data = $result->fetch_assoc();
                
                // Generate signature data based on type
                if ($signature_type === 'text') {
                    // Create a text-based signature
                    $name = $receipt_data['Customer_Name'];
                    $date = date('Y-m-d H:i:s');
                    $signature_data = "data:image/svg+xml;base64," . base64_encode("<svg xmlns='http://www.w3.org/2000/svg' width='300' height='100'><text x='10' y='50' font-family='cursive' font-size='24' fill='blue'>{$name}</text><text x='10' y='80' font-family='sans-serif' font-size='12' fill='gray'>Signed: {$date}</text></svg>");
                }
                
                // Update receipt with signature
                $update_stmt = $mysqli->prepare("UPDATE acknowledgment_receipt SET is_signed = 1, signature_image = ?, signature_date = NOW() WHERE signature_token = ?");
                $update_stmt->bind_param("ss", $signature_data, $submitted_token);
                
                if ($update_stmt->execute()) {
                    $success = "Receipt #{$receipt_data['Receipt_Id']} has been successfully signed!";
                    // Clear token variable to show success screen
                    $token = '';
                    $receiptData = null;
                } else {
                    $error = "Failed to save signature: " . $mysqli->error;
                }
            }
        }
    }
}

// If token is provided in URL, validate it
if ($token && !$receiptData && empty($success)) {
    $stmt = $mysqli->prepare("SELECT * FROM acknowledgment_receipt WHERE signature_token = ? AND is_signed = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $receiptData = $result->fetch_assoc();
    } else {
        $error = "Invalid or expired token in URL. Please enter your token manually.";
        $token = ''; // Clear invalid token
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Acknowledgment Receipt</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .container { padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { text-align: center; color: #1F5497; margin-bottom: 20px; }
        .form-section { margin: 20px 0; }
        .input-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .receipt-summary { background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .receipt-detail { margin: 5px 0; }
        .highlight { font-weight: bold; color: #1F5497; }
        .button { padding: 12px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; margin: 10px 0; border: none; }
        .primary { background: #1F5497; color: white; }
        .secondary { background: #eee; color: #333; }
        .full-width { width: 100%; }
        .tabs { display: flex; margin-bottom: 20px; }
        .tab { padding: 10px 15px; cursor: pointer; background: #f0f0f0; border: 1px solid #ddd; flex: 1; text-align: center; }
        .tab.active { background: #1F5497; color: white; font-weight: bold; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .signature-container { border: 1px solid #ccc; border-radius: 4px; margin: 20px 0; background: #f9f9f9; }
        #signature-pad { width: 100%; height: 200px; }
        .success-message { padding: 15px; background-color: #e8f5e9; border-left: 4px solid #4caf50; margin: 20px 0; }
        .error-message { padding: 15px; background-color: #ffebee; border-left: 4px solid #f44336; margin: 20px 0; }
        .token-display { background: #f0f0f0; padding: 10px; border-radius: 4px; font-family: monospace; word-break: break-all; margin: 10px 0; }
        .note { font-size: 0.9em; color: #666; margin-top: 10px; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Sign Acknowledgment Receipt</h2>
        </div>
        
        <?php if ($success): ?>
            <div class="success-message">
                <h3>Thank You!</h3>
                <p><?= htmlspecialchars($success) ?></p>
                <p>Your receipt has been successfully signed and recorded in our system.</p>
                <p><a href="<?= $baseUrl ?>/afis/index.php" class="button primary full-width">Return to Home</a></p>
            </div>
        <?php elseif ($error): ?>
            <div class="error-message">
                <h3>Error</h3>
                <p><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!$receiptData && !$success): ?>
            <div class="form-section">
                <h3>Enter Your Signature Token</h3>
                <p>Please enter the token you received in your email to sign your receipt.</p>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="input-group">
                        <label for="token">Your Token:</label>
                        <input type="text" id="token" name="token" value="<?= htmlspecialchars($token) ?>" placeholder="Enter your token here" required>
                    </div>
                    
                    <button type="submit" name="submit_token" class="button primary full-width">Continue</button>
                </form>
                
                <p class="note">The token is a unique code that was sent to you in the signature request email.</p>
            </div>
        <?php elseif ($receiptData): ?>
            <div class="receipt-summary">
                <div class="receipt-detail"><strong>Receipt #:</strong> <?= htmlspecialchars($receiptData['Receipt_Id']) ?></div>
                <div class="receipt-detail"><strong>Customer:</strong> <?= htmlspecialchars($receiptData['Customer_Name']) ?></div>
                <div class="receipt-detail"><strong>Amount:</strong> ₱<?= number_format($receiptData['Amount'] ?? 0, 2) ?></div>
                <div class="receipt-detail"><strong>Date:</strong> <?= date("F j, Y", strtotime($receiptData['created_at'] ?? "now")) ?></div>
                <div class="receipt-detail"><strong>Purpose:</strong> <?= htmlspecialchars($receiptData['Payment_For'] ?? $receiptData['items_received'] ?? 'N/A') ?></div>
                <div class="receipt-detail"><strong>Your Token:</strong> <div class="token-display"><?= htmlspecialchars($token) ?></div></div>
            </div>
            
            <div class="tabs">
                <div class="tab active" id="tab-quick">Quick Signature</div>
                <div class="tab" id="tab-draw">Draw Signature</div>
            </div>
            
            <div class="tab-content active" id="content-quick">
                <h3>Quick Signature</h3>
                <p>By clicking "Sign Now", you confirm that you, <span class="highlight"><?= htmlspecialchars($receiptData['Customer_Name']) ?></span>, acknowledge receipt of the items or payment detailed above.</p>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="signature_token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="signature_type" value="text">
                    <button type="submit" name="save_signature" class="button primary full-width">Sign Now</button>
                </form>
                
                <p class="note">This will create an electronic signature with your name and timestamp.</p>
            </div>
            
            <div class="tab-content" id="content-draw">
                <h3>Draw Your Signature</h3>
                <p>Please draw your signature below to acknowledge receipt:</p>
                
                <div class="signature-container">
                    <canvas id="signature-pad"></canvas>
                </div>
                
                <form method="POST" id="draw-form">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="signature_token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="signature_type" value="draw">
                    <input type="hidden" name="signature_data" id="signature-data">
                    
                    <div style="display: flex; justify-content: space-between;">
                        <button type="button" id="clear-button" class="button secondary" style="width: 48%;">Clear</button>
                        <button type="button" id="save-button" class="button primary" style="width: 48%;">Save Signature</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs and contents
                    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    // Show corresponding content
                    const contentId = 'content-' + this.id.split('-')[1];
                    document.getElementById(contentId).classList.add('active');
                    
                    // Initialize signature pad if draw tab is active
                    if (contentId === 'content-draw') {
                        initializeSignaturePad();
                    }
                });
            });
            
            // Initialize signature pad if available
            initializeSignaturePad();
        });
        
        function initializeSignaturePad() {
            const canvas = document.getElementById('signature-pad');
            if (!canvas) return;
            
            const signaturePad = new SignaturePad(canvas, { 
                minWidth: 1, 
                maxWidth: 2.5, 
                penColor: "black",
                backgroundColor: "rgba(255, 255, 255, 0)" // Transparent background
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
            
            // Clear button
            const clearButton = document.getElementById('clear-button');
            if (clearButton) {
                clearButton.addEventListener('click', () => signaturePad.clear());
            }
            
            // Save button
            const saveButton = document.getElementById('save-button');
            if (saveButton) {
                saveButton.addEventListener('click', () => {
                    if (signaturePad.isEmpty()) {
                        alert("Please provide a signature first.");
                        return;
                    }
                    document.getElementById('signature-data').value = signaturePad.toDataURL();
                    document.getElementById('draw-form').submit();
                });
            }
        }
    </script>
</body>
</html>