<?php
// config/fetchEocRunaway.php

// Get filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'EOC';
$nationalityFilter = isset($_GET['nationality']) ? $_GET['nationality'] : 'all';
$page = 1;
$records_per_page = 10; // For JavaScript pagination only

// Initialize variables
$nationalities = [];
$total_records = 0;
$total_eoc_records = 0;
$total_runaway_records = 0;
$total_pages = 0;
$allRecords = []; // Store all records for client-side pagination

// Check database connection
if (!$conn2) {
    die("Database connection failed. Please check your db.php configuration.");
}

// Get all nationalities for filter dropdown - BASED ON CURRENT STATUS
$nationalities_sql = "
    SELECT DISTINCT n.[Nationality]
    FROM [Updated_FCW_List].[dbo].[eoc] AS e
    LEFT JOIN [Updated_FCW_List].[dbo].[Nationality] AS n
        ON e.[NationalityID] = n.[NationalityID]
    WHERE e.[Status] = ?
    AND (n.[Nationality] IS NOT NULL AND n.[Nationality] != '')
    ORDER BY n.[Nationality]
";

$nationalities_params = [$statusFilter];
$nationalities_stmt = sqlsrv_query($conn2, $nationalities_sql, $nationalities_params);
if ($nationalities_stmt !== false) {
    while ($row = sqlsrv_fetch_array($nationalities_stmt, SQLSRV_FETCH_ASSOC)) {
        $nationalities[] = $row['Nationality'];
    }
}

// Build base WHERE clause
$where_conditions = ["e.[Status] = ?"];
$params = [$statusFilter];

// Add nationality filter if selected
if ($nationalityFilter != 'all') {
    if ($nationalityFilter == 'No Nationality') {
        $where_conditions[] = "(n.[Nationality] IS NULL OR n.[Nationality] = '')";
    } else {
        $where_conditions[] = "n.[Nationality] = ?";
        $params[] = $nationalityFilter;
    }
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Count total records matching filters
$count_sql = "
    SELECT COUNT(*) as total 
    FROM [Updated_FCW_List].[dbo].[eoc] AS e
    LEFT JOIN [Updated_FCW_List].[dbo].[Nationality] AS n
        ON e.[NationalityID] = n.[NationalityID]
    $where_clause
";

$count_stmt = sqlsrv_query($conn2, $count_sql, $params);
if ($count_stmt !== false) {
    $count_row = sqlsrv_fetch_array($count_stmt, SQLSRV_FETCH_ASSOC);
    $total_records = $count_row['total'];
}

// Count total EOC records (for badge count) - ALL EOC records
$count_eoc_sql = "
    SELECT COUNT(*) as total 
    FROM [Updated_FCW_List].[dbo].[eoc] AS e
    WHERE e.[Status] = 'EOC'
";
$count_eoc_stmt = sqlsrv_query($conn2, $count_eoc_sql);
if ($count_eoc_stmt !== false) {
    $count_row = sqlsrv_fetch_array($count_eoc_stmt, SQLSRV_FETCH_ASSOC);
    $total_eoc_records = $count_row['total'];
}

// Count total Runaway records (for badge count) - ALL Runaway records
$count_runaway_sql = "
    SELECT COUNT(*) as total 
    FROM [Updated_FCW_List].[dbo].[eoc] AS e
    WHERE e.[Status] = 'RUNAWAY'
";
$count_runaway_stmt = sqlsrv_query($conn2, $count_runaway_sql);
if ($count_runaway_stmt !== false) {
    $count_row = sqlsrv_fetch_array($count_runaway_stmt, SQLSRV_FETCH_ASSOC);
    $total_runaway_records = $count_row['total'];
}

// Fetch ALL records (NO OFFSET/LIMIT) for client-side pagination
$sql = "
    SELECT 
        FORMAT(e.[Employee#], '0') as [Employee#],
        e.[Name],
        d.[Department],
        n.[Nationality],
        e.[Status],
        e.[DateMoved],
        e.[FlightDate],
        e.[Remarks],
        e.[MovedRemarks],
        e.[RemarksPDF]
    FROM [Updated_FCW_List].[dbo].[eoc] AS e
    LEFT JOIN [Updated_FCW_List].[dbo].[Nationality] AS n
        ON e.[NationalityID] = n.[NationalityID]
    LEFT JOIN [Updated_FCW_List].[dbo].[Department] AS d
        ON e.[DepartmentID] = d.[DepartmentID]
    $where_clause
    ORDER BY 
        CASE WHEN e.[DateMoved] IS NULL THEN 1 ELSE 0 END,
        e.[DateMoved] DESC
";

$stmt = sqlsrv_query($conn2, $sql, $params);

if ($stmt === false) {
    $errors = sqlsrv_errors();
    error_log("Error fetching EOC/Runaway data: " . print_r($errors, true));
    die("Database query error. Please contact administrator.");
}

// Store all records in array
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $allRecords[] = $row;
}

// Calculate total pages for initial display
$total_pages = ceil($total_records / $records_per_page);
if ($total_pages === 0) $total_pages = 1;

// DEBUG: Log filter info
error_log("=====================================");
error_log("Status Filter: $statusFilter");
error_log("Nationality Filter: $nationalityFilter");
error_log("Total Records Found: $total_records");
error_log("Total EOC Records (all): $total_eoc_records");
error_log("Total Runaway Records (all): $total_runaway_records");
error_log("Nationalities available for $statusFilter: " . implode(', ', $nationalities));
error_log("Number of records fetched: " . count($allRecords));
if (count($allRecords) > 0) {
    error_log("First record Nationality: " . ($allRecords[0]['Nationality'] ?? 'NULL'));
    error_log("First record Status: " . ($allRecords[0]['Status'] ?? 'NULL'));
}
error_log("=====================================");
?>
