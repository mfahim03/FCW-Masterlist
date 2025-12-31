<?php
// Pagination setup
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get filter parameters
$monthFilter = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$departmentFilter = isset($_GET['department']) ? $_GET['department'] : 'all';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'default';

// Base WHERE conditions
$whereConditions = "WHERE e.[Work Permit Expiry (New)] IS NOT NULL";

// Status filter overrides ALL other filters
if ($statusFilter !== 'default') {
    $today = date('Y-m-d');
    $ninetyDaysLater = date('Y-m-d', strtotime('+90 days'));
    
    if ($statusFilter === 'expired') {
        // Show ALL expired permits (before today, not including today)
        // FIXED: Cast both sides of comparison to DATE type for accurate comparison
        $whereConditions .= " AND CAST(e.[Work Permit Expiry (New)] AS DATE) < CAST('$today' AS DATE)";
    } elseif ($statusFilter === 'expiring_soon') {
        // Show ALL permits expiring within 90 days (from today up to 90 days ahead)
        // FIXED: Cast both sides to ensure proper date comparison
        $whereConditions .= " AND CAST(e.[Work Permit Expiry (New)] AS DATE) >= CAST('$today' AS DATE)";
        $whereConditions .= " AND CAST(e.[Work Permit Expiry (New)] AS DATE) <= CAST('$ninetyDaysLater' AS DATE)";
    } elseif ($statusFilter === 'active') {
        // Show ALL active permits (more than 90 days from today)
        // FIXED: Cast both sides for proper comparison
        $whereConditions .= " AND CAST(e.[Work Permit Expiry (New)] AS DATE) > CAST('$ninetyDaysLater' AS DATE)";
    }
} else {
    // Only apply month and department filters when status is 'default'
    
    // Add month filter
    if ($monthFilter > 0) {
        $whereConditions .= " AND MONTH(e.[Work Permit Expiry (New)]) = $monthFilter";
    } else {
        // Default: show expiring in month 2 months from now
        $twoMonthsAhead = (date('n') + 2) % 12;
        if ($twoMonthsAhead == 0) $twoMonthsAhead = 12;
        $whereConditions .= " AND MONTH(e.[Work Permit Expiry (New)]) = $twoMonthsAhead";
    }
    
    // Add department filter (only when status is default)
    if ($departmentFilter !== 'all') {
        $departmentFilterEscaped = str_replace("'", "''", $departmentFilter);
        $whereConditions .= " AND d.[Department] = '$departmentFilterEscaped'";
    }
}

// Count total records
$count_sql = "
    SELECT COUNT(*) as total 
    FROM [FCW_List].[dbo].[Employee] AS e
    LEFT JOIN [FCW_List].[dbo].[Department] AS d ON e.[DepartmentID] = d.[DepartmentID]
    $whereConditions
";
$count_stmt = sqlsrv_query($conn1, $count_sql);
$count_row = sqlsrv_fetch_array($count_stmt, SQLSRV_FETCH_ASSOC);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch paginated records
$sql = "
    SELECT 
        e.[Employee#],
        e.[Permit Name],
        d.[Department],
        e.[Work Permit Number],
        e.[Work Permit Expiry (New)],
        e.[MedicalDate],
        e.[SPIKPA Expiry ],
        e.[Remarks],
        n.[Nationality],
        e.[Birthdate]
    FROM [FCW_List].[dbo].[Employee] AS e
    LEFT JOIN [FCW_List].[dbo].[Nationality] AS n
        ON e.[NationalityID] = n.[NationalityID]
    LEFT JOIN [FCW_List].[dbo].[Department] AS d
        ON e.[DepartmentID] = d.[DepartmentID]
    $whereConditions
    ORDER BY e.[Work Permit Expiry (New)]
    OFFSET $offset ROWS
    FETCH NEXT $records_per_page ROWS ONLY;
";

$stmt = sqlsrv_query($conn1, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Get all distinct departments for the dropdown
$department_sql = "
    SELECT DISTINCT d.[Department]
    FROM [FCW_List].[dbo].[Department] AS d
    INNER JOIN [FCW_List].[dbo].[Employee] AS e ON e.[DepartmentID] = d.[DepartmentID]
    WHERE d.[Department] IS NOT NULL
    ORDER BY d.[Department] ASC
";
$department_stmt = sqlsrv_query($conn1, $department_sql);
$departments = [];
if ($department_stmt) {
    while ($dept_row = sqlsrv_fetch_array($department_stmt, SQLSRV_FETCH_ASSOC)) {
        $departments[] = $dept_row['Department'];
    }
    sqlsrv_free_stmt($department_stmt);
}
?>