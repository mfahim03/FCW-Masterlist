<?php
// ================== DATABASE CONNECTION ==================
$serverName1 = "MAJ-S-02370\HRSQL"; // SQL Server name
$connectionOptions1 = [
    "Database" => "FCW_List", // SQL Database name
    "Uid" => "sa",
    "PWD" => "Alsecure@1#",// Alsecure@1#
    "TrustServerCertificate" => true // helps avoid SSL cert issues sometimes
];

$conn1 = sqlsrv_connect($serverName1, $connectionOptions1);

if ($conn1 === false) {
    die(print_r(sqlsrv_errors(), true));
}

$serverName2 = "MAJ-S-02370\HRSQL"; // SQL Server name
$connectionOptions2 = [
    "Database" => "EmployeeMasterList", // SQL Database name
    "Uid" => "sa",
    "PWD" => "Alsecure@1#",// Alsecure@1#
    "TrustServerCertificate" => true // helps avoid SSL cert issues sometimes
];

$conn2 = sqlsrv_connect($serverName2, $connectionOptions2);

if ($conn2 === false) {
    die(print_r(sqlsrv_errors(), true));
}

?>