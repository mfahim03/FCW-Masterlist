<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user
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

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: addEmployee.php");
    exit;
}

// Validate database connection
if (!isset($conn1) || $conn1 === false) {
    $_SESSION['error'] = "Database connection failed. Please check your database settings.";
    header("Location: addEmployee.php");
    exit;
}

$employee_id = trim($_POST['employee_no'] ?? '');
$imagePath = null;

// Validate required fields
if (empty($employee_id)) {
    $_SESSION['error'] = "Employee number is required!";
    header("Location: addEmployee.php");
    exit;
}

if (empty($_POST['name'])) {
    $_SESSION['error'] = "Employee name is required!";
    header("Location: addEmployee.php");
    exit;
}

$department_id = trim($_POST['department_id'] ?? '');

if (empty($department_id)) {
    $_SESSION['error'] = "Department is required!";
    header("Location: addEmployee.php");
    exit;
}

if (empty($_POST['position'])) {
    $_SESSION['error'] = "Position is required!";
    header("Location: addEmployee.php");
    exit;
}

if (empty($_POST['hire_date'])) {
    $_SESSION['error'] = "Hire date is required!";
    header("Location: addEmployee.php");
    exit;
}

if (empty($_POST['gender'])) {
    $_SESSION['error'] = "Gender is required!";
    header("Location: addEmployee.php");
    exit;
}

if (empty($_POST['nationality_id'])) {
    $_SESSION['error'] = "Nationality is required!";
    header("Location: addEmployee.php");
    exit;
}

// Check if employee ID already exists
$check_sql = "SELECT [Employee#] FROM [FCW_List].[dbo].[Employee] WHERE [Employee#] = ?";
$check_params = [$employee_id];
$check_stmt = sqlsrv_query($conn1, $check_sql, $check_params);

if ($check_stmt === false) {
    $errors = sqlsrv_errors();
    $error_msg = "Database error: ";
    if (is_array($errors)) {
        foreach ($errors as $error) {
            $error_msg .= isset($error['message']) ? $error['message'] . " " : "";
        }
    }
    $_SESSION['error'] = $error_msg;
    header("Location: addEmployee.php");
    exit;
}

if (sqlsrv_fetch_array($check_stmt, SQLSRV_FETCH_ASSOC)) {
    $_SESSION['error'] = "Employee number already exists!";
    header("Location: addEmployee.php");
    exit;
}

// Handle image upload
if (isset($_FILES['employee_image']) && $_FILES['employee_image']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['employee_image']['name'];
    $filesize = $_FILES['employee_image']['size'];
    
    // Get file extension
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Validate file extension
    if (!in_array($ext, $allowed)) {
        $_SESSION['error'] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        header("Location: addEmployee.php");
        exit;
    }
    
    // Validate file size (10MB max)
    if ($filesize > 10 * 1024 * 1024) {
        $_SESSION['error'] = "File size exceeds 10MB limit.";
        header("Location: addEmployee.php");
        exit;
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = 'img/employee_images/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            $_SESSION['error'] = "Failed to create upload directory. Please check permissions.";
            header("Location: addEmployee.php");
            exit;
        }
        chmod($upload_dir, 0777);
    }
    
    // Generate unique filename using employee ID
    $new_filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $employee_id) . '_' . time() . '.' . $ext;
    $target_file = $upload_dir . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['employee_image']['tmp_name'], $target_file)) {
        $imagePath = $target_file;
    } else {
        $_SESSION['error'] = "Failed to upload image. Please check folder permissions.";
        header("Location: addEmployee.php");
        exit;
    }
}

// Get medical status from POST data and convert to appropriate value for database
$medicalStatus = trim($_POST['medical_status'] ?? 'Incomplete');
// Validate medical status
if (!in_array($medicalStatus, ['Complete', 'Incomplete'])) {
    $medicalStatus = 'Incomplete';
}

// Convert status to database value
// Complete = current date, Incomplete = NULL
$medicalDateValue = ($medicalStatus === 'Complete') ? date('Y-m-d') : null;

// Check if ImagePath column exists
$check_column_sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_NAME = 'Employee' 
                     AND TABLE_SCHEMA = 'dbo' 
                     AND COLUMN_NAME = 'ImagePath'";
$check_column_stmt = sqlsrv_query($conn1, $check_column_sql);
$has_image_column = false;

