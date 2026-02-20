<?php
session_start();
include 'includes/header.php';
?>

<div class="welcome-box">
    <h2>Welcome to CCS Sit-in System</h2>
    <p>Please login or register to access the system</p>
    
    <div class="button-group">
        <a href="login.php" class="btn btn-login">Login</a>
        <a href="register.php" class="btn btn-register">Register</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>