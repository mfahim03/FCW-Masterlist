<?php
include 'db.php';
include 'config/fetchEmployee.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // If not admin, redirect to user view
    header("Location: employeeInfoView.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Foreign Contract Worker</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="icon" type="image/png" href="img/fcw2.png">
    <link rel="stylesheet" href="css/employee.css">
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

    <!-- Context Menu -->
    <div id="contextMenu" class="context-menu">
        <div class="context-menu-item" onclick="viewEmployee()">
            <i class="fa-solid fa-eye"></i>
            <span>View Details</span>
        </div>
        <div class="context-menu-item delete" onclick="deleteEmployee()">
            <i class="fa-solid fa-trash"></i>
            <span>Delete Employee</span>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="employee-info-container">
            <!-- Employee List Sidebar -->
            <div class="employee-list">
                <h3><i class="fa-solid fa-users"></i> Employee List</h3>
                <div class="search-box">
                    <input type="text" id="searchEmployee" placeholder="Search by name or employee no.">
                </div>
                <div id="employeeListContainer">
                    <?php 
                    if ($list_stmt !== false) {
                        while ($emp = sqlsrv_fetch_array($list_stmt, SQLSRV_FETCH_ASSOC)): 
                    ?>
                        <div class="employee-list-item <?php echo ($selectedEmployeeId == $emp['Employee#']) ? 'active' : ''; ?>" 
                             data-employee-id="<?php echo htmlspecialchars($emp['Employee#']); ?>"
                             data-employee-name="<?php echo htmlspecialchars($emp['Name'] ?? 'N/A'); ?>"
                             onclick="viewEmployeeDetails('<?php echo urlencode($emp['Employee#']); ?>')">
                            <div class="emp-no"><?php echo htmlspecialchars($emp['Employee#']); ?></div>
                            <div class="emp-name"><?php echo htmlspecialchars($emp['Name'] ?? 'N/A'); ?></div>
                            <div class="emp-dept"><?php echo htmlspecialchars($emp['Department'] ?? 'N/A'); ?> | <?php echo htmlspecialchars($emp['Nationality'] ?? 'N/A'); ?></div>
                        </div>
                    <?php 
                        endwhile;
                    } else {
                        echo '<div style="padding: 20px; text-align: center; color: #999;">No employees found</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Employee Form -->
            <div class="employee-form-container">
                <?php if ($employeeData): ?>
                    <h2><i class="fa-solid fa-user-pen"></i> Employee Details</h2>
                    
                    <form id="employeeForm" method="POST" action="updateEmployee.php" enctype="multipart/form-data">
                        <input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($employeeData['Employee#']); ?>">
                        
                        <!-- Personal Information -->
                        <div class="form-section">
                            <div class="form-section-title">Personal Information</div>
                            
                            <!-- Image Upload Section -->
                            <div class="image-upload-section">
                                <div class="employee-image-container" onclick="document.getElementById('imageInput').click()">
                                    <img id="employeeImagePreview" src='<?php echo $img_path ?>' class="employee-image" alt="Employee Photo">
                                    <div class="image-upload-overlay">
                                        <i class="fa-solid fa-camera"></i>
                                    </div>
                                </div>
                                <input type="file" id="imageInput" name="employee_image" accept="image/*">
                                <input type="hidden" id="removeImageFlag" name="remove_image" value="0">
                                <div style="text-align: center; margin-top: 1px;">
                                    <button type="button" class="remove-image-btn" id="removeImageBtn" onclick="removeEmployeeImage()">
                                        <i class="fa-solid fa-times"></i> Remove Photo
                                    </button>
                                </div>
                            </div>
                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Employee Number</label>
                                    <input type="text" name="employee_no" value="<?php echo htmlspecialchars($employeeData['Employee#']); ?>" readonly>
                                </div>
                                <div class="form-field">
                                    <label>Name</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($employeeData['Name'] ?? ''); ?>">
                                </div>
                                <div class="form-field">
                                    <label>Permit Name</label>
                                    <input type="text" name="permit_name" value="<?php echo htmlspecialchars($employeeData['Permit Name'] ?? ''); ?>">
                                </div>
                                <div class="form-field">
                                    <label>Date of Birth</label>
                                    <input type="date" name="birthdate" value="<?php echo formatDate($employeeData['Birthdate']); ?>">
                                </div>
                                <div class="form-field">
                                    <label>Gender</label>
                                    <select name="gender">
                                        <option value="">Select Gender</option>
                                        <?php 
                                        $gender = trim($employeeData['Gender'] ?? '');
                                        ?>
                                        <option value="Male" <?php echo (strtolower($gender) == 'male') ? 'selected' : ''; ?>>MALE</option>
                                        <option value="Female" <?php echo (strtolower($gender) == 'female') ? 'selected' : ''; ?>>FEMALE</option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label>Race</label>
                                    <input type="text" name="race" value="<?php echo htmlspecialchars($employeeData['Race'] ?? ''); ?>">
                                </div>
                                <div class="form-field">
                                    <label>Nationality</label>
                                    <select name="nationality_id">
                                        <option value="">Select Nationality</option>
                                        <?php foreach ($nationalities as $nat): ?>
                                            <option value="<?php echo $nat['NationalityID']; ?>" 
                                                <?php echo ($employeeData['NationalityID'] == $nat['NationalityID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($nat['Nationality']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Employment Information -->
                        <div class="form-section">
                            <div class="form-section-title">Employment Information</div>
                            <div class="form-grid">
                                <div class="form-field">
                                <label>Department</label>
                                <select name="department_id">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <?php 
                                            // Determine if this is the currently selected department
                                            $selected = ($dept['DepartmentID'] == ($employeeData['DepartmentID'] ?? '')) ? 'selected' : ''; 
                                        ?>
                                        <option value="<?php echo htmlspecialchars($dept['DepartmentID']); ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($dept['Department']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                </div>
                                <div class="form-field">
                                    <label>Cost Centre</label>
                                    <input type="text" name="cost_centre" value="<?php echo htmlspecialchars($employeeData['Cost Centre'] ?? ''); ?>">
                                </div>
                                <div class="form-field">
                                    <label>Position</label>
                                    <input type="text" name="position" value="<?php echo htmlspecialchars($employeeData['Position'] ?? ''); ?>">
                                </div>
                                <div class="form-field">
                                    <label>Grade</label>
                                    <input type="text" name="grade" value="<?php echo htmlspecialchars($employeeData['Grade'] ?? ''); ?>">
                                </div>
                                <div class="form-field">
                                    <label>Hire Date</label>
                                    <input type="date" name="hire_date" value="<?php echo formatDate($employeeData['Hire Date']); ?>">
                                </div>
                                <div class="form-field">
                                    <label>Years of Service (YOS)</label>
                                    <input type="text" name="yos" value="<?php echo htmlspecialchars($employeeData['YOS'] ?? ''); ?>">
                                </div>
                                <div class="form-field">
                                    <label>Contract Type</label>
                                    <select name="contract">
                                        <option value="">Select Contract Type</option>
                                        <?php foreach ($contracts as $contract): ?>
                                            <option value="<?php echo htmlspecialchars($contract); ?>" 
                                                <?php echo ($employeeData['Contract'] == $contract) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($contract); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label>Shift Group</label>
                                    <input type="text" name="shift_group" value="<?php echo htmlspecialchars($employeeData['(EE)/Shift Group'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Work Permit & Document Information -->
                        <div class="form-section">
                            <div class="form-section-title">Work Permit & Documents</div>
                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Work Permit Number</label>
                                    <input type="text" name="work_permit_no" value="<?php echo htmlspecialchars($employeeData['Work Permit Number'] ?? ''); ?>">
                                </div>
                                <div class="form-field">
                                    <label>Work Permit Expiry Date</label>
                                    <input type="date" name="work_permit_expiry" value="<?php echo formatDate($employeeData['Work Permit Expiry (New)']); ?>">
                                </div>
                                <div class="form-field">
                                    <label>Old Passport</label>
                                    <input type="text" name="old_passport" value="<?php echo htmlspecialchars($employeeData['Old Passport'] ?? ''); ?>">
                                </div>
                                <div class="form-field">
                                    <label>New Passport</label>
                                    <input type="text" name="new_passport" value="<?php echo htmlspecialchars($employeeData['New Passport'] ?? ''); ?>">
                                </div>
                                <div class="form-field">
                                    <label>Passport Expiry Date</label>
                                    <input type="date" name="passport_expiry" value="<?php echo formatDate($employeeData['Passport Expiry Date']); ?>">
                                </div>
                                <div class="form-field">
                                    <label>Medical Checkup Status</label>
                                    <select name="medical_status">
                                        <?php 
                                        $medicalStatus = getMedicalStatus($employeeData['MedicalDate']);
                                        ?>
                                        <option value="Incomplete" <?php echo ($medicalStatus === 'Incomplete') ? 'selected' : ''; ?>>Incomplete</option>
                                        <option value="Complete" <?php echo ($medicalStatus === 'Complete') ? 'selected' : ''; ?>>Complete</option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label>SPIKPA Expiry</label>
                                    <input type="date" name="spikpa_expiry" value="<?php echo formatDate($employeeData['SPIKPA Expiry']); ?>">
                                </div>
                                <div class="form-field">
                                    <label>SOCSO Number</label>
                                    <input type="text" name="socso_no" value="<?php echo htmlspecialchars($employeeData['SOCSO No'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="form-section">
                            <div class="form-section-title">Contact Information</div>
                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Contact Number</label>
                                    <input type="text" name="contact_no" value="<?php echo htmlspecialchars($employeeData['Contact No (Employee)'] ?? ''); ?>">
                                </div>
                                <div class="form-field">
                                    <label>Email Address</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($employeeData['Email Address'] ?? ''); ?>">
                                </div>
                                <div class="form-field">
                                    <label>Hostel</label>
                                    <input type="text" name="hostel" value="<?php echo htmlspecialchars($employeeData['Hostel'] ?? ''); ?>">
                                </div>
                                <div class="form-field">
                                    <label>Destination</label>
                                    <input type="text" name="destination" value="<?php echo htmlspecialchars($employeeData['Destination'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-grid full-width">
                                <div class="form-field">
                                    <label>Address in Source Country</label>
                                    <textarea name="address_source" rows="3"><?php echo htmlspecialchars($employeeData['Address In Source Country'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Emergency Contact -->
                        <div class="form-section">
                            <div class="form-section-title">Emergency Contact</div>
                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Next of Kin</label>
                                    <input type="text" name="next_of_kin" value="<?php echo htmlspecialchars($employeeData['Next Of Kin'] ?? ''); ?>">
                                </div>
                                <div class="form-field">
                                    <label>Relationship</label>
                                    <input type="text" name="relationship" value="<?php echo htmlspecialchars($employeeData['Relationship'] ?? ''); ?>">
                                </div>
                                <div class="form-field">
                                    <label>Contact Number in Source Country</label>
                                    <input type="text" name="contact_source" value="<?php echo htmlspecialchars($employeeData['Contact No In Source Country'] ?? ''); ?>">
                                </div>
                                <div class="form-field">
                                    <label>TPEA</label>
                                    <input type="text" name="tpea" value="<?php echo htmlspecialchars($employeeData['TPEA'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="form-section">
                            <div class="form-section-title">Additional Information</div>
                            <div class="form-grid full-width">
                                <div class="form-field">
                                    <label>Remarks</label>
                                    <textarea name="remarks" rows="4"><?php echo htmlspecialchars($employeeData['Remarks'] ?? ''); ?></textarea>
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
                <?php else: ?>
                    <div class="no-employee-selected">
                        <i class="fa-solid fa-user-circle"></i>
                        <p>Select an employee from the list to view details</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'model/footer.php'; ?>

    <script src="js/employeeInfo.js"></script>
    <script src="js/image.js"></script>
    <script>
    // ===== LOGOUT CONFIRMATION =====
    document.querySelector('.logout-link').addEventListener('click', (e) => {
        e.preventDefault();
        if (confirm("Are you sure you want to log out?")) {
            window.location.href = "logout.php";
        }
    });
    </script>
</body>
</html>