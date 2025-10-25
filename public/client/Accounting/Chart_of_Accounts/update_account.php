<?php
header('Content-Type: application/json');

// Get the posted data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['id']) || empty($data['account_group']) || empty($data['account_type']) || empty($data['account_number'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Database connection
 include('config.php');

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Get current account data
    $currentStmt = $conn->prepare("SELECT account_number, is_parent FROM chart_of_accounts WHERE id = :id");
    $currentStmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
    $currentStmt->execute();
    $currentAccount = $currentStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentAccount) {
        echo json_encode(['success' => false, 'message' => 'Account not found']);
        exit;
    }
    
    // Check if account number is being changed and exists for another account
    if ($currentAccount['account_number'] != $data['account_number']) {
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM chart_of_accounts WHERE account_number = :account_number AND id != :id");
        $checkStmt->bindParam(':account_number', $data['account_number']);
        $checkStmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->fetchColumn() > 0) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Account number already exists']);
            exit;
        }
        
        // If this is a parent account, update all sub-accounts
        if ($currentAccount['is_parent']) {
            $updateSubs = "UPDATE chart_of_accounts SET 
                          parent_account_number = :new_number 
                          WHERE parent_account_number = :old_number";
            $updateStmt = $conn->prepare($updateSubs);
            $updateStmt->bindParam(':new_number', $data['account_number']);
            $updateStmt->bindParam(':old_number', $currentAccount['account_number']);
            $updateStmt->execute();
        }
    }
    
    // Prepare SQL statement
    $sql = "UPDATE chart_of_accounts SET 
            account_group = :account_group,
            account_type = :account_type,
            account_number = :account_number,
            is_parent = :is_parent,
            parent_account_number = :parent_account_number,
            description = :description,
            updated_at = NOW()
            WHERE id = :id";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    $stmt->bindParam(':account_group', $data['account_group']);
    $stmt->bindParam(':account_type', $data['account_type']);
    $stmt->bindParam(':account_number', $data['account_number']);
    $stmt->bindParam(':is_parent', $data['is_parent'], PDO::PARAM_INT);
    $stmt->bindParam(':parent_account_number', $data['parent_account_number']);
    $stmt->bindParam(':description', $data['description']);
    $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
    
    // Execute the query
    $stmt->execute();
    
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Account updated successfully']);
    
} catch(PDOException $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>