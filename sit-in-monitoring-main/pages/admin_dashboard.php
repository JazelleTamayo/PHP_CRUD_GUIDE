<?php
session_start();

if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php?error=" . urlencode("Unauthorized access"));
    exit();
}

require_once __DIR__ . '/../config/database.php';

// ── STATISTICS ─────────────────────────────────────────────────────────────
$total_students = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$current_sitin = $pdo->query("SELECT COUNT(*) FROM sit_in WHERE status = 'active'")->fetchColumn();
$total_sitin = $pdo->query("SELECT COUNT(*) FROM sit_in")->fetchColumn();
$today_sitins = $pdo->query("SELECT COUNT(*) FROM sit_in WHERE login_date = CURDATE()")->fetchColumn();
$pending_reservations = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'")->fetchColumn();

// Purpose breakdown for pie chart
$purpose_rows = $pdo->query("SELECT purpose, COUNT(*) as cnt FROM sit_in GROUP BY purpose ORDER BY cnt DESC")->fetchAll();

// Top 5 active students
$top_students = $pdo->query("
    SELECT s.name, COUNT(*) as total_sitins
    FROM sit_in s
    GROUP BY s.user_id
    ORDER BY total_sitins DESC
    LIMIT 5
")->fetchAll();

$pageTitle = "Admin Dashboard - CCS Sit-in System";
$basePath  = "../";
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/admin_navigation.php'; ?>

<style>
:root {
    --primary:           #2563eb;
    --primary-light:     #3b82f6;
    --accent:            #0ea5e9;
    --text-primary:      #f1f5f9;
    --text-secondary:    #cbd5e1;
    --text-muted:        #94a3b8;
    --text-label:        #7dd3fc;
    --border-light:      rgba(255,255,255,0.10);
    --border-hover:      rgba(14,165,233,0.40);
    --shadow-md:         0 8px 32px rgba(0,0,0,0.50);
    --shadow-lg:         0 20px 60px rgba(0,0,0,0.60);
    --radius-lg:         28px;
    --radius-md:         16px;
    --radius-sm:         10px;
    --transition:        all 0.25s ease;
    --card-bg:           rgba(10,18,40,0.82);
    --card-bg-hover:     rgba(14,24,52,0.90);
    --card-border:       rgba(255,255,255,0.10);
    --card-border-hover: rgba(14,165,233,0.45);
}

.admin-dashboard-container {
    min-height: 100vh;
    padding: 1.5rem 24px 48px;
    position: relative;
}

.admin-dashboard-container::before {
    content: '';
    position: fixed;
    inset: 0;
    background:
        radial-gradient(ellipse at 5%   0%,  rgba(37,99,235,0.35)  0%, transparent 45%),
        radial-gradient(ellipse at 95% 100%, rgba(14,165,233,0.25) 0%, transparent 45%);
    pointer-events: none;
    z-index: -1;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-light);
}

.admin-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
}

.date-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 18px;
    background: rgba(10,18,40,0.70);
    border: 1px solid var(--border-light);
    border-radius: 999px;
    color: var(--text-secondary);
    font-size: 0.875rem;
    backdrop-filter: blur(12px);
}
.date-badge i { color: var(--accent); }

.admin-main {
    max-width: 1400px;
    margin: 0 auto;
}

/* Stats Cards */
.stats-row {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius-md);
    backdrop-filter: blur(24px);
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: var(--transition);
}

.stat-card:hover {
    background: var(--card-bg-hover);
    border-color: var(--card-border-hover);
    transform: translateY(-3px);
}

