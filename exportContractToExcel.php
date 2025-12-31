<?php
include 'db.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Get filter parameters
$monthFilter = isset($_GET['month']) ? (int)$_GET['month'] : 0;

// Generate smart filename based on filters
$filename = 'ContractMasterlist';

// Add month to filename if selected
if ($monthFilter > 0) {
    $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 
                  'July', 'August', 'September', 'October', 'November', 'December'];
    $filename .= '_' . $monthNames[$monthFilter];
}

// Add current date
$filename .= '_' . date('Ymd_His');

// Clean filename - use .xls for compatibility
$filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename) . '.xls';

// Base WHERE conditions
$baseWhereConditions = "WHERE e.[Work Permit Expiry (New)] IS NOT NULL AND e.[Contract] IS NOT NULL";

// Add month/date filter
if ($monthFilter > 0) {
    $baseWhereConditions .= " AND MONTH(e.[Work Permit Expiry (New)]) = $monthFilter";
} else {
    $baseWhereConditions .= "
      AND e.[Work Permit Expiry (New)] >= DATEFROMPARTS(YEAR(DATEADD(MONTH, 2, GETDATE())), MONTH(DATEADD(MONTH, 2, GETDATE())), 1)
      AND e.[Work Permit Expiry (New)] < DATEFROMPARTS(YEAR(DATEADD(MONTH, 3, GETDATE())), MONTH(DATEADD(MONTH, 3, GETDATE())), 1)";
}

