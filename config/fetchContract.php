<?php
// Pagination setup
$records_per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Month filter
$monthFilter = isset($_GET['month']) ? (int)$_GET['month'] : 0;

// Contract filter (EXTEND or NOT EXTEND)
$contractFilter = isset($_GET['contract']) ? $_GET['contract'] : 'EXTEND';

// Base WHERE conditions
$whereConditions = "WHERE e.[Work Permit Expiry (New)] IS NOT NULL AND e.[Contract] IS NOT NULL";

// Add contract type filter
if ($contractFilter === 'EXTEND') {
    $whereConditions .= " AND e.[Contract] = 'EXTEND'";
} elseif ($contractFilter === 'NOT EXTEND') {
    $whereConditions .= " AND e.[Contract] = 'NOT EXTEND'";
}

// Add month/date filter
if ($monthFilter > 0) {
    // Filter by selected month (any year)
    $whereConditions .= " AND MONTH(e.[Work Permit Expiry (New)]) = $monthFilter";
} else {
    // Default: only expiring 2 months from now
    $whereConditions .= "
      AND e.[Work Permit Expiry (New)] >= DATEFROMPARTS(YEAR(DATEADD(MONTH, 2, GETDATE())), MONTH(DATEADD(MONTH, 2, GETDATE())), 1)
      AND e.[Work Permit Expiry (New)] < DATEFROMPARTS(YEAR(DATEADD(MONTH, 3, GETDATE())), MONTH(DATEADD(MONTH, 3, GETDATE())), 1)";
}

// Count total records for current filter
$count_sql = "
    SELECT COUNT(*) as total 
    FROM [FCW_List].[dbo].[Employee] AS e
    $whereConditions
";
$count_stmt = sqlsrv_query($conn1, $count_sql);
$count_row = sqlsrv_fetch_array($count_stmt, SQLSRV_FETCH_ASSOC);
$total_records = $count_row['total'];
$total_pages = max(1, ceil($total_records / $records_per_page));

// Validate page
if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $records_per_page;
}

// Fetch paginated records
$sql = "
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
    $whereConditions
    ORDER BY e.[Work Permit Expiry (New)] ASC
    OFFSET $offset ROWS
    FETCH NEXT $records_per_page ROWS ONLY;
";

$stmt = sqlsrv_query($conn1, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Count for each contract type (for display in pills) - based on current month filter
$baseWhereForCounts = "WHERE [Work Permit Expiry (New)] IS NOT NULL AND [Contract] IS NOT NULL";

if ($monthFilter > 0) {
    $baseWhereForCounts .= " AND MONTH([Work Permit Expiry (New)]) = $monthFilter";
} else {
    $baseWhereForCounts .= "
      AND [Work Permit Expiry (New)] >= DATEFROMPARTS(YEAR(DATEADD(MONTH, 2, GETDATE())), MONTH(DATEADD(MONTH, 2, GETDATE())), 1)
      AND [Work Permit Expiry (New)] < DATEFROMPARTS(YEAR(DATEADD(MONTH, 3, GETDATE())), MONTH(DATEADD(MONTH, 3, GETDATE())), 1)";
}

$count_extend_sql = "SELECT COUNT(*) as total FROM [FCW_List].[dbo].[Employee] $baseWhereForCounts AND [Contract] = 'EXTEND'";
$count_extend_stmt = sqlsrv_query($conn1, $count_extend_sql);
$count_extend_row = sqlsrv_fetch_array($count_extend_stmt, SQLSRV_FETCH_ASSOC);
$total_extend_records = $count_extend_row['total'];

$count_not_extend_sql = "SELECT COUNT(*) as total FROM [FCW_List].[dbo].[Employee] $baseWhereForCounts AND [Contract] = 'NOT EXTEND'";
$count_not_extend_stmt = sqlsrv_query($conn1, $count_not_extend_sql);
$count_not_extend_row = sqlsrv_fetch_array($count_not_extend_stmt, SQLSRV_FETCH_ASSOC);
$total_not_extend_records = $count_not_extend_row['total'];
?>