.stat-icon {
    width: 45px;
    height: 45px;
    background: rgba(37,99,235,0.15);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.stat-icon i { font-size: 1.3rem; color: var(--accent); }

.stat-info h3 {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}
.stat-info p {
    color: var(--text-muted);
    font-size: 0.75rem;
    margin: 0;
}

/* Dashboard Grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.dash-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius-md);
    backdrop-filter: blur(24px);
    overflow: hidden;
    transition: var(--transition);
}

.dash-card:hover {
    background: var(--card-bg-hover);
    border-color: var(--card-border-hover);
    transform: translateY(-3px);
}

.dash-card-header {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 1rem 1.25rem;
    background: rgba(37,99,235,0.20);
    border-bottom: 1px solid var(--border-light);
}

.dash-card-header i { color: var(--accent); }
.dash-card-header h3 {
    color: var(--text-primary);
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
}

.dash-card-body {
    padding: 1.25rem;
}

.stat-row {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    margin-bottom: 1.25rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.stat-item strong {
    color: #ffffff;
    font-weight: 700;
}

.stat-item .highlight-green { color: #6ee7b7; }
.stat-item .highlight-blue  { color: #93c5fd; }

.chart-wrapper {
    position: relative;
    height: 280px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chart-no-data {
    text-align: center;
    color: var(--text-muted);
    padding: 2rem;
}

.chart-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 1rem;
    margin-top: 0.75rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

/* Top Students Table */
.top-students-table {
    width: 100%;
    border-collapse: collapse;
}

.top-students-table td {
    padding: 0.6rem 0;
    color: var(--text-secondary);
    border-bottom: 1px solid var(--border-light);
}

.top-students-table tr:last-child td { border-bottom: none; }

.rank {
    width: 35px;
    color: var(--accent);
    font-weight: 600;
}

.student-name { color: var(--text-primary); font-weight: 500; }
.student-count { text-align: right; color: #facc15; font-weight: 600; }

.empty-state {
    text-align: center;
    padding: 2rem;
    color: var(--text-muted);
}

/* Modal */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(8,14,26,0.90);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-container {
    background: #0d1829;
    border: 1px solid rgba(255,255,255,0.14);
    border-radius: var(--radius-lg);
    padding: 40px 36px;
    max-width: 450px;
    width: 90%;
    text-align: center;
    box-shadow: var(--shadow-lg);
    position: relative;
    overflow: hidden;
}

.modal-container::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), var(--accent), #7c3aed);
}

