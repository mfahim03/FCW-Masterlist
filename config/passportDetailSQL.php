<?php
header('Content-Type: application/json');
include '../db.php';

$status = isset($_GET['status']) ? $_GET['status'] : '';

if (empty($status)) {
    echo json_encode(['success' => false, 'error' => 'No status provided']);
    exit;
}

// Determine WHERE clause based on status
$today = date('Y-m-d');
$oneYearLater = date('Y-m-d', strtotime('+365 days'));

$whereClause = "WHERE e.[Passport Expiry Date] IS NOT NULL";

if ($status === 'expired') {
    // Expired passports (before today)
    $whereClause .= " AND CAST(e.[Passport Expiry Date] AS DATE) < CAST('$today' AS DATE)";
} elseif ($status === 'expiring') {
    // Expiring within 365 days
    $whereClause .= " AND CAST(e.[Passport Expiry Date] AS DATE) >= CAST('$today' AS DATE)";
    $whereClause .= " AND CAST(e.[Passport Expiry Date] AS DATE) <= CAST('$oneYearLater' AS DATE)";
} elseif ($status === 'active') {
    // Active (more than 365 days away)
    $whereClause .= " AND CAST(e.[Passport Expiry Date] AS DATE) > CAST('$oneYearLater' AS DATE)";
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

// Query for department breakdown
$deptSQL = "
    SELECT d.[Department], COUNT(*) as count
    FROM [FCW_List].[dbo].[Employee] AS e
    LEFT JOIN [FCW_List].[dbo].[Department] AS d ON e.[DepartmentID] = d.[DepartmentID]
    $whereClause AND d.[Department] IS NOT NULL
    GROUP BY d.[Department]
    ORDER BY count DESC
";

// Query for nationality breakdown
$natSQL = "
    SELECT n.[Nationality], COUNT(*) as count
    FROM [FCW_List].[dbo].[Employee] AS e
    LEFT JOIN [FCW_List].[dbo].[Nationality] AS n ON e.[NationalityID] = n.[NationalityID]
    $whereClause AND n.[Nationality] IS NOT NULL
    GROUP BY n.[Nationality]
    ORDER BY count DESC
";

// Query for monthly breakdown
$monthlySQL = "
    SELECT 
        FORMAT(e.[Passport Expiry Date], 'MMM yyyy') as month_year,
        YEAR(e.[Passport Expiry Date]) as year,
        MONTH(e.[Passport Expiry Date]) as month,
        COUNT(*) as count
    FROM [FCW_List].[dbo].[Employee] AS e
    $whereClause
    GROUP BY FORMAT(e.[Passport Expiry Date], 'MMM yyyy'), YEAR(e.[Passport Expiry Date]), MONTH(e.[Passport Expiry Date])
    ORDER BY year, month
";

// Execute queries
$deptStmt = sqlsrv_query($conn1, $deptSQL);
$natStmt = sqlsrv_query($conn1, $natSQL);
$monthlyStmt = sqlsrv_query($conn1, $monthlySQL);

if ($deptStmt === false || $natStmt === false || $monthlyStmt === false) {
    echo json_encode(['success' => false, 'error' => 'Query execution failed', 'details' => sqlsrv_errors()]);
    exit;
}

// Fetch department data
$departmentData = [];
while ($row = sqlsrv_fetch_array($deptStmt, SQLSRV_FETCH_ASSOC)) {
    $departmentData[] = [
        'label' => $row['Department'],
        'count' => (int)$row['count']
    ];
}

// Fetch nationality data
$nationalityData = [];
while ($row = sqlsrv_fetch_array($natStmt, SQLSRV_FETCH_ASSOC)) {
    $nationalityData[] = [
        'label' => $row['Nationality'],
        'count' => (int)$row['count']
    ];
}

// Fetch monthly data
$monthlyData = [];
while ($row = sqlsrv_fetch_array($monthlyStmt, SQLSRV_FETCH_ASSOC)) {
    $monthlyData[] = [
        'label' => $row['month_year'],
        'count' => (int)$row['count']
    ];
}

// Free statements
sqlsrv_free_stmt($deptStmt);
sqlsrv_free_stmt($natStmt);
sqlsrv_free_stmt($monthlyStmt);

// Return JSON response
echo json_encode([
    'success' => true,
    'department' => $departmentData,
    'nationality' => $nationalityData,
    'monthly' => $monthlyData
]);
?>