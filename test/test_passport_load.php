<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PASSPORT PAGE DEBUG ===<br><br>";

// Test 1: Check files
echo "1. Checking files...<br>";
$files = ['../db.php', '../config/fetchPassport.php', '../mail/passportExpiry.php'];
foreach ($files as $file) {
    echo "- $file: " . (file_exists($file) ? "EXISTS" : "MISSING") . "<br>";
}
echo "<br>";

// Test 2: Load DB
echo "2. Loading db.php...<br>";
try {
    include '../db.php';
    echo "db.php loaded<br><br>";
} catch (Exception $e) {
    die("ERROR: " . $e->getMessage());
}

// Test 3: Load fetchPassport
echo "3. Loading config/fetchPassport.php...<br>";
try {
    include '../config/fetchPassport.php';
    echo "fetchPassport.php loaded<br>";
    echo "- Variables set: offset=$offset, total_records=$total_records<br><br>";
} catch (Exception $e) {
    die("ERROR: " . $e->getMessage());
}

// Test 4: Load email function
echo "4. Loading mail/passportExpiry.php...<br>";
try {
    include '../mail/passportExpiry.php';
    echo "passportExpiry.php loaded<br>";
    echo "- Function exists: " . (function_exists('sendPassportExpiryAlert') ? "✅ YES" : "❌ NO") . "<br><br>";
} catch (Exception $e) {
    die("ERROR: " . $e->getMessage());
}

// Test 5: Session
echo "5. Starting session...<br>";
session_start();
echo "Session started<br><br>";

// Test 6: Check last_alert.txt
echo "6. Checking last_alert.txt...<br>";
$lastAlertFile = '../mail/last_alert.txt';
if (file_exists($lastAlertFile)) {
    echo "File exists<br>";
    if (is_readable($lastAlertFile)) {
        echo "File is readable<br>";
        $content = file_get_contents($lastAlertFile);
        echo "- Content: $content<br>";
    } else {
        echo "File is NOT readable<br>";
    }
    if (is_writable($lastAlertFile)) {
        echo "File is writable<br>";
    } else {
        echo "File is NOT writable<br>";
    }
} else {
    echo "⚠️ File does not exist yet<br>";
    // Try to create it
    if (@file_put_contents($lastAlertFile, '2020-01-01 00:00:00')) {
        echo "Successfully created file<br>";
    } else {
        echo "CANNOT create file - permission denied<br>";
        echo "<br><strong>THIS IS THE PROBLEM!</strong><br>";
    }
}
echo "<br>";

// Test 7: Test the alert logic
echo "7. Testing alert logic...<br>";
$lastAlertFile = '../mail/last_alert.txt';
$sendAlert = false;

try {
    if (file_exists($lastAlertFile)) {
        $lastAlert = file_get_contents($lastAlertFile);
        $lastAlertTime = strtotime($lastAlert);
        $weekAgo = strtotime('-7 days');
        
        echo "- Last alert: $lastAlert<br>";
        echo "- Week ago: " . date('Y-m-d H:i:s', $weekAgo) . "<br>";
        
        if ($lastAlertTime < $weekAgo) {
            $sendAlert = true;
            echo "Should send alert (last alert was more than 7 days ago)<br>";
        } else {
            echo "Should NOT send alert (last alert was less than 7 days ago)<br>";
        }
    } else {
        $sendAlert = true;
        echo "Should send alert (no previous alert found)<br>";
    }
} catch (Exception $e) {
    echo "ERROR in alert logic: " . $e->getMessage() . "<br>";
}

echo "<br>=== ALL TESTS COMPLETE ===<br>";
echo "If you see this message, all components loaded successfully!";
?>