.modal-icon {
    width: 80px; height: 80px;
    background: rgba(255,215,0,0.15);
    border: 1px solid rgba(255,215,0,0.3);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 20px;
}
.modal-icon i { font-size: 2.5rem; color: #ffd700; }
.modal-title { color: #fff; font-size: 1.8rem; font-weight: 700; margin-bottom: 10px; }
.modal-message { color: var(--text-secondary); font-size: 1rem; margin-bottom: 24px; }
.modal-user-info {
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--border-light);
    border-radius: var(--radius-sm);
    padding: 16px; margin-bottom: 24px; text-align: left;
}
.modal-info-item {
    display: flex; align-items: center; gap: 12px;
    padding: 8px 0; color: #e2e8f0;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.modal-info-item:last-child { border-bottom: none; }
.modal-info-item i { width: 20px; color: var(--accent); }
.modal-btn {
    display: inline-flex; align-items: center; justify-content: center;
    gap: 8px; padding: 12px 32px;
    background: linear-gradient(135deg, var(--primary), #7c3aed);
    color: #fff; border: none; border-radius: 999px;
    font-family: inherit; font-size: 0.95rem; font-weight: 600;
    cursor: pointer; transition: var(--transition); width: 100%;
    box-shadow: 0 4px 16px rgba(37,99,235,0.40);
}
.modal-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(37,99,235,0.55); }

@media (max-width: 1024px) {
    .stats-row { grid-template-columns: repeat(3, 1fr); }
    .dashboard-grid { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
    .admin-dashboard-container { padding: 80px 16px 40px; }
    .stats-row { grid-template-columns: 1fr; }
    .admin-header { flex-direction: column; gap: 12px; text-align: center; }
}
</style>

<!-- SUCCESS MODAL -->
<?php if(isset($_GET['success'])): ?>
<div class="modal-overlay" id="successModal">
    <div class="modal-container">
        <div class="modal-icon"><i class="fas fa-crown"></i></div>
        <h2 class="modal-title">Welcome, Admin!</h2>
        <p class="modal-message"><?php echo htmlspecialchars($_GET['success']); ?></p>
        <div class="modal-user-info">
            <div class="modal-info-item">
                <i class="fas fa-id-card"></i>
                <span><?php echo htmlspecialchars($_SESSION['id_number']); ?></span>
            </div>
            <div class="modal-info-item">
                <i class="fas fa-user-shield"></i>
                <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            </div>
            <div class="modal-info-item">
                <i class="fas fa-envelope"></i>
                <span><?php echo htmlspecialchars($_SESSION['user_email']); ?></span>
            </div>
        </div>
        <button class="modal-btn" onclick="closeModal()">OK, Got It!</button>
    </div>
</div>
<script>
function closeModal() {
    document.getElementById('successModal').style.display = 'none';
    const url = new URL(window.location.href);
    url.searchParams.delete('success');
    window.history.replaceState({}, document.title, url.toString());
}
window.onclick = function(e) {
    const modal = document.getElementById('successModal');
    if (e.target === modal) closeModal();
};
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});
</script>
<?php endif; ?>

<!-- ADMIN DASHBOARD -->
<div class="admin-dashboard-container">

    <div class="admin-header">
        <h1>Dashboard Overview</h1>
        <div class="date-badge">
            <i class="far fa-calendar-alt"></i>
            <?php echo date('F j, Y'); ?>
        </div>
    </div>

    <div class="admin-main">

        <!-- Stats Cards -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_students; ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h3><?php echo $current_sitin; ?></h3>
                    <p>Currently Sit-in</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-history"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_sitin; ?></h3>
                    <p>Total Sit-in Sessions</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-info">
                    <h3><?php echo $today_sitins; ?></h3>
                    <p>Today's Sit-ins</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h3><?php echo $pending_reservations; ?></h3>
                    <p>Pending Reservations</p>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">

            <!-- Statistics with Pie Chart (YOUR EXISTING - INTACT) -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <i class="fas fa-chart-pie"></i>
                    <h3>Sit-in Statistics</h3>
                </div>
                <div class="dash-card-body">
                    <div class="stat-row">
                        <div class="stat-item">
                            Students Registered:
                            <strong>&nbsp;<?php echo $total_students; ?></strong>
                        </div>
                        <div class="stat-item">
                            Currently Sit-in:
                            <strong class="highlight-green">&nbsp;<?php echo $current_sitin; ?></strong>
                        </div>
                        <div class="stat-item">
                            Total Sit-in:
                            <strong class="highlight-blue">&nbsp;<?php echo $total_sitin; ?></strong>
                        </div>
                    </div>

                    <!-- YOUR EXISTING PIE CHART - KEPT INTACT -->
                    <div class="chart-wrapper">
                        <?php if (!empty($purpose_rows)): ?>
                            <canvas id="sitinPieChart"></canvas>
                        <?php else: ?>
                            <div class="chart-no-data">
                                <i class="fas fa-chart-pie" style="font-size:2.5rem;opacity:0.2;display:block;margin-bottom:0.5rem;"></i>
                                No sit-in data yet
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="chart-legend" id="chartLegend"></div>
                </div>
            </div>

            <!-- Top 5 Active Students -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <i class="fas fa-trophy"></i>
                    <h3>Top Active Students</h3>
                </div>
                <div class="dash-card-body">
                    <?php if (empty($top_students)): ?>
                        <div class="empty-state">No data available</div>
                    <?php else: ?>
                        <table class="top-students-table">
                            <?php foreach ($top_students as $i => $student): ?>
                            <tr>
                                <td class="rank">#<?php echo $i + 1; ?></td>
                                <td class="student-name"><?php echo htmlspecialchars($student['name']); ?></td>
                                <td class="student-count"><?php echo $student['total_sitins']; ?> sessions</td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// YOUR EXISTING PIE CHART - KEPT INTACT
<?php if (!empty($purpose_rows)): ?>
const labels = <?php echo json_encode(array_column($purpose_rows, 'purpose')); ?>;
const data   = <?php echo json_encode(array_column($purpose_rows, 'cnt')); ?>;
const colors = ['#3b82f6','#ec4899','#f97316','#eab308','#8b5cf6','#10b981','#0ea5e9','#ef4444','#f59e0b','#6366f1'];

const ctx = document.getElementById('sitinPieChart').getContext('2d');
new Chart(ctx, {
    type: 'pie',
    data: {
        labels: labels,
        datasets: [{
            data: data,
            backgroundColor: colors.slice(0, labels.length),
            borderColor: 'rgba(10,18,40,0.8)',
            borderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } }
    }
});

const legend = document.getElementById('chartLegend');
labels.forEach((label, i) => {
    legend.innerHTML += `
        <div class="legend-item">
            <div class="legend-dot" style="background:${colors[i]}"></div>
            <span>${label} (${data[i]})</span>
        </div>`;
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>