if ($check_column_stmt && sqlsrv_fetch_array($check_column_stmt, SQLSRV_FETCH_ASSOC)) {
    $has_image_column = true;
}

// Build column list
$columns = "[Employee#], [Name], [Permit Name], [Birthdate], [Gender], [Race], [NationalityID], 
            [DepartmentID], [Cost Centre], [Position], [Grade], [Hire Date], [YOS], [Contract], 
            [(EE)/Shift Group], [Work Permit Number], [Work Permit Expiry (New)], [Old Passport], 
            [New Passport], [Passport Expiry Date], [MedicalDate], [SPIKPA Expiry], [SOCSO No], 
            [Contact No (Employee)], [Email Address], [Hostel], [Destination], 
            [Address In Source Country], [Next Of Kin], [Relationship], 
            [Contact No In Source Country], [TPEA], [Remarks]";

// Build values placeholder
$placeholders = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";

// Add ImagePath if column exists and image uploaded
if ($imagePath && $has_image_column) {
    $columns .= ", [ImagePath]";
    $placeholders .= ", ?";
}

// Prepare insert query
$sql = "INSERT INTO [FCW_List].[dbo].[Employee] ($columns) VALUES ($placeholders)";

// Prepare parameters
$params = [
    $employee_id,
    trim($_POST['name'] ?? ''),
    trim($_POST['permit_name'] ?? ''),
    !empty($_POST['birthdate']) ? $_POST['birthdate'] : null,
    trim($_POST['gender'] ?? ''),
    trim($_POST['race'] ?? ''),
    !empty($_POST['nationality_id']) ? intval($_POST['nationality_id']) : null,
    !empty($department_id) ? intval($department_id) : null,
    trim($_POST['cost_centre'] ?? ''),
    trim($_POST['position'] ?? ''),
    trim($_POST['grade'] ?? ''),
    !empty($_POST['hire_date']) ? $_POST['hire_date'] : null,
    trim($_POST['yos'] ?? ''),
    trim($_POST['contract'] ?? ''),
    trim($_POST['shift_group'] ?? ''),
    trim($_POST['work_permit_no'] ?? ''),
    !empty($_POST['work_permit_expiry']) ? $_POST['work_permit_expiry'] : null,
    trim($_POST['old_passport'] ?? ''),
    trim($_POST['new_passport'] ?? ''),
    !empty($_POST['passport_expiry']) ? $_POST['passport_expiry'] : null,
    $medicalDateValue,  // Store current date for Complete, NULL for Incomplete
    !empty($_POST['spikpa_expiry']) ? $_POST['spikpa_expiry'] : null,
    trim($_POST['socso_no'] ?? ''),
    trim($_POST['contact_no'] ?? ''),
    trim($_POST['email'] ?? ''),
    trim($_POST['hostel'] ?? ''),
    trim($_POST['destination'] ?? ''),
    trim($_POST['address_source'] ?? ''),
    trim($_POST['next_of_kin'] ?? ''),
    trim($_POST['relationship'] ?? ''),
    trim($_POST['contact_source'] ?? ''),
    trim($_POST['tpea'] ?? ''),
    trim($_POST['remarks'] ?? '')
];

// Add image path if applicable
if ($imagePath && $has_image_column) {
    $params[] = $imagePath;
}

// Execute insert
$stmt = sqlsrv_query($conn1, $sql, $params);

if ($stmt === false) {
    // If insert fails and image was uploaded, delete the image
    if ($imagePath && file_exists($imagePath)) {
        unlink($imagePath);
    }
    
    $errors = sqlsrv_errors();
    $error_message = "Error adding employee: ";
    if (is_array($errors)) {
        foreach ($errors as $error) {
            if (isset($error['message'])) {
                $error_message .= $error['message'] . " ";
            }
        }
    }
    error_log("SQL Error: " . $error_message);
    $_SESSION['error'] = $error_message;
    header("Location: addEmployee.php");
    exit;
}

// Success
$success_msg = "Employee added successfully!";
if ($imagePath && !$has_image_column) {
    $success_msg .= " Note: Image was uploaded but ImagePath column doesn't exist in database. Please run the setup script.";
}
$_SESSION['success'] = $success_msg;
header("Location: employeeInfo.php?id=" . urlencode($employee_id));
exit;
?>