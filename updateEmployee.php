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

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: employeeInfo.php");
    exit;
}

// Validate database connection
if (!isset($conn1) || $conn1 === false) {
    $_SESSION['error'] = "Database connection failed. Please check your database settings.";
    header("Location: employeeInfo.php");
    exit;
}

$employee_id = trim($_POST['employee_id'] ?? '');
$imagePath = null;
$removeImage = false;

// Validate required field
if (empty($employee_id)) {
    $_SESSION['error'] = "Employee ID is required!";
    header("Location: employeeInfo.php");
    exit;
}

// Check if user wants to remove the image
if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
    // Get current image path
    $old_image_sql = "SELECT [ImagePath] FROM [FCW_List].[dbo].[Employee] WHERE [Employee#] = ?";
    $old_image_stmt = sqlsrv_query($conn1, $old_image_sql, [$employee_id]);
    
    if ($old_image_stmt !== false) {
        if ($old_image_row = sqlsrv_fetch_array($old_image_stmt, SQLSRV_FETCH_ASSOC)) {
            // Delete the physical file
            if (!empty($old_image_row['ImagePath']) && file_exists($old_image_row['ImagePath'])) {
                @unlink($old_image_row['ImagePath']);
            }
            // Set imagePath to empty string to update database
            $imagePath = '';
            $removeImage = true;
        }
    }
}

// Handle image upload (only if not removing)
if (!$removeImage && isset($_FILES['employee_image']) && $_FILES['employee_image']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['employee_image']['name'];
    $filesize = $_FILES['employee_image']['size'];
    
    // Get file extension
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Validate file extension
    if (!in_array($ext, $allowed)) {
        $_SESSION['error'] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        header("Location: employeeInfo.php?id=" . urlencode($employee_id));
        exit;
    }
    
    // Validate file size (10MB max)
    if ($filesize > 10 * 1024 * 1024) {
        $_SESSION['error'] = "File size exceeds 10MB limit.";
        header("Location: employeeInfo.php?id=" . urlencode($employee_id));
        exit;
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = 'img/employee_images/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            $_SESSION['error'] = "Failed to create upload directory.";
            header("Location: employeeInfo.php?id=" . urlencode($employee_id));
            exit;
        }
        chmod($upload_dir, 0777);
    }
    
    // Generate unique filename using employee ID
    $new_filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $employee_id) . '_' . time() . '.' . $ext;
    $target_file = $upload_dir . $new_filename;
    
    // Delete old image if exists
    $old_image_sql = "SELECT [ImagePath] FROM [FCW_List].[dbo].[Employee] WHERE [Employee#] = ?";
    $old_image_params = [$employee_id];
    $old_image_stmt = sqlsrv_query($conn1, $old_image_sql, $old_image_params);
    
    if ($old_image_stmt !== false) {
        if ($old_image_row = sqlsrv_fetch_array($old_image_stmt, SQLSRV_FETCH_ASSOC)) {
            if (!empty($old_image_row['ImagePath']) && file_exists($old_image_row['ImagePath'])) {
                @unlink($old_image_row['ImagePath']);
            }
        }
    }
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['employee_image']['tmp_name'], $target_file)) {
        $imagePath = $target_file;
    } else {
        $_SESSION['error'] = "Failed to upload image. Please check folder permissions.";
        header("Location: employeeInfo.php?id=" . urlencode($employee_id));
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

// Build the UPDATE query
$sql = "UPDATE [FCW_List].[dbo].[Employee] SET 
        [Name] = ?,
        [Permit Name] = ?,
        [Birthdate] = ?,
        [Gender] = UPPER(?),
        [Race] = ?,
        [NationalityID] = ?,
        [DepartmentID] = ?,  
        [Cost Centre] = ?,
        [Position] = ?,
        [Grade] = ?,
        [Hire Date] = ?,
        [YOS] = ?,
        [Contract] = UPPER(?),
        [(EE)/Shift Group] = ?,
        [Work Permit Number] = ?,
        [Work Permit Expiry (New)] = ?,
        [Old Passport] = ?,
        [New Passport] = ?,
        [Passport Expiry Date] = ?,
        [MedicalDate] = ?,
        [SPIKPA Expiry] = ?,
        [SOCSO No] = ?,
        [Contact No (Employee)] = ?,
        [Email Address] = ?,
        [Hostel] = UPPER(?),
        [Destination] = ?,
        [Address In Source Country] = ?,
        [Next Of Kin] = ?,
        [Relationship] = ?,
        [Contact No In Source Country] = ?,
        [TPEA] = UPPER(?),
        [Remarks] = ?";

// Prepare parameters array
$params = [
    trim($_POST['name'] ?? ''),
    trim($_POST['permit_name'] ?? ''),
    !empty($_POST['birthdate']) ? $_POST['birthdate'] : null,
    trim($_POST['gender'] ?? ''),
    trim($_POST['race'] ?? ''),
    !empty($_POST['nationality_id']) ? intval($_POST['nationality_id']) : null,
    !empty($_POST['department_id']) ? intval($_POST['department_id']) : null,
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

// Add ImagePath to query if image was uploaded or removed
if ($imagePath !== null || $removeImage) {
    // Check if ImagePath column exists
    $check_column_sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                         WHERE TABLE_NAME = 'Employee' 
                         AND TABLE_SCHEMA = 'dbo' 
                         AND COLUMN_NAME = 'ImagePath'";
    $check_column_stmt = sqlsrv_query($conn1, $check_column_sql);
    
    if ($check_column_stmt && sqlsrv_fetch_array($check_column_stmt, SQLSRV_FETCH_ASSOC)) {
        // Column exists, add to query
        $sql .= ", [ImagePath] = ?";
        // If removing, set to null, otherwise use the new path
        $params[] = $removeImage ? null : $imagePath; 
    } else {
        // Column doesn't exist, show warning
        $_SESSION['warning'] = "Image uploaded but ImagePath column not found. Run setup script.";
    }
}

$sql .= " WHERE [Employee#] = ?";
$params[] = $employee_id;

// Execute update
$stmt = sqlsrv_query($conn1, $sql, $params);

if ($stmt === false) {
    $errors = sqlsrv_errors();
    $error_message = "Error updating employee: ";
    if (is_array($errors)) {
        foreach ($errors as $error) {
            if (isset($error['message'])) {
                $error_message .= $error['message'] . " ";
            }
        }
    }
    error_log("SQL Update Error: " . $error_message);
    $_SESSION['error'] = $error_message;
    header("Location: employeeInfo.php?id=" . urlencode($employee_id));
    exit;
}

// Success
$success_msg = "Employee information updated successfully!";
if ($removeImage) {
    $success_msg .= " Photo removed.";
}
if (isset($_SESSION['warning'])) {
    $success_msg .= " " . $_SESSION['warning'];
    unset($_SESSION['warning']);
}
$_SESSION['success'] = $success_msg;
header("Location: employeeInfo.php?id=" . urlencode($employee_id));
exit;
?>