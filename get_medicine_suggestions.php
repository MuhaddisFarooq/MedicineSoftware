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

if (isset($_GET['query'])) {
    $query = $_GET['query'];
} else {
    echo json_encode([]);
    exit;
}
$sql = "SELECT Description FROM stock WHERE Description LIKE CONCAT(?, '%') LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $query);
$stmt->execute();
$result = $stmt->get_result();

$medicines = [];
while ($row = $result->fetch_assoc()) {
    $medicines[] = $row['Description'];
}

echo json_encode($medicines);
$stmt->close();
$conn->close();
?>
