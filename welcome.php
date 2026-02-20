<?php
session_start();
$page_title = "Welcome";
include 'includes/header.php';

// Check if user is logged in (for demo)
$is_logged_in = true; // Simulate logged in user
if(!$is_logged_in) {
    header("Location: login.php");
    exit();
}

// Sample user data
$user_data = [
    'name' => 'Juan Dela Cruz',
    'id_number' => '2024-12345'
];
?>

<!-- Simple Welcome Banner -->
<div class="welcome-banner">
    <div class="banner-content">
        <h1>Welcome, <?php echo $user_data['name']; ?>! 👋</h1>
        <p>ID Number: <?php echo $user_data['id_number']; ?></p>
        <p class="banner-date"><?php echo date('l, F j, Y'); ?></p>
        <a href="index.php" class="btn-logout">Logout</a>
    </div>
</div>

<!-- Optional: Simple message -->
<div class="simple-message">
    <p>You have successfully logged in to the CCS Sit-in Monitoring System.</p>
    <p><small>This is a demo version. More features coming soon!</small></p>
</div>

<?php include 'includes/footer.php'; ?>