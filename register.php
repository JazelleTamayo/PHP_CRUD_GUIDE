<?php
session_start();

// Redirect if already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_number = trim($_POST['id_number'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $firstname = trim($_POST['firstname'] ?? '');
    $middlename = trim($_POST['middlename'] ?? '');
    $course_level = $_POST['course_level'] ?? '';
    $password = $_POST['password'] ?? '';
    $repeat_password = $_POST['repeat_password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $course = $_POST['course'] ?? '';
    $address = trim($_POST['address'] ?? '');
    
    // Validation
    if(empty($id_number) || empty($lastname) || empty($firstname) || empty($course_level) || empty($password) || empty($email)) {
        $error = "Please fill in all required fields";
    } elseif($password !== $repeat_password) {
        $error = "Passwords do not match";
    } elseif(strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        // Check if ID exists
        $id_exists = false;
        if(file_exists('users.txt')) {
            $users = file('users.txt', FILE_IGNORE_NEW_LINES);
            foreach($users as $user) {
                if(empty(trim($user))) continue;
                $data = explode('|', $user);
                if($data[0] == $id_number) {
                    $id_exists = true;
                    break;
                }
            }
        }
        
        if($id_exists) {
            $error = "ID number already registered";
        } else {
            // Save user
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $user_data = implode('|', [
                $id_number,
                $hashed,
                $firstname,
                $lastname,
                $middlename,
                $course_level,
                $email,
                $course,
                $address
            ]) . PHP_EOL;
            
            file_put_contents('users.txt', $user_data, FILE_APPEND);
            $success = "Registration successful! You can now login.";
        }
    }
}

include 'includes/header.php';
?>

<div class="register-box">
    <h2>Sign Up</h2>
    
    <?php if($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success">
            <?php echo $success; ?> <a href="login.php" style="color: #22543d; font-weight: bold;">Login here</a>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label>ID Number</label>
            <input type="text" name="id_number" placeholder="Enter ID Number" required>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="lastname" placeholder="Last Name" required>
            </div>
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="firstname" placeholder="First Name" required>
            </div>
        </div>
        
        <div class="form-group">
            <label>Middle Name</label>
            <input type="text" name="middlename" placeholder="Middle Name (Optional)">
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Course Level</label>
                <select name="course_level" required>
                    <option value="">Select Level</option>
                    <option value="1st Year">1st Year</option>
                    <option value="2nd Year">2nd Year</option>
                    <option value="3rd Year">3rd Year</option>
                    <option value="4th Year">4th Year</option>
                </select>
            </div>
            <div class="form-group">
                <label>Course</label>
                <select name="course" required>
                    <option value="">Select Course</option>
                    <option value="BSIT">BSIT</option>
                    <option value="BSCS">BSCS</option>
                    <option value="BSIS">BSIS</option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Min. 6 characters" required>
            </div>
            <div class="form-group">
                <label>Repeat Password</label>
                <input type="password" name="repeat_password" placeholder="Confirm password" required>
            </div>
        </div>
        
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="your@email.com" required>
        </div>
        
        <div class="form-group">
            <label>Address</label>
            <textarea name="address" rows="3" placeholder="Your complete address"></textarea>
        </div>
        
        <button type="submit" class="btn btn-register" style="width: 100%;">Register</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>