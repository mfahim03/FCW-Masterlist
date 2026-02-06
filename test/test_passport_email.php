<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../db.php';
include '/passportExpiryAlt.php';


echo "Testing passport expiry alert...<br><br>";

// Create fake test data
$testEmployees = [
    [
        'employee_no' => 'FCW001',
        'name' => 'Test Employee',
        'department' => 'Production',
        'nationality' => 'Bangladesh',
        'passport_no' => 'A1234567',
        'expiry_date' => '15-03-2025',
        'status' => 'Expiring Soon (104 days)'
    ],
    [
        'employee_no' => 'FCW002',
        'name' => 'Another Test',
        'department' => 'Assembly',
        'nationality' => 'Nepal',
        'passport_no' => 'B9876543',
        'expiry_date' => '01-01-2025',
        'status' => 'Expired'
    ]
];

echo "Attempting to send test email...<br>";

if (sendPassportExpiryAltAlert($testEmployees)) {
    echo "<br>SUCCESS! Email sent successfully!<br>";
} else {
    echo "<br>FAILED! Email was not sent.<br>";
    echo "Check error logs for details.";
}
?>