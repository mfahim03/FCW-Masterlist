<?php
/**
 * Script to set MedicalDate field value as:
 * - 'complete'   if the employee has an actual medical check date
 * - 'incomplete' if MedicalDate (real date column) is NULL
 *
 * NOTE: This DOES NOT rename columns. Only rewrites MedicalDate values.
 */

include '../db.php';

echo "<h2>Updating MedicalDate (complete/incomplete)</h2>";
echo "<pre>";

$sql = "
    SELECT [Employee#], [MedicalDate]
    FROM [Updated_FCW_List].[dbo].[Employee]
";

$stmt = sqlsrv_query($conn2, $sql);

if ($stmt === false) {
    die("Error fetching employees: " . print_r(sqlsrv_errors(), true));
}

$updated = 0;

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {

    $employeeNum  = $row['Employee#'];
    $actualDate   = $row['MedicalDate'];   // currently may store NULL or actual date

    if ($actualDate === null) {
        // No medical date → incomplete
        $valueToStore = 'incomplete';
    } else {
        // Has medical date → complete
        $valueToStore = 'complete';
    }

    // Update MedicalDate COLUMN with value complete/incomplete
    $update_sql = "
        UPDATE [Updated_FCW_List].[dbo].[Employee]
        SET [MedicalDate] = ?
        WHERE [Employee#] = ?
    ";

    $update_stmt = sqlsrv_query($conn2, $update_sql, [$valueToStore, $employeeNum]);

    if ($update_stmt !== false) {
        echo "✓ $employeeNum → MedicalDate set to: $valueToStore\n";
        $updated++;
    } else {
        echo "✗ Failed to update $employeeNum\n";
        error_log(print_r(sqlsrv_errors(), true));
    }
}

echo "\n=================================\n";
echo "Total Updated: $updated\n";
echo "=================================\n";
echo "</pre>";

sqlsrv_close($conn1);
?>

<!DOCTYPE html>
<html>
<head>
    <title>MedicalDate Update Complete</title>
</head>
<body>
    <a href='employeeInfo.php'>Back to Employee Information</a>
</body>
</html>
