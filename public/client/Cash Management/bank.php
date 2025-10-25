<?php
// Database connection parameters
include('../config/config.php');

// Initialize message variable
$message = "";

// Handle form submission for adding a new bank
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_bank'])) {
    $bank_name = htmlspecialchars($_POST['bank_name']);
    $account_name = htmlspecialchars($_POST['account_name']);
    $account_no = htmlspecialchars($_POST['account_no']);
    $account_type = $_POST['account_type'];
    
    // Determine currency based on account type
    $currency = (strpos($account_type, 'Dollar') !== false) ? 'USD' : 'PHP';
    
    // Insert new bank account into database
    $sql = "INSERT INTO bank_accounts (bank_name, account_name, account_no, account_type, currency) 
            VALUES ('$bank_name', '$account_name', '$account_no', '$account_type', '$currency')";
    
    if ($conn->query($sql) === TRUE) {
        $message = "New bank account added successfully";
    } else {
        $message = "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Handle delete request
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM bank_accounts WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        $message = "Bank account deleted successfully";
    } else {
        $message = "Error deleting record: " . $conn->error;
    }
}

// Count accounts by type
$savings_php_count = 0;
$current_php_count = 0;
$savings_dollar_count = 0;
$current_dollar_count = 0;
$dollar_count = 0;
$peso_count = 0;

$sql = "SELECT account_type, currency FROM bank_accounts";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Handle both old and new format account types
        if ($row["account_type"] == "Savings - PHP" || ($row["account_type"] == "Savings" && $row["currency"] == "PHP")) {
            $savings_php_count++;
        } elseif ($row["account_type"] == "Current - PHP" || ($row["account_type"] == "Current" && $row["currency"] == "PHP")) {
            $current_php_count++;
        } elseif ($row["account_type"] == "Savings - Dollar" || ($row["account_type"] == "Savings" && $row["currency"] == "USD")) {
            $savings_dollar_count++;
        } elseif ($row["account_type"] == "Current - Dollar" || ($row["account_type"] == "Current" && $row["currency"] == "USD")) {
            $current_dollar_count++;
        } elseif ($row["account_type"] == "Dollar" || $row["account_type"] == "Dollar - USD") {
            $savings_dollar_count++; // Default old dollar accounts to savings dollar
        }
        
        if ($row["currency"] == "USD" || strpos($row["account_type"], "Dollar") !== false) {
            $dollar_count++;
        }
        
        if ($row["currency"] == "PHP") {
            $peso_count++;
        }
    }
}
include('../../components/sidebar.php');
?>

