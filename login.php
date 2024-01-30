<?php
// login.php

$servername = "localhost"; // Your server name
$username = "root"; // Your database username
$password = ""; // Your database password
$dbname = "medicine"; // Your database name
$port = 3309; 

// Create a new database connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check the database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user input from the form
$userUsername = $_POST['username'];
$userPassword = $_POST['password']; // This should be hashed
$userType = $_POST['userType'];

// SQL query to check if user exists and get the userType
$sql = "SELECT userType, password FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $userUsername);
$stmt->execute();
$result = $stmt->get_result();

// Check if there's a user with the provided username
if ($user = $result->fetch_assoc()) {
    // Verify the password (hashing should be used here)
    if ($userPassword === $user['password']) { // Replace this line with password verification if you use hashed passwords
        // Redirect based on the user type
        if ($user['userType'] === 'admin') {
            // Redirect to the admin stock page if the user is an admin
            echo "<script>alert('Login successful! Redirecting to admin menu page...'); window.location.href='admin_menu.html';</script>";
        } elseif ($user['userType'] === 'worker') {
            // Redirect to the menu page if the user is a worker
            echo "<script>alert('Login successful! Redirecting to menu...'); window.location.href='menu.html';</script>";
        } else {
            // If the user is neither an admin nor a worker
            echo "<script>alert('Access restricted.'); window.location.href='front.html';</script>";
        }
    } else {
        // If the password doesn't match, display an error message
        echo "<script>alert('Invalid username or password!'); window.location.href='front.html';</script>";
    }
} else {
    // If no user is found, display an error message
    echo "<script>alert('Invalid username or password!'); window.location.href='front.html';</script>";
}

// Close the database connection
$conn->close();
?>
