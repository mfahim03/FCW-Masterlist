<?php
include 'db.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang=en>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Foreign Contract Worker</title>
        <link rel="icon" type="image/png" href="img/fcw2.png">
        <link rel="stylesheet" href="css/login.css">
        <link rel="stylesheet" href="css/index.css">
        <link rel="stylesheet" href="css/dashboard.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    </head>

    <body style="background-image: url('img/ww.jpg'); 
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;">
         <div class="header">
        <a href="#" class="logout-link">
            <i class="fa-solid fa-right-from-bracket" style="font-size: medium;"></i>
        </a>
        <p>FCW Masterlist Dashboard</p>
    </div>

    <?php include 'model/navigationBar.php'; ?>

    <div class="content-wrapper">
    </div>

    <?php include 'model/footer.php'; ?>

    <script src="js/employeeInfo.js"></script>

    </body>
</html>
    
