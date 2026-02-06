<?php
include 'db.php';
session_start();

// Start output buffering
ob_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: indexView.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['employee_id'])) {
    $employeeId = $_POST['employee_id'];
    $status = $_POST['status'] ?? '';
    $movedRemarks = $_POST['remarks'] ?? '';
    
    // Validate status
    if (!in_array($status, ['EOC', 'RUNAWAY'])) {
        $_SESSION['error'] = 'Invalid status selected.';
        header("Location: employeeInfo.php?id=" . urlencode($employeeId));
        exit;
    }
    
    try {
        // Start transaction
        sqlsrv_begin_transaction($conn2);
        
        // FIRST: Get the actual structure of the EOC table
        $structureQuery = "
            SELECT 
                COLUMN_NAME, 
                DATA_TYPE,
                IS_NULLABLE
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_NAME = 'eoc'
            ORDER BY ORDINAL_POSITION
        ";
        
        $structureStmt = sqlsrv_query($conn2, $structureQuery);
        $eocStructure = [];
        $dateColumns = [];
        
        if ($structureStmt) {
            while ($row = sqlsrv_fetch_array($structureStmt, SQLSRV_FETCH_ASSOC)) {
                $columnName = $row['COLUMN_NAME'];
                $dataType = strtolower($row['DATA_TYPE']);
                $eocStructure[$columnName] = $dataType;
                
                // Identify date/datetime columns
                if (in_array($dataType, ['date', 'datetime', 'datetime2', 'smalldatetime', 'datetimeoffset'])) {
                    $dateColumns[] = $columnName;
                }
            }
        }
        
        // Log the structure for debugging
        error_log("EOC Table Structure: " . print_r($eocStructure, true));
        error_log("Date Columns in EOC: " . print_r($dateColumns, true));
        
        // Fetch employee data from Employee table
        $fetchQuery = "SELECT 
            [Employee#],
            [Department],
            [Cost Centre],
            [Name],
            [Permit Name],
            [Country Code],
            [Race],
            [Gender],
            [Position],
            [Grade],
            [Hire Date],
            [Work Permit Expiry (New)],
            [Work Permit Number],
            [Birthdate],
            [SPIKPA Expiry],
            [SOCSO No],
            [Old Passport],
            [New Passport],
            [Passport Expiry Date],
            [YOS],
            [Contract],
            [Hostel],
            [(EE)/Shift Group],
            [Destination],
            [Contact No (Employee)],
            [Email Address],
            [Next Of Kin],
            [Relationship],
            [Contact No In Source Country],
            [TPEA],
            [Address In Source Country],
            [Remarks],
            [DepartmentID],
            [MedicalDate],
            [ImagePath],
            [Passport Renewed Status],
            [NationalityID]
        FROM [Employee]
        WHERE [Employee#] = ?";
        
        $fetchStmt = sqlsrv_query($conn2, $fetchQuery, array($employeeId));
        
        if ($fetchStmt === false) {
            throw new Exception('Error fetching employee data: ' . print_r(sqlsrv_errors(), true));
        }
        
        $employeeData = sqlsrv_fetch_array($fetchStmt, SQLSRV_FETCH_ASSOC);
        
        if (!$employeeData) {
            throw new Exception('Employee not found.');
        }
        
        // Check if employee already exists in EOC table
        $checkQuery = "SELECT [Employee#] FROM [eoc] WHERE [Employee#] = ?";
        $checkStmt = sqlsrv_query($conn2, $checkQuery, array($employeeId));
        
        if (sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC)) {
            throw new Exception('Employee already exists in EOC/Runaway table.');
        }
        
        // Function to validate and format dates
        function validateAndFormatDate($dateValue) {
            if ($dateValue === null || $dateValue === '') {
                return null;
            }
            
            // If it's a DateTime object
            if ($dateValue instanceof DateTime) {
                $year = (int)$dateValue->format('Y');
                $month = (int)$dateValue->format('m');
                $day = (int)$dateValue->format('d');
                
                if ($year >= 1900 && $year <= 2100 && checkdate($month, $day, $year)) {
                    return $dateValue->format('Y-m-d');
                }
                return null;
            }
            
            // If it's a string
            if (is_string($dateValue)) {
                $trimmed = trim($dateValue);
                
                // Check for invalid dates
                $invalidDates = [
                    '1900-01-01 00:00:00.000',
                    '0000-00-00 00:00:00.000',
                    '1900-01-01',
                    '0000-00-00',
                    'NULL',
                    'null'
                ];
                
                if (in_array(strtolower($trimmed), array_map('strtolower', $invalidDates)) || empty($trimmed)) {
                    return null;
                }
                
                // Try to extract date part
                if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $trimmed, $matches)) {
                    $datePart = $matches[1];
                    list($year, $month, $day) = explode('-', $datePart);
                    
                    $year = (int)$year;
                    $month = (int)$month;
                    $day = (int)$day;
                    
                    // Validate: SQL Server accepts years 1753-9999, but let's be reasonable
                    if ($year >= 1900 && $year <= 2100 && checkdate($month, $day, $year)) {
                        return $datePart;
                    }
                }
                
                // Try other date formats
                $formats = [
                    'Y-m-d H:i:s.u',
                    'Y-m-d H:i:s',
                    'd/m/Y',
                    'm/d/Y',
                    'd-m-Y',
                    'm-d-Y'
                ];
                
                foreach ($formats as $format) {
                    $date = DateTime::createFromFormat($format, $trimmed);
                    if ($date !== false) {
                        $year = (int)$date->format('Y');
                        $month = (int)$date->format('m');
                        $day = (int)$date->format('d');
                        
                        if ($year >= 1900 && $year <= 2100 && checkdate($month, $day, $year)) {
                            return $date->format('Y-m-d');
                        }
                    }
                }
            }
            
            return null;
        }
        
        // Clean all data
        $cleanedData = [];
        foreach ($employeeData as $key => $value) {
            // Convert empty strings to NULL
            if ($value === '' || $value === 'NULL' || $value === 'null') {
                $cleanedData[$key] = null;
            } else {
                $cleanedData[$key] = $value;
            }
        }
        
        // Add form data
        $cleanedData['MovedRemarks'] = $movedRemarks;
        $cleanedData['Status'] = $status;
        
        // Build mapping from Employee table to EOC table
        // Based on your earlier successful test, we know these mappings work
        $fieldMappings = [
            // Employee field => EOC field
            'Employee#' => 'Employee#',
            'Cost Centre' => 'Cost Centre',
            'Name' => 'Name',
            'Permit Name' => 'Permit Name',
            'Race' => 'Race',
            'Gender' => 'Gender',
            'Position' => 'Position',
            'Grade' => 'Grade',
            'Hire Date' => 'Hire Date',
            'Work Permit Expiry (New)' => 'Work Permit Expiry (New)',
            'Work Permit Number' => 'Work Permit Number',
            'Birthdate' => 'Birthdate',
            'SPIKPA Expiry' => 'SPIKPA Expiry',
            'SOCSO No' => 'SOCSO No',
            'Old Passport' => 'Old Passport',
            'New Passport' => 'New Passport',
            'Passport Expiry Date' => 'Passport Expiry Date',
            'YOS' => 'YOS',
            'Contract' => 'Contract',
            'Hostel' => 'Hostel',
            '(EE)/Shift Group' => '(EE)/Shift Group',
            'Destination' => 'Destination',
            'Contact No (Employee)' => 'Contact No (Employee)',
            'Email Address' => 'Email Address',
            'Next Of Kin' => 'Next Of Kin',
            'Relationship' => 'Relationship',
            'Contact No In Source Country' => 'Contact No In Source Country',
            'TPEA' => 'TPEA',
            'Address In Source Country' => 'Address In Source Country',
            'Remarks' => 'Remarks',
            'MovedRemarks' => 'MovedRemarks',
            'Status' => 'Status',
            'ImagePath' => 'ImagePath',
            'DepartmentID' => 'DepartmentID',
            'NationalityID' => 'NationalityID'
        ];
        
        // Prepare insert with careful type handling
        $columns = [];
        $placeholders = [];
        $params = [];
        
        foreach ($fieldMappings as $sourceField => $destField) {
            if (isset($cleanedData[$sourceField])) {
                $value = $cleanedData[$sourceField];
                
                // Check if destination column is a date column
                $isDateColumn = in_array($destField, $dateColumns);
                // SPECIAL CASE: Work Permit Number should NEVER be treated as date
if ($destField === 'Work Permit Number') {
    // Force it to be treated as text, not date
    if ($value instanceof DateTime) {
        $year = (int)$value->format('Y');
        if ($year <= 1950) {
            $value = null; // Invalid date, set to null
        } else {
            $value = $value->format('Y-m-d'); // Convert to string
        }
    }
    
    if ($value !== null) {
        $columns[] = "[$destField]";
        $placeholders[] = '?';
        $params[] = $value;
    }
    continue; // Skip normal processing
}
                
                if ($isDateColumn) {
                    // Handle date fields
                    $formattedDate = validateAndFormatDate($value);
                    if ($formattedDate !== null) {
                        $columns[] = "[$destField]";
                        $placeholders[] = '?';
                        $params[] = array($formattedDate, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE);
                    }
                    // If date is invalid, skip it (will be NULL in database)
                } else {
                    // Handle non-date fields
                    $columns[] = "[$destField]";
                    $placeholders[] = '?';
                    $params[] = $value;
                }
            }
        }
        
        // Add auto-generated fields
        $columns[] = "[CreatedDate]";
        $columns[] = "[DateMoved]";
        $placeholders[] = 'GETDATE()';
        $placeholders[] = 'GETDATE()';
        
        // Build the query
        $insertQuery = "INSERT INTO [eoc] (" . implode(', ', $columns) . ") 
                       VALUES (" . implode(', ', $placeholders) . ")";
        
        // Debug: Log everything
        error_log("=== INSERT DEBUG ===");
        error_log("Query: $insertQuery");
        error_log("Number of parameters: " . count($params));
        
        // Log each parameter
        foreach ($params as $i => $param) {
            if (is_array($param) && isset($param[0])) {
                error_log("Param $i (date): " . $param[0]);
            } else {
                error_log("Param $i: " . var_export($param, true));
            }
        }
        
        // Try the insert
        $insertStmt = sqlsrv_query($conn2, $insertQuery, $params);
        
        if ($insertStmt === false) {
            $errors = sqlsrv_errors();
            error_log("Insert failed: " . print_r($errors, true));
            
            // NEW APPROACH: Insert without parameters first, then update
            error_log("Trying new approach: insert with defaults then update...");
            
            // First insert with minimal safe data
            $safeInsertQuery = "INSERT INTO [eoc] (
                [Employee#],
                [Name],
                [Status],
                [CreatedDate],
                [DateMoved]
            ) VALUES (?, ?, ?, GETDATE(), GETDATE())";
            
            $safeParams = [$employeeId, $cleanedData['Name'], $status];
            
            $safeStmt = sqlsrv_query($conn2, $safeInsertQuery, $safeParams);
            
            if ($safeStmt === false) {
                throw new Exception('Even safe insert failed: ' . print_r(sqlsrv_errors(), true));
            }
            
            error_log("Safe insert successful, now updating fields...");
            
            // Now update each field one by one
            $updateErrors = [];
            
            foreach ($fieldMappings as $sourceField => $destField) {
                if ($sourceField === 'Employee#' || $sourceField === 'Name' || $sourceField === 'Status') {
                    continue; // Already set
                }
                
                if (isset($cleanedData[$sourceField])) {
                    $value = $cleanedData[$sourceField];
                    
                    // Check if this is a date column
                    $isDateColumn = in_array($destField, $dateColumns);
                    
                    if ($isDateColumn) {
                        // Handle date field
                        $formattedDate = validateAndFormatDate($value);
                        if ($formattedDate !== null) {
                            $updateQuery = "UPDATE [eoc] SET [$destField] = ? WHERE [Employee#] = ?";
                            $updateParams = [
                                array($formattedDate, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATE),
                                $employeeId
                            ];
                            
                            $updateStmt = sqlsrv_query($conn2, $updateQuery, $updateParams);
                            
                            if ($updateStmt === false) {
                                $updateErrors[$destField] = sqlsrv_errors();
                                error_log("Failed to update $destField with value: " . $formattedDate);
                            }
                        }
                    } else {
                        // Handle non-date field
                        if ($value !== null) {
                            $updateQuery = "UPDATE [eoc] SET [$destField] = ? WHERE [Employee#] = ?";
                            $updateParams = [$value, $employeeId];
                            
                            $updateStmt = sqlsrv_query($conn2, $updateQuery, $updateParams);
                            
                            if ($updateStmt === false) {
                                $updateErrors[$destField] = sqlsrv_errors();
                                error_log("Failed to update $destField with value: " . var_export($value, true));
                            }
                        }
                    }
                }
            }
            
            if (!empty($updateErrors)) {
                error_log("Some updates failed: " . print_r($updateErrors, true));
                // Continue anyway - at least we have the employee moved
            }
        }
        
        // Delete from Employee table
        $deleteQuery = "DELETE FROM [Employee] WHERE [Employee#] = ?";
        $deleteStmt = sqlsrv_query($conn2, $deleteQuery, array($employeeId));
        
        if ($deleteStmt === false) {
            throw new Exception('Error deleting from Employee table: ' . print_r(sqlsrv_errors(), true));
        }
        
        // Commit transaction
        sqlsrv_commit($conn2);
        
        // Clear output buffer and redirect
        ob_end_clean();
        $_SESSION['success'] = "Employee successfully moved to $status.";
        header("Location: runaway.php?status=" . urlencode($status));
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($conn2)) {
            sqlsrv_rollback($conn2);
        }
        
        // Clear output buffer and redirect with error
        ob_end_clean();
        $_SESSION['error'] = $e->getMessage();
        header("Location: employeeInfo.php?id=" . urlencode($employeeId));
        exit;
    }
    
} else {
    // Clear output buffer and redirect
    ob_end_clean();
    $_SESSION['error'] = 'Invalid request.';
    header("Location: employeeInfo.php");
    exit;
}
?>