<?php
include 'db.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Get filter parameters
$monthFilter = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$nationalityFilter = isset($_GET['nationality']) ? $_GET['nationality'] : 'all';

$filename = 'PassportMasterlist';

// Add month to filename if selected
if ($monthFilter > 0) {
    $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 
                  'July', 'August', 'September', 'October', 'November', 'December'];
    $filename .= '_' . $monthNames[$monthFilter];
}

// Add nationality to filename if selected
if ($nationalityFilter !== 'all') {
    $cleanNat = preg_replace('/[^a-zA-Z0-9]/', '_', $nationalityFilter);
    $filename .= '_' . $cleanNat;
}

// Add current date
$filename .= '_' . date('Ymd_His');

// Clean filename
$filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename) . '.xls';

// Base WHERE conditions
$whereConditions = "WHERE e.[Passport Expiry Date] IS NOT NULL";

// Add month filter
if ($monthFilter > 0) {
    $whereConditions .= " AND MONTH(e.[Passport Expiry Date]) = $monthFilter";
} else {
    // Default: Passports expiring within 1 year
    $whereConditions .= " AND e.[Passport Expiry Date] <= DATEADD(YEAR, 1, GETDATE())";
}

// Add nationality filter
if ($nationalityFilter !== 'all') {
    $nationalityFilterEscaped = str_replace("'", "''", $nationalityFilter);
    $whereConditions .= " AND n.[Nationality] = '$nationalityFilterEscaped'";
}

// Fetch all records
$sql = "
    SELECT 
        e.[Employee#],
        e.[Permit Name],
        d.[Department],
        n.[Nationality],
        e.[Old Passport],
        e.[New Passport],
        e.[Passport Expiry Date],
        e.[Passport Renewed Status]
    FROM [FCW_List].[dbo].[Employee] AS e
    LEFT JOIN [FCW_List].[dbo].[Nationality] AS n
        ON e.[NationalityID] = n.[NationalityID]
    LEFT JOIN [FCW_List].[dbo].[Department] AS d
        ON e.[DepartmentID] = d.[DepartmentID]
    $whereConditions
    ORDER BY e.[Passport Expiry Date]
";

$stmt = sqlsrv_query($conn1, $sql);

if ($stmt === false) {
    die("Error fetching data: " . print_r(sqlsrv_errors(), true));
}

// Function to determine passport status
function getPassportStatus($expiryDate, $renewedStatus) {
    if ($expiryDate instanceof DateTime) {
        $today = new DateTime();
        $interval = $today->diff($expiryDate);
        
        if ($expiryDate < $today) {
            return 'Expired';
        } elseif ($interval->days <= 365) {
            return 'Expiring Soon';
        } else {
            if (isset($renewedStatus) && $renewedStatus == 1) {
                return 'Completed';
            }
            return 'Active';
        }
    }
    return 'N/A';
}

// Set headers for Excel download
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
echo '<th>Old Passport</th>';
echo '<th>New Passport</th>';
echo '<th>Passport Expiry Date</th>';
echo '<th>Status</th>';
echo '</tr>';

// Table data
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // Passport Expiry Date
    $expiryDate = $row['Passport Expiry Date'];
    if ($expiryDate instanceof DateTime) {
        $expiryDateFormatted = $expiryDate->format('d-m-Y');
    } else {
        $expiryDateFormatted = $expiryDate ? $expiryDate : 'N/A';
    }
    
    // Passport Status
    $status = getPassportStatus($expiryDate, $row['Passport Renewed Status']);
    $statusClass = '';
    
    if ($status === 'Completed') {
        $statusClass = 'status-completed';
    } elseif ($status === 'Expired') {
        $statusClass = 'status-expired';
    } elseif ($status === 'Expiring Soon') {
        $statusClass = 'status-expiring-soon';
    } elseif ($status === 'Active') {
        $statusClass = 'status-active';
    }
    
    echo '<tr>';
    echo '<td>' . htmlspecialchars($row['Employee#'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($row['Permit Name'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($row['Department'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($row['Nationality'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($row['Old Passport'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($row['New Passport'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($expiryDateFormatted) . '</td>';
    echo '<td class="' . $statusClass . '">' . htmlspecialchars($status) . '</td>';
    echo '</tr>';
}

echo '</table>';
echo '</body>';
echo '</html>';

sqlsrv_free_stmt($stmt);
exit;
?>