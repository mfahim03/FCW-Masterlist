<?php
include 'db.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Get filter parameters
$monthFilter = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$departmentFilter = isset($_GET['department']) ? $_GET['department'] : 'all';

// Generate smart filename based on filters
$filename = 'WorkPermitMasterlist';

// Add month to filename if selected
if ($monthFilter > 0) {
    $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 
                  'July', 'August', 'September', 'October', 'November', 'December'];
    $filename .= '_' . $monthNames[$monthFilter];
} else {
    $filename .= '';
}

// Add department to filename if selected
if ($departmentFilter !== 'all') {
    $cleanDept = preg_replace('/[^a-zA-Z0-9]/', '_', $departmentFilter);
    $filename .= '_' . $cleanDept;
}

// Add current date
$filename .= '_' . date('Ymd_His');

// Clean filename - use .csv for better compatibility
$filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename) . '.csv';

// Base WHERE conditions
$whereConditions = "WHERE e.[Work Permit Expiry (New)] IS NOT NULL";

// Add month/date filter
if ($monthFilter > 0) {
    $whereConditions .= " AND MONTH(e.[Work Permit Expiry (New)]) = $monthFilter";
} else {
    $whereConditions .= "
      AND e.[Work Permit Expiry (New)] >= DATEFROMPARTS(YEAR(DATEADD(MONTH, 2, GETDATE())), MONTH(DATEADD(MONTH, 2, GETDATE())), 1)
      AND e.[Work Permit Expiry (New)] < DATEFROMPARTS(YEAR(DATEADD(MONTH, 3, GETDATE())), MONTH(DATEADD(MONTH, 3, GETDATE())), 1)";
}

// Add department filter
if ($departmentFilter !== 'all') {
    $departmentFilterEscaped = str_replace("'", "''", $departmentFilter);
    $whereConditions .= " AND d.[Department] = '$departmentFilterEscaped'";
}

// Fetch all records (no pagination for export)
$sql = "
    SELECT 
        e.[Employee#],
        e.[Permit Name],
        d.[Department],
        n.[Nationality],
        e.[Birthdate],
        e.[Work Permit Number],
        e.[Work Permit Expiry (New)],
        e.[MedicalDate],
        e.[SPIKPA Expiry ],
        e.[Remarks]
    FROM [FCW_List].[dbo].[Employee] AS e
    LEFT JOIN [FCW_List].[dbo].[Nationality] AS n
        ON e.[NationalityID] = n.[NationalityID]
    LEFT JOIN [FCW_List].[dbo].[Department] AS d
        ON e.[DepartmentID] = d.[DepartmentID]
    $whereConditions
    ORDER BY e.[Work Permit Expiry (New)]
";

$stmt = sqlsrv_query($conn1, $sql);

if ($stmt === false) {
    die("Error fetching data: " . print_r(sqlsrv_errors(), true));
}

// Function to format date
function formatDateForCSV($date) {
    if ($date instanceof DateTime) {
        return $date->format('d-m-Y');
    } elseif (!empty($date)) {
        return $date;
    }
    return 'N/A';
}

// Function to determine medical status
function getMedicalStatusForExport($medicalDate) {
    if (is_string($medicalDate) && ($medicalDate === 'Complete' || $medicalDate === 'Incomplete')) {
        return $medicalDate;
    } else if ($medicalDate instanceof DateTime) {
        return 'Complete';
    } else if (!empty($medicalDate) && trim($medicalDate) !== '') {
        return 'Complete';
    } else {
        return 'Incomplete';
    }
}

// Function to determine work permit status
function getPermitStatus($expiryDate) {
    if ($expiryDate instanceof DateTime) {
        $today = new DateTime();
        $interval = $today->diff($expiryDate);
        
        if ($expiryDate < $today) {
            return 'Expired';
        } elseif ($interval->days <= 90) {
            return 'Expiring Soon';
        } else {
            return 'Active';
        }
    }
    return 'N/A';
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write headers
fputcsv($output, [
    'Employee No',
    'Permit Name',
    'Department',
    'Nationality',
    'Date of Birth',
    'Work Permit Number',
    'Work Permit Expiry',
    'Medical Checkup Status',
    'SPIKPA Insurance',
    'Status',
    'Remarks'
]);

// Write data
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $data = [
        $row['Employee#'] ?? 'N/A',
        $row['Permit Name'] ?? 'N/A',
        $row['Department'] ?? 'N/A',
        $row['Nationality'] ?? 'N/A',
        formatDateForCSV($row['Birthdate']),
        $row['Work Permit Number'] ?? 'N/A',
        formatDateForCSV($row['Work Permit Expiry (New)']),
        getMedicalStatusForExport($row['MedicalDate']),
        formatDateForCSV($row['SPIKPA Expiry ']),
        getPermitStatus($row['Work Permit Expiry (New)']),
        $row['Remarks'] ?? ''
    ];
    
    fputcsv($output, $data);
}

fclose($output);
sqlsrv_free_stmt($stmt);
exit;
?>