// Fetch EXTEND contracts
$sql_extend = "
    SELECT 
        e.[Employee#],
        e.[Permit Name],
        d.[Department],
        n.[Nationality],
        e.[Birthdate],
        e.[Work Permit Number],
        e.[Work Permit Expiry (New)],
        e.[Contract]
    FROM [FCW_List].[dbo].[Employee] AS e
    LEFT JOIN [FCW_List].[dbo].[Nationality] AS n ON e.[NationalityID] = n.[NationalityID]
    LEFT JOIN [FCW_List].[dbo].[Department] AS d ON e.[DepartmentID] = d.[DepartmentID]
    $baseWhereConditions AND e.[Contract] = 'EXTEND'
    ORDER BY e.[Work Permit Expiry (New)] ASC
";

$stmt_extend = sqlsrv_query($conn1, $sql_extend);
if ($stmt_extend === false) {
    die("Error fetching EXTEND data: " . print_r(sqlsrv_errors(), true));
}

// Fetch NOT EXTEND contracts
$sql_not_extend = "
    SELECT 
        e.[Employee#],
        e.[Permit Name],
        d.[Department],
        n.[Nationality],
        e.[Birthdate],
        e.[Work Permit Number],
        e.[Work Permit Expiry (New)],
        e.[Contract]
    FROM [FCW_List].[dbo].[Employee] AS e
    LEFT JOIN [FCW_List].[dbo].[Nationality] AS n ON e.[NationalityID] = n.[NationalityID]
    LEFT JOIN [FCW_List].[dbo].[Department] AS d ON e.[DepartmentID] = d.[DepartmentID]
    $baseWhereConditions AND e.[Contract] = 'NOT EXTEND'
    ORDER BY e.[Work Permit Expiry (New)] ASC
";

$stmt_not_extend = sqlsrv_query($conn1, $sql_not_extend);
if ($stmt_not_extend === false) {
    die("Error fetching NOT EXTEND data: " . print_r(sqlsrv_errors(), true));
}

// Store data in arrays
$extend_data = [];
while ($row = sqlsrv_fetch_array($stmt_extend, SQLSRV_FETCH_ASSOC)) {
    $extend_data[] = $row;
}

$not_extend_data = [];
while ($row = sqlsrv_fetch_array($stmt_not_extend, SQLSRV_FETCH_ASSOC)) {
    $not_extend_data[] = $row;
}

$count_extend = count($extend_data);
$count_not_extend = count($not_extend_data);

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

// Set proper headers to avoid format warning
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Output with proper Excel XML
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name=ProgId content=Excel.Sheet>
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Contract Masterlist</x:Name>
                    <x:WorksheetOptions>
                        <x:Print>
                            <x:ValidPrinterInfo/>
                        </x:Print>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        .header-row th {
            font-weight: bold;
            padding: 10px;
            border: 1px solid #000;
            text-align: center;
        }
        .section-header {
            background-color: #28a745;
            color: white;
            font-weight: bold;
            padding: 15px;
            font-size: 14pt;
            text-align: left;
            border: 1px solid #000;
        }
        .section-header-red {
            background-color: #dc3545;
            color: white;
            font-weight: bold;
            padding: 15px;
            font-size: 14pt;
            text-align: left;
            border: 1px solid #000;
        }
        td {
            padding: 8px;
            border: 1px solid #000;
            text-align: left;
        }
    </style>
</head>
<body>
    <table>
        <!-- EXTEND SECTION HEADER -->
        <tr>
            <td colspan="8" class="section-header">EXTEND (<?php echo $count_extend; ?> employees)</td>
        </tr>
        
        <!-- EXTEND TABLE HEADER -->
        <tr class="header-row">
            <th>Employee No</th>
            <th>Permit Name</th>
            <th>Department</th>
            <th>Nationality</th>
            <th>Date of Birth</th>
            <th>Work Permit Number</th>
            <th>Work Permit Expiry</th>
            <th>Contract Status</th>
            <!-- <th>Status</th> -->
        </tr>
        
        <!-- EXTEND TABLE DATA -->
        <?php if ($count_extend > 0): ?>
            <?php foreach ($extend_data as $row): ?>
                <?php
                // Work Permit Expiry
                $expiryDate = $row['Work Permit Expiry (New)'];
                $expiryDateFormatted = ($expiryDate instanceof DateTime) ? $expiryDate->format('d-m-Y') : ($expiryDate ?? 'N/A');
                
                // Date of Birth
                $birthdate = $row['Birthdate'];
                $birthdateFormatted = ($birthdate instanceof DateTime) ? $birthdate->format('d-m-Y') : ($birthdate ?? 'N/A');
                
                // Permit Status
                $permitStatus = getPermitStatus($expiryDate);
                $statusClass = '';
                if ($permitStatus === 'Active') $statusClass = 'status-active';
                elseif ($permitStatus === 'Expiring Soon') $statusClass = 'status-expiring';
                elseif ($permitStatus === 'Expired') $statusClass = 'status-expired';
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['Employee#'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['Permit Name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['Department'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['Nationality'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($birthdateFormatted); ?></td>
                    <td><?php echo htmlspecialchars($row['Work Permit Number'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($expiryDateFormatted); ?></td>
                    <td><?php echo htmlspecialchars($row['Contract'] ?? 'N/A'); ?></td>
                    <!--<td class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($permitStatus); ?></td>-->
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" style="text-align: center; font-style: italic;">No contracts to extend.</td>
            </tr>
        <?php endif; ?>
        
        <!-- SPACING -->
        <tr><td colspan="8" style="border: none; height: 20px;"></td></tr>
        
        <!-- NOT EXTEND SECTION HEADER -->
        <tr>
            <td colspan="8" class="section-header-red">NOT EXTEND (<?php echo $count_not_extend; ?> employees)</td>
        </tr>
        
        <!-- NOT EXTEND TABLE HEADER -->
        <tr class="header-row">
            <th>Employee No</th>
            <th>Permit Name</th>
            <th>Department</th>
            <th>Nationality</th>
            <th>Date of Birth</th>
            <th>Work Permit Number</th>
            <th>Work Permit Expiry</th>
            <th>Contract Status</th>
            <!--<th>Status</th> -->
        </tr>
        
        <!-- NOT EXTEND TABLE DATA -->
        <?php if ($count_not_extend > 0): ?>
            <?php foreach ($not_extend_data as $row): ?>
                <?php
                // Work Permit Expiry
                $expiryDate = $row['Work Permit Expiry (New)'];
                $expiryDateFormatted = ($expiryDate instanceof DateTime) ? $expiryDate->format('d-m-Y') : ($expiryDate ?? 'N/A');
                
                // Date of Birth
                $birthdate = $row['Birthdate'];
                $birthdateFormatted = ($birthdate instanceof DateTime) ? $birthdate->format('d-m-Y') : ($birthdate ?? 'N/A');
                
                // Permit Status
                $permitStatus = getPermitStatus($expiryDate);
                $statusClass = '';
                if ($permitStatus === 'Active') $statusClass = 'status-active';
                elseif ($permitStatus === 'Expiring Soon') $statusClass = 'status-expiring';
                elseif ($permitStatus === 'Expired') $statusClass = 'status-expired';
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['Employee#'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['Permit Name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['Department'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['Nationality'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($birthdateFormatted); ?></td>
                    <td><?php echo htmlspecialchars($row['Work Permit Number'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($expiryDateFormatted); ?></td>
                    <td><?php echo htmlspecialchars($row['Contract'] ?? 'N/A'); ?></td>
                    <!-- <td class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($permitStatus); ?></td> -->
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" style="text-align: center; font-style: italic;">No contracts marked as not extend.</td>
            </tr>
        <?php endif; ?>
    </table>
</body>
</html>
<?php
sqlsrv_free_stmt($stmt_extend);
sqlsrv_free_stmt($stmt_not_extend);
?>