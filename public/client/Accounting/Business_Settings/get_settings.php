<?php
header('Content-Type: application/json');

// Database connection
include('config.php');

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }

    $query = "SELECT * FROM business_settings WHERE id = 1";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No settings found']);
        exit;
    }

    $settings = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'settings' => $settings
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) $conn->close();
}
?>