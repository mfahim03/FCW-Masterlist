<?php
include 'db.php';
include 'config/fetchEmployee.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
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
    <style>
        .display-box {
            width: 100%;
            padding: 10px 12px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 38px;
            font-size: 14px;
            color: #333;
            word-wrap: break-word;
            box-sizing: border-box;
        }
        
        .display-box.empty {
            color: #999;
            font-style: italic;
        }
        
        textarea.display-box {
            min-height: 80px;
            line-height: 1.5;
            resize: none;
            font-family: inherit;
        }
        
        .read-only-badge {
            display: inline-block;
            background: #2196F3;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            margin-left: 10px;
        }
    </style>
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

    <?php include 'model/userNavBar.php'; ?>

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

            <!-- Employee Details (Read-Only) -->
            <div class="employee-form-container">
                <?php if ($employeeData): ?>
                    <h2><i class="fa-solid fa-user-circle"></i> Employee Details</h2>
                    
                    <div id="employeeDisplay">
                        <!-- Personal Information -->
                        <div class="form-section">
                            <div class="form-section-title">Personal Information</div>
                            
                            <!-- Image Display Section -->
                            <div class="image-upload-section">
                                <div class="employee-image-container">
                                    <img src='<?php echo $img_path ?>' class="employee-image" alt="Employee Photo">
                                </div>
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Employee Number</label>
                                    <div class="display-box"><?php echo htmlspecialchars($employeeData['Employee#']); ?></div>
                                </div>
                                <div class="form-field">
                                    <label>Name</label>
                                    <div class="display-box <?php echo empty($employeeData['Name']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['Name']) ? htmlspecialchars($employeeData['Name']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Permit Name</label>
                                    <div class="display-box <?php echo empty($employeeData['Permit Name']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['Permit Name']) ? htmlspecialchars($employeeData['Permit Name']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Date of Birth</label>
                                    <div class="display-box <?php echo empty(formatDate($employeeData['Birthdate'])) ? 'empty' : ''; ?>">
                                        <?php echo !empty(formatDate($employeeData['Birthdate'])) ? formatDate($employeeData['Birthdate']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Gender</label>
                                    <div class="display-box <?php echo empty($employeeData['Gender']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['Gender']) ? htmlspecialchars($employeeData['Gender']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Race</label>
                                    <div class="display-box <?php echo empty($employeeData['Race']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['Race']) ? htmlspecialchars($employeeData['Race']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Nationality</label>
                                    <div class="display-box <?php echo empty($employeeData['Nationality']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['Nationality']) ? htmlspecialchars($employeeData['Nationality']) : ''; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Employment Information -->
                        <div class="form-section">
                            <div class="form-section-title">Employment Information</div>
                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Department</label>
                                    <div class="display-box <?php echo empty($employeeData['Department']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['Department']) ? htmlspecialchars($employeeData['Department']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Cost Centre</label>
                                    <div class="display-box <?php echo empty($employeeData['Cost Centre']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['Cost Centre']) ? htmlspecialchars($employeeData['Cost Centre']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Position</label>
                                    <div class="display-box <?php echo empty($employeeData['Position']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['Position']) ? htmlspecialchars($employeeData['Position']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Grade</label>
                                    <div class="display-box <?php echo empty($employeeData['Grade']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['Grade']) ? htmlspecialchars($employeeData['Grade']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Hire Date</label>
                                    <div class="display-box <?php echo empty(formatDate($employeeData['Hire Date'])) ? 'empty' : ''; ?>">
                                        <?php echo !empty(formatDate($employeeData['Hire Date'])) ? formatDate($employeeData['Hire Date']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Years of Service (YOS)</label>
                                    <div class="display-box <?php echo empty($employeeData['YOS']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['YOS']) ? htmlspecialchars($employeeData['YOS']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Contract Type</label>
                                    <div class="display-box <?php echo empty($employeeData['Contract']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['Contract']) ? htmlspecialchars($employeeData['Contract']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Shift Group</label>
                                    <div class="display-box <?php echo empty($employeeData['(EE)/Shift Group']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['(EE)/Shift Group']) ? htmlspecialchars($employeeData['(EE)/Shift Group']) : ''; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Work Permit & Document Information -->
                        <div class="form-section">
                            <div class="form-section-title">Work Permit & Documents</div>
                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Work Permit Number</label>
                                    <div class="display-box <?php echo empty($employeeData['Work Permit Number']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['Work Permit Number']) ? htmlspecialchars($employeeData['Work Permit Number']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Work Permit Expiry Date</label>
                                    <div class="display-box <?php echo empty(formatDate($employeeData['Work Permit Expiry (New)'])) ? 'empty' : ''; ?>">
                                        <?php echo !empty(formatDate($employeeData['Work Permit Expiry (New)'])) ? formatDate($employeeData['Work Permit Expiry (New)']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Old Passport</label>
                                    <div class="display-box <?php echo empty($employeeData['Old Passport']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['Old Passport']) ? htmlspecialchars($employeeData['Old Passport']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>New Passport</label>
                                    <div class="display-box <?php echo empty($employeeData['New Passport']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['New Passport']) ? htmlspecialchars($employeeData['New Passport']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Passport Expiry Date</label>
                                    <div class="display-box <?php echo empty(formatDate($employeeData['Passport Expiry Date'])) ? 'empty' : ''; ?>">
                                        <?php echo !empty(formatDate($employeeData['Passport Expiry Date'])) ? formatDate($employeeData['Passport Expiry Date']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Medical Checkup Status</label>
                                    <div class="display-box">
                                        <?php echo getMedicalStatus($employeeData['MedicalDate']); ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>SPIKPA Expiry</label>
                                    <div class="display-box <?php echo empty(formatDate($employeeData['SPIKPA Expiry'])) ? 'empty' : ''; ?>">
                                        <?php echo !empty(formatDate($employeeData['SPIKPA Expiry'])) ? formatDate($employeeData['SPIKPA Expiry']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>SOCSO Number</label>
                                    <div class="display-box <?php echo empty($employeeData['SOCSO No']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['SOCSO No']) ? htmlspecialchars($employeeData['SOCSO No']) : ''; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="form-section">
                            <div class="form-section-title">Contact Information</div>
                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Contact Number</label>
                                    <div class="display-box <?php echo empty($employeeData['Contact No (Employee)']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['Contact No (Employee)']) ? htmlspecialchars($employeeData['Contact No (Employee)']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Email Address</label>
                                    <div class="display-box <?php echo empty($employeeData['Email Address']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['Email Address']) ? htmlspecialchars($employeeData['Email Address']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Hostel</label>
                                    <div class="display-box <?php echo empty($employeeData['Hostel']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['Hostel']) ? htmlspecialchars($employeeData['Hostel']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Destination</label>
                                    <div class="display-box <?php echo empty($employeeData['Destination']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['Destination']) ? htmlspecialchars($employeeData['Destination']) : ''; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="form-grid full-width">
                                <div class="form-field">
                                    <label>Address in Source Country</label>
                                    <div class="display-box <?php echo empty($employeeData['Address In Source Country']) ? 'empty' : ''; ?>" style="min-height: 80px; line-height: 1.5;">
                                        <?php echo !empty($employeeData['Address In Source Country']) ? htmlspecialchars($employeeData['Address In Source Country']) : ''; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Emergency Contact -->
                        <div class="form-section">
                            <div class="form-section-title">Emergency Contact</div>
                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Next of Kin</label>
                                    <div class="display-box <?php echo empty($employeeData['Next Of Kin']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['Next Of Kin']) ? htmlspecialchars($employeeData['Next Of Kin']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Relationship</label>
                                    <div class="display-box <?php echo empty($employeeData['Relationship']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['Relationship']) ? htmlspecialchars($employeeData['Relationship']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>Contact Number in Source Country</label>
                                    <div class="display-box <?php echo empty($employeeData['Contact No In Source Country']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['Contact No In Source Country']) ? htmlspecialchars($employeeData['Contact No In Source Country']) : ''; ?>
                                    </div>
                                </div>
                                <div class="form-field">
                                    <label>TPEA</label>
                                    <div class="display-box <?php echo empty($employeeData['TPEA']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($employeeData['TPEA']) ? htmlspecialchars($employeeData['TPEA']) : ''; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="form-section">
                            <div class="form-section-title">Additional Information</div>
                            <div class="form-grid full-width">
                                <div class="form-field">
                                    <label>Remarks</label>
                                    <div class="display-box <?php echo empty($employeeData['Remarks']) ? 'empty' : ''; ?>" style="min-height: 100px; line-height: 1.5;">
                                        <?php echo !empty($employeeData['Remarks']) ? htmlspecialchars($employeeData['Remarks']) : ''; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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

    <script>
    // View employee details function
    function viewEmployeeDetails(employeeId) {
        window.location.href = 'employeeInfoView.php?id=' + employeeId;
    }

    // Search functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchEmployee');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const employeeItems = document.querySelectorAll('.employee-list-item');
                
                employeeItems.forEach(function(item) {
                    const empNo = item.querySelector('.emp-no').textContent.toLowerCase();
                    const empName = item.querySelector('.emp-name').textContent.toLowerCase();
                    
                    if (empNo.includes(searchTerm) || empName.includes(searchTerm)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }

        // Disable context menu on employee list items (no delete option for users)
        const employeeItems = document.querySelectorAll('.employee-list-item');
        employeeItems.forEach(function(item) {
            item.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                return false;
            });
        });
    });

    // Logout confirmation
    document.querySelector('.logout-link').addEventListener('click', (e) => {
        e.preventDefault();
        if (confirm("Are you sure you want to log out?")) {
            window.location.href = "logout.php";
        }
    });
    </script>
</body>
</html>