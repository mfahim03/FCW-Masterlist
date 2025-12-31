<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Prevent any output before headers
ob_start();

include 'db.php';
session_start();

// Clear any output that might have been generated
ob_end_clean();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No employee ID provided!";
    header("Location: employeeInfo.php");
    exit;
}

$employee_id = $_GET['id'];

// Validate database connection
if (!isset($conn1) || $conn1 === false) {
    $_SESSION['error'] = "Database connection failed.";
    header("Location: employeeInfo.php");
    exit;
}

// Get employee details before deleting (for confirmation message and image cleanup)
$image_sql = "SELECT [ImagePath], [Name] FROM [FCW_List].[dbo].[Employee] WHERE [Employee#] = ?";
$image_stmt = sqlsrv_query($conn1, $image_sql, [$employee_id]);

$employee_name = 'Unknown Employee';
$image_path = null;

if ($image_stmt !== false) {
    $row = sqlsrv_fetch_array($image_stmt, SQLSRV_FETCH_ASSOC);
    if ($row) {
        $employee_name = $row['Name'] ?? 'Unknown Employee';
        $image_path = $row['ImagePath'] ?? null;
    }
    sqlsrv_free_stmt($image_stmt);
} else {
    error_log("Error fetching employee details: " . print_r(sqlsrv_errors(), true));
}

// Delete employee record from database
$delete_sql = "DELETE FROM [FCW_List].[dbo].[Employee] WHERE [Employee#] = ?";
$delete_stmt = sqlsrv_query($conn1, $delete_sql, [$employee_id]);

if ($delete_stmt === false) {
    $errors = sqlsrv_errors();
    $error_message = "Error deleting employee: ";
    if (is_array($errors)) {
        foreach ($errors as $error) {
            if (isset($error['message'])) {
                $error_message .= $error['message'] . " ";
            }
        }
    }
    error_log("Delete Error: " . $error_message);
    $_SESSION['error'] = $error_message;
    header("Location: employeeInfo.php");
    exit;
}

sqlsrv_free_stmt($delete_stmt);

// Delete employee image file if it exists
if ($image_path && !empty($image_path) && file_exists($image_path)) {
    if (@unlink($image_path)) {
        error_log("Successfully deleted image: " . $image_path);
    } else {
        error_log("Failed to delete image: " . $image_path);
    }
}

// Success message
$_SESSION['success'] = "Employee " . htmlspecialchars($employee_name) . " (ID: " . htmlspecialchars($employee_id) . ") deleted successfully!";
header("Location: employeeInfo.php");
exit;
?>