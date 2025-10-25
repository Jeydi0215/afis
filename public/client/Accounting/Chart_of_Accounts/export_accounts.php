<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="chart_of_accounts_export.csv"');

// Database connection
include('config.php');
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch all accounts
    $stmt = $conn->prepare("
        SELECT a.*, p.account_type as parent_account_name 
        FROM chart_of_accounts a
        LEFT JOIN chart_of_accounts p ON a.parent_account_number = p.account_number
        ORDER BY a.account_group, a.account_number
    ");
    $stmt->execute();
    
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Write CSV header
    fputcsv($output, [
        'Account Number',
        'Account Name',
        'Account Group',
        'Type',
        'Parent Account',
        'Description',
        'Status'
    ]);
    
    // Write data rows
    foreach ($accounts as $account) {
        fputcsv($output, [
            $account['account_number'],
            $account['account_type'],
            $account['account_group'],
            $account['is_parent'] ? 'Parent' : 'Sub',
            $account['parent_account_name'] ?? 'N/A',
            $account['description'] ?? '',
            $account['is_active'] ? 'Active' : 'Inactive'
        ]);
    }
    
    fclose($output);
    
} catch(PDOException $e) {
    // Fallback to error message if CSV fails
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>