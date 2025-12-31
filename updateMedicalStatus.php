<?php
include 'db.php';
session_start();

if (!isset($_SESSION['username'])) {
    echo "unauthorized";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeNo = $_POST['employeeNo'] ?? '';
    $status = $_POST['status'] ?? '';

    if (empty($employeeNo) || empty($status)) {
        echo "missing_parameters";
        exit;
    }

    // Validate status value
    if ($status !== 'Complete' && $status !== 'Incomplete') {
        echo "invalid_status";
        exit;
    }

    try {
        // Store the actual date when Complete, or 'Incomplete' string when Incomplete
        if ($status === 'Complete') {
            $currentDate = date('Y-m-d');
            $sql = "UPDATE [FCW_List].[dbo].[Employee] 
                    SET [MedicalDate] = ? 
                    WHERE [Employee#] = ?";
            $params = array($currentDate, $employeeNo);
        } else {
            $sql = "UPDATE [FCW_List].[dbo].[Employee] 
                    SET [MedicalDate] = ? 
                    WHERE [Employee#] = ?";
            $params = array('Incomplete', $employeeNo);
        }

        $stmt = sqlsrv_prepare($conn1, $sql, $params);
        
        if ($stmt === false) {
            echo "prepare_failed: " . print_r(sqlsrv_errors(), true);
            exit;
        }

        if (sqlsrv_execute($stmt)) {
            echo "success";
        } else {
            echo "execute_failed: " . print_r(sqlsrv_errors(), true);
        }

        sqlsrv_free_stmt($stmt);
    } catch (Exception $e) {
        echo "error: " . $e->getMessage();
    }
} else {
    echo "invalid_request";
}
?>