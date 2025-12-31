<?php
// Start output buffering immediately
ob_start();

// Suppress all errors from being displayed
error_reporting(0);
ini_set('display_errors', 0);

// Try to include db connection
try {
    require_once __DIR__ . '/../db.php';
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Clear buffer and set header
ob_clean();
header('Content-Type: application/json; charset=utf-8');

// Check if connection exists
if (!isset($conn1) || $conn1 === false) {
    echo json_encode(['success' => false, 'error' => 'Database connection not available']);
    exit;
}

// Get and validate status
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$valid_statuses = ['expired', 'expiring', 'active'];

if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid status parameter']);
    exit;
}

// Build WHERE clause based on status - FIXED to match dashboard and fetchpermit logic
$whereClause = "";
switch ($status) {
    case 'expired':
        // Expired: before today (not including today)
        $whereClause = "CAST(E.[Work Permit Expiry (New)] AS DATE) < CAST(GETDATE() AS DATE)";
        break;
    case 'expiring':
        // Expiring Soon: from today to 90 days ahead (including today)
        $whereClause = "CAST(E.[Work Permit Expiry (New)] AS DATE) >= CAST(GETDATE() AS DATE) 
                       AND CAST(E.[Work Permit Expiry (New)] AS DATE) <= CAST(DATEADD(day, 90, GETDATE()) AS DATE)";
        break;
    case 'active':
        // Active: more than 90 days from today
        $whereClause = "CAST(E.[Work Permit Expiry (New)] AS DATE) > CAST(DATEADD(day, 90, GETDATE()) AS DATE)";
        break;
}

// Query for department data
$dept_sql = "
    SELECT 
        ISNULL(D.Department, 'Unknown') AS label,
        COUNT(E.[Employee#]) AS count
    FROM [FCW_List].[dbo].[Employee] E
    LEFT JOIN [FCW_List].[dbo].[Department] D ON E.DepartmentID = D.DepartmentID
    WHERE E.[Work Permit Expiry (New)] IS NOT NULL 
    AND $whereClause
    GROUP BY D.Department
    ORDER BY count DESC
";

$department = [];
$dept_stmt = sqlsrv_query($conn1, $dept_sql);

if ($dept_stmt === false) {
    $errors = sqlsrv_errors();
    echo json_encode([
        'success' => false, 
        'error' => 'Department query failed',
        'sql_error' => $errors[0]['message'] ?? 'Unknown SQL error',
        'query' => $dept_sql
    ]);
    if ($conn1) sqlsrv_close($conn1);
    exit;
}

while ($row = sqlsrv_fetch_array($dept_stmt, SQLSRV_FETCH_ASSOC)) {
    $department[] = [
        'label' => $row['label'] ?? 'Unknown',
        'count' => (int)($row['count'] ?? 0)
    ];
}
sqlsrv_free_stmt($dept_stmt);

// Query for nationality data - join with Nationality table
$nat_sql = "
    SELECT 
        ISNULL(N.Nationality, 'Unknown') AS label,
        COUNT(E.[Employee#]) AS count
    FROM [FCW_List].[dbo].[Employee] E
    LEFT JOIN [FCW_List].[dbo].[Nationality] N ON E.NationalityID = N.NationalityID
    WHERE E.[Work Permit Expiry (New)] IS NOT NULL 
    AND $whereClause
    GROUP BY N.Nationality
    ORDER BY count DESC
";

$nationality = [];
$nat_stmt = sqlsrv_query($conn1, $nat_sql);

if ($nat_stmt === false) {
    $errors = sqlsrv_errors();
    echo json_encode([
        'success' => false, 
        'error' => 'Nationality query failed',
        'sql_error' => $errors[0]['message'] ?? 'Unknown SQL error',
        'query' => $nat_sql
    ]);
    if ($conn1) sqlsrv_close($conn1);
    exit;
}

while ($row = sqlsrv_fetch_array($nat_stmt, SQLSRV_FETCH_ASSOC)) {
    $nationality[] = [
        'label' => $row['label'] ?? 'Unknown',
        'count' => (int)($row['count'] ?? 0)
    ];
}
sqlsrv_free_stmt($nat_stmt);

// Query for monthly breakdown data (NEW!)
$monthly_sql = "
    SELECT 
        FORMAT(E.[Work Permit Expiry (New)], 'MMM yyyy') AS month_year,
        YEAR(E.[Work Permit Expiry (New)]) AS year_num,
        MONTH(E.[Work Permit Expiry (New)]) AS month_num,
        COUNT(E.[Employee#]) AS count
    FROM [FCW_List].[dbo].[Employee] E
    WHERE E.[Work Permit Expiry (New)] IS NOT NULL 
    AND $whereClause
    GROUP BY 
        FORMAT(E.[Work Permit Expiry (New)], 'MMM yyyy'),
        YEAR(E.[Work Permit Expiry (New)]),
        MONTH(E.[Work Permit Expiry (New)])
    ORDER BY year_num ASC, month_num ASC
";

$monthly = [];
$monthly_stmt = sqlsrv_query($conn1, $monthly_sql);

if ($monthly_stmt === false) {
    $errors = sqlsrv_errors();
    echo json_encode([
        'success' => false, 
        'error' => 'Monthly query failed',
        'sql_error' => $errors[0]['message'] ?? 'Unknown SQL error',
        'query' => $monthly_sql
    ]);
    if ($conn1) sqlsrv_close($conn1);
    exit;
}

while ($row = sqlsrv_fetch_array($monthly_stmt, SQLSRV_FETCH_ASSOC)) {
    $monthly[] = [
        'label' => $row['month_year'] ?? 'Unknown',
        'count' => (int)($row['count'] ?? 0)
    ];
}
sqlsrv_free_stmt($monthly_stmt);

// Close database connection
if ($conn1) {
    sqlsrv_close($conn1);
}

// Return successful JSON response with monthly data
echo json_encode([
    'success' => true,
    'department' => $department,
    'nationality' => $nationality,
    'monthly' => $monthly,  // NEW!
    'status' => $status,
    'record_count' => [
        'department' => count($department),
        'nationality' => count($nationality),
        'monthly' => count($monthly)  // NEW!
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

ob_end_flush();
exit;
?>