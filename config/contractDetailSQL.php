<?php
header('Content-Type: application/json');
include '../db.php';

$contract = isset($_GET['contract']) ? $_GET['contract'] : '';

if (empty($contract)) {
    echo json_encode(['success' => false, 'error' => 'No contract type provided']);
    exit;
}

// Determine WHERE clause based on contract type
$whereClause = "WHERE e.[Work Permit Expiry (New)] IS NOT NULL AND e.[Contract] IS NOT NULL";

if ($contract === 'extend') {
    $whereClause .= " AND e.[Contract] = 'EXTEND'";
} elseif ($contract === 'not_extend') {
    $whereClause .= " AND e.[Contract] = 'NOT EXTEND'";
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid contract type']);
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

// Query for monthly breakdown (based on permit expiry date)
$monthlySQL = "
    SELECT 
        FORMAT(e.[Work Permit Expiry (New)], 'MMM yyyy') as month_year,
        YEAR(e.[Work Permit Expiry (New)]) as year,
        MONTH(e.[Work Permit Expiry (New)]) as month,
        COUNT(*) as count
    FROM [FCW_List].[dbo].[Employee] AS e
    $whereClause
    GROUP BY FORMAT(e.[Work Permit Expiry (New)], 'MMM yyyy'), YEAR(e.[Work Permit Expiry (New)]), MONTH(e.[Work Permit Expiry (New)])
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