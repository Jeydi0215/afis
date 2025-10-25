<?php
header('Content-Type: application/json');

// Database connection
include('../../config/config.php');
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch all accounts with parent account names if they exist
    $stmt = $conn->prepare("
        SELECT a.*, p.account_type as parent_account_name 
        FROM chart_of_accounts a
        LEFT JOIN chart_of_accounts p ON a.parent_account_number = p.account_number
        ORDER BY a.account_group, a.account_number
    ");
    $stmt->execute();
    
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($accounts);
    
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>