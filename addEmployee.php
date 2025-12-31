<?php
include 'db.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // If not admin, redirect to user view
    header("Location: indexView.php");
    exit;
}

// NOTE: Fetching employee data ($employeeData) is not necessary for an ADD page, 
// but is included in the reference, so we'll assume it's for completeness in a 
// combined view/add/edit setup and keep it minimal here.
$employeeData = []; // Initialize to prevent errors if not fetching

// Fetch all nationalities for dropdown
$nationality_sql = "SELECT [NationalityID], [Nationality] FROM [FCW_List].[dbo].[Nationality] ORDER BY [Nationality]";
$nationality_stmt = sqlsrv_query($conn1, $nationality_sql);
$nationalities = [];
if ($nationality_stmt !== false) {
    while ($nat = sqlsrv_fetch_array($nationality_stmt, SQLSRV_FETCH_ASSOC)) {
        $nationalities[] = $nat;
    }
} else {
    error_log("Error fetching nationalities: " . print_r(sqlsrv_errors(), true));
}

// Fetch all department for dropdown
$department_sql = "SELECT [DepartmentID], [Department] FROM [FCW_List].[dbo].[Department] ORDER BY [Department]";
$department_stmt = sqlsrv_query($conn1, $department_sql);
$departments = [];
if ($department_stmt !== false) {
    while ($dept = sqlsrv_fetch_array($department_stmt, SQLSRV_FETCH_ASSOC)) {
        $departments[] = $dept;
    }
} else {
    error_log("Error fetching departments: " . print_r(sqlsrv_errors(), true));
}

// Fetch all unique contract types for dropdown
$contracts = [];
$contract_sql = "SELECT DISTINCT [Contract] FROM [FCW_List].[dbo].[Employee] WHERE [Contract] IS NOT NULL";
$contract_stmt = sqlsrv_query($conn1, $contract_sql);

if ($contract_stmt !== false) {
    while ($row = sqlsrv_fetch_array($contract_stmt, SQLSRV_FETCH_ASSOC)) {
        $contractValue = trim($row['Contract']);
        if ($contractValue != '') {
            $contracts[] = $contractValue;
        }
    }
    sqlsrv_free_stmt($contract_stmt);
}

