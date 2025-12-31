<!-- UPDATED : permitDetailSQL.php and permitDetail.js -->
<?php
include 'db.php';
include 'config/dashboardSQL.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: indexView.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Foreign Contract Worker</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="icon" type="image/png" href="img/fcw2.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <style>
        .clickable {
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .clickable:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .detail-charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        /* Full width chart for monthly breakdown */
        .detail-full-width-chart {
            width: 100%;
            margin-top: 10px;
        }
    </style>
</head>
<body style="background-image: url('img/bck.png'); background-size: cover;">
    <div class="header">
        <a href="#" class="logout-link">
            <i class="fa-solid fa-right-from-bracket" style="font-size: medium;"></i>
        </a>
        <p>Foreign Contract Worker Dashboard</p>
    </div>

    <?php include 'model/navigationBar.php'; ?>

<div class="content-wrapper">
    <div class="dashboard-container">

        <?php include 'model/mainDashboard.php'; ?>

        <!-- WORK PERMIT SECTION -->
        <div id="work-permit-section" class="dashboard-section">
            <div id="permit-main-header">
                <button class="back-btn" onclick="backToMain()">
                    <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
                </button>
                
                <div class="section-title">
                    <i class="fa-solid fa-id-card"></i>
                    Work Permit Overview
                </div>
            </div>

            <div id="permit-main-content">
                <div id="permit-stats-cards" class="stats-grid">
                    <div class="stat-card expired clickable" onclick="showPermitDetail('expired')">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <div class="stat-number"><?php echo number_format($permit_summary['ExpiredCount']); ?></div>
                        <div class="stat-label">Expired Permits</div>
                        <small style="display: block; margin-top: 5px; color: white;">Click for details</small>
                    </div>
                    <div class="stat-card expiring clickable" onclick="showPermitDetail('expiring')">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <div class="stat-number"><?php echo number_format($permit_summary['ExpiringSoonCount']); ?></div>
                        <div class="stat-label">Expiring Soon (90 days)</div>
                        <small style="display: block; margin-top: 5px; color: white;">Click for details</small>
                    </div>
                    <div class="stat-card completed clickable" onclick="showPermitDetail('active')">
                        <i class="fa-solid fa-square-check"></i>
                        <div class="stat-number"><?php echo number_format($permit_summary['ActiveCount']); ?></div>
                        <div class="stat-label">Active</div>
                        <small style="display: block; margin-top: 5px; color: white;">Click for details</small>
                    </div>
                </div>

                <div id="permit-overview-charts">
                    <div class="chart-container">
                        <div class="chart-title">
                            <i class="fa-solid fa-calendar-days"></i> Work Permit Expiry by Month & Year
                        </div>
                        <canvas id="permitExpiryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- DETAIL VIEW -->
            <div id="permit-detail-view" style="display: none;">
                <div>
                    <button class="back-btn" onclick="hidePermitDetail()">
                        <i class="fa-solid fa-arrow-left"></i> Back to Overview
                    </button>
                </div>
                <div class="section-title">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span id="permit-detail-title">Permit Detail View</span>
                </div>

                <div class="detail-charts-container">
                    <div class="chart-container">
                        <div class="chart-title">
                            <i class="fa-solid fa-building"></i> Employees by Department
                        </div>
                        <canvas id="permitDepartmentChart"></canvas>
                    </div>

                    <div class="chart-container">
                        <div class="chart-title">
                            <i class="fa-solid fa-globe"></i> Employees by Nationality
                        </div>
                        <canvas id="permitNationalityChart"></canvas>
                    </div>
                </div>

                <!-- Monthly Breakdown Chart -->
                <div class="detail-full-width-chart">
                    <div class="chart-container">
                        <div class="chart-title">
                            <i class="fa-solid fa-calendar-alt"></i> Monthly Breakdown
                        </div>
                        <canvas id="permitMonthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- DEPARTMENT SECTION -->
        <div id="department-section" class="dashboard-section">
            <button class="back-btn" onclick="backToMain()">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </button>
            
            <div class="section-title">
                <i class="fa-solid fa-building"></i>
                Employees Distribution
            </div>

            <div class="chart-container">
                <div class="chart-title">
                    <i class="fa-solid fa-chart-column"></i> Employees by Department
                </div>
                <canvas id="departmentChart"></canvas>
            </div>
        </div>

        <!-- CONTRACT SECTION -->
        <div id="contract-section" class="dashboard-section">
            <div id="contract-main-header">
                <button class="back-btn" onclick="backToMain()">
                    <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
                </button>
                
                <div class="section-title">
                    <i class="fa-solid fa-file-contract"></i>
                    Contract Status Overview
                </div>
            </div>

            <div id="contract-main-content">
                <div class="stats-grid">
                    <div class="stat-card completed clickable" onclick="showContractDetail('extend')">
                        <i class="fa-solid fa-circle-check"></i>
                        <div class="stat-number"><?php echo number_format($contract_summary['ExtendCount']); ?></div>
                        <div class="stat-label">Contract Extend</div>
                        <small style="display: block; margin-top: 5px; color: white;">Click for details</small>
                    </div>
                    <div class="stat-card expired clickable" onclick="showContractDetail('not_extend')">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <div class="stat-number"><?php echo number_format($contract_summary['NotExtendCount']); ?></div>
                        <div class="stat-label">Contract Not Extend</div>
                        <small style="display: block; margin-top: 5px; color: white;">Click for details</small>
                    </div>
                </div>

                <div id="contract-overview-charts">
                    <div class="chart-container">
                        <div class="chart-title">
                            <i class="fa-solid fa-chart-line"></i> Contract Status by Permit Expiry Date
                        </div>
                        <canvas id="contractExpiryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- DETAIL VIEW -->
            <div id="contract-detail-view" style="display: none;">
                <div>
                    <button class="back-btn" onclick="hideContractDetail()">
                        <i class="fa-solid fa-arrow-left"></i> Back to Overview
                    </button>
                </div>
                <div class="section-title">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span id="contract-detail-title">Contract Detail View</span>
                </div>

                <div class="detail-charts-container">
                    <div class="chart-container">
                        <div class="chart-title">
                            <i class="fa-solid fa-building"></i> Employees by Department
                        </div>
                        <canvas id="contractDepartmentChart"></canvas>
                    </div>

                    <div class="chart-container">
                        <div class="chart-title">
                            <i class="fa-solid fa-globe"></i> Employees by Nationality
                        </div>
                        <canvas id="contractNationalityChart"></canvas>
                    </div>
                </div>

                <!-- Monthly Breakdown Chart -->
                <div class="detail-full-width-chart">
                    <div class="chart-container">
                        <div class="chart-title">
                            <i class="fa-solid fa-calendar-alt"></i> Monthly Breakdown by Permit Expiry
                        </div>
                        <canvas id="contractMonthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- PASSPORT SECTION -->
        <div id="passport-section" class="dashboard-section">
            <div id="passport-main-header">
                <button class="back-btn" onclick="backToMain()">
                    <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
                </button>
                
                <div class="section-title">
                    <i class="fa-solid fa-passport"></i>
                    Passport Overview
                </div>
            </div>

            <div id="passport-main-content">
                <div class="stats-grid">
                    <div class="stat-card expired clickable" onclick="showPassportDetail('expired')">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <div class="stat-number"><?php echo number_format($passport_summary['ExpiredCount']); ?></div>
                        <div class="stat-label">Expired Passports</div>
                        <small style="display: block; margin-top: 5px; color: white;">Click for details</small>
                    </div>
                    <div class="stat-card expiring clickable" onclick="showPassportDetail('expiring')">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <div class="stat-number"><?php echo number_format($passport_summary['ExpiringSoonCount']); ?></div>
                        <div class="stat-label">Expiring Soon (365 days)</div>
                        <small style="display: block; margin-top: 5px; color: white;">Click for details</small>
                    </div>
                    <div class="stat-card completed clickable" onclick="showPassportDetail('active')">
                        <i class="fa-solid fa-square-check"></i>
                        <div class="stat-number"><?php echo number_format($passport_summary['ActiveCount']); ?></div>
                        <div class="stat-label">Active</div>
                        <small style="display: block; margin-top: 5px; color: white;">Click for details</small>
                    </div>
                </div>

                <div id="passport-overview-charts">
                    <div class="chart-container">
                        <div class="chart-title">
                            <i class="fa-solid fa-calendar-days"></i> Passport Expiry by Month & Year
                        </div>
                        <canvas id="passportExpiryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- DETAIL VIEW -->
            <div id="passport-detail-view" style="display: none;">
                <div>
                    <button class="back-btn" onclick="hidePassportDetail()">
                        <i class="fa-solid fa-arrow-left"></i> Back to Overview
                    </button>
                </div>
                <div class="section-title">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span id="passport-detail-title">Passport Detail View</span>
                </div>

                <div class="detail-charts-container">
                    <div class="chart-container">
                        <div class="chart-title">
                            <i class="fa-solid fa-building"></i> Employees by Department
                        </div>
                        <canvas id="passportDepartmentChart"></canvas>
                    </div>

                    <div class="chart-container">
                        <div class="chart-title">
                            <i class="fa-solid fa-globe"></i> Employees by Nationality
                        </div>
                        <canvas id="passportNationalityChart"></canvas>
                    </div>
                </div>

                <!-- Monthly Breakdown Chart -->
                <div class="detail-full-width-chart">
                    <div class="chart-container">
                        <div class="chart-title">
                            <i class="fa-solid fa-calendar-alt"></i> Monthly Breakdown by Passport Expiry
                        </div>
                        <canvas id="passportMonthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

    <?php include 'model/footer.php'; ?>
    <script>
        const permitLabels = <?php echo json_encode($permitLabels); ?>;
        const permitCounts = <?php echo json_encode($permitCounts); ?>;
        const passportLabels = <?php echo json_encode($passportLabels); ?>;
        const passportCounts = <?php echo json_encode($passportCounts); ?>;
        const departmentLabels = <?php echo json_encode($departmentLabels); ?>;
        const departmentCounts = <?php echo json_encode($departmentCounts); ?>;
        const contractLabels = <?php echo json_encode($contractLabels); ?>;
        const contractExtendCounts = <?php echo json_encode($contractExtendCounts); ?>;
        const contractNotExtendCounts = <?php echo json_encode($contractNotExtendCounts); ?>;

        function navigateToSection(sectionName) {
            const mainDashboard = document.getElementById('main-dashboard');
            const targetSection = document.getElementById(`${sectionName}-section`);
            const allSections = document.querySelectorAll('.dashboard-section');
            
            mainDashboard.classList.remove('active');
            allSections.forEach(section => {
                section.classList.remove('active');
            });
            
            targetSection.classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function backToMain() {
            hidePermitDetail();
            hidePassportDetail();
            hideContractDetail();
            
            const mainDashboard = document.getElementById('main-dashboard');
            const allSections = document.querySelectorAll('.dashboard-section');
            
            allSections.forEach(section => {
                section.classList.remove('active');
            });
            
            mainDashboard.classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            const logoutLink = document.querySelector('.logout-link');
            if (logoutLink) {
                logoutLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (confirm("Are you sure you want to log out?")) {
                        window.location.href = "logout.php";
                    }
                });
            }
        });
    </script>
    <script src="js/employeeInfo.js"></script>
    <script src="js/dashboard.js"></script>
    <script src="js/details/permitDetail.js"></script>
    <script src="js/details/passportDetail.js"></script>
    <script src="js/details/contractDetail.js"></script>
</body>
</html>