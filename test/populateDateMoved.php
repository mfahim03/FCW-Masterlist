<?php
// populateDateMoved.php
// This script will populate DateMoved for existing EocRunaway records

include '../db.php'; // Adjust path to your db.php

echo "<!DOCTYPE html>
<html>
<head>
    <title>Populate DateMoved Field</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; padding: 10px; background: #e8f5e8; border: 1px solid #c3e6c3; }
        .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; }
        .info { color: #31708f; padding: 10px; background: #d9edf7; border: 1px solid #bce8f1; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h2>Populate DateMoved Field in EocRunaway Table</h2>";

// Check connection
if (!$conn2) {
    die("<div class='error'>Database connection failed. Please check your db.php configuration.</div>");
}

// 1. First, check current status
$check_sql = "
    SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN [DateMoved] IS NULL THEN 1 ELSE 0 END) as null_datemoved,
        SUM(CASE WHEN [DateMoved] IS NOT NULL THEN 1 ELSE 0 END) as not_null_datemoved,
        MIN([CreatedDate]) as min_created,
        MAX([CreatedDate]) as max_created
    FROM [Updated_FCW_List].[dbo].[eoc]
";

$check_stmt = sqlsrv_query($conn2, $check_sql);

if ($check_stmt === false) {
    $errors = sqlsrv_errors();
    echo "<div class='error'>Error checking data: " . print_r($errors, true) . "</div>";
    exit;
}

$check_data = sqlsrv_fetch_array($check_stmt, SQLSRV_FETCH_ASSOC);

echo "<div class='info'>
    <h3>Current Status:</h3>
    <ul>
        <li>Total Records: " . $check_data['total_records'] . "</li>
        <li>Records with NULL DateMoved: " . $check_data['null_datemoved'] . "</li>
        <li>Records with DateMoved: " . $check_data['not_null_datemoved'] . "</li>
        <li>Earliest CreatedDate: " . ($check_data['min_created'] ? $check_data['min_created']->format('Y-m-d H:i:s') : 'N/A') . "</li>
        <li>Latest CreatedDate: " . ($check_data['max_created'] ? $check_data['max_created']->format('Y-m-d H:i:s') : 'N/A') . "</li>
    </ul>
</div>";

