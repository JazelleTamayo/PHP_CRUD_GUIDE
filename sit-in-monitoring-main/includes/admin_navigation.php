<!-- Admin Navigation Bar -->
<nav class="navbar admin-navbar">
    <div class="nav-container">
        <div class="logo">
            <i class="fas fa-crown" style="color: #ffd700;"></i>
            <span>CCS <span class="logo-highlight">Admin</span></span>
        </div>
        
        <ul class="nav-menu">
            <li><a href="admin_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Home
            </a></li>

            <li><a href="admin_search.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_search.php' ? 'active' : ''; ?>">
                <i class="fas fa-search"></i> Search
            </a></li>

            <li><a href="admin_students.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_students.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Students
            </a></li>

            <!-- Sit-in Dropdown -->
            <li class="nav-dropdown">
                <a href="#" class="nav-dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['admin_sitin.php','admin_sitin_records.php','admin_sitin_reports.php','admin_feedback_reports.php']) ? 'active' : ''; ?>"
                   onclick="toggleDropdown(event, this)">
                    <i class="fas fa-clock"></i> Sit-in <i class="fas fa-chevron-down dropdown-arrow"></i>
                </a>
                <ul class="dropdown-menu" id="sitinDropdown">
                    <li><a href="admin_sitin.php">
                        <i class="fas fa-clock"></i> Sit-in
                    </a></li>
                    <li><a href="admin_sitin_records.php">
                        <i class="fas fa-list-alt"></i> View Sit-in Records
                    </a></li>
                    <li><a href="admin_sitin_reports.php">
                        <i class="fas fa-chart-bar"></i> Sit-in Reports
                    </a></li>
                    <li><a href="admin_feedback_reports.php">
                        <i class="fas fa-comment-alt"></i> Feedback Reports
                    </a></li>
                </ul>
            </li>

            <!-- NEW: Announcements Link -->
            <li><a href="admin_announcements.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_announcements.php' ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn"></i> Announcements
            </a></li>

            <li><a href="admin_reservation.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_reservation.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Reservation
            </a></li>

            <li>
                <a href="logout.php" class="btn-logout-nav">
                    <i class="fas fa-sign-out-alt"></i> Log out
                </a>
            </li>
        </ul>
        
        <div class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </div>
    </div>
</nav>

<!-- Mobile Menu for Admin -->
<div class="mobile-menu" id="mobileMenu">
    <a href="admin_dashboard.php" class="mobile-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
        <i class="fas fa-home"></i> Home
    </a>
    <a href="admin_search.php" class="mobile-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_search.php' ? 'active' : ''; ?>">
        <i class="fas fa-search"></i> Search
    </a>
    <a href="admin_students.php" class="mobile-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_students.php' ? 'active' : ''; ?>">
        <i class="fas fa-users"></i> Students
    </a>
    <a href="admin_sitin.php" class="mobile-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_sitin.php' ? 'active' : ''; ?>">
        <i class="fas fa-clock"></i> Sit-in
    </a>
    <a href="admin_sitin_records.php" class="mobile-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_sitin_records.php' ? 'active' : ''; ?>">
        <i class="fas fa-list-alt"></i> View Sit-in Records
    </a>
    <a href="admin_sitin_reports.php" class="mobile-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_sitin_reports.php' ? 'active' : ''; ?>">
        <i class="fas fa-chart-bar"></i> Sit-in Reports
    </a>
    <a href="admin_feedback_reports.php" class="mobile-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_feedback_reports.php' ? 'active' : ''; ?>">
        <i class="fas fa-comment-alt"></i> Feedback Reports
    </a>
    <!-- NEW: Announcements Mobile Link -->
    <a href="admin_announcements.php" class="mobile-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_announcements.php' ? 'active' : ''; ?>">
        <i class="fas fa-bullhorn"></i> Announcements
    </a>
    <a href="admin_reservation.php" class="mobile-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_reservation.php' ? 'active' : ''; ?>">
        <i class="fas fa-calendar-check"></i> Reservation
    </a>
    <div class="mobile-divider"></div>
    <a href="logout.php" class="mobile-link logout">
        <i class="fas fa-sign-out-alt"></i> Log out
    </a>
</div>