// Add default values if no contracts found in database
if (empty($contracts)) {
    $contracts = ['EXTEND', 'NOT EXTEND'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Foreign Contract Worker</title>
    <link rel="icon" type="image/png" href="img/fcw2.png">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/employee.css">
    <link rel="stylesheet" href="css/form.css">
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
        <p>FCW Employee Masterlist</p>
    </div>

    <?php include 'model/navigationBar.php'; ?>

    <div class="content-wrapper">
        <div class="form-container-wrapper">
            <div class="add-employee-header">
                <button class="back-btn" onclick="window.location.href='employeeInfo.php'">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to List
                </button>
                <h2><i class="fa-solid fa-user-plus"></i> Add New Employee</h2>
                <p>Fill in the information below to register a new employee</p>
            </div>

            <form id="addEmployeeForm" method="POST" action="saveEmployee.php" enctype="multipart/form-data">
                
                <div class="image-upload-section">
                    <div class="employee-image-container">
                        <div class="employee-image-preview" id="imagePreviewContainer">
                            <i class="fa-solid fa-user placeholder-icon" id="placeholderIcon"></i>
                            <img id="imagePreview" style="display: none;" alt="Employee Photo">
                        </div>
                    </div>
                    <div>
                        <label for="imageInput" class="image-upload-btn">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Choose Photo
                        </label>
                        <input type="file" id="imageInput" name="employee_image" accept="image/*">
                        <button type="button" class="remove-image-btn" id="removeImageBtn" onclick="removeImage()">
                            <i class="fa-solid fa-times"></i> Remove Photo
                        </button>
                    </div>
                    <div class="image-info">
                        <i class="fa-solid fa-info-circle"></i> Supported formats: JPG, PNG, GIF (Max 10MB)
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="form-section-title">Personal Information</div>
                    <div class="form-grid">
                        <div class="form-field required">
                            <label>Employee Number</label>
                            <input type="text" name="employee_no" required placeholder="Enter employee number">
                        </div>
                        <div class="form-field required">
                            <label>Name</label>
                            <input type="text" name="name" required placeholder="Enter full name">
                        </div>
                        <div class="form-field">
                            <label>Permit Name</label>
                            <input type="text" name="permit_name" placeholder="Enter permit name">
                        </div>
                        <div class="form-field">
                            <label>Date of Birth</label>
                            <input type="date" name="birthdate">
                        </div>
                        <div class="form-field required">
                            <label>Gender</label>
                            <select name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">MALE</option>
                                <option value="Female">FEMALE</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Race</label>
                            <input type="text" name="race" placeholder="Enter race">
                        </div>
                        <div class="form-field required">
                            <label>Nationality</label>
                            <select name="nationality_id" required>
                                <option value="">Select Nationality</option>
                                <?php foreach ($nationalities as $nat): ?>
                                    <option value="<?php echo htmlspecialchars($nat['NationalityID']); ?>">
                                        <?php echo htmlspecialchars($nat['Nationality']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">Employment Information</div>
                    <div class="form-grid">
                        <div class="form-field required">
                            <label>Department</label>
                            <select name="department_id" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept['DepartmentID']); ?>"> 
                                        <?php echo htmlspecialchars($dept['Department']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Cost Centre</label>
                            <input type="text" name="cost_centre" placeholder="Enter cost centre">
                        </div>
                        <div class="form-field required">
                            <label>Position</label>
                            <input type="text" name="position" required placeholder="Enter position">
                        </div>
                        <div class="form-field">
                            <label>Grade</label>
                            <input type="text" name="grade" placeholder="Enter grade">
                        </div>
                        <div class="form-field required">
                            <label>Hire Date</label>
                            <input type="date" name="hire_date" required>
                        </div>
                        <div class="form-field">
                            <label>Years of Service (YOS)</label>
                            <input type="text" name="yos" placeholder="Calculated automatically" readonly>
                        </div>
                        <div class="form-field">
                            <label>Contract Type</label>
                            <select name="contract">
                                <option value="">Select Contract Type</option>
                                <?php foreach ($contracts as $contract): ?>
                                    <option value="<?php echo htmlspecialchars($contract); ?>"> 
                                        <?php echo htmlspecialchars($contract); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Shift Group</label>
                            <input type="text" name="shift_group" placeholder="Enter shift group">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">Work Permit & Documents</div>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Work Permit Number</label>
                            <input type="text" name="work_permit_no" placeholder="Enter work permit number">
                        </div>
                        <div class="form-field">
                            <label>Work Permit Expiry Date</label>
                            <input type="date" name="work_permit_expiry">
                        </div>
                        <div class="form-field">
                            <label>Old Passport</label>
                            <input type="text" name="old_passport" placeholder="Enter old passport number">
                        </div>
                        <div class="form-field">
                            <label>New Passport</label>
                            <input type="text" name="new_passport" placeholder="Enter new passport number">
                        </div>
                        <div class="form-field">
                            <label>Passport Expiry Date</label>
                            <input type="date" name="passport_expiry">
                        </div>
                        <div class="form-field">
                            <label>Medical Checkup Status</label>
                            <select name="medical_status">
                                <option value="Incomplete">Incomplete</option>
                                <option value="Complete">Complete</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>SPIKPA Expiry</label>
                            <input type="date" name="spikpa_expiry">
                        </div>
                        <div class="form-field">
                            <label>SOCSO Number</label>
                            <input type="text" name="socso_no" placeholder="Enter SOCSO number">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">Contact Information</div>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Contact Number</label>
                            <input type="text" name="contact_no" placeholder="Enter contact number">
                        </div>
                        <div class="form-field">
                            <label>Email Address</label>
                            <input type="email" name="email" placeholder="Enter email address">
                        </div>
                        <div class="form-field">
                            <label>Hostel</label>
                            <input type="text" name="hostel" placeholder="Enter hostel name">
                        </div>
                        <div class="form-field">
                            <label>Destination</label>
                            <input type="text" name="destination" placeholder="Enter destination">
                        </div>
                    </div>
                    <div class="form-grid full-width">
                        <div class="form-field">
                            <label>Address in Source Country</label>
                            <textarea name="address_source" rows="3" placeholder="Enter address in source country"></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">Emergency Contact</div>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Next of Kin</label>
                            <input type="text" name="next_of_kin" placeholder="Enter next of kin name">
                        </div>
                        <div class="form-field">
                            <label>Relationship</label>
                            <input type="text" name="relationship" placeholder="Enter relationship">
                        </div>
                        <div class="form-field">
                            <label>Contact Number in Source Country</label>
                            <input type="text" name="contact_source" placeholder="Enter contact number">
                        </div>
                        <div class="form-field">
                            <label>TPEA</label>
                            <input type="text" name="tpea" placeholder="Enter TPEA">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">Additional Information</div>
                    <div class="form-grid full-width">
                        <div class="form-field">
                            <label>Remarks</label>
                            <textarea name="remarks" rows="4" placeholder="Enter any additional remarks"></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <i class="fa-solid fa-user-plus"></i> Add Employee
                    </button>
                    <button type="button" class="btn-cancel" onclick="window.location.href='employeeInfo.php'">
                        <i class="fa-solid fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'model/footer.php'; ?>

    <script src="js/employeeForm.js"></script>
    <script>
    // Image preview functionality
    document.getElementById('imageInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validate file type
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file (JPG, PNG, or GIF)');
                this.value = '';
                return;
            }
            
            // Validate file size (max 5MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('Image size should be less than 10MB');
                this.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').src = e.target.result;
                document.getElementById('imagePreview').style.display = 'block';
                document.getElementById('placeholderIcon').style.display = 'none';
                document.getElementById('removeImageBtn').style.display = 'inline-block';
            }
            reader.readAsDataURL(file);
        }
    });

    // Remove image function
    function removeImage() {
        document.getElementById('imageInput').value = '';
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('imagePreview').src = '';
        document.getElementById('placeholderIcon').style.display = 'block';
        document.getElementById('removeImageBtn').style.display = 'none';
    }

    // ===== LOGOUT CONFIRMATION =====
    document.querySelector('.logout-link').addEventListener('click', (e) => {
        e.preventDefault();
        if (confirm("Are you sure you want to log out?")) {
            window.location.href = "logout.php";
        }
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            alert.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(function() {
                alert.remove();
            }, 300);
        });
    }, 5000);
    </script>
</body>
</html>