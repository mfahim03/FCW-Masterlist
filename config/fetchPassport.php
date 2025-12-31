<?php
// Pagination setup
$records_per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Filter parameters
$monthFilter = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$nationalityFilter = isset($_GET['nationality']) ? $_GET['nationality'] : 'all';

// Base WHERE conditions
$whereConditions = "WHERE e.[Passport Expiry Date] IS NOT NULL";

// Add month/date filter
if ($monthFilter > 0) {
    // Filter by selected month (any year)
    $whereConditions .= " AND MONTH(e.[Passport Expiry Date]) = $monthFilter";
} else {
    // Default: only expiring within 1 year from now
    $whereConditions .= " AND e.[Passport Expiry Date] <= DATEADD(YEAR, 1, GETDATE())";
}

// Add nationality filter
if ($nationalityFilter !== 'all') {
    // Escape single quotes for SQL Server
    $nationalityFilterEscaped = str_replace("'", "''", $nationalityFilter);
    $whereConditions .= " AND n.[Nationality] = '$nationalityFilterEscaped'";
}

// Count total records for current filter
$count_sql = "
    SELECT COUNT(*) as total 
    FROM [FCW_List].[dbo].[Employee] AS e
    LEFT JOIN [FCW_List].[dbo].[Nationality] AS n ON e.[NationalityID] = n.[NationalityID]
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
        d.[Department],
        e.[Permit Name],
        e.[Old Passport],
        e.[New Passport],
        e.[Passport Expiry Date],
        e.[SPIKPA Expiry],
        e.[Passport Renewed Status],
        n.[Nationality]
    FROM [FCW_List].[dbo].[Employee] AS e
    LEFT JOIN [FCW_List].[dbo].[Nationality] AS n
        ON e.[NationalityID] = n.[NationalityID]
    LEFT JOIN [FCW_List].[dbo].[Department] AS d
        ON e.[DepartmentID] = d.[DepartmentID]
    $whereConditions
    ORDER BY e.[Passport Expiry Date] ASC
    OFFSET $offset ROWS
    FETCH NEXT $records_per_page ROWS ONLY;
";

$stmt = sqlsrv_query($conn1, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Get all distinct nationalities for the dropdown
$nationality_sql = "
    SELECT DISTINCT n.[Nationality]
    FROM [FCW_List].[dbo].[Nationality] AS n
    INNER JOIN [FCW_List].[dbo].[Employee] AS e ON e.[NationalityID] = n.[NationalityID]
    WHERE n.[Nationality] IS NOT NULL
    ORDER BY n.[Nationality] ASC
";
$nationality_stmt = sqlsrv_query($conn1, $nationality_sql);
$nationalities = [];
if ($nationality_stmt) {
    while ($nat_row = sqlsrv_fetch_array($nationality_stmt, SQLSRV_FETCH_ASSOC)) {
        $nationalities[] = $nat_row['Nationality'];
    }
    sqlsrv_free_stmt($nationality_stmt);
}
?>