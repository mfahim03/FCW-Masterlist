<?php
include 'db.php';
include 'config/fetchContract.php';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    .filters-container {
        display: flex;
        justify-content: flex-start;
        align-items: center;
        margin: 10px;
        gap: 10px;
        width: calc(100% - 20px);
        flex-wrap: wrap;
    }

    .month-filter-pill {
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

    .month-filter-pill label {
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

    .month-filter-pill select {
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

    .month-filter-pill select:focus {
        outline: none;
        background-color: white;
        box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.4);
    }

    .month-filter-pill select:hover {
        background-color: white;
    }

    .pill-toggle-container {
        display: flex;
        gap: 5px;
        background: linear-gradient(
            to right, 
            rgba(44, 62, 80, 0.7), 
            rgba(55, 7, 77, 0.7), 
            rgba(44, 62, 80, 0.7)
        );
        padding: 5px;
        border-radius: 25px;
        backdrop-filter: blur(10px);
    }

    .pill-btn {
        padding: 8px 20px;
        background: transparent;
        color: white;
        border: none;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pill-btn:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .pill-btn.active {
        background: linear-gradient(135deg, #28a745, #218838);
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.4);
    }

    .filter-icon {
        font-size: 14px;
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
</style>
</head>
<body style="background-image: url('img/bck.png'); background-size: cover;">
    <div class="header">
        <a href="#" class="logout-link">
            <i class="fa-solid fa-right-from-bracket" style="font-size: medium;"></i>
        </a>
        <p>FCW Contract Masterlist</p>
    </div>

    <?php include 'model/userNavBar.php'; ?>

    <div class="content-wrapper">
        <div class="filters-container">
            <div class="month-filter-pill">
                <label for="monthFilter">
                    <i class="fa-solid fa-filter filter-icon"></i> Filter by:
                </label>
                <div class="select-wrapper">
                    <select id="monthFilter" onchange="filterByMonth()">
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

            <!-- Contract Type Pills -->
            <div class="pill-toggle-container">
                <a href="?contract=EXTEND&page=1<?php echo isset($_GET['month']) ? '&month=' . $_GET['month'] : ''; ?>" class="pill-btn <?php echo ($contractFilter == 'EXTEND') ? 'active' : ''; ?>" style="text-decoration: none;">
                    <i class="fa-solid fa-file-contract"></i> Extend (<?php echo $total_extend_records; ?>)
                </a>
                <a href="?contract=NOT EXTEND&page=1<?php echo isset($_GET['month']) ? '&month=' . $_GET['month'] : ''; ?>" class="pill-btn <?php echo ($contractFilter == 'NOT EXTEND') ? 'active' : ''; ?>" style="text-decoration: none;">
                    <i class="fa-solid fa-file-circle-xmark"></i> Not Extend (<?php echo $total_not_extend_records; ?>)
                </a>
            </div>
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
                </tr>
            </thead>
            <tbody>
                <?php
                $hasData = false;
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $hasData = true;

                    $birthdate = $row['Birthdate'];
                    if ($birthdate instanceof DateTime) {
                        $birthdateFormatted = $birthdate->format('d-m-Y');
                    } else {
                        $birthdateFormatted = $birthdate ? $birthdate : 'N/A';
                    }

                    $expiryDate = $row['Work Permit Expiry (New)'];
                    $expiryClass = '';
                    $status = 'Active';
                    
                    if ($expiryDate instanceof DateTime) {
                        $today = new DateTime();
                        $interval = $today->diff($expiryDate);
                        
                        if ($expiryDate < $today) {
                            $expiryClass = 'expired';
                            $status = 'Expired';
                        } elseif ($interval->days <= 90) {
                            $expiryClass = 'expiring-soon';
                            $status = 'Expiring Soon';
                        }
                        $expiryDateFormatted = $expiryDate->format('d-m-Y');
                    } else {
                        $expiryDateFormatted = $expiryDate ? $expiryDate : 'N/A';
                    }

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['Employee#'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['Permit Name'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['Department'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['Nationality'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($birthdateFormatted) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Work Permit Number'] ?? 'N/A') . "</td>";
                    echo "<td class='$expiryClass'>" . htmlspecialchars($expiryDateFormatted) . "</td>";
                    echo "</tr>";
                }

                if (!$hasData) {
                    $message = $contractFilter === 'EXTEND' ? 'No contracts to extend.' : 'No contracts marked as not extend.';
                    echo "<tr><td colspan='8' class='no-data'>$message</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php 
            $queryParams = [];
            if ($contractFilter) $queryParams[] = 'contract=' . urlencode($contractFilter);
            if (isset($_GET['month']) && $_GET['month'] != 0) $queryParams[] = 'month=' . $_GET['month'];
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

    <script src="js/employeeInfo.js"></script>
    <script src="js/contract.js"></script>
    <script>
    // ===== LOGOUT CONFIRMATION =====
    document.querySelector('.logout-link').addEventListener('click', (e) => {
        e.preventDefault();
        if (confirm("Are you sure you want to log out?")) {
            window.location.href = "logout.php";
        }
    });

    // ===== FILTER FUNCTION =====
    function filterByMonth() {
        const month = document.getElementById('monthFilter').value;
        const contract = '<?php echo $contractFilter; ?>';
        const page = 1; // Reset to first page when filtering
        
        let url = '?';
        if (contract) {
            url += 'contract=' + encodeURIComponent(contract) + '&';
        }
        if (month != '0') {
            url += 'month=' + month + '&';
        }
        url += 'page=' + page;
        
        window.location.href = url;
    }

    // ===== DROPDOWN ARROW ANIMATION =====
    document.addEventListener('DOMContentLoaded', function() {
        const selects = document.querySelectorAll('.month-filter-pill select');
        
        selects.forEach(select => {
            const wrapper = select.closest('.select-wrapper');
            
            select.addEventListener('focus', () => wrapper?.classList.add('open'));
            select.addEventListener('blur', () => wrapper?.classList.remove('open'));
            select.addEventListener('change', () => {
                setTimeout(() => wrapper?.classList.remove('open'), 300);
            });
        });
    });
    </script>
</body>
</html>