// 2. Preview what will be updated
$preview_sql = "
    SELECT TOP 10 
        [Employee#],
        [Name],
        [Status],
        [CreatedDate],
        [DateMoved]
    FROM [Updated_FCW_List].[dbo].[eoc]
    WHERE [DateMoved] IS NULL
    ORDER BY [CreatedDate] DESC
";

$preview_stmt = sqlsrv_query($conn2, $preview_sql);

if ($preview_stmt !== false) {
    echo "<div class='info'>
        <h3>Sample Records with NULL DateMoved (First 10):</h3>
        <table border='1' cellpadding='5' cellspacing='0'>
            <tr>
                <th>Employee#</th>
                <th>Name</th>
                <th>Status</th>
                <th>CreatedDate</th>
                <th>DateMoved (Current)</th>
            </tr>";
    
    while ($row = sqlsrv_fetch_array($preview_stmt, SQLSRV_FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Employee#']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Status']) . "</td>";
        echo "<td>" . ($row['CreatedDate'] ? $row['CreatedDate']->format('Y-m-d H:i:s') : 'N/A') . "</td>";
        echo "<td>" . ($row['DateMoved'] ? $row['DateMoved']->format('Y-m-d H:i:s') : 'NULL') . "</td>";
        echo "</tr>";
    }
    
    echo "</table></div>";
}

// 3. Update logic
echo "<h3>Update Options:</h3>
<form method='POST'>
    <label>
        <input type='radio' name='update_option' value='created_date' checked>
        Set DateMoved = CreatedDate (for all NULL DateMoved)
    </label><br><br>
    
    <label>
        <input type='radio' name='update_option' value='custom_date'>
        Set DateMoved to custom date: 
        <input type='date' name='custom_date' value='" . date('Y-m-d') . "'>
    </label><br><br>
    
    <label>
        <input type='radio' name='update_option' value='random_past'>
        Set DateMoved to random past date (within last 30 days)
    </label><br><br>
    
    <button type='submit' name='preview' style='background: #f0ad4e; color: white; padding: 10px 20px; border: none; cursor: pointer;'>Preview Update</button>
    <button type='submit' name='execute' style='background: #5cb85c; color: white; padding: 10px 20px; border: none; cursor: pointer;'>Execute Update</button>
</form>";

// Handle form submission
if (isset($_POST['preview']) || isset($_POST['execute'])) {
    $update_option = $_POST['update_option'] ?? 'created_date';
    
    // Build the update query based on option
    switch ($update_option) {
        case 'created_date':
            $update_sql = "
                UPDATE [Updated_FCW_List].[dbo].[eoc]
                SET [DateMoved] = [CreatedDate]
                WHERE [DateMoved] IS NULL
            ";
            $description = "Set DateMoved = CreatedDate";
            break;
            
        case 'custom_date':
            $custom_date = $_POST['custom_date'] ?? date('Y-m-d');
            $update_sql = "
                UPDATE [Updated_FCW_List].[dbo].[eoc]
                SET [DateMoved] = ?
                WHERE [DateMoved] IS NULL
            ";
            $params = [$custom_date];
            $description = "Set DateMoved to custom date: " . $custom_date;
            break;
            
        case 'random_past':
            $update_sql = "
                UPDATE [Updated_FCW_List].[dbo].[eoc]
                SET [DateMoved] = DATEADD(DAY, -ABS(CHECKSUM(NEWID())) % 30, GETDATE())
                WHERE [DateMoved] IS NULL
            ";
            $description = "Set DateMoved to random past date (within last 30 days)";
            break;
            
        default:
            $update_sql = "";
            $description = "Invalid option";
    }
    
    if (isset($_POST['preview'])) {
        echo "<div class='info'>
            <h3>Preview Update:</h3>
            <p><strong>Action:</strong> $description</p>
            <p><strong>SQL to be executed:</strong></p>
            <pre>" . htmlspecialchars($update_sql) . "</pre>
            <p>This will affect " . $check_data['null_datemoved'] . " records.</p>
        </div>";
    }
    
    if (isset($_POST['execute'])) {
        echo "<div class='info'>Executing update: $description</div>";
        
        // Get count before update
        $count_before_sql = "SELECT COUNT(*) as cnt FROM [Updated_FCW_List].[dbo].[eoc] WHERE [DateMoved] IS NOT NULL";
        $count_before_stmt = sqlsrv_query($conn2, $count_before_sql);
        $count_before = sqlsrv_fetch_array($count_before_stmt, SQLSRV_FETCH_ASSOC)['cnt'];
        
        // Execute update
        if ($update_option == 'custom_date') {
            $update_stmt = sqlsrv_query($conn2, $update_sql, $params);
        } else {
            $update_stmt = sqlsrv_query($conn2, $update_sql);
        }
        
        if ($update_stmt === false) {
            $errors = sqlsrv_errors();
            echo "<div class='error'>Update failed: " . print_r($errors, true) . "</div>";
        } else {
            $affected_rows = sqlsrv_rows_affected($update_stmt);
            
            // Get count after update
            $count_after_sql = "SELECT COUNT(*) as cnt FROM [Updated_FCW_List].[dbo].[eoc] WHERE [DateMoved] IS NOT NULL";
            $count_after_stmt = sqlsrv_query($conn2, $count_after_sql);
            $count_after = sqlsrv_fetch_array($count_after_stmt, SQLSRV_FETCH_ASSOC)['cnt'];
            
            echo "<div class='success'>
                <h3>Update Successful!</h3>
                <ul>
                    <li>Records updated: $affected_rows</li>
                    <li>DateMoved populated from: $description</li>
                    <li>Total records with DateMoved before: $count_before</li>
                    <li>Total records with DateMoved after: $count_after</li>
                </ul>
            </div>";
            
            // Show sample of updated records
            $sample_sql = "
                SELECT TOP 5 
                    [Employee#],
                    [Name],
                    [Status],
                    [CreatedDate],
                    [DateMoved]
                FROM [Updated_FCW_List].[dbo].[eoc]
                WHERE [DateMoved] IS NOT NULL
                ORDER BY [DateMoved] DESC
            ";
            
            $sample_stmt = sqlsrv_query($conn2, $sample_sql);
            
            if ($sample_stmt !== false) {
                echo "<div class='info'>
                    <h3>Sample Updated Records:</h3>
                    <table border='1' cellpadding='5' cellspacing='0'>
                        <tr>
                            <th>Employee#</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>CreatedDate</th>
                            <th>DateMoved (New)</th>
                        </tr>";
                
                while ($row = sqlsrv_fetch_array($sample_stmt, SQLSRV_FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['Employee#']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Status']) . "</td>";
                    echo "<td>" . ($row['CreatedDate'] ? $row['CreatedDate']->format('Y-m-d H:i:s') : 'N/A') . "</td>";
                    echo "<td>" . ($row['DateMoved'] ? $row['DateMoved']->format('Y-m-d H:i:s') : 'N/A') . "</td>";
                    echo "</tr>";
                }
                
                echo "</table></div>";
            }
        }
    }
}

// 4. Alternative: Simple one-click update
echo "<hr>
<h3>Quick Update (One Click)</h3>
<p>This will set DateMoved = CreatedDate for all NULL DateMoved records.</p>
<form method='POST'>
    <input type='hidden' name='quick_update' value='1'>
    <button type='submit' style='background: #5bc0de; color: white; padding: 10px 20px; border: none; cursor: pointer;'>
        <i class='fa-solid fa-bolt'></i> Quick Update DateMoved = CreatedDate
    </button>
</form>";

if (isset($_POST['quick_update'])) {
    $quick_sql = "
        UPDATE [Updated_FCW_List].[dbo].[eoc]
        SET [DateMoved] = [CreatedDate]
        WHERE [DateMoved] IS NULL
    ";
    
    $quick_stmt = sqlsrv_query($conn2, $quick_sql);
    
    if ($quick_stmt === false) {
        $errors = sqlsrv_errors();
        echo "<div class='error'>Quick update failed: " . print_r($errors, true) . "</div>";
    } else {
        $affected = sqlsrv_rows_affected($quick_stmt);
        echo "<div class='success'>Quick update completed! $affected records updated.</div>";
    }
}

echo "</body>
</html>";

sqlsrv_close($conn2);
?>