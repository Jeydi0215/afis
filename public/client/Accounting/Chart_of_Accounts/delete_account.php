<?php
header('Content-Type: application/json');

// Get the posted data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing account ID']);
    exit;
}

// Database connection
include('config.php');
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Soft delete (set is_active to 0) instead of actual deletion
    $stmt = $conn->prepare("UPDATE chart_of_accounts SET is_active = 0 WHERE id = :id");
    $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>