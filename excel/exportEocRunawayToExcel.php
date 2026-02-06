<?php
include '../db.php';
session_start();

// Get filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$nationalityFilter = isset($_GET['nationality']) ? $_GET['nationality'] : 'all';

// Generate smart filename based on filters
$filename = 'FCW';

// Add status to filename if selected
if ($statusFilter) {
    $filename .= '_' . $statusFilter;
}

// Add nationality to filename if selected
if ($nationalityFilter != 'all') {
    $filename .= '_' . preg_replace('/[^a-zA-Z0-9]/', '', $nationalityFilter);
}

// Add current date
$filename .= '_' . date('Ymd_His');

// Clean filename - use .xls for compatibility
$filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename) . '.xls';

// Build WHERE clause based on filters
$where_conditions = [];
$params = [];

// Status filter
if (!empty($statusFilter)) {
    $where_conditions[] = "e.[Status] = ?";
    $params[] = $statusFilter;
}

// Nationality filter
if ($nationalityFilter != 'all') {
    if ($nationalityFilter == 'No Nationality') {
        $where_conditions[] = "(n.[Nationality] IS NULL OR n.[Nationality] = '')";
    } else {
        $where_conditions[] = "n.[Nationality] = ?";
        $params[] = $nationalityFilter;
    }
}

