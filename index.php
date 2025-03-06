<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<style>
:root {
  --primary-color: #4776E6;
  --secondary-color: #8E54E9;
  --text-color: #444;
  --light-text: #777;
  --bg-color: #f8f9fa;
  --input-bg: #ffffff;
  --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
  --error: #e74c3c;
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  margin: 0;
  color: var(--text-color);
}

.login-container {
  background-color: var(--bg-color);
  padding: 40px;
  border-radius: 16px;
  box-shadow: var(--shadow);
  width: 360px;
  max-width: 90%;
  transform: translateY(0);
  transition: all 0.3s ease;
}

.login-container:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
}

.login-container h2 {
  text-align: center;
  margin-bottom: 30px;
  color: var(--primary-color);
  font-weight: 600;
  font-size: 28px;
}

.form-group {
  margin-bottom: 24px;
  position: relative;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  font-size: 14px;
  color: var(--light-text);
  transition: color 0.3s;
}

.form-group input {
  width: 100%;
  padding: 12px 15px;
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  font-size: 16px;
  transition: all 0.3s;
  background-color: var(--input-bg);
  color: var(--text-color);
}

.form-group input:focus {
  outline: none;
  border-color: var(--secondary-color);
  box-shadow: 0 0 0 3px rgba(142, 84, 233, 0.15);
}

.form-group input:focus + label {
  color: var(--secondary-color);
}

.form-group button {
  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  color: white;
  padding: 14px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  width: 100%;
  font-size: 16px;
  font-weight: 600;
  letter-spacing: 0.5px;
  transition: all 0.3s;
}

.form-group button:hover {
  background: linear-gradient(135deg, #3d67d6, #7e47d9);
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(71, 118, 230, 0.3);
}

.form-group button:active {
  transform: translateY(0);
}

.error-message {
  color: var(--error);
  margin-top: 15px;
  text-align: center;
  font-size: 14px;
  min-height: 20px;
}

@media (max-width: 480px) {
  .login-container {
    padding: 30px 20px;
  }
  
  .login-container h2 {
    font-size: 24px;
    margin-bottom: 24px;
  }
  
  .form-group {
    margin-bottom: 20px;
  }
  
  .form-group input {
    padding: 10px 12px;
  }
  
  .form-group button {
    padding: 12px;
  }
}
</style>
</head>
<body>
<div class="login-container">
<h2>Login</h2>
<form id="loginForm" action="login.php" method="post">
<div class="form-group">
<label for="username">Username</label>
<input type="text" id="username" name="username" required>
</div>
<div class="form-group">
<label for="password">Password</label>
<input type="password" id="password" name="password_hash" required>
</div>
<div class="form-group">
<button type="submit">Log In</button>
</div>
<div id="error-message" class="error-message"></div>
</form>
</div>
<script>
const form = document.getElementById('loginForm');
const errorMessage = document.getElementById('error-message');
form.addEventListener('submit', async (e) => {
e.preventDefault();
const formData = new FormData(form);
try {
const response = await fetch('login.php', {
method: 'POST',
body: formData,
});
const data = await response.json();
if (data.success) {
window.location.href = 'dashboard.php';
} else {
errorMessage.textContent = data.message;
}
} catch (error) {
console.error('Error:', error);
errorMessage.textContent = 'An error occurred during login.';
}
});
</script>
</body>
</html>