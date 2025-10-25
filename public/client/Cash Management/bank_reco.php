<?php
/**
 * Bank Reconciliation System
 * A simple bank reconciliation application for tracking deposits, withdrawals,
 * and balance adjustments with transaction history management.
 */

// Start session for flash messages and data persistence
session_start();

// Database configuration
include('../config/config.php');

// Create tables if they don't exist
function createTables($conn) {
    // Check if active_banks_ph table exists
    $result = $conn->query("SHOW TABLES LIKE 'active_banks_ph'");
    if ($result->num_rows == 0) {
        // Create active_banks_ph table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS active_banks_ph (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bank_name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->query($sql);
        
        // Insert some default Philippine banks
        $conn->query("INSERT INTO active_banks_ph (bank_name) VALUES 
                     ('BDO'), 
                     ('BPI'), 
                     ('Metrobank'), 
                     ('PNB'), 
                     ('Security Bank'),
                     ('Landbank'),
                     ('UCPB'),
                     ('Eastwest Bank'),
                     ('RCBC'),
                     ('Chinabank')");
    }
    
    // Bank accounts table (bank_reco)
    $result = $conn->query("SHOW TABLES LIKE 'bank_reco'");
    if ($result->num_rows == 0) {
        $sql = "CREATE TABLE IF NOT EXISTS bank_reco (
            id INT AUTO_INCREMENT PRIMARY KEY,
            account_name VARCHAR(255) NOT NULL,
            account_number VARCHAR(100) NOT NULL,
            account_type VARCHAR(50) NOT NULL,
            bank_id INT,
            opening_balance DECIMAL(10,2) DEFAULT 0.00,
            current_balance DECIMAL(10,2) DEFAULT 0.00,
            last_reconciled_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (bank_id) REFERENCES active_banks_ph(id) ON DELETE SET NULL
        )";
        $conn->query($sql);
    } else {
        // Check if bank_id column exists
        $result = $conn->query("SHOW COLUMNS FROM bank_reco LIKE 'bank_id'");
        if ($result->num_rows == 0) {
            // Add bank_id column if it doesn't exist
            $conn->query("ALTER TABLE bank_reco ADD COLUMN bank_id INT AFTER account_type");
            $conn->query("ALTER TABLE bank_reco ADD FOREIGN KEY (bank_id) REFERENCES active_banks_ph(id) ON DELETE SET NULL");
        }
    }

    // Transactions table
    $sql = "CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_date DATE NOT NULL,
        reference_number VARCHAR(100),
        description TEXT,
        amount DECIMAL(10,2) NOT NULL,
        transaction_type ENUM('deposit', 'withdrawal', 'adjustment') NOT NULL,
        method VARCHAR(50),
        payee VARCHAR(255),
        bank_account_id INT NOT NULL,
        status ENUM('cleared', 'pending', 'outstanding') NOT NULL,
        adjustment_type VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (bank_account_id) REFERENCES bank_reco(id)
    )";
    $conn->query($sql);
    
    // Check if there are any accounts in bank_reco
    $result = $conn->query("SELECT COUNT(*) as count FROM bank_reco");
    if ($result && $row = $result->fetch_assoc()) {
        if ($row['count'] == 0) {
            // Get banks from active_banks_ph
            $banksResult = $conn->query("SELECT id, bank_name FROM active_banks_ph");
            if ($banksResult && $banksResult->num_rows > 0) {
                while ($bank = $banksResult->fetch_assoc()) {
                    $bankId = $bank['id'];
                    $bankName = $bank['bank_name'];
                    $conn->query("INSERT INTO bank_reco (account_name, account_number, account_type, bank_id, opening_balance, current_balance) 
                              VALUES ('$bankName Account', '1000$bankId', 'Checking', $bankId, 0.00, 0.00)");
                }
            } else {
                // If no banks in active_banks_ph, create a default account without bank_id
                $conn->query("INSERT INTO bank_reco (account_name, account_number, account_type, opening_balance, current_balance) 
                          VALUES ('Main Account', '12345678', 'Checking', 0.00, 0.00)");
            }
        }
    } else {
        error_log("Error in SELECT COUNT(*) FROM bank_reco: " . $conn->error);
    }
    
    // Update existing bank_reco records to set bank_id if it's NULL
    $conn->query("UPDATE bank_reco br
                  JOIN active_banks_ph abp ON br.account_name LIKE CONCAT('%', abp.bank_name, '%')
                  SET br.bank_id = abp.id
                  WHERE br.bank_id IS NULL");
}

createTables($conn);

// Flash messages
function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_deposit':
                addDeposit($conn);
                break;
            case 'add_withdrawal':
                addWithdrawal($conn);
                break;
            case 'add_adjustment':
                addAdjustment($conn);
                break;
            case 'delete_transaction':
                deleteTransaction($conn);
                break;
        }
    }
}

