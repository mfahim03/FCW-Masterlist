<?php
include '../db.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: indexView.php");
    exit;
}

// Handle form submission for updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_employee'])) {
    $employeeId = $_POST['employee_id'];
    
    // Update query
    $updateSql = "
        UPDATE [Updated_FCW_List].[dbo].[eoc]
        SET 
            [Name] = ?,
            [Permit Name] = ?,
            [NationalityID] = ?,
            [Race] = ?,
            [Gender] = ?,
            [DepartmentID] = ?,
            [Cost Centre] = ?,
            [Position] = ?,
            [Grade] = ?,
            [Hire Date] = ?,
            [Work Permit Expiry (New)] = ?,
            [Work Permit Number] = ?,
            [Birthdate] = ?,
            [SPIKPA Expiry] = ?,
            [SOCSO No] = ?,
            [Old Passport] = ?,
            [New Passport] = ?,
            [Passport Expiry Date] = ?,
            [YOS] = ?,
            [Contract] = ?,
            [Hostel] = ?,
            [(EE)/Shift Group] = ?,
            [Destination] = ?,
            [Contact No (Employee)] = ?,
            [Email Address] = ?,
            [Next Of Kin] = ?,
            [Relationship] = ?,
            [Contact No In Source Country] = ?,
            [TPEA] = ?,
            [Address In Source Country] = ?,
            [Last_ Working Day] = ?,
            [FlightDate] = ?,
            [POL#Number] = ?,
            [Date of Report] = ?,
            [Remarks] = ?,
            [MovedRemarks] = ?
        WHERE [Employee#] = ?
    ";
    
    $params = array(
        $_POST['name'],
        $_POST['permit_name'],
        !empty($_POST['nationality_id']) ? $_POST['nationality_id'] : null,
        $_POST['race'],
        $_POST['gender'],
        !empty($_POST['department_id']) ? $_POST['department_id'] : null,
        $_POST['cost_centre'],
        $_POST['position'],
        $_POST['grade'],
        !empty($_POST['hire_date']) ? $_POST['hire_date'] : null,
        !empty($_POST['work_permit_expiry']) ? $_POST['work_permit_expiry'] : null,
        $_POST['work_permit_number'],
        !empty($_POST['birthdate']) ? $_POST['birthdate'] : null,
        !empty($_POST['spikpa_expiry']) ? $_POST['spikpa_expiry'] : null,
        $_POST['socso_no'],
        $_POST['old_passport'],
        $_POST['new_passport'],
        !empty($_POST['passport_expiry']) ? $_POST['passport_expiry'] : null,
        $_POST['yos'],
        $_POST['contract'],
        $_POST['hostel'],
        $_POST['shift_group'],
        $_POST['destination'],
        $_POST['contact_no'],
        $_POST['email'],
        $_POST['next_of_kin'],
        $_POST['relationship'],
        $_POST['contact_source_country'],
        $_POST['tpea'],
        $_POST['address_source_country'],
        !empty($_POST['last_working_day']) ? $_POST['last_working_day'] : null,
        !empty($_POST['flight_date']) ? $_POST['flight_date'] : null,
        $_POST['pol_number'],
        !empty($_POST['date_of_report']) ? $_POST['date_of_report'] : null,
        $_POST['remarks'],
        $_POST['moved_remarks'],
        $employeeId
    );
    
    $updateStmt = sqlsrv_query($conn2, $updateSql, $params);
    
    if ($updateStmt === false) {
        $errors = sqlsrv_errors();
        error_log("Update Error: " . print_r($errors, true));
        $_SESSION['error'] = "Error updating employee details.";
    } else {
        $_SESSION['success'] = "Employee details updated successfully!";
    }
    
    // Redirect back to detail page
    header("Location: ../runawayDetail.php?id=" . urlencode($employeeId));
    exit;
} else {
    // If accessed directly without POST
    $_SESSION['error'] = "Invalid request.";
    header("Location: runaway.php");
    exit;
}
?>