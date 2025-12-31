<?php
include '../db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo "unauthorized";
    exit;
}

// Check if required data is present
if (!isset($_POST['employeeNo']) || !isset($_POST['remarks'])) {
    echo "missing_data";
    exit;
}

$employeeNo = $_POST['employeeNo'];
$remarks = $_POST['remarks'];

// Update remarks in database
$sql = "UPDATE [FCW_List].[dbo].[Employee] 
        SET [Remarks] = ? 
        WHERE [Employee#] = ?";

$params = array($remarks, $employeeNo);
$stmt = sqlsrv_query($conn1, $sql, $params);

if ($stmt === false) {
    error_log(print_r(sqlsrv_errors(), true));
    echo "error";
    exit;
}

echo "success";
sqlsrv_free_stmt($stmt);
?>