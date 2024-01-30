<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "medicine";
$port = 3309;

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to select all data from the stock table
$sql = "SELECT Description, Qty FROM stock";
$result = $conn->query($sql);

$stockData = [];

// Check if there are any results
if ($result && $result->num_rows > 0) {
    // Output data of each row
    while($row = $result->fetch_assoc()) {
        $stockData[] = [
            'name' => $row['Description'],
            'quantity' => $row['Qty']
        ];
    }
} else {
    echo "0 results";
}

// Close connection
$conn->close();

// Convert the result to JSON
header('Content-Type: application/json');
echo json_encode($stockData);
?>
