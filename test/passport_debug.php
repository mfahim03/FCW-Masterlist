<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo "Step 1: Loading db.php...<br>";
    include 'db.php';
    echo "Success<br><br>";

    echo "Step 2: Loading fetchPassport.php...<br>";
    include 'config/fetchPassport.php';
    echo "Success (offset=$offset, total=$total_records)<br><br>";

    echo "Step 3: Loading passportExpiry.php...<br>";
    include 'mail/passportExpiry.php';
    echo "Success<br><br>";

    echo "Step 4: Starting session...<br>";
    session_start();
    echo "Success<br><br>";

    echo "Step 5: Checking authentication...<br>";
    if (!isset($_SESSION['username'])) {
        echo "Not logged in - would redirect to login.php<br><br>";
        // Don't actually redirect for testing
    } else {
        echo "User logged in: " . $_SESSION['username'] . "<br><br>";
    }

    echo "Step 6: Alert logic...<br>";
    $lastAlertFile = 'mail/last_alert.txt';
    $sendAlert = false;

    if (file_exists($lastAlertFile)) {
        $lastAlert = file_get_contents($lastAlertFile);
        $lastAlertTime = strtotime($lastAlert);
        $weekAgo = strtotime('-7 days');
        
        if ($lastAlertTime < $weekAgo) {
            $sendAlert = true;
        }
    } else {
        $sendAlert = true;
    }

    echo "Send alert: " . ($sendAlert ? "YES" : "NO") . "<br><br>";

    if ($sendAlert) {
        echo "Step 7: Querying database for expiring passports...<br>";
        
        $alertQuery = "
            SELECT 
                e.[Employee#],
                e.[Permit Name],
                d.[Department],
                e.[DepartmentID],
                n.[Nationality],
                COALESCE(e.[New Passport], e.[Old Passport]) AS [Passport Number],
                e.[Passport Expiry Date],
                e.[Passport Renewed Status]
            FROM [FCW_List].[dbo].[Employee1] AS e
            LEFT JOIN [FCW_List].[dbo].[Nationality1] AS n
                ON e.[NationalityID] = n.[NationalityID]
            LEFT JOIN [FCW_List].[dbo].[Department1] AS d
                ON e.[DepartmentID] = d.[DepartmentID]
            WHERE e.[Passport Expiry Date] IS NOT NULL
                AND e.[Passport Expiry Date] <= DATEADD(YEAR, 1, GETDATE())
                AND (e.[Passport Renewed Status] IS NULL OR e.[Passport Renewed Status] != 1)
            ORDER BY e.[Passport Expiry Date] ASC
        ";

        $alertStmt = sqlsrv_query($conn1, $alertQuery);
        
        if ($alertStmt === false) {
            echo "❌ Query failed: " . print_r(sqlsrv_errors(), true) . "<br>";
        } else {
            echo "✅ Query successful<br>";
            
            $employeesExpiringSoon = [];
            
            while ($row = sqlsrv_fetch_array($alertStmt, SQLSRV_FETCH_ASSOC)) {
                $expiryDate = $row['Passport Expiry Date'];
                
                if ($expiryDate instanceof DateTime) {
                    $today = new DateTime();
                    $interval = $today->diff($expiryDate);
                    
                    if ($expiryDate < $today) {
                        $status = 'Expired';
                    } else {
                        $status = 'Expiring Soon (' . $interval->days . ' days)';
                    }
                    
                    $employeesExpiringSoon[] = [
                        'employee_no' => $row['Employee#'] ?? 'N/A',
                        'name' => $row['Permit Name'] ?? 'N/A',
                        'department' => $row['Department'] ?? 'N/A',
                        'nationality' => $row['Nationality'] ?? 'N/A',
                        'passport_no' => $row['Passport Number'] ?? 'N/A',
                        'expiry_date' => $expiryDate->format('d-m-Y'),
                        'status' => $status
                    ];
                }
            }
            
            sqlsrv_free_stmt($alertStmt);
            
            echo "Found " . count($employeesExpiringSoon) . " employees with expiring passports<br><br>";
            
            if (!empty($employeesExpiringSoon)) {
                echo "Step 8: Sending email alert...<br>";
                
                if (sendPassportExpiryAlert($employeesExpiringSoon)) {
                    echo "Email sent successfully!<br>";
                    file_put_contents($lastAlertFile, date('Y-m-d H:i:s'));
                    echo "Updated last_alert.txt<br>";
                } else {
                    echo "Email failed to send<br>";
                }
            }
        }
    }

    echo "<br>Step 9: Rendering HTML page...<br>";
    echo "About to show table with $total_records employees<br><br>";

    echo "<strong>ALL CHECKS PASSED! passport.php should work now.</strong><br>";
    echo "<a href='passport.php'>Click here to test actual passport.php</a>";

} catch (Exception $e) {
    echo "<br><strong style='color: red;'>ERROR at current step:</strong><br>";
    echo $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>