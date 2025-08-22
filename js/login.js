document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const errorMsg = document.getElementById('errorMsg');
    const spinner = document.getElementById('spinner');
    const loginBtn = document.getElementById('loginBtn');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const rememberMe = document.getElementById('rememberMe');

    // Password visibility toggle
    togglePassword.addEventListener('click', function() {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            togglePassword.textContent = '🙈';
        } else {
            passwordInput.type = 'password';
            togglePassword.textContent = '👁️';
        }
    });

    // Animate error message
    function showError(msg) {
        errorMsg.textContent = msg;
        errorMsg.classList.add('show');
        errorMsg.style.display = 'block';
    }
    function hideError() {
        errorMsg.classList.remove('show');
        errorMsg.style.display = 'none';
    }

    // Handle login form submission via AJAX
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        hideError();
        const username = loginForm.username.value.trim();
        const password = loginForm.password.value.trim();
        loginBtn.disabled = true;
        spinner.style.display = 'block';
        if (!username || !password) {
            spinner.style.display = 'none';
            loginBtn.disabled = false;
            showError('Please enter both username and password.');
            return;
        }
        // Send AJAX request to login.php
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../login.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                spinner.style.display = 'none';
                loginBtn.disabled = false;
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            if (rememberMe.checked) {
                                localStorage.setItem('rememberedUser', username);
                            } else {
                                localStorage.removeItem('rememberedUser');
                            }
                            window.location.href = response.redirect;
                        } else {
                            showError(response.error || 'Invalid username or password.');
                        }
                    } catch (err) {
                        showError('Unexpected server response.');
                    }
                } else {
                    showError('Server error. Please try again later.');
                }
            }
        };
        xhr.send('username=' + encodeURIComponent(username) + '&password=' + encodeURIComponent(password));
    });

    // Autofill username if remembered
    const rememberedUser = localStorage.getItem('rememberedUser');
    if (rememberedUser) {
        loginForm.username.value = rememberedUser;
        rememberMe.checked = true;
    }
});
