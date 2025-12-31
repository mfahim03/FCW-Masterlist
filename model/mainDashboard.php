<?php?>
<!-- MAIN DASHBOARD CARDS -->
<div id="main-dashboard" class="main-dashboard active">
    <div class="dashboard-cards-grid">
        <!-- Contract Status Card -->
        <div class="dashboard-nav-card contract-card" onclick="navigateToSection('contract')">
            <!-- style="background-image: url('img/contract-bg.png'); 
                background-size: contain; 
                background-position: center; 
                background-repeat: no-repeat;" -->
            <div class="card-icon">
                <i class="fa-solid fa-file-contract"></i>
            </div>
            <div class="card-content">
                <h3>Contract Status Overview</h3>
                <div class="card-stats">
                    <div class="card-stat completed">
                        <span class="stat-value"><?php echo $contract_summary['ExtendCount']; ?></span>
                        <span class="stat-label">Extend</span>
                    </div>
                    <div class="card-stat expired">
                        <span class="stat-value"><?php echo $contract_summary['NotExtendCount']; ?></span>
                        <span class="stat-label">Not Extend</span>
                    </div>
                </div>
            </div>
            <div class="card-arrow">
                <i class="fa-solid fa-chevron-right"></i>
            </div>
        </div>

        <!-- Work Permit Card -->
        <div class="dashboard-nav-card work-permit-card" onclick="navigateToSection('work-permit')">
            <div class="card-icon">
                <i class="fa-solid fa-id-card"></i>
            </div>
            <div class="card-content">
                <h3>Work Permit Overview</h3>
                <div class="card-stats">
                    <div class="card-stat expired">
                        <span class="stat-value"><?php echo $permit_summary['ExpiredCount']; ?></span>
                        <span class="stat-label">Expired</span>
                    </div>
                    <div class="card-stat expiring">
                        <span class="stat-value"><?php echo $permit_summary['ExpiringSoonCount']; ?></span>
                        <span class="stat-label">Expiring Soon</span>
                    </div>
                    <div class="card-stat active">
                        <span class="stat-value"><?php echo $permit_summary['ActiveCount']; ?></span>
                        <span class="stat-label">Active</span>
                    </div>
                </div>
            </div>
            <div class="card-arrow">
                <i class="fa-solid fa-chevron-right"></i>
            </div>
        </div>

        <!-- Department Card -->
        <div class="dashboard-nav-card department-card" onclick="navigateToSection('department')">
            <div class="card-icon">
                <i class="fa-solid fa-building"></i>
            </div>
            <div class="card-content">
                <h3>Employees Distribution</h3>
                <div class="card-stats">
                    <div class="card-stat info">
                        <span class="stat-value"><?php echo count($departmentLabels); ?></span>
                        <span class="stat-label">Total Departments</span>
                    </div>
                    <div class="card-stat info">
                        <span class="stat-value"><?php echo array_sum($departmentCounts); ?></span>
                        <span class="stat-label">Total Employees</span>
                    </div>
                </div>
            </div>
            <div class="card-arrow">
                <i class="fa-solid fa-chevron-right"></i>
            </div>
        </div>

        <!-- Passport Card -->
        <div class="dashboard-nav-card passport-card" onclick="navigateToSection('passport')">
            <div class="card-icon">
                <i class="fa-solid fa-passport"></i>
            </div>
            <div class="card-content">
                <h3>Passport Overview</h3>
                <div class="card-stats">
                    <div class="card-stat expired">
                        <span class="stat-value"><?php echo $passport_summary['ExpiredCount']; ?></span>
                        <span class="stat-label">Expired</span>
                    </div>
                    <div class="card-stat expiring">
                        <span class="stat-value"><?php echo $passport_summary['ExpiringSoonCount']; ?></span>
                        <span class="stat-label">Expiring Soon</span>
                    </div>
                    <div class="card-stat active">
                        <span class="stat-value"><?php echo $passport_summary['ActiveCount']; ?></span>
                        <span class="stat-label">Active</span>
                    </div>
                </div>
            </div>
            <div class="card-arrow">
                <i class="fa-solid fa-chevron-right"></i>
            </div>
        </div>
    </div>
</div>