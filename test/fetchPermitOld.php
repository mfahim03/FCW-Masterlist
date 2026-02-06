<?php
// Pagination setup
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Count total records
$count_sql = "SELECT COUNT(*) as total 
            FROM [FCW_List].[dbo].[Employee]"; // ADD WHERE EXPIRED < 3 MONTHS FROM CURRENT DATE
$count_stmt = sqlsrv_query($conn1, $count_sql);
$count_row = sqlsrv_fetch_array($count_stmt, SQLSRV_FETCH_ASSOC);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch paginated records with Department table join
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
        n.[Nationality]
    FROM [FCW_List].[dbo].[Employee] AS e
    LEFT JOIN [FCW_List].[dbo].[Nationality] AS n
        ON e.[NationalityID] = n.[NationalityID]
    LEFT JOIN [FCW_List].[dbo].[Department] AS d
        ON e.[DepartmentID] = d.[DepartmentID]
    ORDER BY e.[Work Permit Expiry (New)]
    OFFSET $offset ROWS
    FETCH NEXT $records_per_page ROWS ONLY;
";

$stmt = sqlsrv_query($conn1, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>