<?php
// test_eoc_data.php - Diagnostic tool to see what's actually in the database
include 'db.php';
session_start();

if (!isset($_SESSION['username'])) {
    die("Please login first");
}

$employeeId = $_GET['id'] ?? '';

if (empty($employeeId)) {
    die("Please provide an employee ID in the URL: test_eoc_data.php?id=1234");
}

echo "<h1>Diagnostic Report for Employee: " . htmlspecialchars($employeeId) . "</h1>";

// Test 1: Check if employee exists in EOC table
echo "<h2>Test 1: Check if employee exists in EOC table</h2>";
$sql = "SELECT COUNT(*) as cnt FROM [Updated_FCW_List].[dbo].[eoc] WHERE [Employee#] = ?";
$stmt = sqlsrv_query($conn2, $sql, array($employeeId));

if ($stmt === false) {
    echo "<p style='color:red'>ERROR: " . print_r(sqlsrv_errors(), true) . "</p>";
} else {
    $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($result['cnt'] > 0) {
        echo "<p style='color:green'>✓ Employee EXISTS in EOC table</p>";
    } else {
        echo "<p style='color:red'>✗ Employee NOT FOUND in EOC table</p>";
        die("Employee not found. Cannot continue tests.");
    }
}

// Test 2: Fetch ALL data from EOC table
echo "<h2>Test 2: Fetch ALL raw data</h2>";
$sql = "SELECT * FROM [Updated_FCW_List].[dbo].[eoc] WHERE [Employee#] = ?";
$stmt = sqlsrv_query($conn2, $sql, array($employeeId));

