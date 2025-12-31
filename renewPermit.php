<?php
include 'db.php';
session_start();

if (!isset($_SESSION['username'])) {
    echo "unauthorized";
    exit;
}

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // Update Work Permit Expiry by adding 1 year AND Reset Medical Status to Incomplete
    $sql = "
        UPDATE [FCW_List].[dbo].[Employee]
        SET [Work Permit Expiry (New)] = DATEADD(year, 1, [Work Permit Expiry (New)]),
            [MedicalDate] = 'Incomplete'
        WHERE [Employee#] = ?
    ";

    $stmt = sqlsrv_query($conn1, $sql, [$id]);

    if ($stmt) {
        echo "success";
    } else {
        echo "error: " . print_r(sqlsrv_errors(), true);
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn1);
} else {
    echo "error: No ID provided";
}
?>