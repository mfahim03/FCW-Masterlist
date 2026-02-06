<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Check if user is logged in and is admin
$isAdmin = isset($_SESSION['username']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>

<!-- Navigation Menu -->
<div class="nav-menu">
    <a href="<?php echo $isAdmin ? 'index.php' : 'indexView.php'; ?>" 
       class="nav-btn <?php echo (in_array($current_page, ['index.php', 'indexView.php'])) ? 'active' : ''; ?>">
        <i class="fa-solid fa-chart-simple"></i>
        Dashboard
    </a>
    <a href="<?php echo $isAdmin ? 'contract.php' : 'contractView.php'; ?>" 
       class="nav-btn <?php echo (in_array($current_page, ['contract.php', 'contractView.php'])) ? 'active' : ''; ?>">
        <i class="fa-solid fa-folder-open"></i>
        Contract
    </a>
    <a href="<?php echo $isAdmin ? 'permit.php' : 'permitView.php'; ?>" 
       class="nav-btn <?php echo (in_array($current_page, ['permit.php', 'permitView.php'])) ? 'active' : ''; ?>">
        <i class="fa-solid fa-id-card"></i>
        Work Permit Information
    </a>
    <a href="<?php echo $isAdmin ? 'passport.php' : 'passportView.php'; ?>" 
       class="nav-btn <?php echo (in_array($current_page, ['passport.php', 'passportView.php'])) ? 'active' : ''; ?>">
        <i class="fa-solid fa-passport"></i>
        Passport Information
    </a>
    <a href="<?php echo $isAdmin ? 'employeeInfo.php' : 'employeeInfoView.php'; ?>" 
       class="nav-btn <?php echo (in_array($current_page, ['employeeInfo.php', 'employeeInfoView.php'])) ? 'active' : ''; ?>">
        <i class="fa-solid fa-user-tie"></i>
        Employee Information
    </a>
    <a href="<?php echo $isAdmin ? 'runaway.php' : 'runawayView.php'; ?>" 
       class="nav-btn <?php echo (in_array($current_page, ['runaway.php', 'runawayView.php'])) ? 'active' : ''; ?>">
        <i class="fa-solid fa-running"></i>
        EOC & Runaway
    </a>
    <?php if ($isAdmin): ?>
    <button class="add-employee-btn" onclick="openAddEmployeeForm()">
        <i class="fa-solid fa-user-plus"></i>
    </button>
    <?php endif; ?>
</div>