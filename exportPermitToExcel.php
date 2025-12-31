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
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'default';

// Generate smart filename based on filters
$filename = 'WorkPermitMasterlist';

// Add month to filename if selected
if ($monthFilter > 0) {
    $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 
                  'July', 'August', 'September', 'October', 'November', 'December'];
    $filename .= '_' . $monthNames[$monthFilter];
}

// Add department to filename if selected
if ($departmentFilter !== 'all') {
    $cleanDept = preg_replace('/[^a-zA-Z0-9]/', '_', $departmentFilter);
    $filename .= '_' . $cleanDept;
}

// Add status to filename if selected
if ($statusFilter !== 'default') {
    $filename .= '_' . ucfirst(str_replace('_', '', $statusFilter));
}

// Add current date
$filename .= '_' . date('Ymd_His');

// Clean filename
$filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename) . '.xls';

// Base WHERE conditions
$whereConditions = "WHERE e.[Work Permit Expiry (New)] IS NOT NULL";

// Status filter overrides ALL other filters
if ($statusFilter !== 'default') {
    $today = date('Y-m-d');
    $ninetyDaysLater = date('Y-m-d', strtotime('+90 days'));
    
    if ($statusFilter === 'expired') {
        // Show ALL expired permits (ignore month and department)
        $whereConditions .= " AND e.[Work Permit Expiry (New)] < CAST('$today' AS DATE)";
    } elseif ($statusFilter === 'expiring_soon') {
        // Show ALL permits expiring within 90 days (ignore month and department)
        $whereConditions .= " AND e.[Work Permit Expiry (New)] >= CAST('$today' AS DATE)";
        $whereConditions .= " AND e.[Work Permit Expiry (New)] <= CAST('$ninetyDaysLater' AS DATE)";
    } elseif ($statusFilter === 'active') {
        // Show ALL active permits (ignore month and department)
        $whereConditions .= " AND e.[Work Permit Expiry (New)] > CAST('$ninetyDaysLater' AS DATE)";
    }
} else {
    // Only apply month and department filters when status is 'default'
    
    // Add month filter
    if ($monthFilter > 0) {
        $whereConditions .= " AND MONTH(e.[Work Permit Expiry (New)]) = $monthFilter";
    } else {
        // Default: show expiring 2-3 months from now
        $whereConditions .= "
          AND e.[Work Permit Expiry (New)] >= DATEFROMPARTS(YEAR(DATEADD(MONTH, 2, GETDATE())), MONTH(DATEADD(MONTH, 2, GETDATE())), 1)
          AND e.[Work Permit Expiry (New)] < DATEFROMPARTS(YEAR(DATEADD(MONTH, 3, GETDATE())), MONTH(DATEADD(MONTH, 3, GETDATE())), 1)";
    }
    
    // Add department filter (only when status is default)
    if ($departmentFilter !== 'all') {
        $departmentFilterEscaped = str_replace("'", "''", $departmentFilter);
        $whereConditions .= " AND d.[Department] = '$departmentFilterEscaped'";
    }
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

// CORRECTED Function to determine work permit status
function getPermitStatus($expiryDate) {
    if ($expiryDate instanceof DateTime) {
        $today = new DateTime();
        
        // Reset times to midnight for accurate comparison
        $today->setTime(0, 0, 0);
        $expiryDate->setTime(0, 0, 0);
        
        // Check if expired FIRST
        if ($expiryDate < $today) {
            return 'Expired';
        }
        
        // Create date 90 days from now
        $ninetyDaysLater = clone $today;
        $ninetyDaysLater->modify('+90 days');
        
        // Check if expiring soon (within 90 days)
        if ($expiryDate <= $ninetyDaysLater) {
            return 'Expiring Soon';
        }
        
        // Otherwise it's active
        return 'Active';
    }
    return 'N/A';
}

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Description: File Transfer');
header('Pragma: public');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Cache-Control: public');
header('Content-Transfer-Encoding: binary');

// Output Excel content
echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<style>';
echo 'table { border-collapse: collapse; width: 100%; }';
echo 'th { background-color: #4472C4; color: white; font-weight: bold; padding: 10px; border: 1px solid #000; text-align: center; }';
echo 'td { padding: 8px; border: 1px solid #000; text-align: left; }';
echo '.medical-incomplete { background-color: #ffffffff; color: #9C0006; }';
echo '</style>';
echo '</head>';
echo '<body>';
echo '<table border="1">';

// Table header
echo '<tr>';
echo '<th>Employee No</th>';
echo '<th>Permit Name</th>';
echo '<th>Department</th>';
echo '<th>Nationality</th>';
echo '<th>Date of Birth</th>';
echo '<th>Work Permit Number</th>';
echo '<th>Work Permit Expiry</th>';
echo '<th>Medical Checkup Status</th>';
echo '<th>SPIKPA Insurance</th>';
echo '<th>Status</th>';
echo '<th>Remarks</th>';
echo '</tr>';

// Table data
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // Work Permit Expiry
    $expiryDate = $row['Work Permit Expiry (New)'];
    if ($expiryDate instanceof DateTime) {
        $expiryDateFormatted = $expiryDate->format('d-m-Y');
    } else {
        $expiryDateFormatted = $expiryDate ? $expiryDate : 'N/A';
    }
    
    // Medical Status
    $medicalStatus = getMedicalStatusForExport($row['MedicalDate']);
    $medicalClass = ($medicalStatus === 'Complete') ? 'medical-complete' : 'medical-incomplete';
    
    // SPIKPA Expiry
    $spikpaExpiry = $row['SPIKPA Expiry '];
    if ($spikpaExpiry instanceof DateTime) {
        $spikpaExpiryFormatted = $spikpaExpiry->format('d-m-Y');
    } else {
        $spikpaExpiryFormatted = $spikpaExpiry ? $spikpaExpiry : 'N/A';
    }
    
    // Date of Birth
    $birthdate = $row['Birthdate'];
    if ($birthdate instanceof DateTime) {
        $birthdateFormatted = $birthdate->format('d-m-Y');
    } else {
        $birthdateFormatted = $birthdate ? $birthdate : 'N/A';
    }
    
    // Permit Status - USING THE CORRECTED FUNCTION
    $permitStatus = getPermitStatus($expiryDate);
    $permitStatusClass = '';
    if ($permitStatus === 'Active') {
        $permitStatusClass = 'status-active';
    } elseif ($permitStatus === 'Expiring Soon') {
        $permitStatusClass = 'status-expiring';
    } elseif ($permitStatus === 'Expired') {
        $permitStatusClass = 'status-expired';
    }
    
    echo '<tr>';
    echo '<td>' . htmlspecialchars($row['Employee#'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($row['Permit Name'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($row['Department'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($row['Nationality'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($birthdateFormatted) . '</td>';
    echo '<td>' . htmlspecialchars($row['Work Permit Number'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($expiryDateFormatted) . '</td>';
    echo '<td class="' . $medicalClass . '">' . htmlspecialchars($medicalStatus) . '</td>';
    echo '<td>' . htmlspecialchars($spikpaExpiryFormatted) . '</td>';
    echo '<td class="' . $permitStatusClass . '">' . htmlspecialchars($permitStatus) . '</td>';
    echo '<td>' . htmlspecialchars($row['Remarks'] ?? '') . '</td>';
    echo '</tr>';
}

echo '</table>';
echo '</body>';
echo '</html>';

sqlsrv_free_stmt($stmt);
exit;
?>