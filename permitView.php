<?php
include 'db.php';
include 'config/fetchPermit.php';
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Foreign Contract Worker</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/login1.css">
    <link rel="icon" type="image/png" href="img/logo1.png">
    <link rel="stylesheet" href="css/modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .medical-complete { color: #218838; font-weight: bold;}
        .medical-incomplete { color: #d32f2f; font-weight: bold; }

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

        .filter-icon {
            font-size: 14px;
        }
    </style>
</head>
<body style="background-image: url('img/bck.png'); background-size: cover;">
    <div class="header">
        <p>FCW Work Permit Masterlist</p> <!-- Or your page-specific title -->
        <a href="#" class="login-btn" id="openLoginModal">
            <i class="fa-solid fa-user-lock"></i>
            Admin Login
        </a>
    </div>

    <?php include 'model/navigationBar.php'; ?>
    <?php include 'model/loginModal.php'; ?>

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
                        $today->setTime(0, 0, 0);
                        $expiryDateCompare = clone $expiryDate;
                        $expiryDateCompare->setTime(0, 0, 0);
                        
                        $interval = $today->diff($expiryDateCompare);
                        
                        if ($expiryDateCompare < $today) {
                            $permitStatus = 'Expired';
                            $permitStatusClass = 'status-expired';
                        } 
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

                    if ($medicalDate instanceof DateTime) {
                        $medicalCheckupDate = $medicalDate;
                        $medicalStatus = 'Complete';
                    } else if (is_string($medicalDate)) {
                        if ($medicalDate === 'Complete') {
                            $medicalStatus = 'Complete';
                        } else if ($medicalDate === 'Incomplete') {
                            $medicalStatus = 'Incomplete';
                        } else {
                            try {
                                $medicalCheckupDate = new DateTime($medicalDate);
                                $medicalStatus = 'Complete';
                            } catch (Exception $e) {
                                $medicalStatus = 'Incomplete';
                            }
                        }
                    }

                    if ($medicalStatus === 'Complete' && $medicalCheckupDate !== null) {
                        $today = new DateTime();
                        $medicalAge = $today->diff($medicalCheckupDate);
                        
                        if ($medicalAge->days > 335 && ($permitStatus === 'Expired' || $permitStatus === 'Expiring Soon')) {
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
                        
                        if ($expiryDateInCompare < $today) {
                            $expiryClassI = 'expired';
                        } elseif ($interval->days <= 90 && $expiryDateInCompare >= $today) {
                            $expiryClassI = 'expiring-soon';
                        }
                        $expiryDateInFormatted = $expiryDateIn->format('d-m-Y');
                    } else {
                        $expiryDateInFormatted = $expiryDateIn ? $expiryDateIn : 'N/A';
                    }

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['Employee#'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['Permit Name'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['Department'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['Nationality'] ?? 'N/A') . "</td>";
                    
                    $birthdate = $row['Birthdate'];
                    if ($birthdate instanceof DateTime) {
                        $birthdateFormatted = $birthdate->format('d-m-Y');
                    } else {
                        $birthdateFormatted = $birthdate ? $birthdate : 'N/A';
                    }
                    echo "<td>" . htmlspecialchars($birthdateFormatted) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Work Permit Number'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($expiryDateFormatted) . "</td>";
                    echo "<td class='$medicalClass'>" . htmlspecialchars($medicalStatus) . "</td>";
                    echo "<td class='$expiryClassI'>" . htmlspecialchars($expiryDateInFormatted) . "</td>";
                    echo "<td class='$permitStatusClass'>" . $permitStatus . "</td>";
                    
                    echo "<td class='remarks-cell'>";
                    if (!empty($row['Remarks'])) {
                        $remarks = nl2br(htmlspecialchars($row['Remarks']));
                        echo "<div class='readonly-remarks'>$remarks</div>";
                    } else {
                        echo "<span class='no-remarks'>-</span>";
                    }
                    echo "</td>";
                    echo "</tr>";
                }

                if (!$hasData) {
                    echo "<tr><td colspan='11' class='no-data'>No employee records found.</td></tr>";
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

    <?php include 'model/footer.php'; ?>

    <script src="js/login.js"></script>
    <script>
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
                let exportUrl = 'excel/exportPermitToExcel.php';
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
    </script>
</body>
</html>