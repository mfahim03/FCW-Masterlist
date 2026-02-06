<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

try {
    include 'db.php';
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    include 'config/eocDetail.php';
    
    if (!isset($employee) || !is_array($employee)) {
        throw new Exception("Employee data not loaded");
    }
    
} catch (Exception $e) {
    error_log("ERROR: " . $e->getMessage());
    ob_end_clean();
    $_SESSION['error'] = "Error loading page";
    header("Location: runaway.php");
    exit;
}

// Enhanced safe get that handles DateTime objects
function safeGet($array, $key, $default = '') {
    if (!isset($array[$key])) {
        return $default;
    }
    
    $value = $array[$key];
    
    // If it's a DateTime object for a non-date field, convert to string
    if ($value instanceof DateTime) {
        // Check if this should be a date field
        $dateFields = [
            'Birthdate', 'Hire Date', 'Work Permit Expiry (New)', 
            'Passport Expiry Date', 'SPIKPA Expiry', 
            'Last_ Working Day', 'FlightDate', 'Date of Report',
            'CreatedDate', 'DateMoved'
        ];
        
        if (!in_array($key, $dateFields)) {
            // This is NOT a date field but has DateTime - it's probably invalid data
            // Return empty string instead
            return $default;
        }
    }
    
    return $value;
}

