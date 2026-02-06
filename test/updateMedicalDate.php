<?php
include '../db.php';
session_start();

if (!isset($_SESSION['username'])) {
    echo "unauthorized";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = $_POST['id'] ?? '';
    $medicalDate = $_POST['date'] ?? '';

    if (empty($employeeId) || empty($medicalDate)) {
        echo "missing_data";
        exit;
    }

    // Update the medical checkup date
    $sql = "UPDATE [FCW_List].[dbo].[Employee] 
            SET [MedicalDate] = ? 
            WHERE [Employee#] = ?";
    
    $params = array($medicalDate, $employeeId);
    $stmt = sqlsrv_query($conn1, $sql, $params);

    if ($stmt === false) {
        echo "error: " . print_r(sqlsrv_errors(), true);
        exit;
    }

    echo "success";
} else {
    echo "invalid_request";
}
?>