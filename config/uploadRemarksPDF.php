<?php
// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php-errors.log'); // Make sure this directory exists

session_start();
include '../db.php';

// Set header for JSON response
header('Content-Type: application/json');

try {
    // Check if user is authenticated and is admin
    if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized access'
        ]);
        exit;
    }

    // Check if required parameters are provided
    if (!isset($_POST['empNo'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Employee number is required'
        ]);
        exit;
    }

    // Check if file was uploaded
    if (!isset($_FILES['remarksPDF']) || $_FILES['remarksPDF']['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'No file uploaded';
        if (isset($_FILES['remarksPDF']['error'])) {
            switch($_FILES['remarksPDF']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMessage = 'File size exceeds limit';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMessage = 'File upload was incomplete';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMessage = 'No file was uploaded';
                    break;
                default:
                    $errorMessage = 'File upload error occurred (Code: ' . $_FILES['remarksPDF']['error'] . ')';
            }
        }
        
        echo json_encode([
            'success' => false,
            'message' => $errorMessage
        ]);
        exit;
    }

    $empNo = $_POST['empNo'];
    $file = $_FILES['remarksPDF'];

    // Validate employee number
    if (empty($empNo)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid employee number'
        ]);
        exit;
    }

    // Validate file type
    $allowedTypes = ['application/pdf'];
    $allowedExtensions = ['pdf'];
    
    // Check file extension first
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode([
            'success' => false,
            'message' => 'Only PDF files are allowed'
        ]);
        exit;
    }
    
    // Check MIME type if finfo is available
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid file type. Only PDF files are allowed.'
                ]);
                exit;
            }
        }
    }

    // Validate file size (max 10MB)
    $maxFileSize = 10 * 1024 * 1024; // 10MB in bytes
    if ($file['size'] > $maxFileSize) {
        echo json_encode([
            'success' => false,
            'message' => 'File size must be less than 10MB'
        ]);
        exit;
    }

    // Check database connection
    if (!isset($conn2) || !$conn2) {
        error_log("Database connection error: conn2 not available");
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit;
    }

    // Create upload directory if it doesn't exist
    $uploadDir = dirname(__FILE__) . '/../uploads/remarks/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            error_log("Failed to create directory: " . $uploadDir);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create upload directory'
            ]);
            exit;
        }
    }
    
    // Check if directory is writable
    if (!is_writable($uploadDir)) {
        error_log("Upload directory not writable: " . $uploadDir);
        echo json_encode([
            'success' => false,
            'message' => 'Upload directory is not writable'
        ]);
        exit;
    }
    
    // Generate unique filename
    $sanitizedEmpNo = preg_replace('/[^a-zA-Z0-9]/', '_', $empNo);
    $timestamp = date('YmdHis');
    $uniqueId = uniqid();
    $newFileName = "remarks_{$sanitizedEmpNo}_{$timestamp}_{$uniqueId}.{$fileExtension}";
    $uploadPath = $uploadDir . $newFileName;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        error_log("Failed to move uploaded file from " . $file['tmp_name'] . " to " . $uploadPath);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to upload file. Please check server permissions.'
        ]);
        exit;
    }
    
    // Store relative path in database
    $relativePath = 'uploads/remarks/' . $newFileName;
    
    // Update database with file path
    $sql = "UPDATE [Updated_FCW_List].[dbo].[eoc] 
            SET [RemarksPDF] = ? 
            WHERE [Employee#] = ?";
    $params = [$relativePath, $empNo];
    
    $stmt = sqlsrv_query($conn2, $sql, $params);
    
    if ($stmt === false) {
        // Delete uploaded file if database update fails
        if (file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        
        $errors = sqlsrv_errors();
        error_log("SQL Server Error updating RemarksPDF: " . print_r($errors, true));
        
        // Provide more specific error message based on SQL error
        $errorMsg = 'Database error occurred';
        if (is_array($errors) && count($errors) > 0) {
            $errorMsg .= ': ' . $errors[0]['message'];
        }
        
        echo json_encode([
            'success' => false,
            'message' => $errorMsg
        ]);
        exit;
    }
    
    // Check if any rows were affected
    $rows_affected = sqlsrv_rows_affected($stmt);
    
    if ($rows_affected === false) {
        error_log("Could not determine rows affected");
    }
    
    if ($rows_affected === 0) {
        // Delete uploaded file if no records updated
        if (file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        
        error_log("No employee found with Employee# = " . $empNo);
        
        echo json_encode([
            'success' => false,
            'message' => 'Employee not found in database'
        ]);
        exit;
    }
    
    // Success
    echo json_encode([
        'success' => true,
        'message' => 'Remarks PDF uploaded successfully',
        'empNo' => $empNo,
        'filePath' => $relativePath
    ]);
    
    // Close statement
    sqlsrv_free_stmt($stmt);
    
} catch (Exception $e) {
    error_log("Exception in uploadRemarksPDF.php: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your request'
    ]);
} catch (Error $e) {
    error_log("Fatal Error in uploadRemarksPDF.php: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'A system error occurred'
    ]);
}
?>