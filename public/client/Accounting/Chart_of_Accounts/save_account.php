<?php
header('Content-Type: application/json');

// Get the posted data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['account_group']) || empty($data['account_type']) || empty($data['account_number'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Database connection
include('config.php');
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // First check if account number already exists
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM chart_of_accounts WHERE account_number = :account_number");
    $checkStmt->bindParam(':account_number', $data['account_number']);
    $checkStmt->execute();
    
    if ($checkStmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Account number already exists']);
        exit;
    }
    
    // Prepare SQL statement
    $sql = "INSERT INTO chart_of_accounts 
            (account_group, account_type, account_number, is_parent, parent_account_number, description)
            VALUES 
            (:account_group, :account_type, :account_number, :is_parent, :parent_account_number, :description)";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    $stmt->bindParam(':account_group', $data['account_group']);
    $stmt->bindParam(':account_type', $data['account_type']);
    $stmt->bindParam(':account_number', $data['account_number']);
    $stmt->bindParam(':is_parent', $data['is_parent'], PDO::PARAM_INT);
    $stmt->bindParam(':parent_account_number', $data['parent_account_number']);
    $stmt->bindParam(':description', $data['description']);
    
    // Execute the query
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Account saved successfully']);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>