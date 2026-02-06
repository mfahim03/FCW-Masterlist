// ===== LOGIN MODAL =====
const loginModal = document.getElementById('loginModal');
const openLoginBtn = document.getElementById('openLoginModal');
const closeLoginBtn = document.querySelector('.close-login');
const loginForm = document.getElementById('loginForm');
const loginError = document.getElementById('loginError');

openLoginBtn.addEventListener('click', (e) => {
    e.preventDefault();
    loginModal.style.display = 'block';
});

closeLoginBtn.addEventListener('click', () => {
    loginModal.style.display = 'none';
    loginError.style.display = 'none';
    loginForm.reset();
});

window.addEventListener('click', (e) => {
    if (e.target == loginModal) {
        loginModal.style.display = 'none';
        loginError.style.display = 'none';
        loginForm.reset();
    }
});

// ===== AJAX LOGIN FORM =====
loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(loginForm);
    
    try {
        const response = await fetch('login_ajax.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Get current page name and redirect to admin version
            const currentPage = window.location.pathname.split('/').pop();
            let adminPage = currentPage.replace('View.php', '.php');
            window.location.href = adminPage;
        } else {
            loginError.textContent = result.message;
            loginError.style.display = 'block';
        }
    } catch (error) {
        loginError.textContent = 'An error occurred. Please try again.';
        loginError.style.display = 'block';
    }
});

// ===== PASSWORD TOGGLE =====
const passwordField = document.getElementById('password');
const togglePassword = document.getElementById('togglePassword');

togglePassword.addEventListener('click', function() {
    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordField.setAttribute('type', type);
    this.classList.toggle('fa-eye-slash');
    this.classList.toggle('fa-eye');
});