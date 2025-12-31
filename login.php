<?php
session_start();
include 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Query to check if user exists
    $sql = "SELECT * FROM users WHERE username = ? AND password = ?";
    $params = [$username, $password];
    $stmt = sqlsrv_query($conn1, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];
        
        // Redirect based on role
        if ($row['role'] === 'admin') {
            header("Location: index.php"); // Admin dashboard
        } elseif ($row['role'] === 'user') {
            header("Location: indexView.php"); // User view
        } else {
            // Default 
            header("Location: indexView.php");
        }
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Foreign Contract Worker</title>
  <link rel="stylesheet" href="css/login.css">
  <link rel="icon" type="image/png" href="img/fcw2.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .login-logo {
      display: block;
      width: 100%;
      height: auto;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <img src="img/fcw1.jpg" alt="Logo" class="login-logo">
   
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="text" name="username" placeholder="Username" required>

      <div class="password-container">
        <input type="password" name="password" id="password" placeholder="Password" required>
        <i class="fa-solid fa-eye-slash toggle-password" id="togglePassword"></i>
      </div>

      <input type="submit" value="Login">
    </form>
  </div>

  <script>
    const passwordField = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');

    togglePassword.addEventListener('click', function() {
      const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordField.setAttribute('type', type);
      this.classList.toggle('fa-eye-slash');
      this.classList.toggle('fa-eye');
    });
  </script>
</body>
</html>