// Add deposit function
function addDeposit($conn) {
    // Validate required fields
    if (
        !isset($_POST['date']) || !isset($_POST['amount']) || !isset($_POST['bank_id']) || !isset($_POST['status']) ||
        $_POST['date'] === '' || $_POST['amount'] === '' || $_POST['bank_id'] === '' || $_POST['status'] === ''
    ) {
        setFlashMessage('Please fill in all required fields', 'error');
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Validate amount is positive
    if (!is_numeric($_POST['amount']) || floatval($_POST['amount']) <= 0) {
        setFlashMessage('Amount must be a positive number', 'error');
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    $date = $conn->real_escape_string($_POST['date']);
    $reference = $conn->real_escape_string($_POST['reference'] ?? '');
    $description = $conn->real_escape_string($_POST['description'] ?? '');
    $amount = floatval($_POST['amount']);
    $method = $conn->real_escape_string($_POST['deposit_method'] ?? 'Cash');
    $bank_account_id = intval($_POST['bank_id']);
    $status = $conn->real_escape_string($_POST['status']);
    
    $sql = "INSERT INTO transactions (transaction_date, reference_number, description, amount, transaction_type, method, bank_account_id, status) 
            VALUES ('$date', '$reference', '$description', $amount, 'deposit', '$method', $bank_account_id, '$status')";
    
    if ($conn->query($sql) === TRUE) {
        // Update account balance
        updateAccountBalance($conn, $bank_account_id, $amount, 'deposit');
        setFlashMessage('Deposit added successfully');
    } else {
        setFlashMessage('Error: ' . $conn->error, 'error');
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Add withdrawal function
function addWithdrawal($conn) {
    // Validate required fields
    if (
        !isset($_POST['date']) || !isset($_POST['amount']) || !isset($_POST['bank_id']) || !isset($_POST['status']) ||
        $_POST['date'] === '' || $_POST['amount'] === '' || $_POST['bank_id'] === '' || $_POST['status'] === ''
    ) {
        setFlashMessage('Please fill in all required fields', 'error');
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Validate amount is positive
    if (!is_numeric($_POST['amount']) || floatval($_POST['amount']) <= 0) {
        setFlashMessage('Amount must be a positive number', 'error');
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    $date = $conn->real_escape_string($_POST['date']);
    $reference = $conn->real_escape_string($_POST['reference'] ?? '');
    $payee = $conn->real_escape_string($_POST['payee'] ?? '');
    $description = $conn->real_escape_string($_POST['description'] ?? '');
    $amount = floatval($_POST['amount']);
    $method = $conn->real_escape_string($_POST['withdrawal_method'] ?? 'Check');
    $bank_account_id = intval($_POST['bank_id']);
    $status = $conn->real_escape_string($_POST['status']);
    
    $sql = "INSERT INTO transactions (transaction_date, reference_number, payee, description, amount, transaction_type, method, bank_account_id, status) 
            VALUES ('$date', '$reference', '$payee', '$description', $amount, 'withdrawal', '$method', $bank_account_id, '$status')";
    
    if ($conn->query($sql) === TRUE) {
        // Update account balance
        updateAccountBalance($conn, $bank_account_id, $amount, 'withdrawal');
        setFlashMessage('Withdrawal added successfully');
    } else {
        setFlashMessage('Error: ' . $conn->error, 'error');
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Add balance adjustment function
function addAdjustment($conn) {
    // Validate required fields
    if (empty($_POST['date']) || empty($_POST['amount']) || empty($_POST['bank_id'])) {
        setFlashMessage('Please fill in all required fields', 'error');
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    $date = $conn->real_escape_string($_POST['date']);
    $description = $conn->real_escape_string($_POST['description'] ?? '');
    $amount = floatval($_POST['amount']);
    $adjustment_type = $conn->real_escape_string($_POST['adjustment_type'] ?? 'Service charge');
    $bank_account_id = intval($_POST['bank_id']);
    
    $sql = "INSERT INTO transactions (transaction_date, description, amount, transaction_type, adjustment_type, bank_account_id, status) 
            VALUES ('$date', '$description', $amount, 'adjustment', '$adjustment_type', $bank_account_id, 'cleared')";
    
    if ($conn->query($sql) === TRUE) {
        // Update account balance
        updateAccountBalance($conn, $bank_account_id, $amount, 'adjustment');
        setFlashMessage('Balance adjustment added successfully');
    } else {
        setFlashMessage('Error: ' . $conn->error, 'error');
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Delete transaction
function deleteTransaction($conn) {
    $id = intval($_POST['transaction_id']);
    
    // Get transaction info before deleting
    $result = $conn->query("SELECT amount, transaction_type, bank_account_id FROM transactions WHERE id = $id");
    if ($result->num_rows > 0) {
        $transaction = $result->fetch_assoc();
        $amount = $transaction['amount'];
        $type = $transaction['transaction_type'];
        $accountId = $transaction['bank_account_id'];
        
        // Reverse the balance change
        if ($type === 'deposit') {
            $sql = "UPDATE bank_reco SET current_balance = current_balance - $amount, updated_at = NOW() WHERE id = $accountId";
        } elseif ($type === 'withdrawal') {
            $sql = "UPDATE bank_reco SET current_balance = current_balance + $amount, updated_at = NOW() WHERE id = $accountId";
        } elseif ($type === 'adjustment') {
            $sql = "UPDATE bank_reco SET current_balance = current_balance - $amount, updated_at = NOW() WHERE id = $accountId";
        }
        $conn->query($sql);
    }
    
    // Delete the transaction
    $sql = "DELETE FROM transactions WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        setFlashMessage('Transaction deleted successfully');
    } else {
        setFlashMessage('Error deleting transaction: ' . $conn->error, 'error');
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Get reconciliation summary
function getReconciliationSummary($conn, $bank_id = null) {
    $summary = [
        'deposits' => 0,
        'withdrawals' => 0,
        'balance' => 0
    ];
    
    $where_clause = '';
    if ($bank_id) {
        $bank_id = intval($bank_id);
        $where_clause = " WHERE bank_account_id = $bank_id";
    }
    
    // Get total deposits
    $result = $conn->query("SELECT SUM(amount) as total FROM transactions WHERE transaction_type = 'deposit'" . ($bank_id ? " AND bank_account_id = $bank_id" : ""));
    if ($row = $result->fetch_assoc()) {
        $summary['deposits'] = $row['total'] ? floatval($row['total']) : 0;
    }
    
    // Get total withdrawals
    $result = $conn->query("SELECT SUM(amount) as total FROM transactions WHERE transaction_type = 'withdrawal'" . ($bank_id ? " AND bank_account_id = $bank_id" : ""));
    if ($row = $result->fetch_assoc()) {
        $summary['withdrawals'] = $row['total'] ? floatval($row['total']) : 0;
    }
    
    // Get current balance from bank_reco table
    if ($bank_id) {
        $result = $conn->query("SELECT current_balance as total FROM bank_reco WHERE id = $bank_id");
        if ($row = $result->fetch_assoc()) {
            $summary['balance'] = $row['total'] ? floatval($row['total']) : 0;
        }
    } else {
        $result = $conn->query("SELECT SUM(current_balance) as total FROM bank_reco");
        if ($row = $result->fetch_assoc()) {
            $summary['balance'] = $row['total'] ? floatval($row['total']) : 0;
        }
    }
    
    return $summary;
}

// Get transactions with pagination
function getTransactions($conn, $page = 1, $perPage = 10, $bank_id = null) {
    $offset = ($page - 1) * $perPage;
    
    $where_clause = '';
    if ($bank_id) {
        $bank_id = intval($bank_id);
        $where_clause = " WHERE t.bank_account_id = $bank_id";
    }
    
    $sql = "SELECT t.*, br.account_name, br.account_type, abp.bank_name
            FROM transactions t
            JOIN bank_reco br ON t.bank_account_id = br.id
            LEFT JOIN active_banks_ph abp ON br.bank_id = abp.id
            $where_clause
            ORDER BY t.transaction_date DESC, t.id DESC
            LIMIT $offset, $perPage";
    
    $result = $conn->query($sql);
    $transactions = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
    }
    
    return $transactions;
}

// Count total transactions for pagination
function countTransactions($conn, $bank_id = null) {
    $where_clause = '';
    if ($bank_id) {
        $bank_id = intval($bank_id);
        $where_clause = " WHERE bank_account_id = $bank_id";
    }
    
    $result = $conn->query("SELECT COUNT(*) as total FROM transactions" . $where_clause);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Get all bank accounts linked with active_banks_ph
function getBankAccounts($conn) {
    // Get all bank accounts with their associated bank names
    $sql = "SELECT br.*, abp.bank_name 
            FROM bank_reco br
            LEFT JOIN active_banks_ph abp ON br.bank_id = abp.id
            ORDER BY abp.bank_name";
    
    $result = $conn->query($sql);
    $accounts = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $accounts[] = $row;
        }
    }
    
    return $accounts;
}

// Update account balance when a transaction is added
function updateAccountBalance($conn, $accountId, $amount, $transactionType) {
    // Determine the impact on balance based on transaction type
    if ($transactionType === 'deposit') {
        $sql = "UPDATE bank_reco SET current_balance = current_balance + $amount, updated_at = NOW() WHERE id = $accountId";
    } elseif ($transactionType === 'withdrawal') {
        $sql = "UPDATE bank_reco SET current_balance = current_balance - $amount, updatedated_at = NOW() WHERE id = $accountId";
    } elseif ($transactionType === 'adjustment') {
        $sql = "UPDATE bank_reco SET current_balance = current_balance + $amount, updated_at = NOW() WHERE id = $accountId";
    }
    
    $conn->query($sql);
}

// Pagination setup
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$selectedBank = isset($_GET['bank_id']) ? intval($_GET['bank_id']) : null;

// Get summary data for display
$summary = getReconciliationSummary($conn, $selectedBank);
$totalTransactions = countTransactions($conn, $selectedBank);
$totalPages = ceil($totalTransactions / $perPage);
$transactions = getTransactions($conn, $page, $perPage, $selectedBank);
$bankAccounts = getBankAccounts($conn);
$flashMessage = getFlashMessage();

// Get list of active banks from the database
$activeBanks = [];
$banksQuery = $conn->query("SELECT id, bank_name FROM active_banks_ph ORDER BY bank_name");
if ($banksQuery && $banksQuery->num_rows > 0) {
    while ($bank = $banksQuery->fetch_assoc()) {
        $activeBanks[] = $bank;
    }
}

// Include sidebar if it exists
if (file_exists('../../components/sidebar.php')) {
 include('../../components/sidebar.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Reconciliation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            border-radius: 8px;
        }

        .summary-card {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
        }

        .summary-value {
            font-size: 24px;
            font-weight: bold;
        }

        .transaction-form {
            display: none;
        }

        .transaction-actions {
            display: flex;
            gap: 5px;
        }
        
        .alert {
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .status-badge {
            font-size: 0.8em;
            padding: 5px 10px;
            border-radius: 12px;
        }
        
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .container-fluid {
            margin-left: -3%;
            padding-top: 50px;
            width: 106%;
        }
        
        .filter button {
            position: absolute;
            width: 100px;
            right: 30px;
            top: 45px;
            height: 35%;
        }
        
        /* New table styles to match the invoice example exactly */
        .table-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .table-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        
        .transaction-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .transaction-table th {
            background-color: #f5f5f5;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
            font-size: 14px;
        }
        
        .transaction-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
            font-size: 14px;
        }
        
        .transaction-table tr:last-child td {
            border-bottom: none;
        }
        
        .transaction-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .transaction-table .actions {
            text-align: center;
        }
        
        .btn-icon {
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 14px;
            border: 1px solid #ddd;
            background: white;
        }
        
        .btn-icon:hover {
            background-color: #f5f5f5;
        }
        
        .amount-positive {
            color: #28a745;
            font-weight: 600;
        }
        
        .amount-negative {
            color: #dc3545;
            font-weight: 600;
        }
        
        .amount-neutral {
            color: #ffc107;
            font-weight: 600;
        }
        
        .badge-cleared {
            background-color: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .badge-pending {
            background-color: #ffc107;
            color: #212529;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .badge-outstanding {
            background-color: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .page-info {
            font-size: 14px;
            color: #6c757d;
            margin-right: 15px;
        }
        
        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .entries-info {
            font-size: 14px;
            color: #6c757d;
        }
        
        /* Exact style from the invoice example */
        .invoice-style-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }
        
        .invoice-style-table th {
            background-color: #4A4A4A;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: white;
            border-bottom: 2px solid #e9ecef;
        }
        
        .invoice-style-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            color: #212529;
        }
        
        .invoice-style-table tr:last-child td {
            border-bottom: none;
        }
        
        .invoice-style-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .action-checkbox {
            width: 30px;
            text-align: center;
        }
        
        .action-checkbox input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }
    </style>
</head>
<body>
  
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="page-title">
            Bank Reconciliation
            </h1>
            <div class="btn-group">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                    <i class="fas fa-plus me-2"></i>Add Transaction
                </button>
            </div>
        </div>

        <?php if ($flashMessage): ?>
            <div class="alert alert-<?= $flashMessage['type'] == 'error' ? 'danger' : $flashMessage['type'] ?>">
                <?= $flashMessage['message'] ?>
            </div>
        <?php endif; ?>

        <!-- Bank filter -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Filter by Bank</h5>
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <select name="bank_id" class="form-select" id="bank-filter">
                            <option value="">All Banks</option>
                            <?php
                            $bankResult = $conn->query("SELECT id, bank_name FROM active_banks_ph");
                            if ($bankResult && $bankResult->num_rows > 0) {
                                while ($bank = $bankResult->fetch_assoc()) {
                                    $selected = ($selectedBank == $bank['id']) ? 'selected' : '';
                                    echo '<option value="' . $bank['id'] . '" ' . $selected . '>' . htmlspecialchars($bank['bank_name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter">
                        <button type="submit" class="btn btn-secondary">Filter</button>
                        <?php if ($selectedBank): ?>
                            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-outline-secondary ms-2">Clear Filter</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card summary-card bg-light">
                    <h5>Deposits</h5>
                    <div class="summary-value text-success">₱<?= number_format($summary['deposits'], 2) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card summary-card bg-light">
                    <h5>Withdrawals</h5>
                    <div class="summary-value text-danger">₱<?= number_format($summary['withdrawals'], 2) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card summary-card bg-light">
                    <h5>Balance</h5>
                    <div class="summary-value <?= $summary['balance'] >= 0 ? 'text-primary' : 'text-danger' ?>">
                        ₱<?= number_format($summary['balance'], 2) ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-container mt-4">
            <div class="table-header">
                <h2 class="table-title">Transactions</h2>
                <div class="page-info">Page <?= $page ?> of <?= $totalPages ? $totalPages : 1 ?></div>
            </div>
            
            <div class="table-controls">
                <div class="entries-info">
                    Showing <?= count($transactions) ?> of <?= $totalTransactions ?> entries
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="invoice-style-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Account</th>
                            <th>Status</th>
                            <th class="action-checkbox">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">No transactions found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('M j, Y', strtotime($transaction['transaction_date']))) ?></td>
                                    <td><?= htmlspecialchars($transaction['reference_number'] ?? ($transaction['payee'] ?? '-')) ?></td>
                                    <td><?= htmlspecialchars($transaction['description'] ?? '-') ?></td>
                                    <td class="<?= $transaction['transaction_type'] === 'withdrawal' ? 'amount-negative' : ($transaction['transaction_type'] === 'adjustment' ? 'amount-neutral' : 'amount-positive') ?>">
                                        <?php if ($transaction['transaction_type'] === 'withdrawal'): ?>
                                            -₱<?= number_format($transaction['amount'], 2) ?>
                                        <?php else: ?>
                                            ₱<?= number_format($transaction['amount'], 2) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($transaction['bank_name'] ?: $transaction['account_name']) ?></td>
                                    <td>
                                        <?php if ($transaction['status'] === 'cleared'): ?>
                                            <span class="badge-cleared">Cleared</span>
                                        <?php elseif ($transaction['status'] === 'pending'): ?>
                                            <span class="badge-pending">Pending</span>
                                        <?php else: ?>
                                            <span class="badge-outstanding">Outstanding</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-checkbox">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="delete_transaction">
                                            <input type="hidden" name="transaction_id" value="<?= $transaction['id'] ?>">
                                            <button type="submit" class="btn-icon" onclick="return confirm('Are you sure you want to delete this transaction?')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination-container mt-4">
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1<?= $selectedBank ? '&bank_id='.$selectedBank : '' ?>">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?><?= $selectedBank ? '&bank_id='.$selectedBank : '' ?>">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $startPage + 4);
                            if ($endPage - $startPage < 4) {
                                $startPage = max(1, $endPage - 4);
                            }
                            ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= $selectedBank ? '&bank_id='.$selectedBank : '' ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?><?= $selectedBank ? '&bank_id='.$selectedBank : '' ?>">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $totalPages ?><?= $selectedBank ? '&bank_id='.$selectedBank : '' ?>">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Transaction Modal -->
    <div class="modal fade" id="addTransactionModal" tabindex="-1" aria-labelledby="addTransactionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTransactionModalLabel">Add Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="transactionTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="deposit-tab" data-bs-toggle="tab" data-bs-target="#deposit" type="button" role="tab" aria-controls="deposit" aria-selected="true">Deposit</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="withdrawal-tab" data-bs-toggle="tab" data-bs-target="#withdrawal" type="button" role="tab" aria-controls="withdrawal" aria-selected="false">Withdrawal</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="adjustment-tab" data-bs-toggle="tab" data-bs-target="#adjustment" type="button" role="tab" aria-controls="adjustment" aria-selected="false">Balance Adjustment</button>
                        </li>
                    </ul>
                    <div class="tab-content pt-3" id="transactionTabContent">
                        <!-- Deposit Form -->
                        <div class="tab-pane fade show active" id="deposit" role="tabpanel" aria-labelledby="deposit-tab">
                            <form method="POST">
                                <input type="hidden" name="action" value="add_deposit">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="deposit-date" class="form-label">Date *</label>
                                        <input type="date" class="form-control" id="deposit-date" name="date" required value="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="deposit-amount" class="form-label">Amount *</label>
                                        <input type="number" class="form-control" id="deposit-amount" name="amount" step="0.01" min="0.01" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="deposit-reference" class="form-label">Reference Number</label>
                                        <input type="text" class="form-control" id="deposit-reference" name="reference">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="deposit-method" class="form-label">Deposit Method</label>
                                        <select class="form-select" id="deposit-method" name="deposit_method">
                                            <option value="Cash">Cash</option>
                                            <option value="Check">Check</option>
                                            <option value="Bank transfer">Bank transfer</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="deposit-bank-id" class="form-label">Bank *</label>
                                        <select class="form-select" id="deposit-bank-id" name="bank_id" required>
                                            <option value="">Select Bank</option>
                                            <?php
                                            $bankResult = $conn->query("SELECT id, bank_name FROM active_banks_ph");
                                            if ($bankResult && $bankResult->num_rows > 0) {
                                                while ($bank = $bankResult->fetch_assoc()) {
                                                    echo '<option value="' . $bank['id'] . '">' . htmlspecialchars($bank['bank_name']) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="deposit-status" class="form-label">Status *</label>
                                        <select class="form-select" id="deposit-status" name="status" required>
                                            <option value="cleared">Cleared</option>
                                            <option value="pending">Pending</option>
                                            <option value="outstanding">Outstanding</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="deposit-description" class="form-label">Description</label>
                                    <textarea class="form-control" id="deposit-description" name="description" rows="2"></textarea>
                                </div>
                                <div class="text-end">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Add Deposit</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Withdrawal Form -->
                        <div class="tab-pane fade" id="withdrawal" role="tabpanel" aria-labelledby="withdrawal-tab">
                            <form method="POST">
                                <input type="hidden" name="action" value="add_withdrawal">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="withdrawal-date" class="form-label">Date *</label>
                                        <input type="date" class="form-control" id="withdrawal-date" name="date" required value="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="withdrawal-amount" class="form-label">Amount *</label>
                                        <input type="number" class="form-control" id="withdrawal-amount" name="amount" step="0.01" min="0.01" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="withdrawal-reference" class="form-label">Check/Ref Number</label>
                                        <input type="text" class="form-control" id="withdrawal-reference" name="reference">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="withdrawal-payee" class="form-label">Payee</label>
                                        <input type="text" class="form-control" id="withdrawal-payee" name="payee">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="withdrawal-method" class="form-label">Withdrawal Method</label>
                                        <select class="form-select" id="withdrawal-method" name="withdrawal_method">
                                            <option value="Check">Check</option>
                                            <option value="EFT">EFT</option>
                                            <option value="Cash">Cash</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="withdrawal-bank-id" class="form-label">Bank *</label>
                                        <select class="form-select" id="withdrawal-bank-id" name="bank_id" required>
                                            <option value="">Select Bank</option>
                                            <?php
                                            $bankResult = $conn->query("SELECT id, bank_name FROM active_banks_ph");
                                            if ($bankResult && $bankResult->num_rows > 0) {
                                                while ($bank = $bankResult->fetch_assoc()) {
                                                    echo '<option value="' . $bank['id'] . '">' . htmlspecialchars($bank['bank_name']) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="withdrawal-status" class="form-label">Status *</label>
                                        <select class="form-select" id="withdrawal-status" name="status" required>
                                            <option value="cleared">Cleared</option>
                                            <option value="pending">Pending</option>
                                            <option value="outstanding">Outstanding</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="withdrawal-description" class="form-label">Description</label>
                                    <textarea class="form-control" id="withdrawal-description" name="description" rows="2"></textarea>
                                </div>
                                <div class="text-end">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Add Withdrawal</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Balance Adjustment Form -->
                        <div class="tab-pane fade" id="adjustment" role="tabpanel" aria-labelledby="adjustment-tab">
                            <form method="POST">
                                <input type="hidden" name="action" value="add_adjustment">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="adjustment-date" class="form-label">Date *</label>
                                        <input type="date" class="form-control" id="adjustment-date" name="date" required value="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="adjustment-amount" class="form-label">Amount *</label>
                                        <input type="number" class="form-control" id="adjustment-amount" name="amount" step="0.01" required>
                                        <small class="form-text text-muted">Use negative values for charges or losses.</small>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="adjustment-type" class="form-label">Adjustment Type</label>
                                        <select class="form-select" id="adjustment-type" name="adjustment_type">
                                            <option value="Service charge">Service charge</option>
                                            <option value="Bank error">Bank error</option>
                                            <option value="FX gain-loss">FX gain-loss</option>
                                            <option value="Interest earned">Interest earned</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="adjustment-bank-id" class="form-label">Bank *</label>
                                        <select class="form-select" id="adjustment-bank-id" name="bank_id" required>
                                            <option value="">Select Bank</option>
                                            <?php
                                            $bankResult = $conn->query("SELECT id, bank_name FROM active_banks_ph");
                                            if ($bankResult && $bankResult->num_rows > 0) {
                                                while ($bank = $bankResult->fetch_assoc()) {
                                                    echo '<option value="' . $bank['id'] . '">' . htmlspecialchars($bank['bank_name']) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="adjustment-description" class="form-label">Description</label>
                                    <textarea class="form-control" id="adjustment-description" name="description" rows="2"></textarea>
                                </div>
                                <div class="text-end">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Add Adjustment</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set today's date as default for all date inputs
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('deposit-date').value = today;
            document.getElementById('withdrawal-date').value = today;
            document.getElementById('adjustment-date').value = today;
        });
    </script>
</body>
</html>