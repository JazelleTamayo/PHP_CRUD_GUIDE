<?php
// ─── includes/user_navigation.php ───────────────────────────────────
// User navigation menu - for logged-in users ONLY

// Include functions for notification count
require_once __DIR__ . '/get_announcements.php';
$totalUnreadCount = isset($_SESSION['user_id']) ? 
    (getUnreadAnnouncementCount($_SESSION['user_id']) + getUnreadNotificationCount($_SESSION['user_id'])) : 0;
?>

<!-- User Navigation Bar - for logged-in users ONLY -->
<nav class="navbar">
    <div class="nav-container">
        <div class="logo">
            <i class="fas fa-laptop-code"></i>
            <span>CCS <span class="logo-highlight">Student</span></span>
        </div>
        
        <ul class="nav-menu">
            <li><a href="<?= $basePath ?>pages/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Home
            </a></li>
            <li class="nav-item">
                <a href="<?= $basePath ?>pages/notifications.php" class="notification-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i> Notifications
                    <?php if ($totalUnreadCount > 0): ?>
                        <span class="nav-notification-badge"><?php echo $totalUnreadCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="<?= $basePath ?>pages/edit_profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'edit_profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-edit"></i> Edit Profile
            </a></li>
            <li><a href="<?= $basePath ?>pages/history.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'history.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i> History
            </a></li>
            <li><a href="<?= $basePath ?>pages/reservation.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reservation.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Reservation
            </a></li>
            <li><a href="<?= $basePath ?>pages/logout.php" class="btn-logout-nav">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a></li>
        </ul>
        
        <div class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </div>
    </div>
</nav>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobileMenu">
    <a href="<?= $basePath ?>pages/dashboard.php" class="mobile-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
        <i class="fas fa-home"></i> Home
    </a>
    <a href="<?= $basePath ?>pages/notifications.php" class="mobile-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">
        <i class="fas fa-bell"></i> Notifications
        <?php if ($totalUnreadCount > 0): ?>
            <span class="mobile-notification-badge"><?php echo $totalUnreadCount; ?></span>
        <?php endif; ?>
    </a>
    <a href="<?= $basePath ?>pages/edit_profile.php" class="mobile-link <?php echo basename($_SERVER['PHP_SELF']) == 'edit_profile.php' ? 'active' : ''; ?>">
        <i class="fas fa-user-edit"></i> Edit Profile
    </a>
    <a href="<?= $basePath ?>pages/history.php" class="mobile-link <?php echo basename($_SERVER['PHP_SELF']) == 'history.php' ? 'active' : ''; ?>">
        <i class="fas fa-history"></i> History
    </a>
    <a href="<?= $basePath ?>pages/reservation.php" class="mobile-link <?php echo basename($_SERVER['PHP_SELF']) == 'reservation.php' ? 'active' : ''; ?>">
        <i class="fas fa-calendar-check"></i> Reservation
    </a>
    <div class="mobile-divider"></div>
    <a href="<?= $basePath ?>pages/logout.php" class="mobile-link logout">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<style>
/* Notification Badge Styles */
.notification-link {
    position: relative;
}

.nav-notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    font-size: 0.7rem;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 50%;
    min-width: 18px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.mobile-notification-badge {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    font-size: 0.7rem;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 50%;
    min-width: 18px;
    display: inline-block;
    text-align: center;
    margin-left: 8px;
}

/* Existing navbar styles */
.navbar {
    background: linear-gradient(135deg, #1e3a5f, #0f2b42);
    padding: 0.75rem 0;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.nav-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
}

.logo i {
    font-size: 1.8rem;
    color: #3b82f6;
}

.logo-highlight {
    color: #3b82f6;
}

.nav-menu {
    display: flex;
    list-style: none;
    gap: 0.5rem;
    margin: 0;
    padding: 0;
    align-items: center;
}

.nav-menu li a {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.6rem 1.2rem;
    color: #e2e8f0;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 500;
    transition: all 0.25s ease;
}

.nav-menu li a:hover {
    background: rgba(59,130,246,0.2);
    color: white;
    transform: translateY(-1px);
}

.nav-menu li a.active {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: white;
    box-shadow: 0 4px 12px rgba(37,99,235,0.3);
}

/* Logout Button */
.btn-logout-nav {
    background: rgba(220,38,38,0.2) !important;
    border: 1px solid rgba(220,38,38,0.5);
}

.btn-logout-nav:hover {
    background: #dc2626 !important;
    color: white !important;
    border-color: #dc2626;
}

/* Mobile Menu Toggle */
.menu-toggle {
    display: none;
    font-size: 1.8rem;
    color: white;
    cursor: pointer;
    transition: color 0.25s;
}

.menu-toggle:hover {
    color: #3b82f6;
}

.mobile-menu {
    display: none;
    position: fixed;
    top: 0;
    left: -280px;
    width: 280px;
    height: 100%;
    background: linear-gradient(180deg, #0f172a, #1e293b);
    z-index: 2000;
    padding: 2rem 1rem;
    box-shadow: 2px 0 20px rgba(0,0,0,0.3);
    transition: left 0.3s ease;
    overflow-y: auto;
}

.mobile-menu.open {
    left: 0;
}

.mobile-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.9rem 1rem;
    color: #cbd5e1;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.25s;
    margin-bottom: 0.5rem;
}

.mobile-link:hover {
    background: rgba(59,130,246,0.2);
    color: white;
}

.mobile-link.active {
    background: #2563eb;
    color: white;
}

.mobile-divider {
    height: 1px;
    background: rgba(255,255,255,0.1);
    margin: 1rem 0;
}

.mobile-link.logout {
    background: rgba(220,38,38,0.2);
    color: #ef4444;
}

.mobile-link.logout:hover {
    background: #dc2626;
    color: white;
}

/* Overlay */
.mobile-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1999;
}

.mobile-overlay.active {
    display: block;
}

@media (max-width: 768px) {
    .nav-menu {
        display: none;
    }
    
    .menu-toggle {
        display: block;
    }
    
    .nav-container {
        padding: 0 1rem;
    }
}
</style>

<script>
// Mobile menu functionality
const menuToggle = document.getElementById('menuToggle');
const mobileMenu = document.getElementById('mobileMenu');

// Create overlay if not exists
let overlay = document.querySelector('.mobile-overlay');
if (!overlay) {
    overlay = document.createElement('div');
    overlay.className = 'mobile-overlay';
    document.body.appendChild(overlay);
}

function openMobileMenu() {
    mobileMenu.classList.add('open');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeMobileMenu() {
    mobileMenu.classList.remove('open');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

if (menuToggle) {
    menuToggle.addEventListener('click', openMobileMenu);
}

overlay.addEventListener('click', closeMobileMenu);

// Close mobile menu when clicking a link
document.querySelectorAll('.mobile-link').forEach(link => {
    link.addEventListener('click', closeMobileMenu);
});

// Close on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && mobileMenu.classList.contains('open')) {
        closeMobileMenu();
    }
});
</script>