// Safe date format that returns empty for invalid dates
function safeFormatDate($date) {
    try {
        if ($date === null || $date === '') {
            return 'N/A';
        }
        
        if ($date instanceof DateTime) {
            $year = (int)$date->format('Y');
            // Invalid dates like 1900-01-01 should return N/A
            if ($year < 1950) {
                return 'N/A';
            }
            return $date->format('d-m-Y');
        }
        
        if (is_string($date)) {
            $dt = new DateTime($date);
            $year = (int)$dt->format('Y');
            if ($year < 1950) {
                return 'N/A';
            }
            return $dt->format('d-m-Y');
        }
        
        return 'N/A';
    } catch (Exception $e) {
        return 'N/A';
    }
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foreign Contract Worker</title>
    <link rel="icon" type="image/png" href="img/logo1.png">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/login1.css">
    <link rel="stylesheet" href="css/employee.css">
    <link rel="stylesheet" href="css/runawayDetail.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body style="background-image: url('img/bck.png'); background-size: cover;">
    <div class="header">
        <p>FCW EOC/Runaway</p> <!-- Or your page-specific title -->
        <a href="#" class="login-btn" id="openLoginModal">
            <i class="fa-solid fa-user-lock"></i>
            Admin Login
        </a>
    </div>

    <?php include 'model/navigationBar.php'; ?>
    <?php include 'model/loginModal.php'; ?>

    <div class="content-wrapper">
        <div class="detail-container">
            <a href="runawayView.php?status=<?php echo urlencode(safeGet($employee, 'Status', 'EOC')); ?>" class="back-button">
                <i class="fa-solid fa-arrow-left"></i> Back to List
            </a>

            <div class="detail-header">
                <img src="<?php echo htmlspecialchars($img_path ?? 'img/default-avatar.png'); ?>" alt="Employee Photo">
                <div class="detail-header-info">
                    <h2><?php echo htmlspecialchars(safeGet($employee, 'Name', 'Unknown')); ?></h2>
                    <p style="color: #666; margin: 5px 0;">
                        <strong>Employee No:</strong> <?php echo htmlspecialchars(safeGet($employee, 'Employee#', 'N/A')); ?>
                    </p>
                    <span class="status-badge <?php echo strtolower(safeGet($employee, 'Status', 'eoc')); ?>">
                        <?php echo htmlspecialchars(safeGet($employee, 'Status', 'EOC')); ?>
                    </span>
                    <p style="color: #999; margin-top: 10px; font-size: 14px;">
                        <i class="fa-solid fa-calendar"></i> Moved on: <?php echo safeFormatDate(safeGet($employee, 'DateMoved')); ?>
                    </p>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="form-section">
                <div class="form-section-title">Personal Information</div>
                <div class="form-grid">
                    <div class="form-field">
                        <label>Permit Name</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'Permit Name', 'N/A')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Date of Birth</label>
                        <input type="text" value="<?php echo safeFormatDate(safeGet($employee, 'Birthdate')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Gender</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'Gender', 'N/A')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Race</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'Race', 'N/A')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Nationality</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'Nationality', 'N/A')); ?>" readonly>
                    </div>
                </div>
            </div>

            <!-- Employment Information -->
            <div class="form-section">
                <div class="form-section-title">Employment Information</div>
                <div class="form-grid">
                    <div class="form-field">
                        <label>Department</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'Department', 'N/A')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Cost Centre</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'Cost Centre', 'N/A')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Position</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'Position', 'N/A')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Grade</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'Grade', 'N/A')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Hire Date</label>
                        <input type="text" value="<?php echo safeFormatDate(safeGet($employee, 'Hire Date')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Years of Service (YOS)</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'YOS', 'N/A')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Contract Type</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'Contract', 'N/A')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Shift Group</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, '(EE)/Shift Group', 'N/A')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Hostel</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'Hostel', 'N/A')); ?>" readonly>
                    </div>
                </div>
            </div>

            <!-- Work Permit & Documents -->
            <div class="form-section">
                <div class="form-section-title">Work Permit & Documents</div>
                <div class="form-grid">
                    <div class="form-field">
                        <label>Work Permit Number</label>
                        <?php 
                        // SPECIAL HANDLING: Work Permit Number might be DateTime object
                        $workPermitValue = safeGet($employee, 'Work Permit Number', 'N/A');
                        ?>
                        <input type="text" value="<?php echo htmlspecialchars($workPermitValue); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Work Permit Expiry Date</label>
                        <input type="text" value="<?php echo safeFormatDate(safeGet($employee, 'Work Permit Expiry (New)')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Old Passport</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'Old Passport', 'N/A')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>New Passport</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'New Passport', 'N/A')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Passport Expiry Date</label>
                        <input type="text" value="<?php echo safeFormatDate(safeGet($employee, 'Passport Expiry Date')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>SPIKPA Expiry</label>
                        <input type="text" value="<?php echo safeFormatDate(safeGet($employee, 'SPIKPA Expiry')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>SOCSO Number</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'SOCSO No', 'N/A')); ?>" readonly>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="form-section">
                <div class="form-section-title">Contact Information</div>
                <div class="form-grid">
                    <div class="form-field">
                        <label>Contact Number</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'Contact No (Employee)', 'N/A')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Email Address</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'Email Address', 'N/A')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Airport Destination</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'Destination', 'N/A')); ?>" readonly>
                    </div>
                </div>
                <div class="form-grid full-width">
                    <div class="form-field">
                        <label>Address in Source Country</label>
                        <textarea rows="3" readonly><?php echo htmlspecialchars(safeGet($employee, 'Address In Source Country', 'N/A')); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="form-section">
                <div class="form-section-title">Emergency Contact</div>
                <div class="form-grid">
                    <div class="form-field">
                        <label>Next of Kin</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'Next Of Kin', 'N/A')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Relationship</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'Relationship', 'N/A')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Contact Number in Source Country</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'Contact No In Source Country', 'N/A')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>TPEA</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'TPEA', 'N/A')); ?>" readonly>
                    </div>
                </div>
            </div>

            <!-- EOC/Runaway Specific Information -->
            <div class="form-section">
                <div class="form-section-title">EOC/Runaway Information</div>
                <div class="form-grid">
                    <div class="form-field">
                        <label>Last Working Day</label>
                        <input type="text" value="<?php echo safeFormatDate(safeGet($employee, 'Last_ Working Day')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Flight Date</label>
                        <input type="text" value="<?php echo safeFormatDate(safeGet($employee, 'FlightDate')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>POL# Number</label>
                        <input type="text" value="<?php echo htmlspecialchars(safeGet($employee, 'POL#Number', 'N/A')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Date of Report</label>
                        <input type="text" value="<?php echo safeFormatDate(safeGet($employee, 'Date of Report')); ?>" readonly>
                    </div>
                    <div class="form-field">
                        <label>Record Created</label>
                        <input type="text" value="<?php echo safeFormatDate(safeGet($employee, 'CreatedDate')); ?>" readonly>
                    </div>
                </div>
            </div>

            <!-- Remarks -->
            <div class="form-section">
                <div class="form-section-title">Remarks</div>
                <div class="form-grid full-width">
                    <div class="form-field">
                        <label>General Remarks</label>
                        <textarea rows="4" readonly><?php echo htmlspecialchars(safeGet($employee, 'Remarks', 'N/A')); ?></textarea>
                    </div>
                    <div class="form-field">
                        <label>Move Remarks (Reason for <?php echo htmlspecialchars(safeGet($employee, 'Status', 'EOC/Runaway')); ?>)</label>
                        <textarea rows="4" readonly><?php echo htmlspecialchars(safeGet($employee, 'MovedRemarks', 'N/A')); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'model/footer.php'; ?>

    <script>
    document.querySelector('.logout-link').addEventListener('click', (e) => {
        e.preventDefault();
        if (confirm("Are you sure you want to log out?")) {
            window.location.href = "logout.php";
        }
    });
    </script>
    <script src="js/flightDate.js"></script>
    <script src="js/login.js"></script>
</body>
</html>