<!DOCTYPE html>
<html>
<head>
     <h1 class="page-title">
            Bank Enrollment
            </h1>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1F5497;
            --secondary-color: #28a745;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-gray: #f5f5f5;
            --medium-gray: #e0e0e0;
            --dark-gray: #333;
            --text-color: #444;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        html, body {
            overflow-x: hidden;
            overflow-y: auto;
        }

        body {
            font-family: "Quicksand", 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            width: 100%;
            background: white;
            padding: 30px;
            margin-top: -4%;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .page-title {
            font-weight: bold;
            font-size: 24px;
            color: #1f2937;
            margin: 0 0 20px 0;
            margin-left: 120px;
            padding: 20px 20px 0 20px;
            position: relative;
            top: -12%;
            left: -8%;
            display: block;
            visibility: visible;
            opacity: 1;
            z-index: 10;
            border-bottom: none !important;
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
            right: 2.5%;
            top: 8%;
            transform: translateY(-50%);
            
        }

        .btn-create:hover {
            background-color: #333;
        }

        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .notification.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            text-align: center;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .savings-card {
            background-color: #ffedde;
        }

        .current-card {
            background-color: #e0cffc;
        }

        .dollar-card {
            background-color: #b8e6e1;
        }

        .peso-card {
            background-color: #d7f5e8;
        }

        .stat-label {
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 10px;
            color: var(--dark-gray);
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            color: var(--primary-color);
        }

        /* Table styling to match invoice system */
        .banks-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .banks-table th {
            background-color: #4A4A4A;
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        .banks-table td {
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        .banks-table tr {
            background-color: white;
            transition: var(--transition);
        }

        .banks-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .banks-table tr:hover {
            background-color: #e6f3ff;
        }

        .banks-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Action buttons styling - FIXED */
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
        }

        .action-icon-btn {
            background-color: #f0f2f5;
            color: #333;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            cursor: pointer;
            font-size: 14px;
        }

        .action-icon-btn:hover {
            background-color: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }

        .action-icon-btn.delete:hover {
            background-color: var(--danger-color);
        }

        /* Center the SVG icons inside the buttons */
        .action-icon-btn svg {
            width: 16px;
            height: 16px;
            display: block;
            margin: 0 auto;
        }

        /* Ensure the Actions column header is centered */
        .banks-table th:last-child {
            text-align: center;
        }

        /* Center the action buttons in their table cell */
        .banks-table td:last-child {
            text-align: center;
        }

        .no-banks {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .no-banks h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: var(--dark-gray);
        }

        .no-banks p {
            font-size: 16px;
            max-width: 500px;
            margin: 0 auto;
        }

        /* Modal styling */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(3px);
        }

        .bank-modal {
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

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            background: none;
            border: none;
            color: #999;
        }

        .close-modal:hover {
            color: var(--danger-color);
        }

        .bank-modal h2 {
            color: var(--primary-color);
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--medium-gray);
        }

        .edit-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }

        .edit-form label {
            margin-top: 10px;
            font-weight: 600;
            color: var(--dark-gray);
        }

        .edit-form input,
        .edit-form select {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
            font-size: 14px;
            transition: var(--transition);
            box-sizing: border-box;
        }

        .edit-form input:focus,
        .edit-form select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(31, 84, 151, 0.2);
        }

        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .btn-save {
            background-color: #4A4A4A;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-save:hover {
            background-color: #333;
        }

        .btn-cancel {
            background-color: #ccc;
            color: #000;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-cancel:hover {
            background-color: #999;
        }

        @media (max-width: 1200px) {
            .container {
                margin: 20px;
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .edit-form {
                grid-template-columns: 1fr;
            }

            .banks-table {
                font-size: 12px;
            }

            .banks-table th,
            .banks-table td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="page-title">Bank Enrollment</div>
    
    <div class="container">
        <button type="button" class="btn-create" onclick="openModal()">+ Add Bank</button>
        
        <?php if(!empty($message)): ?>
        <div class="notification <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card savings-card">
                <div class="stat-label">Savings - PHP</div>
                <div class="stat-value"><?php echo $savings_php_count; ?></div>
            </div>
            <div class="stat-card current-card">
                <div class="stat-label">Current - PHP</div>
                <div class="stat-value"><?php echo $current_php_count; ?></div>
            </div>
            <div class="stat-card dollar-card">
                <div class="stat-label">Savings - Dollar</div>
                <div class="stat-value"><?php echo $savings_dollar_count; ?></div>
            </div>
            <div class="stat-card dollar-card">
                <div class="stat-label">Current - Dollar</div>
                <div class="stat-value"><?php echo $current_dollar_count; ?></div>
            </div>
        </div>
        
        <!-- Enrolled Banks Table -->
        <table class="banks-table">
            <thead>
                <tr>
                    <th>Bank ID</th>
                    <th>Bank Name</th>
                    <th>Account Name</th>
                    <th>Account No.</th>
                    <th>Account Type</th>
                    <th>Currency</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT * FROM bank_accounts ORDER BY bank_name";
                $result = $conn->query($sql);
                
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row["id"]) . '</td>';
                        echo '<td>' . htmlspecialchars($row["bank_name"]) . '</td>';
                        echo '<td>' . htmlspecialchars($row["account_name"]) . '</td>';
                        echo '<td>' . htmlspecialchars($row["account_no"]) . '</td>';
                        echo '<td>' . htmlspecialchars($row["account_type"]) . '</td>';
                        echo '<td>' . htmlspecialchars($row["currency"]) . '</td>';
                        echo '<td>';
                        echo '<div class="action-buttons">';
                        echo '<button class="action-icon-btn" onclick="viewBank(' . $row["id"] . ')" title="View"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-eye"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg></button>';
                        echo '<button class="action-icon-btn" onclick="editBank(' . $row["id"] . ')" title="Edit"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-edit"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg></button>';
                        echo '<button class="action-icon-btn delete" onclick="confirmDelete(' . $row["id"] . ')" title="Delete"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg></button>';
                        echo '</div>';
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="7" style="text-align: center; padding: 40px; color: #999;">No banks enrolled yet</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Add Bank Modal -->
    <div class="modal-overlay" id="addBankModal">
        <div class="bank-modal">
            <button class="close-modal" onclick="closeModal()">&times;</button>
            <h2>Add Bank Account</h2>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="addBankForm">
                <div class="edit-form">
                    <div>
                        <label for="bank_name">Bank Name</label>
                        <input type="text" id="bank_name" name="bank_name" placeholder="BPI, BDO, UnionBank..." required>
                    </div>
                    <div>
                        <label for="account_name">Account Name</label>
                        <input type="text" id="account_name" name="account_name" placeholder="Stafiiy Holdings Inc." required>
                    </div>
                    <div>
                        <label for="account_no">Account No.</label>
                        <input type="text" id="account_no" name="account_no" placeholder="1234-5678-9012" required>
                    </div>
                    <div>
                        <label for="account_type">Account Type</label>
                        <select id="account_type" name="account_type" required>
                            <option value="" selected disabled>Select account type</option>
                            <option value="Savings - PHP">Savings - PHP</option>
                            <option value="Current - PHP">Current - PHP</option>
                            <option value="Savings - Dollar">Savings - Dollar</option>
                            <option value="Current - Dollar">Current - Dollar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="add_bank" class="btn-save">Add Bank</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('addBankModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('addBankModal').style.display = 'none';
        }

        function viewBank(id) {
            // Implement view functionality
            alert('View bank account #' + id);
        }

        function editBank(id) {
            // Implement edit functionality
            alert('Edit bank account #' + id);
        }

        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this bank account?')) {
                window.location.href = '?delete=' + id;
            }
        }

        // Close modal when clicking outside
        document.getElementById('addBankModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>

<?php
// Close connection
$conn->close();
?>