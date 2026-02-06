<?php
/**
 * eocDetail.php - Ultra-robust employee data fetching for EOC/Runaway details
 * This version includes comprehensive error handling and logging
 */

// Enable error logging
error_log("=== eocDetail.php START ===");

// Get employee ID from URL
$employeeId = $_GET['id'] ?? '';
error_log("Received Employee ID: '" . $employeeId . "'");

if (empty($employeeId)) {
    error_log("ERROR: No employee ID provided");
    $_SESSION['error'] = "No employee ID provided.";
    header("Location: runaway.php");
    exit;
}

/**
 * Helper function to format dates safely
 * Returns empty string for invalid dates, Y-m-d format for valid dates
 */
function formatDate($date) {
    try {
        // Handle null or empty
        if ($date === null || $date === '' || $date === 'NULL') {
            return '';
        }
        
        // Handle DateTime objects
        if ($date instanceof DateTime) {
            return $date->format('Y-m-d');
        }
        
        // Handle string dates
        if (is_string($date)) {
            $trimmed = trim($date);
            
            // Check for invalid dates
            $invalidDates = [
                '1900-01-01 00:00:00.000',
                '0000-00-00 00:00:00.000',
                '1900-01-01',
                '0000-00-00',
                ''
            ];
            
            if (in_array($trimmed, $invalidDates)) {
                return '';
            }
            
            // Try to create DateTime object
            try {
                $dt = new DateTime($trimmed);
                $year = (int)$dt->format('Y');
                
                // Validate year is reasonable
                if ($year >= 1900 && $year <= 2100) {
                    return $dt->format('Y-m-d');
                }
            } catch (Exception $e) {
                error_log("Date parsing failed for: " . $trimmed);
            }
        }
        
        return '';
        
    } catch (Exception $e) {
        error_log("formatDate exception: " . $e->getMessage());
        return '';
    }
}

// Initialize employee data array with defaults
$employee = [];
$img_path = 'img/default-avatar.png';

try {
    // Validate database connection
    if (!isset($conn2) || $conn2 === false) {
        throw new Exception("Database connection not available");
    }
    
    error_log("Database connection OK");
    
    // Simple direct query to fetch ALL data
    $sql = "SELECT * FROM [Updated_FCW_List].[dbo].[eoc] WHERE [Employee#] = ?";
    error_log("Executing query: $sql with parameter: $employeeId");
    
    $stmt = sqlsrv_query($conn2, $sql, array($employeeId));
    
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        error_log("SQL Error: " . print_r($errors, true));
        throw new Exception("Database query failed");
    }
    
    error_log("Query executed successfully");
    
    // Fetch the data
    $employee = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    
    if (!$employee) {
        error_log("No employee found with ID: $employeeId");
        $_SESSION['error'] = "Employee not found with ID: " . htmlspecialchars($employeeId);
        header("Location: runaway.php");
        exit;
    }
    
    error_log("Employee data fetched successfully. Name: " . ($employee['Name'] ?? 'N/A'));
    
    // Get Department name if DepartmentID exists
    if (!empty($employee['DepartmentID'])) {
        try {
            $deptSql = "SELECT [Department] FROM [Updated_FCW_List].[dbo].[Department] WHERE [DepartmentID] = ?";
            $deptStmt = sqlsrv_query($conn2, $deptSql, array($employee['DepartmentID']));
            
            if ($deptStmt !== false) {
                $deptRow = sqlsrv_fetch_array($deptStmt, SQLSRV_FETCH_ASSOC);
                if ($deptRow && isset($deptRow['Department'])) {
                    $employee['Department'] = $deptRow['Department'];
                    error_log("Department fetched: " . $employee['Department']);
                }
            }
        } catch (Exception $e) {
            error_log("Department fetch failed: " . $e->getMessage());
            // Continue anyway
        }
    }
    
    // Get Nationality name if NationalityID exists
    if (!empty($employee['NationalityID'])) {
        try {
            $natSql = "SELECT [Nationality] FROM [Updated_FCW_List].[dbo].[Nationality] WHERE [NationalityID] = ?";
            $natStmt = sqlsrv_query($conn2, $natSql, array($employee['NationalityID']));
            
            if ($natStmt !== false) {
                $natRow = sqlsrv_fetch_array($natStmt, SQLSRV_FETCH_ASSOC);
                if ($natRow && isset($natRow['Nationality'])) {
                    $employee['Nationality'] = $natRow['Nationality'];
                    error_log("Nationality fetched: " . $employee['Nationality']);
                }
            }
        } catch (Exception $e) {
            error_log("Nationality fetch failed: " . $e->getMessage());
            // Continue anyway
        }
    }
    
    // Set default values for critical fields that might be missing
    $employee['Department'] = $employee['Department'] ?? 'N/A';
    $employee['Nationality'] = $employee['Nationality'] ?? 'N/A';
    $employee['Status'] = $employee['Status'] ?? 'EOC';
    $employee['Name'] = $employee['Name'] ?? 'Unknown';
    
    // Format Employee# to remove decimals if needed
    if (isset($employee['Employee#'])) {
        if (is_numeric($employee['Employee#'])) {
            $employee['Employee#'] = (string)(int)$employee['Employee#'];
        }
    }
    
    // Get image path or use default
    if (!empty($employee['ImagePath'])) {
        // Check if file actually exists
        if (file_exists($employee['ImagePath'])) {
            $img_path = $employee['ImagePath'];
            error_log("Using employee image: " . $img_path);
        } else {
            error_log("Image path in database but file not found: " . $employee['ImagePath']);
            $img_path = 'img/default-avatar.png';
        }
    } else {
        $img_path = 'img/default-avatar.png';
    }
    
    error_log("=== eocDetail.php SUCCESS ===");
    
} catch (Exception $e) {
    error_log("=== eocDetail.php FATAL ERROR ===");
    error_log("Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $_SESSION['error'] = "Error loading employee details: " . $e->getMessage();
    header("Location: runaway.php");
    exit;
}

// Final validation - ensure we have minimum required data
if (empty($employee['Employee#']) || empty($employee['Name'])) {
    error_log("CRITICAL: Missing required employee data");
    $_SESSION['error'] = "Incomplete employee data";
    header("Location: runaway.php");
    exit;
}

error_log("Employee data ready for display. Employee#: " . $employee['Employee#']);
?>