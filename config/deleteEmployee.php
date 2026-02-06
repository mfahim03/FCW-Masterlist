<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Prevent any output before headers
ob_start();

include '../db.php';
session_start();

// Clear any output that might have been generated
ob_end_clean();

// Check authentication
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../indexView.php");
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method!";
    header("Location: ../employeeInfo.php");
    exit;
}

// Get employee ID from POST
if (!isset($_POST['employee_id']) || empty($_POST['employee_id'])) {
    $_SESSION['error'] = "No employee ID provided!";
    header("Location: ../employeeInfo.php");
    exit;
}

$employee_id = trim($_POST['employee_id']);

// Validate database connection
if (!isset($conn2) || $conn2 === false) {
    $_SESSION['error'] = "Database connection failed.";
    header("Location: ../employeeInfo.php");
    exit;
}

// Log the deletion attempt
error_log("=== DELETE EMPLOYEE ATTEMPT ===");
error_log("Employee ID: " . $employee_id);
error_log("User: " . $_SESSION['username']);

// Get employee details before deleting (for confirmation message and image cleanup)
$image_sql = "SELECT [ImagePath], [Name] FROM [Updated_FCW_List].[dbo].[Employee] WHERE [Employee#] = ?";
$image_stmt = sqlsrv_query($conn2, $image_sql, [$employee_id]);

$employee_name = 'Unknown Employee';
$image_path = null;

if ($image_stmt !== false) {
    $row = sqlsrv_fetch_array($image_stmt, SQLSRV_FETCH_ASSOC);
    if ($row) {
        $employee_name = $row['Name'] ?? 'Unknown Employee';
        $image_path = $row['ImagePath'] ?? null;
        error_log("Found employee: " . $employee_name);
        if ($image_path) {
            error_log("Image path: " . $image_path);
        }
    } else {
        error_log("Employee not found in database");
        $_SESSION['error'] = "Employee not found!";
        header("Location: ../employeeInfo.php");
        exit;
    }
    sqlsrv_free_stmt($image_stmt);
} else {
    $errors = sqlsrv_errors();
    error_log("Error fetching employee details: " . print_r($errors, true));
    $_SESSION['error'] = "Error fetching employee details.";
    header("Location: ../employeeInfo.php");
    exit;
}

// Delete employee record from database
$delete_sql = "DELETE FROM [Updated_FCW_List].[dbo].[Employee] WHERE [Employee#] = ?";
$delete_stmt = sqlsrv_query($conn2, $delete_sql, [$employee_id]);

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
    error_log("SQL Errors: " . print_r($errors, true));
    $_SESSION['error'] = $error_message;
    header("Location: ../employeeInfo.php");
    exit;
}

// Check if any rows were affected
$rows_affected = sqlsrv_rows_affected($delete_stmt);
error_log("Rows affected: " . $rows_affected);

sqlsrv_free_stmt($delete_stmt);

if ($rows_affected === 0) {
    error_log("No rows deleted - employee may not exist");
    $_SESSION['error'] = "Employee not found or already deleted.";
    header("Location: ../employeeInfo.php");
    exit;
}

// Delete employee image file if it exists
if ($image_path && !empty($image_path)) {
    // Get the full path
    $full_path = $image_path;
    
    // If path doesn't start with root, prepend the parent directory
    if (!file_exists($full_path) && !preg_match('/^[\/\\\\]/', $image_path)) {
        $full_path = '../' . $image_path;
    }
    
    if (file_exists($full_path)) {
        if (@unlink($full_path)) {
            error_log("Successfully deleted image: " . $full_path);
        } else {
            error_log("Failed to delete image: " . $full_path);
        }
    } else {
        error_log("Image file not found: " . $full_path);
    }
}

// Success message
error_log("=== DELETE SUCCESSFUL ===");
$_SESSION['success'] = "Employee " . htmlspecialchars($employee_name) . " (ID: " . htmlspecialchars($employee_id) . ") deleted successfully!";
header("Location: ../employeeInfo.php");
exit;
?>