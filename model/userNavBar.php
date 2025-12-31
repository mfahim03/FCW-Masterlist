<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Navigation Menu -->
<div class="nav-menu">
    <a href="indexView.php" class="nav-btn <?php echo ($current_page == 'indexView.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-chart-simple"></i>
        Dashboard
    </a>
    <a href="contractView.php" class="nav-btn <?php echo ($current_page == 'contractView.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-folder-open"></i>
        Contract
    </a>
    <a href="permitView.php" class="nav-btn <?php echo ($current_page == 'permitView.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-id-card"></i>
        Work Permit Information
    </a>
    <a href="passportView.php" class="nav-btn <?php echo ($current_page == 'passportView.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-passport"></i>
        Passport Information
    </a>
    <a href="employeeInfoView.php" class="nav-btn <?php echo ($current_page == 'employeeInfoView.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-user-tie"></i>
        Employee Information
    </a>
</div>