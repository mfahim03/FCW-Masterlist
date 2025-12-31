<?php
// Fetch work permit expiry data grouped by month and year
$permit_sql = "
    SELECT 
        YEAR([Work Permit Expiry (New)]) as ExpiryYear,
        MONTH([Work Permit Expiry (New)]) as ExpiryMonth,
        COUNT(*) as EmployeeCount
    FROM [FCW_List].[dbo].[Employee]
    WHERE [Work Permit Expiry (New)] IS NOT NULL
    GROUP BY YEAR([Work Permit Expiry (New)]), MONTH([Work Permit Expiry (New)])
    ORDER BY ExpiryYear, ExpiryMonth
";

$permit_stmt = sqlsrv_query($conn1, $permit_sql);
if ($permit_stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$permitChartData = [];
while ($row = sqlsrv_fetch_array($permit_stmt, SQLSRV_FETCH_ASSOC)) {
    $permitChartData[] = [
        'year' => $row['ExpiryYear'],
        'month' => $row['ExpiryMonth'],
        'count' => $row['EmployeeCount']
    ];
}

// Fetch passport expiry data grouped by month and year
$passport_sql = "
    SELECT 
        YEAR([Passport Expiry Date]) as ExpiryYear,
        MONTH([Passport Expiry Date]) as ExpiryMonth,
        COUNT(*) as EmployeeCount
    FROM [FCW_List].[dbo].[Employee]
    WHERE [Passport Expiry Date] IS NOT NULL
    GROUP BY YEAR([Passport Expiry Date]), MONTH([Passport Expiry Date])
    ORDER BY ExpiryYear, ExpiryMonth
";

$passport_stmt = sqlsrv_query($conn1, $passport_sql);
if ($passport_stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$passportChartData = [];
while ($row = sqlsrv_fetch_array($passport_stmt, SQLSRV_FETCH_ASSOC)) {
    $passportChartData[] = [
        'year' => $row['ExpiryYear'],
        'month' => $row['ExpiryMonth'],
        'count' => $row['EmployeeCount']
    ];
}

$monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

$permitLabels = [];
$permitCounts = [];
foreach ($permitChartData as $data) {
    $permitLabels[] = $monthNames[$data['month'] - 1] . ' ' . $data['year'];
    $permitCounts[] = $data['count'];
}

$passportLabels = [];
$passportCounts = [];
foreach ($passportChartData as $data) {
    $passportLabels[] = $monthNames[$data['month'] - 1] . ' ' . $data['year'];
    $passportCounts[] = $data['count'];
}

// Statistics for work permits - FIXED to match fetchpermit.php logic
// Expired: before today (not including today)
// Expiring Soon: from today to 90 days ahead (including today)
// Active: more than 90 days from today
$permit_summary_sql = "
    SELECT 
        COUNT(*) as TotalEmployees,
        SUM(CASE 
            WHEN CAST([Work Permit Expiry (New)] AS DATE) < CAST(GETDATE() AS DATE) 
            THEN 1 ELSE 0 
        END) as ExpiredCount,
        SUM(CASE 
            WHEN CAST([Work Permit Expiry (New)] AS DATE) >= CAST(GETDATE() AS DATE)
            AND CAST([Work Permit Expiry (New)] AS DATE) <= CAST(DATEADD(day, 90, GETDATE()) AS DATE)
            THEN 1 ELSE 0 
        END) as ExpiringSoonCount,
        SUM(CASE 
            WHEN CAST([Work Permit Expiry (New)] AS DATE) > CAST(DATEADD(day, 90, GETDATE()) AS DATE)
            THEN 1 ELSE 0 
        END) as ActiveCount
    FROM [FCW_List].[dbo].[Employee]
    WHERE [Work Permit Expiry (New)] IS NOT NULL";

$permit_summary_stmt = sqlsrv_query($conn1, $permit_summary_sql);
$permit_summary = sqlsrv_fetch_array($permit_summary_stmt, SQLSRV_FETCH_ASSOC);

// Statistics for passports - FIXED to match work permit logic
// Expired: before today (not including today)
// Expiring Soon: from today to 365 days ahead (including today)
// Active: more than 365 days from today
$passport_summary_sql = "
    SELECT 
        COUNT(*) as TotalEmployees,
        SUM(CASE 
            WHEN CAST([Passport Expiry Date] AS DATE) < CAST(GETDATE() AS DATE) 
            THEN 1 ELSE 0 
        END) as ExpiredCount,
        SUM(CASE 
            WHEN CAST([Passport Expiry Date] AS DATE) >= CAST(GETDATE() AS DATE)
            AND CAST([Passport Expiry Date] AS DATE) <= CAST(DATEADD(day, 365, GETDATE()) AS DATE)
            THEN 1 ELSE 0 
        END) as ExpiringSoonCount,
        SUM(CASE 
            WHEN CAST([Passport Expiry Date] AS DATE) > CAST(DATEADD(day, 365, GETDATE()) AS DATE)
            THEN 1 ELSE 0 
        END) as ActiveCount
    FROM [FCW_List].[dbo].[Employee]
    WHERE [Passport Expiry Date] IS NOT NULL";

$passport_summary_stmt = sqlsrv_query($conn1, $passport_summary_sql);
$passport_summary = sqlsrv_fetch_array($passport_summary_stmt, SQLSRV_FETCH_ASSOC);

// Fetch employee count by department
$department_sql = "
   SELECT 
    ISNULL(D.Department, 'Unknown') AS Department,
    COUNT(E.Employee#) AS EmployeeCount
FROM 
    [FCW_List].[dbo].[Employee] E
LEFT JOIN 
    [FCW_List].[dbo].[Department] D 
    ON E.DepartmentID = D.DepartmentID
GROUP BY 
    D.Department";

$department_stmt = sqlsrv_query($conn1, $department_sql);
if ($department_stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$departmentLabels = [];
$departmentCounts = [];
while ($row = sqlsrv_fetch_array($department_stmt, SQLSRV_FETCH_ASSOC)) {
    $departmentLabels[] = $row['Department'];
    $departmentCounts[] = $row['EmployeeCount'];
}

// ===== CONTRACT STATISTICS AND DATA =====

// Contract summary statistics
$contract_summary_sql = "
    SELECT 
        COUNT(*) as TotalEmployees,
        SUM(CASE WHEN [Contract] = 'EXTEND' THEN 1 ELSE 0 END) as ExtendCount,
        SUM(CASE WHEN [Contract] = 'NOT EXTEND' THEN 1 ELSE 0 END) as NotExtendCount,
        SUM(CASE WHEN [Contract] IS NULL OR [Contract] = '' THEN 1 ELSE 0 END) as UndecidedCount,
        SUM(CASE 
            WHEN [Contract] = 'EXTEND' 
            AND CAST([Work Permit Expiry (New)] AS DATE) >= CAST(GETDATE() AS DATE)
            AND CAST([Work Permit Expiry (New)] AS DATE) <= CAST(DATEADD(day, 90, GETDATE()) AS DATE)
            THEN 1 ELSE 0 
        END) as ExtendExpiringSoonCount,
        SUM(CASE 
            WHEN [Contract] = 'NOT EXTEND' 
            AND CAST([Work Permit Expiry (New)] AS DATE) >= CAST(GETDATE() AS DATE)
            AND CAST([Work Permit Expiry (New)] AS DATE) <= CAST(DATEADD(day, 90, GETDATE()) AS DATE)
            THEN 1 ELSE 0 
        END) as NotExtendExpiringSoonCount
    FROM [FCW_List].[dbo].[Employee]
    WHERE [Work Permit Expiry (New)] IS NOT NULL";

$contract_summary_stmt = sqlsrv_query($conn1, $contract_summary_sql);
$contract_summary = sqlsrv_fetch_array($contract_summary_stmt, SQLSRV_FETCH_ASSOC);

// Contract distribution by month and year (for trend chart)
$contract_chart_sql = "
    SELECT 
        YEAR([Work Permit Expiry (New)]) as ExpiryYear,
        MONTH([Work Permit Expiry (New)]) as ExpiryMonth,
        [Contract],
        COUNT(*) as EmployeeCount
    FROM [FCW_List].[dbo].[Employee]
    WHERE [Work Permit Expiry (New)] IS NOT NULL 
        AND [Contract] IN ('EXTEND', 'NOT EXTEND')
    GROUP BY YEAR([Work Permit Expiry (New)]), MONTH([Work Permit Expiry (New)]), [Contract]
    ORDER BY ExpiryYear, ExpiryMonth
";

$contract_chart_stmt = sqlsrv_query($conn1, $contract_chart_sql);
if ($contract_chart_stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$contractChartData = [];
while ($row = sqlsrv_fetch_array($contract_chart_stmt, SQLSRV_FETCH_ASSOC)) {
    $label = $monthNames[$row['ExpiryMonth'] - 1] . ' ' . $row['ExpiryYear'];
    if (!isset($contractChartData[$label])) {
        $contractChartData[$label] = ['EXTEND' => 0, 'NOT EXTEND' => 0];
    }
    $contractChartData[$label][$row['Contract']] = $row['EmployeeCount'];
}

$contractLabels = array_keys($contractChartData);
$contractExtendCounts = [];
$contractNotExtendCounts = [];
foreach ($contractChartData as $data) {
    $contractExtendCounts[] = $data['EXTEND'];
    $contractNotExtendCounts[] = $data['NOT EXTEND'];
}

// Contract distribution by department
$contract_dept_sql = "
    SELECT 
        ISNULL(D.Department, 'Unknown') AS Department,
        SUM(CASE WHEN E.[Contract] = 'EXTEND' THEN 1 ELSE 0 END) as ExtendCount,
        SUM(CASE WHEN E.[Contract] = 'NOT EXTEND' THEN 1 ELSE 0 END) as NotExtendCount
    FROM [FCW_List].[dbo].[Employee] E
    LEFT JOIN [FCW_List].[dbo].[Department] D ON E.DepartmentID = D.DepartmentID
    WHERE E.[Contract] IN ('EXTEND', 'NOT EXTEND')
    GROUP BY D.Department
    HAVING SUM(CASE WHEN E.[Contract] IN ('EXTEND', 'NOT EXTEND') THEN 1 ELSE 0 END) > 0
    ORDER BY D.Department
";

$contract_dept_stmt = sqlsrv_query($conn1, $contract_dept_sql);
if ($contract_dept_stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$contractDeptLabels = [];
$contractDeptExtendCounts = [];
$contractDeptNotExtendCounts = [];
while ($row = sqlsrv_fetch_array($contract_dept_stmt, SQLSRV_FETCH_ASSOC)) {
    $contractDeptLabels[] = $row['Department'];
    $contractDeptExtendCounts[] = $row['ExtendCount'];
    $contractDeptNotExtendCounts[] = $row['NotExtendCount'];
}
?>