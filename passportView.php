<?php
include 'db.php';
include 'config/fetchPassport.php';
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
        .status-completed {
            color: #218838;
            font-weight: bold;
        }
        .status-expired {
            color: #dc3545;
            font-weight: bold;
        }
        .status-expiring-soon {
            color: #ffc107;
            font-weight: bold;
        }
        .status-active {
            color: #218838;
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
        <a href="#" class="logout-link">
            <i class="fa-solid fa-right-from-bracket" style="font-size: medium;"></i>
        </a>
        <p>FCW Passport Masterlist</p>
    </div>

    <?php include 'model/userNavBar.php'; ?>

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

            <!-- Nationality Filter -->
            <div class="filter-pill">
                <label for="nationalityFilter">
                    <i class="fa-solid fa-flag filter-icon"></i> Nationality:
                </label>
                <div class="select-wrapper">
                    <select id="nationalityFilter" onchange="applyFilters()">
                        <option value="all">All Nationalities</option>
                        <?php foreach ($nationalities as $nat): ?>
                            <option value="<?php echo htmlspecialchars($nat); ?>" 
                                <?php echo (isset($_GET['nationality']) && $_GET['nationality'] == $nat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($nat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Employee No</th>
                    <th>Permit Name</th>
                    <th>Department</th>
                    <th>Nationality</th>
                    <th>Old Passport</th>
                    <th>New Passport</th>
                    <th>Passport Expiry Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $hasData = false;
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $hasData = true;

                    // === Passport Expiry ===
                    $expiryDate = $row['Passport Expiry Date'];
                    $expiryClass = '';
                    $status = '';
                    $statusClass = '';
                    
                    if ($expiryDate instanceof DateTime) {
                        $today = new DateTime();
                        $interval = $today->diff($expiryDate);
                        
                        if ($expiryDate < $today) {
                            $expiryClass = 'expired';
                            $status = 'Expired';
                            $statusClass = 'status-expired';
                        } elseif ($interval->days <= 365) {
                            $expiryClass = 'expiring-soon';
                            $status = 'Expiring Soon';
                            $statusClass = 'status-expiring-soon';
                        } else {
                            $status = 'Active';
                            $statusClass = 'status-active';
                        }
                        $expiryDateFormatted = $expiryDate->format('d-m-Y');
                    } else {
                        $expiryDateFormatted = $expiryDate ? $expiryDate : 'N/A';
                        $status = 'N/A';
                    }

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['Employee#'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['Permit Name'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['Department'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['Nationality'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['Old Passport'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['New Passport'] ?? 'N/A') . "</td>";
                    echo "<td class='$expiryClass'>" . htmlspecialchars($expiryDateFormatted) . "</td>";
                    echo "<td class='$statusClass'>" . htmlspecialchars($status) . "</td>";
                    echo "</tr>";
                }

                if (!$hasData) {
                    echo "<tr><td colspan='9' class='no-data'>No employee records found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Pagination Controls -->
        <div class="pagination">
            <?php 
            $queryParams = [];
            if (isset($_GET['month']) && $_GET['month'] != 0) $queryParams[] = 'month=' . $_GET['month'];
            if (isset($_GET['nationality']) && $_GET['nationality'] != 'all') $queryParams[] = 'nationality=' . urlencode($_GET['nationality']);
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
        const nationality = document.getElementById('nationalityFilter').value;
        const page = 1; // Reset to first page when filtering
        
        let url = '?page=' + page;
        if (month != '0') {
            url += '&month=' + month;
        }
        if (nationality != 'all') {
            url += '&nationality=' + encodeURIComponent(nationality);
        }
        
        window.location.href = url;
    }
    </script>
</body>
</html>