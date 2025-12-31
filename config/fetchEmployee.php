<?php
// Get selected employee ID if provided
$selectedEmployeeId = isset($_GET['id']) ? $_GET['id'] : null;
$employeeData = null;
$myName = '';
$img_path = 'img/default-avatar.png'; // Default image

if ($selectedEmployeeId) {
    // Fetch specific employee data
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
            e.[Remarks],
            e.[NationalityID],
            e.[MedicalDate],
            e.[ImagePath],
            n.[Nationality]
        FROM [FCW_List].[dbo].[Employee] AS e
        LEFT JOIN [FCW_List].[dbo].[Nationality] AS n
            ON e.[NationalityID] = n.[NationalityID]
        LEFT JOIN [FCW_List].[dbo].[Department] AS d
            ON e.[DepartmentID] = d.[DepartmentID]
        WHERE e.[Employee#] = ?
    ";
    
    $stmt = sqlsrv_query($conn1, $sql, array($selectedEmployeeId));
    if ($stmt !== false) {
        $employeeData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        if ($employeeData) {
            $myName = $employeeData['Name'] ?? 'N/A';
            
            // Check if ImagePath exists in database
            if (!empty($employeeData['ImagePath']) && file_exists($employeeData['ImagePath'])) {
                $img_path = $employeeData['ImagePath'];
            } else {
                // Try to locate image by Employee# in img/employee_images folder
                $photoDir = 'img/employee_images/';
                $employeeNum = $employeeData['Employee#'];
                
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
                        $update_sql = "UPDATE [FCW_List].[dbo].[Employee] SET [ImagePath] = ? WHERE [Employee#] = ?";
                        $update_stmt = sqlsrv_query($conn1, $update_sql, array($photoPath, $employeeNum));
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
        }
    } else {
        error_log("Error fetching employee: " . print_r(sqlsrv_errors(), true));
    }
}

// Fetch all nationalities for dropdown
$nationality_sql = "SELECT [NationalityID], [Nationality] FROM [FCW_List].[dbo].[Nationality] ORDER BY [Nationality]";
$nationality_stmt = sqlsrv_query($conn1, $nationality_sql);
$nationalities = [];
if ($nationality_stmt !== false) {
    while ($nat = sqlsrv_fetch_array($nationality_stmt, SQLSRV_FETCH_ASSOC)) {
        $nationalities[] = $nat;
    }
} else {
    error_log("Error fetching nationalities: " . print_r(sqlsrv_errors(), true));
}

// Fetch all departments for dropdown
$department_sql = "SELECT [DepartmentID], [Department] FROM [FCW_List].[dbo].[Department] ORDER BY [Department]";
$department_stmt = sqlsrv_query($conn1, $department_sql);
$departments = [];
if ($department_stmt !== false) {
    while ($dept = sqlsrv_fetch_array($department_stmt, SQLSRV_FETCH_ASSOC)) {
        $departments[] = $dept;
    }
} else {
    error_log("Error fetching departments: " . print_r(sqlsrv_errors(), true));
}

// Fetch all unique contract types for dropdown
$contract_sql = "SELECT DISTINCT [Contract] FROM [FCW_List].[dbo].[Employee] WHERE [Contract] IS NOT NULL AND [Contract] <> '' ORDER BY [Contract]";
$contract_stmt = sqlsrv_query($conn1, $contract_sql);
$contracts = [];
if ($contract_stmt !== false) {
    while ($contract = sqlsrv_fetch_array($contract_stmt, SQLSRV_FETCH_ASSOC)) {
        $contracts[] = $contract['Contract'];
    }
} else {
    error_log("Error fetching contracts: " . print_r(sqlsrv_errors(), true));
}

// Fetch all employees for the list
$list_sql = "
    SELECT 
        e.[Employee#],
        e.[Name],
        d.[Department],
        n.[Nationality]
    FROM [FCW_List].[dbo].[Employee] AS e
    LEFT JOIN [FCW_List].[dbo].[Nationality] AS n
        ON e.[NationalityID] = n.[NationalityID]
    LEFT JOIN [FCW_List].[dbo].[Department] AS d
        ON e.[DepartmentID] = d.[DepartmentID]
    ORDER BY e.[Employee#]
";
$list_stmt = sqlsrv_query($conn1, $list_sql);

if ($list_stmt === false) {
    error_log("Error fetching employee list: " . print_r(sqlsrv_errors(), true));
}

// Helper function to format dates
function formatDate($date) {
    if ($date instanceof DateTime) {
        return $date->format('Y-m-d');
    }
    return '';
}

function formatDateDisplay($date) {
    if ($date instanceof DateTime) {
        return $date->format('d-m-Y');
    }
    return 'N/A';
}

// Helper function to get medical status
function getMedicalStatus($medicalDate) {
    if (is_string($medicalDate) && ($medicalDate === 'Complete' || $medicalDate === 'Incomplete')) {
        return $medicalDate;
    } else if ($medicalDate instanceof DateTime) {
        // Legacy: If it's a DateTime object, consider it Complete
        return 'Complete';
    } else if (!empty($medicalDate)) {
        // If it's any other non-empty value, consider it Complete
        return 'Complete';
    } else {
        // Default to Incomplete if null/empty
        return 'Incomplete';
    }
}
?>