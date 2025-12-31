<?php
include 'db.php';
include 'config/fetchPermit.php';
include 'mail/permitExpiry.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // If not admin, redirect to user view
    header("Location: permitView.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Foreign Contract Worker</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="icon" type="image/png" href="img/fcw2.png">
    <link rel="stylesheet" href="css/modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .medical-complete { color: #218838; font-weight: bold;}
        .medical-incomplete { color: #d32f2f; font-weight: bold; }
        .status-btn {
            padding: 5px 10px;
            margin-left: 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            background-color: #13155c;
            color: white;
        }
        .status-btn:hover {
            background-color: #13155c;
        }

        .filters-container {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin: 10px;
            gap: 10px;
            width: calc(100% - 20px);
            flex-wrap: wrap;
        }

        .filter-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(
                to right, 
                rgba(44, 62, 80, 0.7), 
                rgba(55, 7, 77, 0.7), 
                rgba(44, 62, 80, 0.7)
            );
            padding: 8px 15px;
            border-radius: 25px;
            backdrop-filter: blur(10px);
            color: white;
            font-weight: 600;
        }

        .filter-pill label {
            color: white;
            font-weight: 600;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .select-wrapper {
            position: relative;
            display: inline-block;
            min-width: 140px;
        }

        .filter-pill select {
            width: 100%;
            padding: 6px 30px 6px 12px;
            border-radius: 15px;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.9);
            color: #2c3e50;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        .dropdown-arrow {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #2c3e50;
            font-size: 12px;
            pointer-events: none;
            transition: transform 0.3s ease;
        }

        .select-wrapper.open .dropdown-arrow {
            transform: translateY(-50%) rotate(180deg);
        }

        .filter-pill select:focus {
            outline: none;
            background-color: white;
            box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.4);
        }

        .filter-pill select:hover {
            background-color: white;
        }

        .download-btn-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #28a745, #218838);
            color: white;
            border: none;
            padding: 13px 25px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }

        .download-btn-pill:hover {
            background: linear-gradient(135deg, #218838, #1e7e34);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
            transform: translateY(-1px);
        }

        .download-btn-pill:active {
            transform: translateY(0);
        }

        .filter-icon {
            font-size: 14px;
        }
    </style>
</head>
<body style="background-image: url('img/bck.png'); background-size: cover;">
    <div class="header">
        <a href="#" class="logout-link">
            <i class="fa-solid fa-right-from-bracket" style="font-size: medium;"></i>
        </a>
        <p>FCW Work Permit Masterlist</p>
    </div>

    <?php include 'model/navigationBar.php'; ?>

    <div class="content-wrapper">
        <div class="filters-container">
            <!-- Month Filter -->
            <div class="filter-pill">
                <label for="monthFilter">
                    <i class="fa-solid fa-calendar-days filter-icon"></i> Month:
                </label>
                <div class="select-wrapper">
                    <select id="monthFilter" onchange="applyFilters()">
                        <option value="0">Default</option>
                        <option value="1" <?php echo (isset($_GET['month']) && $_GET['month'] == 1) ? 'selected' : ''; ?>>January</option>
                        <option value="2" <?php echo (isset($_GET['month']) && $_GET['month'] == 2) ? 'selected' : ''; ?>>February</option>
                        <option value="3" <?php echo (isset($_GET['month']) && $_GET['month'] == 3) ? 'selected' : ''; ?>>March</option>
                        <option value="4" <?php echo (isset($_GET['month']) && $_GET['month'] == 4) ? 'selected' : ''; ?>>April</option>
                        <option value="5" <?php echo (isset($_GET['month']) && $_GET['month'] == 5) ? 'selected' : ''; ?>>May</option>
                        <option value="6" <?php echo (isset($_GET['month']) && $_GET['month'] == 6) ? 'selected' : ''; ?>>June</option>
                        <option value="7" <?php echo (isset($_GET['month']) && $_GET['month'] == 7) ? 'selected' : ''; ?>>July</option>
                        <option value="8" <?php echo (isset($_GET['month']) && $_GET['month'] == 8) ? 'selected' : ''; ?>>August</option>
                        <option value="9" <?php echo (isset($_GET['month']) && $_GET['month'] == 9) ? 'selected' : ''; ?>>September</option>
                        <option value="10" <?php echo (isset($_GET['month']) && $_GET['month'] == 10) ? 'selected' : ''; ?>>October</option>
                        <option value="11" <?php echo (isset($_GET['month']) && $_GET['month'] == 11) ? 'selected' : ''; ?>>November</option>
                        <option value="12" <?php echo (isset($_GET['month']) && $_GET['month'] == 12) ? 'selected' : ''; ?>>December</option>
                    </select>
                    <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                </div>
            </div>

            <!-- Department Filter -->
            <div class="filter-pill">
                <label for="departmentFilter">
                    <i class="fa-solid fa-building filter-icon"></i> Department:
                </label>
                <div class="select-wrapper">
                    <select id="departmentFilter" onchange="applyFilters()">
                        <option value="all">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>" 
                                <?php echo (isset($_GET['department']) && $_GET['department'] == $dept) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                </div>
            </div>

            <!-- Status Filter -->
            <div class="filter-pill">
                <label for="statusFilter">
                    <i class="fa-solid fa-layer-group"></i> Category:
                </label>
                <div class="select-wrapper">
                    <select id="statusFilter" onchange="applyFilters()">
                        <option value="default" <?php echo (!isset($_GET['status']) || $_GET['status'] == 'default') ? 'selected' : ''; ?>>Default</option>
                        <option value="expired" <?php echo (isset($_GET['status']) && $_GET['status'] == 'expired') ? 'selected' : ''; ?>>Expired</option>
                        <option value="expiring_soon" <?php echo (isset($_GET['status']) && $_GET['status'] == 'expiring_soon') ? 'selected' : ''; ?>>Expiring Soon</option>
                        <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                    </select>
                    <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                </div>
            </div>

            <!-- Download Button -->
            <button class="download-btn-pill">
                <i class="fa-regular fa-file-excel"></i>
                Download Excel
            </button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Employee No</th>
                    <th>Permit Name</th>
                    <th>Department</th>
                    <th>Nationality</th>
                    <th>Date of Birth</th>
                    <th>Work Permit Number</th>
                    <th>Work Permit Expiry</th>
                    <th>Medical Checkup Status</th>
                    <th>SPIKPA Insurance</th>
                    <th>Status</th>
                    <th>Action</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $hasData = false;
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $hasData = true;

                // === Work Permit Expiry Status ===
                $expiryDate = $row['Work Permit Expiry (New)'];
                $permitStatus = 'Active';
                $permitStatusClass = 'status-active';

                if ($expiryDate instanceof DateTime) {
                    $today = new DateTime();
                    // Reset time components to midnight for accurate date-only comparison
                    $today->setTime(0, 0, 0);
                    $expiryDateCompare = clone $expiryDate;
                    $expiryDateCompare->setTime(0, 0, 0);
                    
                    $interval = $today->diff($expiryDateCompare);
                    
                    // Expired = expiry date is BEFORE today (not including today)
                    if ($expiryDateCompare < $today) {
                        $permitStatus = 'Expired';
                        $permitStatusClass = 'status-expired';
                    } 
                    // Expiring Soon = from today up to 90 days in the future
                    elseif ($interval->days <= 90 && $expiryDateCompare >= $today) {
                        $permitStatus = 'Expiring Soon';
                        $permitStatusClass = 'status-expiring';
                    }
                    else {
                        $permitStatus = 'Active';
                        $permitStatusClass = 'status-active';
                    }
                    $expiryDateFormatted = $expiryDate->format('d-m-Y');
                } else {
                    $expiryDateFormatted = $expiryDate ? $expiryDate : 'N/A';
                    $permitStatus = 'N/A';
                    $permitStatusClass = '';
                }

                // === Medical Status Logic ===
                $medicalDate = $row['MedicalDate'];
                $medicalStatus = 'Incomplete';
                $medicalCheckupDate = null;

                // Parse the medical date field
                if ($medicalDate instanceof DateTime) {
                    // It's a date - medical was completed on this date
                    $medicalCheckupDate = $medicalDate;
                    $medicalStatus = 'Complete';
                } else if (is_string($medicalDate)) {
                    if ($medicalDate === 'Complete') {
                        $medicalStatus = 'Complete';
                    } else if ($medicalDate === 'Incomplete') {
                        $medicalStatus = 'Incomplete';
                    } else {
                        // Try to parse as date string
                        try {
                            $medicalCheckupDate = new DateTime($medicalDate);
                            $medicalStatus = 'Complete';
                        } catch (Exception $e) {
                            $medicalStatus = 'Incomplete';
                        }
                    }
                }

                // Reset to Incomplete if medical checkup is older than 11 months (335 days)
                // This applies to ALL cases, not just when permit is expiring/expired
                if ($medicalStatus === 'Complete' && $medicalCheckupDate !== null && $expiryDate instanceof DateTime) {
                    $today = new DateTime();
                    $today->setTime(0, 0, 0);
                    
                    // Calculate how old the medical checkup is
                    $medicalAge = $today->diff($medicalCheckupDate);
                    
                    // Calculate time until permit expiry
                    $expiryDateCompare = clone $expiryDate;
                    $expiryDateCompare->setTime(0, 0, 0);
                    $daysUntilExpiry = $today->diff($expiryDateCompare);
                    
                    // SCENARIO 1: Medical is old (>11 months) AND permit is expiring/expired
                    // Need new medical for renewal
                    if ($medicalAge->days > 335 && ($permitStatus === 'Expired' || $permitStatus === 'Expiring Soon')) {
                        $medicalStatus = 'Incomplete';
                    }
                    
                    // SCENARIO 2: Medical is old (>11 months) AND permit was recently renewed
                    // This catches cases where permit is "Active" but medical is outdated
                    // If permit expiry is MORE THAN 11 months away, and medical is older than 11 months,
                    // it means the permit was likely renewed without updating medical
                    else if ($medicalAge->days > 335 && $daysUntilExpiry->days > 335 && $expiryDateCompare > $today) {
                        // Check if medical date is BEFORE the permit expiry minus 1 year
                        // This indicates the medical is from the previous permit period
                        $previousPermitDate = clone $expiryDateCompare;
                        $previousPermitDate->modify('-1 year');
                        
                        if ($medicalCheckupDate < $previousPermitDate) {
                            $medicalStatus = 'Incomplete';
                        }
                    }
                    
                    // SCENARIO 3: After renewal, if medical is older than permit's remaining validity
                    // Medical should be recent (within last 11 months) for active permits
                    else if ($medicalAge->days > 335) {
                        $medicalStatus = 'Incomplete';
                    }
                }

                $hasMedical = ($medicalStatus === 'Complete');
                $medicalClass = $hasMedical ? 'medical-complete' : 'medical-incomplete';

                    // === SPIKPA Expiry ===
                    $expiryDateIn = $row['SPIKPA Expiry '];
                    $expiryClassI = '';
                    if ($expiryDateIn instanceof DateTime) {
                        $today = new DateTime();
                        $today->setTime(0, 0, 0);
                        $expiryDateInCompare = clone $expiryDateIn;
                        $expiryDateInCompare->setTime(0, 0, 0);
                        
                        $interval = $today->diff($expiryDateInCompare);
                        
                        // FIXED: Use proper date comparison
                        if ($expiryDateInCompare < $today) {
                            $expiryClassI = 'expired';
                        } elseif ($interval->days <= 90 && $expiryDateInCompare >= $today) {
                            $expiryClassI = 'expiring-soon';
                        }
                        $expiryDateInFormatted = $expiryDateIn->format('d-m-Y');
                    } else {
                        $expiryDateInFormatted = $expiryDateIn ? $expiryDateIn : 'N/A';
                    }

                    // === Check if both requirements are complete ===
                    $hasInsurance = ($expiryDateIn && $expiryDateIn instanceof DateTime);
                    $isComplete = $hasMedical && $hasInsurance;

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['Employee#'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['Permit Name'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['Department'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['Nationality'] ?? 'N/A') . "</td>";
                    
                    // === Date of Birth ===
                    $birthdate = $row['Birthdate'];
                    if ($birthdate instanceof DateTime) {
                        $birthdateFormatted = $birthdate->format('d-m-Y');
                    } else {
                        $birthdateFormatted = $birthdate ? $birthdate : 'N/A';
                    }
                    echo "<td>" . htmlspecialchars($birthdateFormatted) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Work Permit Number'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($expiryDateFormatted) . "</td>";
                    
                    // === Medical Checkup Status with Update Button ===
                    echo "<td class='$medicalClass'>" . htmlspecialchars($medicalStatus);
                    // Only show button if status is Incomplete
                    if ($medicalStatus === 'Incomplete') {
                        echo "<button class='status-btn' onclick='openMedicalModal(\"" . htmlspecialchars($row['Employee#']) . "\", \"" . htmlspecialchars($medicalStatus) . "\")'>";
                        echo "<i class='fa-solid fa-pencil'></i>";
                        echo "</button>";
                    }
                    echo "</td>";

                    echo "<td class='$expiryClassI'>" . htmlspecialchars($expiryDateInFormatted) . "</td>";
                    
                    // === Work Permit Status (Expired/Expiring Soon/Active) ===
                    echo "<td class='$permitStatusClass'>" . $permitStatus . "</td>";
                    
                    echo "<td>";
                    if ($isComplete) {
                        echo "<button class='renew-btn' data-id='" . htmlspecialchars($row['Employee#']) . "'>Renew</button>";
                    } else {
                        echo "<button class='renew-btn' disabled title='Complete both medical checkup and insurance first'>Renew</button>";
                    }
                    echo "</td>";
                    echo "<td>";
                    echo "<textarea data-employee-no='" . htmlspecialchars($row['Employee#']) . "' class='editable-remarks' placeholder=''>";
                    echo htmlspecialchars($row['Remarks'] ?? '');
                    echo "</textarea>";
                    echo "</td>";
                    echo "</tr>";
                }

                if (!$hasData) {
                    echo "<tr><td colspan='12' class='no-data'>No employee records found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Pagination Controls -->
        <div class="pagination">
            <?php 
            $queryParams = [];
            if (isset($_GET['month']) && $_GET['month'] != 0) $queryParams[] = 'month=' . $_GET['month'];
            if (isset($_GET['department']) && $_GET['department'] != 'all') $queryParams[] = 'department=' . urlencode($_GET['department']);
            if (isset($_GET['status']) && $_GET['status'] != 'default') $queryParams[] = 'status=' . urlencode($_GET['status']);
            $queryString = !empty($queryParams) ? '?' . implode('&', $queryParams) . '&' : '?';
            ?>
            
            <?php if ($page > 1): ?>
                <a href="<?php echo $queryString; ?>page=1">First</a>
                <a href="<?php echo $queryString; ?>page=<?php echo $page - 1; ?>"><i class='fa-solid fa-chevron-left'></i></a>
            <?php else: ?>
                <span class="disabled">First</span>
                <span class="disabled"><i class='fa-solid fa-chevron-left'></i></span>
            <?php endif; ?>

            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++):
                if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="<?php echo $queryString; ?>page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif;
            endfor;
            ?>

            <?php if ($page < $total_pages): ?>
                <a href="<?php echo $queryString; ?>page=<?php echo $page + 1; ?>"><i class='fa-solid fa-chevron-right'></i></a>
                <a href="<?php echo $queryString; ?>page=<?php echo $total_pages; ?>">Last</a>
            <?php else: ?>
                <span class="disabled"><i class='fa-solid fa-chevron-right'></i></span>
                <span class="disabled">Last</span>
            <?php endif; ?>
        </div>
        <div class="page-info">
            Showing <?php echo ($total_records > 0) ? $offset + 1 : 0; ?> - <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> employees
        </div>
    </div>

    <!-- Medical Checkup Status Modal -->
    <div id="medicalModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeMedicalModal()">&times;</span>
            <div class="modal-header">
                <i class="fa-solid fa-notes-medical"></i> Update Medical Checkup Status
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Employee Number:</label>
                    <input type="text" id="modalEmployeeNo" readonly style="background-color: #f0f0f0;">
                </div>
                <div class="form-group">
                    <label>Medical Checkup Status:</label>
                    <select id="modalMedicalStatus" required>
                        <option value="Incomplete">Incomplete</option>
                        <option value="Complete">Complete</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-cancel" onclick="closeMedicalModal()">Cancel</button>
                <button class="modal-btn modal-btn-save" onclick="saveMedicalStatus()">Save</button>
            </div>
        </div>
    </div>

    <?php include 'model/footer.php'; ?>

    <script src="js/employeeInfo.js"></script>
    <script src="js/remarks.js"></script>
    <script src="js/medicalStatus.js"></script>
    <script>
    // ===== LOGOUT CONFIRMATION =====
    document.querySelector('.logout-link').addEventListener('click', (e) => {
        e.preventDefault();
        if (confirm("Are you sure you want to log out?")) {
            window.location.href = "logout.php";
        }
    });

    // ===== FILTER FUNCTION =====
    function applyFilters() {
        const month = document.getElementById('monthFilter').value;
        const department = document.getElementById('departmentFilter').value;
        const status = document.getElementById('statusFilter').value;
        const page = 1; // Reset to first page when filtering
        
        let url = '?page=' + page;
        if (month != '0') {
            url += '&month=' + month;
        }
        if (department != 'all') {
            url += '&department=' + encodeURIComponent(department);
        }
        if (status != 'default') {
            url += '&status=' + status;
        }
        
        window.location.href = url;
    }

    // ===== DROPDOWN ARROW ANIMATION =====
    document.addEventListener('DOMContentLoaded', function() {
        const selects = document.querySelectorAll('.filter-pill select');
        
        selects.forEach(select => {
            const wrapper = select.closest('.select-wrapper');
            
            select.addEventListener('focus', () => wrapper?.classList.add('open'));
            select.addEventListener('blur', () => wrapper?.classList.remove('open'));
            select.addEventListener('change', () => {
                setTimeout(() => wrapper?.classList.remove('open'), 300);
            });
        });

        // ===== EXPORT TO EXCEL FUNCTIONALITY (DIRECT DOWNLOAD) =====
        const downloadBtn = document.querySelector('.download-btn-pill');
        if (downloadBtn) {
            downloadBtn.addEventListener('click', function() {
                // Get current filter values
                const month = document.getElementById('monthFilter').value;
                const department = document.getElementById('departmentFilter').value;
                const status = document.getElementById('statusFilter').value;
                
                // Build export URL with filters
                let exportUrl = 'exportPermitToExcel.php';
                let hasParams = false;
                
                if (month != '0' || department != 'all' || status != 'default') {
                    exportUrl += '?';
                    if (month != '0') {
                        exportUrl += 'month=' + month;
                        hasParams = true;
                    }
                    if (department != 'all') {
                        if (hasParams) exportUrl += '&';
                        exportUrl += 'department=' + encodeURIComponent(department);
                        hasParams = true;
                    }
                    if (status != 'default') {
                        if (hasParams) exportUrl += '&';
                        exportUrl += 'status=' + status;
                    }
                }
                
                // Show loading state
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
                this.disabled = true;
                
                // Direct download - most reliable and secure
                window.location.href = exportUrl;

                // Reset button after a delay
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }, 1500);
            });
        }
    });
        
    // ===== RENEW BUTTON FUNCTION =====
    document.querySelectorAll('.renew-btn:not([disabled])').forEach(button => {
        button.addEventListener('click', () => {
            const id = button.getAttribute('data-id');
            if (confirm("Renew this permit for 1 more year?")) {
                fetch('renewPermit.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + encodeURIComponent(id)
                })
                .then(response => response.text())
                .then(result => {
                    if (result.trim() === "success") {
                        alert("Permit renewed successfully!");
                        location.reload();
                    } else {
                        alert("Failed to renew permit.");
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("An error occurred while renewing.");
                });
            }
        });
    });
    </script>
</body>
</html>