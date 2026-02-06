<?php
/**
 * One-time script to populate ImagePath in database for existing employee images
 * Run this ONCE after adding the ImagePath column to your database
 */
include 'db.php';

// Fetch all employees
$sql = "SELECT [Employee#] FROM [Updated_FCW_List].[dbo].[eoc]";
$stmt = sqlsrv_query($conn2, $sql);

if ($stmt === false) {
    die("Error fetching employees: " . print_r(sqlsrv_errors(), true));
}

$updated = 0;
$notFound = 0;
$photoDir = 'img/employee_images/';
$extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'JPG', 'JPEG', 'PNG', 'GIF', 'WEBP'];

echo "<h2>Mapping Employee Image Paths</h2>";
echo "<pre>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $employeeNum = $row['Employee#'];
    $imageFound = false;
    
    // Check for existing image files
    foreach ($extensions as $ext) {
        $photoPath = $photoDir . $employeeNum . '.' . $ext;
        
        if (file_exists($photoPath)) {
            // Update database with image path
            $update_sql = "UPDATE [Updated_FCW_List].[dbo].[eoc] SET [ImagePath] = ? WHERE [Employee#] = ?";
            $update_stmt = sqlsrv_query($conn2, $update_sql, array($photoPath, $employeeNum));
            
            if ($update_stmt !== false) {
                echo "✓ Updated: $employeeNum → $photoPath\n";
                $updated++;
                $imageFound = true;
            } else {
                echo "✗ Failed to update: $employeeNum\n";
                error_log("Error: " . print_r(sqlsrv_errors(), true));
            }
            break;
        }
    }
    
    if (!$imageFound) {
        echo "No image found for: $employeeNum\n";
        $notFound++;
    }
}

echo "\n=================================\n";
echo "Summary:\n";
echo "- Images found and updated: $updated\n";
echo "- No images found: $notFound\n";
echo "=================================\n";
echo "</pre>";

sqlsrv_close($conn2);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Image Path Mapping Complete</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            max-height: 500px;
            overflow-y: auto;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <a href="employeeInfo.php" class="btn">Back to Employee Information</a>
    <p><strong>Note:</strong> This script should only be run once. You can delete this file after completion.</p>
</body>
</html>