<?php
header('Content-Type: application/json');

// Get the posted data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['ids']) || !is_array($data['ids'])) {
    echo json_encode(['success' => false, 'message' => 'Missing account IDs']);
    exit;
}

// Database connection
include('config.php');
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Begin transaction
    $conn->beginTransaction();
    
    // First check for any parent accounts that would orphan sub-accounts
    $placeholders = implode(',', array_fill(0, count($data['ids']), '?'));
    
    $checkOrphans = "SELECT COUNT(*) FROM chart_of_accounts 
                    WHERE parent_account_number IN (
                        SELECT account_number FROM chart_of_accounts 
                        WHERE id IN ($placeholders) AND is_active = 1)";
    $stmt = $conn->prepare($checkOrphans);
    
    foreach ($data['ids'] as $index => $id) {
        $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $orphanCount = $stmt->fetchColumn();
    
    if ($orphanCount > 0) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Cannot delete parent accounts with active sub-accounts']);
        exit;
    }
    
    // Perform soft delete (consistent with single delete)
    $deleteSql = "UPDATE chart_of_accounts SET is_active = 0, deleted_at = NOW() WHERE id IN ($placeholders)";
    $deleteStmt = $conn->prepare($deleteSql);
    
    foreach ($data['ids'] as $index => $id) {
        $deleteStmt->bindValue($index + 1, $id, PDO::PARAM_INT);
    }
    
    $deleteStmt->execute();
    $deletedCount = $deleteStmt->rowCount();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Accounts deleted successfully',
        'deletedCount' => $deletedCount
    ]);
    
} catch(PDOException $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>