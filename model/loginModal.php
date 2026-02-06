<!-- Login Modal -->
<div id="loginModal" class="login-modal">
    <div class="login-modal-content">
        <div class="login-modal-header">
            <h2><i class="fa-solid fa-user-shield"></i> Admin Login</h2>
            <span class="close-login">&times;</span>
        </div>
        <div class="login-modal-body">
            <img src="img/fcw1.jpg" alt="Logo">
            <div id="loginError" class="error-message" style="display: none;"></div>
            <form class="login-form" id="loginForm">
                <input type="text" name="username" id="username" placeholder="Username" required>
                <div class="password-container">
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <i class="fa-solid fa-eye-slash toggle-password" id="togglePassword"></i>
                </div>
                <input type="submit" value="Login">
            </form>
        </div>
    </div>
</div>