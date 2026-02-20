<?php
session_start();

// Redirect if already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_number = trim($_POST['id_number'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if(empty($id_number) || empty($password)) {
        $error = "Please enter ID number and password";
    } else {
        if(file_exists('users.txt')) {
            $users = file('users.txt', FILE_IGNORE_NEW_LINES);
            $found = false;
            
            foreach($users as $user) {
                if(empty(trim($user))) continue;
                $data = explode('|', $user);
                if($data[0] == $id_number && password_verify($password, $data[1])) {
                    $_SESSION['user_id'] = $data[0];
                    $_SESSION['user_name'] = $data[2] . ' ' . $data[3];
                    $found = true;
                    header("Location: index.php");
                    exit();
                }
            }
            
            if(!$found) {
                $error = "Invalid ID number or password";
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="login-box">
    <h2>Login</h2>
    
    <?php if($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label>Enter a valid id number</label>
            <input type="text" name="id_number" placeholder="ID Number" required>
        </div>
        
        <div class="form-group">
            <label>Enter password</label>
            <input type="password" name="password" placeholder="Password" required>
        </div>
        
        <div class="checkbox-group">
            <input type="checkbox" name="remember" id="remember">
            <label for="remember">Remember me</label>
        </div>
        
        <div class="forgot-link">
            <a href="#">Forgot password?</a>
        </div>
        
        <button type="submit" class="btn btn-login" style="width: 100%;">Login</button>
        
        <div class="register-link">
            <p>Don't have an account? <a href="register.php">Register</a></p>
        </div>
    </form>
    
</div>

<?php include 'includes/footer.php'; ?>