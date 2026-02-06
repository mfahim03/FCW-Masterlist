<?php
session_start();
include '../db.php';

// Set header for JSON response
header('Content-Type: application/json');

// Check if user is authenticated and is admin
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Check if required parameters are provided
if (!isset($_POST['empNo']) || !isset($_POST['FlightDate'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$empNo = $_POST['empNo'];
$flightDate = $_POST['FlightDate'];

// Validate employee number
if (empty($empNo)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid employee number'
    ]);
    exit;
}

// Check database connection
if (!$conn2) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

try {
    // Prepare the update query
    // If flightDate is empty, set it to NULL
    if (empty($flightDate)) {
        $sql = "UPDATE [Updated_FCW_List].[dbo].[eoc] 
                SET [FlightDate] = NULL 
                WHERE [Employee#] = ?";
        $params = [$empNo];
    } else {
        // Validate date format (YYYY-MM-DD)
        $dateObj = DateTime::createFromFormat('Y-m-d', $flightDate);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $flightDate) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid date format'
            ]);
            exit;
        }
        
        $sql = "UPDATE [Updated_FCW_List].[dbo].[eoc] 
                SET [FlightDate] = ? 
                WHERE [Employee#] = ?";
        $params = [$flightDate, $empNo];
    }
    
    // Execute the update
    $stmt = sqlsrv_query($conn2, $sql, $params);
    
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        error_log("Error updating flight date: " . print_r($errors, true));
        
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred'
        ]);
        exit;
    }
    
    // Check if any rows were affected
    $rows_affected = sqlsrv_rows_affected($stmt);
    
    if ($rows_affected === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Employee not found or no changes made'
        ]);
        exit;
    }
    
    // Success
    echo json_encode([
        'success' => true,
        'message' => 'Flight date updated successfully',
        'empNo' => $empNo,
        'FlightDate' => $flightDate
    ]);
    
} catch (Exception $e) {
    error_log("Exception updating flight date: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating flight date'
    ]);
}

// Close the statement if it exists
if (isset($stmt)) {
    sqlsrv_free_stmt($stmt);
}
?>