<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Query to check if user exists
    $sql = "SELECT * FROM users WHERE username = ? AND password = ?";
    $params = [$username, $password];
    $stmt = sqlsrv_query($conn2, $sql, $params);

    if ($stmt === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred.'
        ]);
        exit;
    }

    if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];
        
        echo json_encode([
            'success' => true,
            'role' => $row['role']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username or password.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}
?>