$where_clause = "";
if (count($where_conditions) > 0) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Fetch EOC data
$sql_eoc = "
    SELECT 
        FORMAT(e.[Employee#], '0') as [Employee#],
        e.[Name],
        e.[Permit Name],
        d.[Department],
        n.[Nationality],
        e.[Birthdate],
        e.[Gender],
        e.[Race],
        e.[Position],
        e.[Grade],
        e.[Hire Date],
        e.[Work Permit Number],
        e.[Work Permit Expiry (New)],
        e.[Old Passport],
        e.[New Passport],
        e.[Passport Expiry Date],
        e.[SPIKPA Expiry],
        e.[SOCSO No],
        e.[Contact No (Employee)],
        e.[Email Address],
        e.[Hostel],
        e.[Next Of Kin],
        e.[Relationship],
        e.[Contact No In Source Country],
        e.[Status],
        e.[DateMoved],
        e.[MovedRemarks],
        e.[Last_ Working Day],
        e.[FlightDate],
        e.[POL#Number],
        e.[Date of Report],
        e.[Remarks]
    FROM [Updated_FCW_List].[dbo].[eoc] AS e
    LEFT JOIN [Updated_FCW_List].[dbo].[Nationality] AS n
        ON e.[NationalityID] = n.[NationalityID]
    LEFT JOIN [Updated_FCW_List].[dbo].[Department] AS d
        ON e.[DepartmentID] = d.[DepartmentID]
    $where_clause
    " . (empty($statusFilter) ? "" : "AND e.[Status] = 'EOC'") . "
    ORDER BY e.[DateMoved] DESC
";

// If status filter is set, adjust the query
if (!empty($statusFilter)) {
    $sql_eoc = "
        SELECT 
            FORMAT(e.[Employee#], '0') as [Employee#],
            e.[Name],
            e.[Permit Name],
            d.[Department],
            n.[Nationality],
            e.[Birthdate],
            e.[Gender],
            e.[Race],
            e.[Position],
            e.[Grade],
            e.[Hire Date],
            e.[Work Permit Number],
            e.[Work Permit Expiry (New)],
            e.[Old Passport],
            e.[New Passport],
            e.[Passport Expiry Date],
            e.[SPIKPA Expiry],
            e.[SOCSO No],
            e.[Contact No (Employee)],
            e.[Email Address],
            e.[Hostel],
            e.[Next Of Kin],
            e.[Relationship],
            e.[Contact No In Source Country],
            e.[Status],
            e.[DateMoved],
            e.[MovedRemarks],
            e.[Last_ Working Day],
            e.[FlightDate],
            e.[POL#Number],
            e.[Date of Report],
            e.[Remarks]
        FROM [Updated_FCW_List].[dbo].[eoc] AS e
        LEFT JOIN [Updated_FCW_List].[dbo].[Nationality] AS n
            ON e.[NationalityID] = n.[NationalityID]
        LEFT JOIN [Updated_FCW_List].[dbo].[Department] AS d
            ON e.[DepartmentID] = d.[DepartmentID]
        $where_clause
        " . ($statusFilter == 'EOC' ? "AND e.[Status] = 'EOC'" : "AND e.[Status] = 'RUNAWAY'") . "
        ORDER BY e.[DateMoved] DESC
    ";
}

$stmt_eoc = sqlsrv_query($conn2, $sql_eoc, $params);
if ($stmt_eoc === false) {
    die("Error fetching EOC data: " . print_r(sqlsrv_errors(), true));
}

// Fetch RUNAWAY data (only if no status filter or if showing both)
$sql_runaway = "
    SELECT 
        FORMAT(e.[Employee#], '0') as [Employee#],
        e.[Name],
        e.[Permit Name],
        d.[Department],
        n.[Nationality],
        e.[Birthdate],
        e.[Gender],
        e.[Race],
        e.[Position],
        e.[Grade],
        e.[Hire Date],
        e.[Work Permit Number],
        e.[Work Permit Expiry (New)],
        e.[Old Passport],
        e.[New Passport],
        e.[Passport Expiry Date],
        e.[SPIKPA Expiry],
        e.[SOCSO No],
        e.[Contact No (Employee)],
        e.[Email Address],
        e.[Hostel],
        e.[Next Of Kin],
        e.[Relationship],
        e.[Contact No In Source Country],
        e.[Status],
        e.[DateMoved],
        e.[MovedRemarks],
        e.[Last_ Working Day],
        e.[FlightDate],
        e.[POL#Number],
        e.[Date of Report],
        e.[Remarks]
    FROM [Updated_FCW_List].[dbo].[eoc] AS e
    LEFT JOIN [Updated_FCW_List].[dbo].[Nationality] AS n
        ON e.[NationalityID] = n.[NationalityID]
    LEFT JOIN [Updated_FCW_List].[dbo].[Department] AS d
        ON e.[DepartmentID] = d.[DepartmentID]
    $where_clause
    " . (empty($statusFilter) ? "AND e.[Status] = 'RUNAWAY'" : "") . "
    ORDER BY e.[DateMoved] DESC
";

// Only fetch runaway if no status filter or not EOC filter
$stmt_runaway = null;
if (empty($statusFilter) || $statusFilter !== 'EOC') {
    $stmt_runaway = sqlsrv_query($conn2, $sql_runaway, $params);
    if ($stmt_runaway === false) {
        die("Error fetching RUNAWAY data: " . print_r(sqlsrv_errors(), true));
    }
}

// Store data in arrays
$eoc_data = [];
while ($row = sqlsrv_fetch_array($stmt_eoc, SQLSRV_FETCH_ASSOC)) {
    // Only add to eoc_data if it's actually EOC status
    if ($row['Status'] == 'EOC' || empty($statusFilter)) {
        $eoc_data[] = $row;
    }
}

$runaway_data = [];
if ($stmt_runaway) {
    while ($row = sqlsrv_fetch_array($stmt_runaway, SQLSRV_FETCH_ASSOC)) {
        // Only add to runaway_data if it's actually RUNAWAY status
        if ($row['Status'] == 'RUNAWAY') {
            $runaway_data[] = $row;
        }
    }
}

$count_eoc = count($eoc_data);
$count_runaway = count($runaway_data);

// Function to safely format dates
function formatExcelDate($date) {
    if ($date instanceof DateTime) {
        $year = (int)$date->format('Y');
        // Skip invalid dates
        if ($year < 1950) {
            return 'N/A';
        }
        return $date->format('d-m-Y');
    }
    return 'N/A';
}

// Function to safely get value
function safeValue($value) {
    if ($value === null || $value === '') {
        return 'N/A';
    }
    
    // Handle DateTime objects for non-date fields
    if ($value instanceof DateTime) {
        return formatExcelDate($value);
    }
    
    return htmlspecialchars($value);
}

// Determine which sections to show based on filter
$showEOC = (empty($statusFilter) || $statusFilter === 'EOC') && $count_eoc > 0;
$showRunaway = (empty($statusFilter) || $statusFilter === 'RUNAWAY') && $count_runaway > 0;

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
                    <x:Name>EOC Runaway List</x:Name>
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
            background-color: #f0f0f0;
            white-space: nowrap;
        }
        .section-header-eoc {
            background-color: #dc3545;
            color: white;
            font-weight: bold;
            padding: 15px;
            font-size: 14pt;
            text-align: left;
            border: 1px solid #000;
        }
        .section-header-runaway {
            background-color: #fd7e14;
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
        .status-eoc {
            background-color: #dc3545;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        .status-runaway {
            background-color: #fd7e14;
            color: white;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>
    <table>
        <?php if ($showEOC): ?>
        <!-- EOC SECTION HEADER -->
        <tr>
            <td colspan="20" class="section-header-eoc">EOC - END OF CONTRACT (<?php echo $count_eoc; ?> employees)</td>
        </tr>
        
        <!-- EOC TABLE HEADER -->
        <tr class="header-row">
            <th>Employee No</th>
            <th>Name</th>
            <th>Permit Name</th>
            <th>Department</th>
            <th>Nationality</th>
            <th>Position</th>
            <th>Grade</th>
            <th>Date of Birth</th>
            <th>Gender</th>
            <th>Work Permit Number</th>
            <th>Work Permit Expiry</th>
            <th>Passport (New)</th>
            <th>Passport Expiry</th>
            <th>Contact Number</th>
            <th>Last Working Day</th>
            <th>Flight Date</th>
            <th>Date Moved</th>
            <th>Status</th>
            <th>Move Remarks</th>
            <th>Emergency Contact</th>
        </tr>
        
        <!-- EOC TABLE DATA -->
        <?php if ($count_eoc > 0): ?>
            <?php foreach ($eoc_data as $row): ?>
                <tr>
                    <td><?php echo safeValue($row['Employee#']); ?></td>
                    <td><?php echo safeValue($row['Name']); ?></td>
                    <td><?php echo safeValue($row['Permit Name']); ?></td>
                    <td><?php echo safeValue($row['Department']); ?></td>
                    <td><?php echo safeValue($row['Nationality']); ?></td>
                    <td><?php echo safeValue($row['Position']); ?></td>
                    <td><?php echo safeValue($row['Grade']); ?></td>
                    <td><?php echo formatExcelDate($row['Birthdate']); ?></td>
                    <td><?php echo safeValue($row['Gender']); ?></td>
                    <td><?php echo safeValue($row['Work Permit Number']); ?></td>
                    <td><?php echo formatExcelDate($row['Work Permit Expiry (New)']); ?></td>
                    <td><?php echo safeValue($row['New Passport']); ?></td>
                    <td><?php echo formatExcelDate($row['Passport Expiry Date']); ?></td>
                    <td><?php echo safeValue($row['Contact No (Employee)']); ?></td>
                    <td><?php echo formatExcelDate($row['Last_ Working Day']); ?></td>
                    <td><?php echo formatExcelDate($row['FlightDate']); ?></td>
                    <td><?php echo formatExcelDate($row['DateMoved']); ?></td>
                    <td class="status-eoc"><?php echo htmlspecialchars($row['Status']); ?></td>
                    <td><?php echo safeValue($row['MovedRemarks']); ?></td>
                    <td><?php echo safeValue($row['Next Of Kin']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="20" style="text-align: center; font-style: italic;">No EOC records found.</td>
            </tr>
        <?php endif; ?>
        
        <!-- SPACING -->
        <?php if ($showEOC && $showRunaway): ?>
        <tr><td colspan="20" style="border: none; height: 20px;"></td></tr>
        <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($showRunaway): ?>
        <!-- RUNAWAY SECTION HEADER -->
        <tr>
            <td colspan="20" class="section-header-runaway">RUNAWAY (<?php echo $count_runaway; ?> employees)</td>
        </tr>
        
        <!-- RUNAWAY TABLE HEADER -->
        <tr class="header-row">
            <th>Employee No</th>
            <th>Name</th>
            <th>Permit Name</th>
            <th>Department</th>
            <th>Nationality</th>
            <th>Position</th>
            <th>Grade</th>
            <th>Date of Birth</th>
            <th>Gender</th>
            <th>Work Permit Number</th>
            <th>Work Permit Expiry</th>
            <th>Passport (New)</th>
            <th>Passport Expiry</th>
            <th>Contact Number</th>
            <th>Last Working Day</th>
            <th>POL# Number</th>
            <th>Date of Report</th>
            <th>Date Moved</th>
            <th>Status</th>
            <th>Move Remarks</th>
        </tr>
        
        <!-- RUNAWAY TABLE DATA -->
        <?php if ($count_runaway > 0): ?>
            <?php foreach ($runaway_data as $row): ?>
                <tr>
                    <td><?php echo safeValue($row['Employee#']); ?></td>
                    <td><?php echo safeValue($row['Name']); ?></td>
                    <td><?php echo safeValue($row['Permit Name']); ?></td>
                    <td><?php echo safeValue($row['Department']); ?></td>
                    <td><?php echo safeValue($row['Nationality']); ?></td>
                    <td><?php echo safeValue($row['Position']); ?></td>
                    <td><?php echo safeValue($row['Grade']); ?></td>
                    <td><?php echo formatExcelDate($row['Birthdate']); ?></td>
                    <td><?php echo safeValue($row['Gender']); ?></td>
                    <td><?php echo safeValue($row['Work Permit Number']); ?></td>
                    <td><?php echo formatExcelDate($row['Work Permit Expiry (New)']); ?></td>
                    <td><?php echo safeValue($row['New Passport']); ?></td>
                    <td><?php echo formatExcelDate($row['Passport Expiry Date']); ?></td>
                    <td><?php echo safeValue($row['Contact No (Employee)']); ?></td>
                    <td><?php echo formatExcelDate($row['Last_ Working Day']); ?></td>
                    <td><?php echo safeValue($row['POL#Number']); ?></td>
                    <td><?php echo formatExcelDate($row['Date of Report']); ?></td>
                    <td><?php echo formatExcelDate($row['DateMoved']); ?></td>
                    <td class="status-runaway"><?php echo htmlspecialchars($row['Status']); ?></td>
                    <td><?php echo safeValue($row['MovedRemarks']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="20" style="text-align: center; font-style: italic;">No Runaway records found.</td>
            </tr>
        <?php endif; ?>
        <?php endif; ?>
        
        <?php if (!$showEOC && !$showRunaway): ?>
        <tr>
            <td colspan="20" style="text-align: center; font-style: italic; padding: 30px;">
                No records found matching the selected filters.
            </td>
        </tr>
        <?php endif; ?>
    </table>
</body>
</html>
<?php
sqlsrv_free_stmt($stmt_eoc);
if ($stmt_runaway) {
    sqlsrv_free_stmt($stmt_runaway);
}
?>