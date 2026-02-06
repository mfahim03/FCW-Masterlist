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
    
    if (!isset($_SESSION['username'])) {
        header("Location: indexView.php");
        exit;
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
            return '';
        }
        
        if ($date instanceof DateTime) {
            $year = (int)$date->format('Y');
            // Invalid dates like 1900-01-01 should return empty
            if ($year < 1950) {
                return '';
            }
            return $date->format('Y-m-d');
        }
        
        if (is_string($date)) {
            $dt = new DateTime($date);
            $year = (int)$dt->format('Y');
            if ($year < 1950) {
                return '';
            }
            return $dt->format('Y-m-d');
        }
        
        return '';
    } catch (Exception $e) {
        return '';
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
    <link rel="icon" type="image/png" href="img/fcw2.png">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/employee.css">
    <link rel="stylesheet" href="css/runawayDetail.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body style="background-image: url('img/bck.png'); background-size: cover;">
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <span class="alert-close" onclick="this.parentElement.remove()">&times;</span>
            <i class="fa-solid fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <span class="alert-close" onclick="this.parentElement.remove()">&times;</span>
            <i class="fa-solid fa-check-circle"></i>
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <div class="header">
        <a href="#" class="logout-link">
            <i class="fa-solid fa-right-from-bracket" style="font-size: medium;"></i>
        </a>
        <p>FCW EOC/Runaway</p>
    </div>

    <?php include 'model/navigationBar.php'; ?>

    <div class="content-wrapper">
        <div class="detail-container">
            <a href="runaway.php?status=<?php echo urlencode(safeGet($employee, 'Status', 'EOC')); ?>" class="back-button">
                <i class="fa-solid fa-arrow-left"></i> Back to List
            </a>

            <form id="eocEmployeeForm" method="POST" action="updateEOCEmployee.php" enctype="multipart/form-data">
                <input type="hidden" name="employee_id" value="<?php echo htmlspecialchars(safeGet($employee, 'Employee#')); ?>">

                <div class="detail-header">
                    <div class="image-upload-section">
                        <div class="employee-image-container" onclick="document.getElementById('imageInput').click()">
                            <img id="employeeImagePreview" src="<?php echo htmlspecialchars($img_path ?? 'img/default-avatar.png'); ?>" class="employee-image" alt="Employee Photo">
                            <div class="image-upload-overlay">
                                <i class="fa-solid fa-camera"></i>
                            </div>
                        </div>
                        <input type="file" id="imageInput" name="employee_image" accept="image/*">
                        <input type="hidden" id="removeImageFlag" name="remove_image" value="0">
                        <button type="button" class="remove-image-btn" onclick="removeEmployeeImage()">
                            <i class="fa-solid fa-times"></i> Remove Photo
                        </button>
                    </div>

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
                            <label>Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars(safeGet($employee, 'Name')); ?>">
                        </div>
                        <div class="form-field">
                            <label>Permit Name</label>
                            <input type="text" name="permit_name" value="<?php echo htmlspecialchars(safeGet($employee, 'Permit Name')); ?>">
                        </div>
                        <div class="form-field">
                            <label>Date of Birth</label>
                            <input type="date" name="birthdate" value="<?php echo safeFormatDate(safeGet($employee, 'Birthdate')); ?>">
                        </div>
                        <div class="form-field">
                            <label>Gender</label>
                            <select name="gender">
                                <option value="">Select Gender</option>
                                <?php 
                                $gender = strtolower(trim(safeGet($employee, 'Gender')));
                                ?>
                                <option value="Male" <?php echo ($gender == 'male') ? 'selected' : ''; ?>>MALE</option>
                                <option value="Female" <?php echo ($gender == 'female') ? 'selected' : ''; ?>>FEMALE</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Race</label>
                            <input type="text" name="race" value="<?php echo htmlspecialchars(safeGet($employee, 'Race')); ?>">
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
                            <input type="text" name="cost_centre" value="<?php echo htmlspecialchars(safeGet($employee, 'Cost Centre')); ?>">
                        </div>
                        <div class="form-field">
                            <label>Position</label>
                            <input type="text" name="position" value="<?php echo htmlspecialchars(safeGet($employee, 'Position')); ?>">
                        </div>
                        <div class="form-field">
                            <label>Grade</label>
                            <input type="text" name="grade" value="<?php echo htmlspecialchars(safeGet($employee, 'Grade')); ?>">
                        </div>
                        <div class="form-field">
                            <label>Hire Date</label>
                            <input type="date" name="hire_date" value="<?php echo safeFormatDate(safeGet($employee, 'Hire Date')); ?>">
                        </div>
                        <div class="form-field">
                            <label>Years of Service (YOS)</label>
                            <input type="text" name="yos" value="<?php echo htmlspecialchars(safeGet($employee, 'YOS')); ?>">
                        </div>
                        <div class="form-field">
                            <label>Contract Type</label>
                            <input type="text" name="contract" value="<?php echo htmlspecialchars(safeGet($employee, 'Contract')); ?>">
                        </div>
                        <div class="form-field">
                            <label>Shift Group</label>
                            <input type="text" name="shift_group" value="<?php echo htmlspecialchars(safeGet($employee, '(EE)/Shift Group')); ?>">
                        </div>
                        <div class="form-field">
                            <label>Hostel</label>
                            <input type="text" name="hostel" value="<?php echo htmlspecialchars(safeGet($employee, 'Hostel')); ?>">
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
                            $workPermitValue = safeGet($employee, 'Work Permit Number');
                            ?>
                            <input type="text" name="work_permit_no" value="<?php echo htmlspecialchars($workPermitValue); ?>">
                        </div>
                        <div class="form-field">
                            <label>Work Permit Expiry Date</label>
                            <input type="date" name="work_permit_expiry" value="<?php echo safeFormatDate(safeGet($employee, 'Work Permit Expiry (New)')); ?>">
                        </div>
                        <div class="form-field">
                            <label>Old Passport</label>
                            <input type="text" name="old_passport" value="<?php echo htmlspecialchars(safeGet($employee, 'Old Passport')); ?>">
                        </div>
                        <div class="form-field">
                            <label>New Passport</label>
                            <input type="text" name="new_passport" value="<?php echo htmlspecialchars(safeGet($employee, 'New Passport')); ?>">
                        </div>
                        <div class="form-field">
                            <label>Passport Expiry Date</label>
                            <input type="date" name="passport_expiry" value="<?php echo safeFormatDate(safeGet($employee, 'Passport Expiry Date')); ?>">
                        </div>
                        <div class="form-field">
                            <label>SPIKPA Expiry</label>
                            <input type="date" name="spikpa_expiry" value="<?php echo safeFormatDate(safeGet($employee, 'SPIKPA Expiry')); ?>">
                        </div>
                        <div class="form-field">
                            <label>SOCSO Number</label>
                            <input type="text" name="socso_no" value="<?php echo htmlspecialchars(safeGet($employee, 'SOCSO No')); ?>">
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="form-section">
                    <div class="form-section-title">Contact Information</div>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Contact Number</label>
                            <input type="text" name="contact_no" value="<?php echo htmlspecialchars(safeGet($employee, 'Contact No (Employee)')); ?>">
                        </div>
                        <div class="form-field">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars(safeGet($employee, 'Email Address')); ?>">
                        </div>
                        <div class="form-field">
                            <label>Airport Destination</label>
                            <input type="text" name="destination" value="<?php echo htmlspecialchars(safeGet($employee, 'Destination')); ?>">
                        </div>
                    </div>
                    <div class="form-grid full-width">
                        <div class="form-field">
                            <label>Address in Source Country</label>
                            <textarea name="address_source" rows="3"><?php echo htmlspecialchars(safeGet($employee, 'Address In Source Country')); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div class="form-section">
                    <div class="form-section-title">Emergency Contact</div>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Next of Kin</label>
                            <input type="text" name="next_of_kin" value="<?php echo htmlspecialchars(safeGet($employee, 'Next Of Kin')); ?>">
                        </div>
                        <div class="form-field">
                            <label>Relationship</label>
                            <input type="text" name="relationship" value="<?php echo htmlspecialchars(safeGet($employee, 'Relationship')); ?>">
                        </div>
                        <div class="form-field">
                            <label>Contact Number in Source Country</label>
                            <input type="text" name="contact_source" value="<?php echo htmlspecialchars(safeGet($employee, 'Contact No In Source Country')); ?>">
                        </div>
                        <div class="form-field">
                            <label>TPEA</label>
                            <input type="text" name="tpea" value="<?php echo htmlspecialchars(safeGet($employee, 'TPEA')); ?>">
                        </div>
                    </div>
                </div>

                <!-- EOC/Runaway Specific Information -->
                <div class="form-section">
                    <div class="form-section-title">EOC/Runaway Information</div>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Last Working Day</label>
                            <input type="date" name="last_working_day" value="<?php echo safeFormatDate(safeGet($employee, 'Last_ Working Day')); ?>">
                        </div>
                        <div class="form-field">
                            <label>Flight Date</label>
                            <input type="date" name="flight_date" value="<?php echo safeFormatDate(safeGet($employee, 'FlightDate')); ?>">
                        </div>
                        <div class="form-field">
                            <label>POL# Number</label>
                            <input type="text" name="pol_number" value="<?php echo htmlspecialchars(safeGet($employee, 'POL#Number')); ?>">
                        </div>
                        <div class="form-field">
                            <label>Date of Report</label>
                            <input type="date" name="date_of_report" value="<?php echo safeFormatDate(safeGet($employee, 'Date of Report')); ?>">
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
                            <textarea name="remarks" rows="4"><?php echo htmlspecialchars(safeGet($employee, 'Remarks')); ?></textarea>
                        </div>
                        <div class="form-field">
                            <label>Move Remarks (Reason for <?php echo htmlspecialchars(safeGet($employee, 'Status', 'EOC/Runaway')); ?>)</label>
                            <textarea name="moved_remarks" rows="4"><?php echo htmlspecialchars(safeGet($employee, 'MovedRemarks')); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <i class="fa-solid fa-save"></i> Save Changes
                    </button>
                    <button type="button" class="btn-cancel" onclick="window.location.reload()">
                        <i class="fa-solid fa-times"></i> Cancel
                    </button>
                </div>
            </form>
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

    document.getElementById('imageInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('employeeImagePreview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    function removeEmployeeImage() {
        if (confirm('Are you sure you want to remove this photo?')) {
            document.getElementById('removeImageFlag').value = '1';
            document.getElementById('employeeImagePreview').src = 'img/default-avatar.png';
            document.getElementById('imageInput').value = '';
        }
    }
    </script>
    <script src="js/flightDate.js"></script>
</body>
</html>