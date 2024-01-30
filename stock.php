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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $qty = $_POST['qty'];
    $description = $_POST['description'];
    $type = $_POST['type']; // Make sure to validate and sanitize this

    $conn->begin_transaction(); // Start a transaction

    $checkStmt = $conn->prepare("SELECT Qty FROM stock WHERE Description = ?");
    $checkStmt->bind_param("s", $description);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $newQty = $row['Qty'] + $qty;
        $updateStmt = $conn->prepare("UPDATE stock SET Qty = ? WHERE Description = ?");
        $updateStmt->bind_param("is", $newQty, $description);
        if ($updateStmt->execute()) {
            echo "Stock updated successfully";
        } else {
            echo "Error updating record: " . $conn->error;
            $conn->rollback(); // Rollback transaction on error
        }
    } else {
        $insertStmt = $conn->prepare("INSERT INTO stock (Qty, Description, Type) VALUES (?, ?, ?)");
        $insertStmt->bind_param("iss", $qty, $description, $type);
        if ($insertStmt->execute()) {
            echo "New record created successfully";
        } else {
            echo "Error creating record: " . $conn->error;
            $conn->rollback(); // Rollback transaction on error
        }
    }

    $conn->commit(); // Commit the transaction

    $checkStmt->close();
    if (isset($updateStmt)) $updateStmt->close();
    if (isset($insertStmt)) $insertStmt->close();
    $conn->close();
} else {
    echo "Form not submitted";
}
?>