if ($stmt === false) {
    echo "<p style='color:red'>ERROR: " . print_r(sqlsrv_errors(), true) . "</p>";
} else {
    $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($data) {
        echo "<p style='color:green'>✓ Data fetched successfully</p>";
        echo "<h3>Raw Data (All Fields):</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>Field Name</th><th>Value</th><th>Type</th></tr>";
        
        foreach ($data as $key => $value) {
            $type = gettype($value);
            $displayValue = '';
            
            if ($value === null) {
                $displayValue = '<em style="color:gray">NULL</em>';
            } elseif ($value instanceof DateTime) {
                $displayValue = $value->format('Y-m-d H:i:s') . ' <em>(DateTime object)</em>';
            } elseif (is_string($value)) {
                $displayValue = htmlspecialchars($value);
            } else {
                $displayValue = var_export($value, true);
            }
            
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
            echo "<td>" . $displayValue . "</td>";
            echo "<td><em>" . $type . "</em></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>✗ No data returned</p>";
    }
}

// Test 3: Check with JOIN (like in runawayDetail.php)
echo "<h2>Test 3: Fetch with JOINs (Department & Nationality)</h2>";
$sql = "
    SELECT 
        e.*,
        d.[Department],
        n.[Nationality]
    FROM [Updated_FCW_List].[dbo].[eoc] AS e
    LEFT JOIN [Updated_FCW_List].[dbo].[Department] AS d
        ON e.[DepartmentID] = d.[DepartmentID]
    LEFT JOIN [Updated_FCW_List].[dbo].[Nationality] AS n
        ON e.[NationalityID] = n.[NationalityID]
    WHERE e.[Employee#] = ?
";

$stmt = sqlsrv_query($conn2, $sql, array($employeeId));

if ($stmt === false) {
    echo "<p style='color:red'>ERROR with JOIN: " . print_r(sqlsrv_errors(), true) . "</p>";
} else {
    $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($data) {
        echo "<p style='color:green'>✓ JOIN query successful</p>";
        echo "<p>Department: " . htmlspecialchars($data['Department'] ?? 'N/A') . "</p>";
        echo "<p>Nationality: " . htmlspecialchars($data['Nationality'] ?? 'N/A') . "</p>";
    } else {
        echo "<p style='color:red'>✗ JOIN query returned no data</p>";
    }
}

// Test 4: Test the formatDate function
echo "<h2>Test 4: Test formatDate() function</h2>";

function formatDate($date) {
    if ($date === null || $date === '') {
        return '';
    }
    
    if ($date instanceof DateTime) {
        return $date->format('Y-m-d');
    }
    
    if (is_string($date)) {
        try {
            $dt = new DateTime($date);
            return $dt->format('Y-m-d');
        } catch (Exception $e) {
            return $date;
        }
    }
    
    return '';
}

// Re-fetch data to test date formatting
$sql = "SELECT * FROM [Updated_FCW_List].[dbo].[eoc] WHERE [Employee#] = ?";
$stmt = sqlsrv_query($conn2, $sql, array($employeeId));
$data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

$dateFields = [
    'Birthdate',
    'Hire Date',
    'Work Permit Expiry (New)',
    'Passport Expiry Date',
    'SPIKPA Expiry',
    'Last_ Working Day',
    'FlightDate',
    'Date of Report',
    'CreatedDate',
    'DateMoved'
];

echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
echo "<tr><th>Date Field</th><th>Raw Value</th><th>Formatted Value</th><th>Status</th></tr>";

foreach ($dateFields as $field) {
    $rawValue = $data[$field] ?? null;
    $formatted = formatDate($rawValue);
    
    $status = '✓';
    $statusColor = 'green';
    
    if ($rawValue === null) {
        $status = 'NULL (OK)';
        $statusColor = 'gray';
    } elseif ($formatted === '') {
        $status = '✗ Format failed';
        $statusColor = 'red';
    }
    
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($field) . "</strong></td>";
    echo "<td>" . ($rawValue instanceof DateTime ? $rawValue->format('Y-m-d H:i:s') . ' (DateTime)' : var_export($rawValue, true)) . "</td>";
    echo "<td>" . htmlspecialchars($formatted) . "</td>";
    echo "<td style='color:" . $statusColor . "'>" . $status . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test 5: Simulate the actual page load
echo "<h2>Test 5: Simulate Actual Page Load</h2>";
try {
    // This is what eocDetail.php does
    $simpleSql = "SELECT * FROM [Updated_FCW_List].[dbo].[eoc] WHERE [Employee#] = ?";
    $stmt = sqlsrv_query($conn2, $simpleSql, array($employeeId));
    
    if ($stmt === false) {
        throw new Exception("Query failed: " . print_r(sqlsrv_errors(), true));
    }
    
    $employee = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    
    if (!$employee) {
        throw new Exception("No employee found");
    }
    
    // Get department
    if (!empty($employee['DepartmentID'])) {
        $deptSql = "SELECT [Department] FROM [Updated_FCW_List].[dbo].[Department] WHERE [DepartmentID] = ?";
        $deptStmt = sqlsrv_query($conn2, $deptSql, array($employee['DepartmentID']));
        if ($deptStmt !== false) {
            $deptRow = sqlsrv_fetch_array($deptStmt, SQLSRV_FETCH_ASSOC);
            if ($deptRow) {
                $employee['Department'] = $deptRow['Department'];
            }
        }
    }
    
    // Get nationality
    if (!empty($employee['NationalityID'])) {
        $natSql = "SELECT [Nationality] FROM [Updated_FCW_List].[dbo].[Nationality] WHERE [NationalityID] = ?";
        $natStmt = sqlsrv_query($conn2, $natSql, array($employee['NationalityID']));
        if ($natStmt !== false) {
            $natRow = sqlsrv_fetch_array($natStmt, SQLSRV_FETCH_ASSOC);
            if ($natRow) {
                $employee['Nationality'] = $natRow['Nationality'];
            }
        }
    }
    
    echo "<p style='color:green'>✓ Page load simulation successful</p>";
    echo "<p>Successfully loaded employee: <strong>" . htmlspecialchars($employee['Name'] ?? 'N/A') . "</strong></p>";
    echo "<p>Department: " . htmlspecialchars($employee['Department'] ?? 'N/A') . "</p>";
    echo "<p>Nationality: " . htmlspecialchars($employee['Nationality'] ?? 'N/A') . "</p>";
    
    // Try to render a sample form section
    echo "<h3>Sample Form Section (Personal Information):</h3>";
    echo "<div style='background:#f5f5f5;padding:15px;border:1px solid #ddd;'>";
    echo "<p><strong>Name:</strong> " . htmlspecialchars($employee['Name'] ?? '') . "</p>";
    echo "<p><strong>Birthdate:</strong> " . formatDate($employee['Birthdate'] ?? null) . "</p>";
    echo "<p><strong>Gender:</strong> " . htmlspecialchars($employee['Gender'] ?? '') . "</p>";
    echo "<p><strong>Status:</strong> " . htmlspecialchars($employee['Status'] ?? '') . "</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>If all tests pass, the issue is in the actual runawayDetail.php page</li>";
echo "<li>If Test 4 shows formatting errors, the formatDate() function needs fixing</li>";
echo "<li>If Test 5 fails, check the database structure and eocDetail.php</li>";
echo "<li>Try accessing the actual page: <a href='runawayDetail.php?id=" . urlencode($employeeId) . "'>runawayDetail.php?id=" . urlencode($employeeId) . "</a></li>";
echo "</ol>";

echo "<p><a href='runaway.php'>← Back to EOC/Runaway List</a></p>";
?>