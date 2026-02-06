<?php
// Fetch Employee Detail from EOC Table
// Get selected employee ID if provided
$selectedEmployeeId = isset($_GET['id']) ? $_GET['id'] : null;
$employee = null;
$img_path = 'img/default-avatar.png'; // Default image

if ($selectedEmployeeId) {
    // Fetch specific employee data from EOC table
    $sql = "
        SELECT 
            e.[Employee#],
            e.[DepartmentID],
            d.[Department],
            e.[Cost Centre],
            e.[Name],
            e.[Permit Name],
            e.[Race],
            e.[Gender],
            e.[Position],
            e.[Grade],
            e.[Hire Date],
            e.[Work Permit Expiry (New)],
            e.[Work Permit Number],
            e.[Birthdate],
            e.[SPIKPA Expiry],
            e.[SOCSO No],
            e.[Old Passport],
            e.[New Passport],
            e.[Passport Expiry Date],
            e.[YOS],
            e.[Contract],
            e.[Hostel],
            e.[(EE)/Shift Group],
            e.[Destination],
            e.[Contact No (Employee)],
            e.[Email Address],
            e.[Next Of Kin],
            e.[Relationship],
            e.[Contact No In Source Country],
            e.[TPEA],
            e.[Address In Source Country],
            e.[Last_ Working Day],
            e.[FlightDate],
            e.[Status],
            e.[POL#Number],
            e.[Date of Report],
            e.[Remarks],
            e.[MovedRemarks],
            e.[CreatedDate],
            e.[DateMoved],
            e.[NationalityID],
            e.[ImagePath],
            n.[Nationality]
        FROM [Updated_FCW_List].[dbo].[eoc] AS e
        LEFT JOIN [Updated_FCW_List].[dbo].[Nationality] AS n
            ON e.[NationalityID] = n.[NationalityID]
        LEFT JOIN [Updated_FCW_List].[dbo].[Department] AS d
            ON e.[DepartmentID] = d.[DepartmentID]
        WHERE e.[Employee#] = ?
    ";
    
    $stmt = sqlsrv_query($conn2, $sql, array($selectedEmployeeId));
    if ($stmt !== false) {
        $employee = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        if ($employee) {
            // Format Employee# to remove decimals if needed
            if (isset($employee['Employee#']) && is_numeric($employee['Employee#'])) {
                $employee['Employee#'] = (string)(int)$employee['Employee#'];
            }
            
            // Check if ImagePath exists in database
            if (!empty($employee['ImagePath']) && file_exists($employee['ImagePath'])) {
                $img_path = $employee['ImagePath'];
            } else {
                // Try to locate image by Employee# in img/employee_images folder
                $photoDir = 'img/employee_images/';
                $employeeNum = $employee['Employee#'];
                
                // Common possible image extensions
                $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'JPG', 'JPEG', 'PNG', 'GIF', 'WEBP'];
                
                // Loop through possible extensions to find an existing file
                $imageFound = false;
                foreach ($extensions as $ext) {
                    $photoPath = $photoDir . $employeeNum . '.' . $ext;
                    if (file_exists($photoPath)) {
                        $img_path = $photoPath;
                        $imageFound = true;
                        
                        // Update database with found image path
                        $update_sql = "UPDATE [Updated_FCW_List].[dbo].[eoc] SET [ImagePath] = ? WHERE [Employee#] = ?";
                        $update_stmt = sqlsrv_query($conn2, $update_sql, array($photoPath, $employeeNum));
                        if ($update_stmt === false) {
                            error_log("Error updating image path: " . print_r(sqlsrv_errors(), true));
                        }
                        break;
                    }
                }
                
                // If no image found in folder, keep default avatar
                if (!$imageFound) {
                    $img_path = 'img/default-avatar.png';
                }
            }
        } else {
            error_log("Employee not found with ID: " . $selectedEmployeeId);
        }
    } else {
        error_log("Error fetching employee: " . print_r(sqlsrv_errors(), true));
    }
}

// Fetch all nationalities for dropdown
$nationality_sql = "SELECT [NationalityID], [Nationality] FROM [Updated_FCW_List].[dbo].[Nationality] ORDER BY [Nationality]";
$nationality_stmt = sqlsrv_query($conn2, $nationality_sql);
$nationalities = [];
if ($nationality_stmt !== false) {
    while ($nat = sqlsrv_fetch_array($nationality_stmt, SQLSRV_FETCH_ASSOC)) {
        $nationalities[] = $nat;
    }
} else {
    error_log("Error fetching nationalities: " . print_r(sqlsrv_errors(), true));
}

// Fetch all departments for dropdown
$department_sql = "SELECT [DepartmentID], [Department] FROM [Updated_FCW_List].[dbo].[Department] ORDER BY [Department]";
$department_stmt = sqlsrv_query($conn2, $department_sql);
$departments = [];
if ($department_stmt !== false) {
    while ($dept = sqlsrv_fetch_array($department_stmt, SQLSRV_FETCH_ASSOC)) {
        $departments[] = $dept;
    }
} else {
    error_log("Error fetching departments: " . print_r(sqlsrv_errors(), true));
}

// Fetch all unique contract types for dropdown
$contract_sql = "SELECT DISTINCT [Contract] FROM [Updated_FCW_List].[dbo].[eoc] WHERE [Contract] IS NOT NULL AND [Contract] <> '' ORDER BY [Contract]";
$contract_stmt = sqlsrv_query($conn2, $contract_sql);
$contracts = [];
if ($contract_stmt !== false) {
    while ($contract = sqlsrv_fetch_array($contract_stmt, SQLSRV_FETCH_ASSOC)) {
        $contracts[] = $contract['Contract'];
    }
} else {
    // Default contract types if query fails
    $contracts = ['Permanent', 'Contract', 'Temporary', 'Fixed Term'];
    error_log("Error fetching contracts: " . print_r(sqlsrv_errors(), true));
}

// Helper function to format dates for input fields (Y-m-d)
function formatDate($date) {
    if ($date instanceof DateTime) {
        return $date->format('Y-m-d');
    }
    return '';
}

// Helper function to format dates for display (d-m-Y)
function formatDateDisplay($date) {
    if ($date instanceof DateTime) {
        return $date->format('d-m-Y');
    }
    return 'N/A';
}
?>