<?php
include 'db.php';
session_start();

if (!isset($_SESSION['username'])) {
    die("unauthorized");
}

$id = $_POST['id'] ?? null;
$years = isset($_POST['years']) ? (int)$_POST['years'] : 5; // Default to 5 

// Validate years (1-10)
if ($years < 1 || $years > 10) {
    $years = 5;
}

if ($id) {
    $sql = "UPDATE [FCW_List].[dbo].[Employee] 
            SET [Passport Expiry Date] = DATEADD(YEAR, ?, [Passport Expiry Date]),
                [Passport Renewed Status] = 1
            WHERE [Employee#] = ?";
    
    $params = array($years, $id);
    $stmt = sqlsrv_query($conn1, $sql, $params);
    
    if ($stmt) {
        echo "success";
    } else {
        echo "error";
    }
    sqlsrv_free_stmt($stmt);
} else {
    echo "error";
}
?>