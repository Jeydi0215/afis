<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database configuration
include('../../config/config.php');

// Fetch unsigned receipts with valid tokens
$sql = "SELECT Receipt_Id, Customer_Name, signature_token 
        FROM acknowledgment_receipt 
        WHERE is_signed = 0 AND signature_token IS NOT NULL";
$result = $conn->query($sql);

// Get base URL dynamically
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
$baseUrl .= dirname($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Available Acknowledgment Receipt Tokens</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        th { background-color: #f5f5f5; }
        a { color: #1F5497; text-decoration: none; font-weight: bold; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<h2>Available Acknowledgment Receipt Tokens</h2>

<?php if ($result->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Receipt ID</th>
                <th>Customer Name</th>
                <th>Token Link</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): 
                $link = $baseUrl . "/sign_receipt.php?token=" . urlencode($row['signature_token']);
            ?>
            <tr>
                <td><?= htmlspecialchars($row['Receipt_Id']) ?></td>
                <td><?= htmlspecialchars($row['Customer_Name']) ?></td>
                <td><a href="<?= $link ?>" target="_blank"><?= $link ?></a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No valid tokens found.</p>
<?php endif; ?>

<?php $conn->close(); ?>

</body>
</html>
