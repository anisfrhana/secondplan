// auth.js - Authentication page interactivity

// Role descriptions for register page
const roleDescriptions = {
    client: 'Book events and manage your bookings',
    customer: 'Purchase merchandise and track orders',
    member: 'Access band member dashboard and tasks'
};

// Update role info on selection
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    const roleInfo = document.getElementById('roleInfo');
    
    if (roleSelect && roleInfo) {
        roleSelect.addEventListener('change', function() {
            roleInfo.textContent = roleDescriptions[this.value];
        });
    }
    
    // Password strength checker
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('strengthBar');
    
    if (passwordInput && strengthBar) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            
            strengthBar.className = 'password-strength-bar';
            if (strength <= 1) {
                strengthBar.classList.add('weak');
            } else if (strength <= 3) {
                strengthBar.classList.add('medium');
            } else {
                strengthBar.classList.add('strong');
            }
        });
    }
    
    // Form submission for register
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            const registerBtn = document.getElementById('registerBtn');
            if (registerBtn) { 
                registerBtn.disabled = true;
                registerBtn.textContent = 'Creating account...';
            }
        });
    }
    
    // Form submission for login
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginBtn');
            if (loginBtn) {
                loginBtn.disabled = true;
                loginBtn.textContent = 'Signing in...';
            }
        });
    }
});

// Toggle password visibility
function togglePassword(inputId) {
    const input = inputId ? document.getElementById(inputId) : document.getElementById('password');
    if (input) {
        input.type = input.type === 'password' ? 'text' : 'password';
    }
}