<style>
/* Admin Navbar Base Styles */
.admin-navbar {
    background: linear-gradient(135deg, #1e3a5f, #0f2b42);
    padding: 0.75rem 0;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    margin: 0;
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

/* HOVER EFFECT - Blue background */
.nav-menu li a:hover {
    background: rgba(59,130,246,0.2);
    color: white;
    transform: translateY(-1px);
}

/* Active state */
.nav-menu li a.active {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: white;
    box-shadow: 0 4px 12px rgba(37,99,235,0.3);
}

.nav-dropdown { 
    position: relative; 
}

.nav-dropdown-toggle {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    cursor: pointer;
    user-select: none;
}

.dropdown-arrow {
    font-size: 0.65rem;
    margin-left: 2px;
    transition: transform 0.25s ease;
}

.nav-dropdown-toggle.dropdown-open .dropdown-arrow {
    transform: rotate(180deg);
}

.nav-dropdown .dropdown-menu {
    display: none;
    position: absolute;
    top: calc(100% + 12px);
    left: 50%;
    transform: translateX(-50%);
    background: #0d1829;
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 14px;
    min-width: 210px;
    padding: 8px;
    box-shadow: 0 16px 48px rgba(0,0,0,0.6);
    z-index: 9999;
}

.nav-dropdown .dropdown-menu.open {
    display: block;
    animation: dropdownFade 0.2s ease forwards;
}

@keyframes dropdownFade {
    from { opacity: 0; transform: translateX(-50%) translateY(-6px); }
    to   { opacity: 1; transform: translateX(-50%) translateY(0); }
}

.nav-dropdown .dropdown-menu li { list-style: none; }

.nav-dropdown .dropdown-menu li a {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.65rem 1rem;
    color: #cbd5e1;
    text-decoration: none;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.nav-dropdown .dropdown-menu li a:hover {
    background: rgba(37,99,235,0.20);
    color: #7dd3fc;
}

.nav-dropdown .dropdown-menu li a i {
    color: #0ea5e9;
    width: 16px;
    font-size: 0.85rem;
}

.nav-dropdown .dropdown-menu li:not(:last-child) {
    border-bottom: 1px solid rgba(255,255,255,0.05);
    margin-bottom: 2px;
    padding-bottom: 2px;
}

.mobile-divider {
    height: 1px;
    background: rgba(255,255,255,0.1);
    margin: 10px 0;
}

.admin-navbar .btn-logout-nav {
    background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
    color: white !important;
    box-shadow: 0 4px 12px rgba(220,38,38,0.3) !important;
    padding: 0.4rem 1.2rem !important;
    border-radius: 30px;
    font-size: 0.9rem !important;
    font-weight: 500 !important;
    transition: all 0.25s ease !important;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    text-decoration: none;
}

.admin-navbar .btn-logout-nav:hover {
    background: linear-gradient(135deg, #b91c1c, #991b1b) !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(220,38,38,0.4) !important;
}

.admin-navbar .btn-logout-nav i { color: white; font-size: 1rem; }

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

.mobile-link.logout {
    background: rgba(220,38,38,0.2);
    color: #ef4444;
}

.mobile-link.logout:hover {
    background: #dc2626;
    color: white;
}

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
function toggleDropdown(e, el) {
    e.preventDefault();
    e.stopPropagation();

    const menu = el.nextElementSibling;
    const isOpen = menu.classList.contains('open');

    document.querySelectorAll('.dropdown-menu.open').forEach(m => m.classList.remove('open'));
    document.querySelectorAll('.nav-dropdown-toggle.dropdown-open').forEach(t => t.classList.remove('dropdown-open'));

    if (!isOpen) {
        menu.classList.add('open');
        el.classList.add('dropdown-open');
    }
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.nav-dropdown')) {
        document.querySelectorAll('.dropdown-menu.open').forEach(m => m.classList.remove('open'));
        document.querySelectorAll('.nav-dropdown-toggle.dropdown-open').forEach(t => t.classList.remove('dropdown-open'));
    }
});

// Mobile menu functionality
const menuToggle = document.getElementById('menuToggle');
const mobileMenu = document.getElementById('mobileMenu');

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

document.querySelectorAll('.mobile-link').forEach(link => {
    link.addEventListener('click', closeMobileMenu);
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && mobileMenu.classList.contains('open')) {
        closeMobileMenu();
    }
});
</script>