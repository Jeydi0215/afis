<?php
header('Content-Type: application/json');
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$dbConfig = [
    'host' => '127.0.0.1',
    'username' => 'root',
    'password' => '',
    'database' => 'afis'
];

$response = ['success' => false, 'message' => '', 'debug' => []];

try {
    // 1. Get JSON input
    $jsonInput = file_get_contents('php://input');
    $response['debug']['raw_input'] = $jsonInput;
    
    if (empty($jsonInput)) {
        throw new Exception('No data received', 400);
    }

    // 2. Decode JSON
    $data = json_decode($jsonInput, true);
    $response['debug']['json_decode_error'] = json_last_error_msg();
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg(), 400);
    }

    $response['debug']['received_data'] = $data;

    // 3. Database connection with better error handling
    $conn = new mysqli(
        $dbConfig['host'],
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['database']
    );
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error, 500);
    }

    // 4. Check if record exists first
    $checkQuery = "SELECT id FROM business_settings WHERE id = 1";
    $checkResult = $conn->query($checkQuery);
    $recordExists = $checkResult && $checkResult->num_rows > 0;
    $response['debug']['record_exists'] = $recordExists;

    if (!$recordExists) {
        // Insert a default record if it doesn't exist
        $insertQuery = "INSERT INTO business_settings (id) VALUES (1)";
        if (!$conn->query($insertQuery)) {
            throw new Exception('Failed to create default record: ' . $conn->error, 500);
        }
        $response['debug']['created_default_record'] = true;
    }

    // 5. Prepare SQL with proper parameter count
    $query = "UPDATE business_settings SET 
        business_legal_name = ?,
        trade_name = ?,
        registration_type = ?,
        registration_no = ?,
        date_of_registration = ?,
        industry_code = ?,
        business_description = ?,
        business_tin = ?,
        rdo_code = ?,
        official_address = ?,
        zip_code = ?,
        contact_phone = ?,
        official_email = ?,
        sss_no = ?,
        phic_no = ?,
        hdmf_no = ?,
        peza_cert_no = ?,
        permits = ?,
        enable_multi_branch = ?,
        inventory_tracking_mode = ?,
        use_weighted_avg_cost = ?,
        enable_audit_trail = ?,
        tax_type = ?,
        books_of_accounts = ?,
        accounting_method = ?,
        fiscal_start_month = ?,
        quarter_cutoff = ?,
        withholding_agent = ?,
        currency = ?,
        timezone = ?,
        week_start = ?,
        date_format = ?,
        number_format = ?,
        or_prefix = ?,
        si_prefix = ?,
        next_or_number = ?,
        next_si_number = ?,
        pdf_template = ?
        WHERE id = 1";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error, 500);
    }

    // 6. Prepare values with proper defaults
    $values = [
        $data['business_legal_name'] ?? '',
        $data['trade_name'] ?? null,
        $data['registration_type'] ?? '',
        $data['registration_no'] ?? null,
        $data['date_of_registration'] ?? null,
        $data['industry_code'] ?? null,
        $data['business_description'] ?? null,
        $data['business_tin'] ?? null,
        $data['rdo_code'] ?? null,
        $data['official_address'] ?? null,
        $data['zip_code'] ?? null,
        $data['contact_phone'] ?? null,
        $data['official_email'] ?? null,
        $data['sss_no'] ?? null,
        $data['phic_no'] ?? null,
        $data['hdmf_no'] ?? null,
        $data['peza_cert_no'] ?? null,
        $data['permits'] ?? null,
        (int)($data['enable_multi_branch'] ?? 0),
        $data['inventory_tracking_mode'] ?? null,
        (int)($data['use_weighted_avg_cost'] ?? 0),
        (int)($data['enable_audit_trail'] ?? 0),
        $data['tax_type'] ?? null,
        $data['books_of_accounts'] ?? null,
        $data['accounting_method'] ?? null,
        $data['fiscal_start_month'] ?? null,
        $data['quarter_cutoff'] ?? null,
        (int)($data['withholding_agent'] ?? 0),
        $data['currency'] ?? null,
        $data['timezone'] ?? null,
        $data['week_start'] ?? null,
        $data['date_format'] ?? null,
        $data['number_format'] ?? null,
        $data['or_prefix'] ?? null,
        $data['si_prefix'] ?? null,
        (int)($data['next_or_number'] ?? 0),
        (int)($data['next_si_number'] ?? 0),
        $data['pdf_template'] ?? null
    ];

    $response['debug']['prepared_values'] = $values;
    $response['debug']['value_count'] = count($values);
    
    // Debug - Let's build the type string systematically
    $typeArray = [];
    foreach ($values as $index => $value) {
        if (is_int($value)) {
            $typeArray[] = 'i';
        } else {
            $typeArray[] = 's';
        }
    }
    $typeString = implode('', $typeArray);
    
    $response['debug']['type_string'] = $typeString;
    $response['debug']['type_string_length'] = strlen($typeString);
    $response['debug']['type_breakdown'] = $typeArray;

    // 7. Bind parameters - Using dynamically generated type string
    $bind = $stmt->bind_param($typeString, ...$values);

    if (!$bind) {
        $response['debug']['bind_error'] = $stmt->error ?: 'Unknown bind error';
        $response['debug']['mysql_error'] = $conn->error;
        throw new Exception('Parameter binding failed: ' . ($stmt->error ?: 'Unknown error'), 500);
    }
    
    $response['debug']['bind_success'] = true;

    // 8. Execute and verify
    $executeResult = $stmt->execute();
    $response['debug']['execute_result'] = $executeResult;
    
    if (!$executeResult) {
        $response['debug']['execute_error'] = $stmt->error;
        $response['debug']['mysql_errno'] = $stmt->errno;
        throw new Exception('Execute failed: ' . $stmt->error, 500);
    }

    $affectedRows = $stmt->affected_rows;
    $response['debug']['affected_rows'] = $affectedRows;

    if ($affectedRows === 0) {
        $response['message'] = 'No changes made - data may be identical to existing values';
        $response['success'] = true; // Still success, just no changes
    } else {
        $response['success'] = true;
        $response['message'] = 'Settings saved successfully';
    }

    $response['affected_rows'] = $affectedRows;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['debug']['error_line'] = $e->getLine();
    $response['debug']['error_file'] = $e->getFile();
    http_response_code($e->getCode() ?: 500);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}
?>