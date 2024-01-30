<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "medicine";
$port = 3309;

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT MAX(invoice_id) AS last_invoice_id FROM Invoices";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    $nextInvoiceId = $row['last_invoice_id'] + 1;
} else {
    $nextInvoiceId = 1; // Default to 1 if no invoices are present
}

echo json_encode(['next_invoice_id' => $nextInvoiceId]);
$conn->close();
?>
