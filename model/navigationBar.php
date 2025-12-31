<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Navigation Menu -->
<div class="nav-menu">
    <a href="index.php" class="nav-btn <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-chart-simple"></i>
        Dashboard
    </a>
    <a href="contract.php" class="nav-btn <?php echo ($current_page == 'contract.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-folder-open"></i>
        Contract
    </a>
    <a href="permit.php" class="nav-btn <?php echo ($current_page == 'permit.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-id-card"></i>
        Work Permit Information
    </a>
    <a href="passport.php" class="nav-btn <?php echo ($current_page == 'passport.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-passport"></i>
        Passport Information
    </a>
    <a href="employeeInfo.php" class="nav-btn <?php echo ($current_page == 'employeeInfo.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-user-tie"></i>
        Employee Information
    </a>
    <!--<a href="runaway.php" class="nav-btn <?php echo ($current_page == 'runaway.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-running"></i>
        EOC & Runaway
    </a> -->
    <button class="add-employee-btn" onclick="openAddEmployeeForm()">
            <i class="fa-solid fa-user-plus"></i>
    </button>
</div>