<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php?error=" . urlencode("Unauthorized access"));
    exit();
}

require_once __DIR__ . '/../config/database.php';

$pageTitle = "Reservations - CCS Sit-in System";
$basePath  = "../";

$success_message = '';
$error_message   = '';

// ── Handle Approve ────────────────────────────────────────────────────────
if (isset($_GET['approve_id'])) {
    $id = (int)$_GET['approve_id'];
    $pdo->prepare("UPDATE reservations SET status = 'approved' WHERE id = ?")
        ->execute([$id]);
    $success_message = "Reservation approved successfully.";
}

// ── Handle Reject ─────────────────────────────────────────────────────────
if (isset($_GET['reject_id'])) {
    $id = (int)$_GET['reject_id'];
    // Restore the student's session when rejected
    $pdo->prepare("
        UPDATE users u
        JOIN reservations r ON r.user_id = u.id
        SET u.sessions = u.sessions + 1
        WHERE r.id = ?
    ")->execute([$id]);
    $pdo->prepare("UPDATE reservations SET status = 'rejected' WHERE id = ?")
        ->execute([$id]);
    $success_message = "Reservation rejected.";
}

// ── Handle Delete ─────────────────────────────────────────────────────────
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $pdo->prepare("DELETE FROM reservations WHERE id = ?")->execute([$id]);
    $success_message = "Reservation deleted.";
}

// ── Pagination & search ───────────────────────────────────────────────────
$entriesRaw = isset($_GET['entries']) ? (int)$_GET['entries'] : 10;
$entries    = in_array($entriesRaw, [10, 25, 50, 100]) ? $entriesRaw : 10;
$page       = max(1, (int)($_GET['page'] ?? 1));
$search     = trim($_GET['search'] ?? '');
$filter     = $_GET['filter'] ?? 'all'; // all | pending | approved | rejected

// ── WHERE clause ──────────────────────────────────────────────────────────
$params = [];
$where  = [];

if ($filter !== 'all') {
    $where[]  = "r.status = ?";
    $params[] = $filter;
}

if ($search !== '') {
    $where[]  = "(r.name LIKE ? OR r.id_number LIKE ? OR r.purpose LIKE ? OR r.laboratory LIKE ?)";
    $like     = "%$search%";
    $params   = array_merge($params, [$like, $like, $like, $like]);
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ── Count ─────────────────────────────────────────────────────────────────
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM reservations r $whereSql");
$countStmt->execute($params);
$totalRows  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $entries));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $entries;

// ── Fetch rows ────────────────────────────────────────────────────────────
$dataStmt = $pdo->prepare("
    SELECT r.*, u.course, u.year_level, u.sessions AS remaining_sessions
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    $whereSql
    ORDER BY r.created_at DESC
    LIMIT ? OFFSET ?
");
$paramIndex = 1;
foreach ($params as $val) {
    $dataStmt->bindValue($paramIndex++, $val);
}
$dataStmt->bindValue($paramIndex++, $entries, PDO::PARAM_INT);
$dataStmt->bindValue($paramIndex++, $offset,  PDO::PARAM_INT);
$dataStmt->execute();
$rows = $dataStmt->fetchAll();

$showingFrom = $totalRows === 0 ? 0 : $offset + 1;
$showingTo   = min($offset + $entries, $totalRows);

// ── Quick stats ───────────────────────────────────────────────────────────
$stats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'pending')  AS pending,
        SUM(status = 'approved') AS approved,
        SUM(status = 'rejected') AS rejected
    FROM reservations
")->fetch();
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
    --radius-lg:         28px;
    --radius-md:         16px;
    --radius-sm:         10px;
    --transition:        all 0.25s ease;
    --card-bg:           rgba(10,18,40,0.82);
    --card-border:       rgba(255,255,255,0.10);
    --card-border-hover: rgba(14,165,233,0.45);
    --success:           #10b981;
    --danger:            #ef4444;
    --warning:           #f59e0b;
}

.res-page {
    min-height: 100vh;
    padding: 90px 24px 48px;
    position: relative;
}

.res-page::before {
    content: '';
    position: fixed;
    inset: 0;
    background:
        radial-gradient(ellipse at 5%   0%,  rgba(37,99,235,0.35)  0%, transparent 45%),
        radial-gradient(ellipse at 95% 100%, rgba(14,165,233,0.25) 0%, transparent 45%),
        radial-gradient(ellipse at 75%  15%, rgba(124,58,237,0.18) 0%, transparent 38%),
        radial-gradient(ellipse at 25%  85%, rgba(16,185,129,0.12) 0%, transparent 38%);
    pointer-events: none;
    z-index: -1;
}

.res-main {
    max-width: 1200px;
    margin: 0 auto;
    position: relative;
    z-index: 2;
}

/* ── Page header ─────────────────────────────────────────────────────── */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-light);
}

.page-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
    letter-spacing: -0.02em;
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

/* ── Alert ───────────────────────────────────────────────────────────── */
.alert {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 20px;
    border-radius: var(--radius-sm);
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    font-weight: 500;
}
.alert-success { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25); color: #6ee7b7; }
.alert-error   { background: rgba(239,68,68,0.12);  border: 1px solid rgba(239,68,68,0.25);  color: #fca5a5; }

/* ── Stat cards ──────────────────────────────────────────────────────── */
.stat-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius-md);
    backdrop-filter: blur(24px);
    padding: 1.1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: var(--transition);
}
.stat-card:hover {
    border-color: var(--card-border-hover);
    transform: translateY(-2px);
}

.stat-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}
.stat-icon.blue   { background: rgba(37,99,235,0.18);  color: #93c5fd; }
.stat-icon.yellow { background: rgba(245,158,11,0.18); color: #fcd34d; }
.stat-icon.green  { background: rgba(16,185,129,0.18); color: #6ee7b7; }
.stat-icon.red    { background: rgba(239,68,68,0.18);  color: #fca5a5; }

.stat-info { min-width: 0; }
.stat-value {
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1;
}
.stat-label {
    font-size: 0.78rem;
    color: var(--text-muted);
    margin-top: 3px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* ── Main card ───────────────────────────────────────────────────────── */
.dash-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius-md);
    backdrop-filter: blur(24px);
    overflow: hidden;
}

.dash-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    background: rgba(37,99,235,0.20);
    border-bottom: 1px solid var(--border-light);
    flex-wrap: wrap;
    gap: 10px;
}

.dash-card-header-left {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.dash-card-header i { color: var(--accent); }
.dash-card-header h3 { color: var(--text-primary); font-size: 1rem; font-weight: 600; margin: 0; }

/* ── Filter tabs ─────────────────────────────────────────────────────── */
.filter-tabs {
    display: flex;
    gap: 6px;
}

.filter-tab {
    padding: 5px 14px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid transparent;
    transition: var(--transition);
    color: var(--text-muted);
    background: rgba(255,255,255,0.04);
    border-color: rgba(255,255,255,0.07);
}
.filter-tab:hover { color: var(--text-primary); background: rgba(255,255,255,0.08); }
.filter-tab.active {
    background: linear-gradient(135deg, var(--primary), #7c3aed);
    color: #fff;
    border-color: transparent;
}

/* ── Controls bar ────────────────────────────────────────────────────── */
.controls-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    flex-wrap: wrap;
    gap: 12px;
}

.entries-control {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-muted);
    font-size: 0.875rem;
}

.entries-select {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    color: var(--text-primary);
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 0.875rem;
    cursor: pointer;
}
.entries-select:focus { outline: none; border-color: var(--accent); }

.search-form { display: flex; gap: 8px; }

.search-input {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    color: var(--text-primary);
    padding: 8px 14px;
    border-radius: 8px;
    width: 240px;
    font-size: 0.875rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.search-input::placeholder { color: #475569; }
.search-input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(14,165,233,0.15); }

.btn-search {
    background: linear-gradient(135deg, var(--primary), #7c3aed);
    color: #fff;
    border: none;
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: opacity 0.2s, transform 0.2s;
    font-family: inherit;
}
.btn-search:hover { opacity: 0.88; transform: translateY(-1px); }

/* ── Table ───────────────────────────────────────────────────────────── */
.table-wrapper { overflow-x: auto; }

.res-table { width: 100%; border-collapse: collapse; }

.res-table thead tr {
    background: rgba(255,255,255,0.03);
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.res-table th {
    padding: 13px 16px;
    text-align: left;
    color: var(--text-muted);
    font-weight: 600;
    font-size: 0.72rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    white-space: nowrap;
}

.res-table tbody tr {
    border-bottom: 1px solid rgba(255,255,255,0.04);
    transition: background 0.18s;
}
.res-table tbody tr:last-child { border-bottom: none; }
.res-table tbody tr:hover { background: rgba(255,255,255,0.04); }

.res-table td {
    padding: 12px 16px;
    color: var(--text-secondary);
    font-size: 0.875rem;
    vertical-align: middle;
    white-space: nowrap;
}

.td-id   { color: var(--text-label) !important; font-weight: 700; font-size: 0.82rem; }
.td-name { color: var(--text-primary) !important; font-weight: 600; }

/* ── Status badges ───────────────────────────────────────────────────── */
.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.03em;
}
.badge-pending  { background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.30); color: #fcd34d; }
.badge-approved { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25); color: #6ee7b7; }
.badge-rejected { background: rgba(239,68,68,0.12);  border: 1px solid rgba(239,68,68,0.25);  color: #fca5a5; }

/* ── Action buttons ──────────────────────────────────────────────────── */
.action-group { display: flex; gap: 6px; align-items: center; }

.btn-approve {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 12px;
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.25);
    border-radius: var(--radius-sm);
    color: #6ee7b7;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: var(--transition);
}
.btn-approve:hover { background: rgba(16,185,129,0.25); color: #fff; }

.btn-reject {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 12px;
    background: rgba(245,158,11,0.12);
    border: 1px solid rgba(245,158,11,0.25);
    border-radius: var(--radius-sm);
    color: #fcd34d;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: var(--transition);
}
.btn-reject:hover { background: rgba(245,158,11,0.25); color: #fff; }

.btn-delete {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px;
    background: rgba(239,68,68,0.10);
    border: 1px solid rgba(239,68,68,0.20);
    border-radius: var(--radius-sm);
    color: #fca5a5;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: var(--transition);
}
.btn-delete:hover { background: rgba(239,68,68,0.25); color: #fff; }

/* ── Empty state ─────────────────────────────────────────────────────── */
.empty-state { padding: 60px 20px; text-align: center; }
.empty-state i { font-size: 3rem; color: #475569; display: block; margin-bottom: 16px; }
.empty-state h3 { color: var(--text-primary); margin-bottom: 6px; }
.empty-state p  { color: #64748b; font-size: 0.875rem; }

/* ── Table footer ────────────────────────────────────────────────────── */
.table-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-top: 1px solid rgba(255,255,255,0.06);
    flex-wrap: wrap;
    gap: 12px;
}

.showing-text { color: #64748b; font-size: 0.875rem; }
.showing-text strong { color: var(--text-muted); }

.pagination { display: flex; gap: 4px; align-items: center; }

.page-btn {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 6px 12px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 8px;
    color: #64748b;
    font-size: 0.82rem;
    text-decoration: none;
    transition: var(--transition);
    white-space: nowrap;
    font-weight: 500;
}
.page-btn:hover:not(.disabled):not(.active) {
    background: rgba(255,255,255,0.08);
    color: var(--text-secondary);
}
.page-btn.active {
    background: linear-gradient(135deg, var(--primary), #7c3aed);
    color: #fff;
    border-color: transparent;
    cursor: default;
}
.page-btn.disabled { opacity: 0.35; cursor: not-allowed; pointer-events: none; }

@media (max-width: 768px) {
    .stat-grid { grid-template-columns: repeat(2, 1fr); }
    .search-input { width: 160px; }
}
@media (max-width: 480px) {
    .stat-grid { grid-template-columns: 1fr 1fr; }
}
</style>

<div class="res-page">
    <div class="res-main">

        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-calendar-check" style="color:#0ea5e9;margin-right:8px;"></i>Reservation Management</h1>
            <div class="date-badge">
                <i class="far fa-calendar-alt"></i>
                <?php echo date('F j, Y'); ?>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="stat-label">Total</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow"><i class="fas fa-hourglass-half"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['pending'] ?? 0; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['approved'] ?? 0; ?></div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['rejected'] ?? 0; ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
        </div>

        <!-- Main Table Card -->
        <div class="dash-card">

            <!-- Card Header with Filter Tabs -->
            <div class="dash-card-header">
                <div class="dash-card-header-left">
                    <i class="fas fa-list"></i>
                    <h3>Reservation Requests</h3>
                </div>
                <div class="filter-tabs">
                    <?php
                    $tabs = ['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
                    foreach ($tabs as $val => $label):
                        $isActive = $filter === $val;
                        $href = '?filter=' . $val . '&entries=' . $entries . '&search=' . urlencode($search) . '&page=1';
                    ?>
                    <a href="<?php echo $href; ?>" class="filter-tab <?php echo $isActive ? 'active' : ''; ?>">
                        <?php echo $label; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Controls -->
            <div class="controls-bar">
                <div class="entries-control">
                    <span>Show</span>
                    <select class="entries-select" onchange="changeEntries(this.value)">
                        <?php foreach ([10, 25, 50, 100] as $opt): ?>
                        <option value="<?php echo $opt; ?>" <?php echo $entries === $opt ? 'selected' : ''; ?>
                                style="background:#1e293b;">
                            <?php echo $opt; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <span>entries</span>
                </div>

                <form method="GET" action="" class="search-form">
                    <input type="hidden" name="filter"   value="<?php echo htmlspecialchars($filter); ?>">
                    <input type="hidden" name="entries"  value="<?php echo $entries; ?>">
                    <input type="hidden" name="page"     value="1">
                    <input type="text"
                           name="search"
                           class="search-input"
                           placeholder="Search name, ID, purpose, lab..."
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn-search">
                        <i class="fas fa-search"></i> Search
                    </button>
                </form>
            </div>

            <!-- Table -->
            <div class="table-wrapper">
                <table class="res-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ID Number</th>
                            <th>Student Name</th>
                            <th>Course / Year</th>
                            <th>Purpose</th>
                            <th>Laboratory</th>
                            <th>Date</th>
                            <th>Time In</th>
                            <th>Sessions Left</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="11">
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <h3>No reservations found</h3>
                                    <p>
                                        <?php if ($search || $filter !== 'all'): ?>
                                            No records match your filter. <a href="admin_reservation.php" style="color:#0ea5e9;">Clear filters</a>
                                        <?php else: ?>
                                            No reservation requests yet.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $i => $row): ?>
                            <tr>
                                <td style="color:#475569;"><?php echo $offset + $i + 1; ?></td>
                                <td class="td-id"><?php echo htmlspecialchars($row['id_number']); ?></td>
                                <td class="td-name"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td style="color:#94a3b8;font-size:0.82rem;">
                                    <?php echo htmlspecialchars($row['course'] ?? '—'); ?>
                                    <?php if (!empty($row['year_level'])): ?>
                                    <span style="color:#475569;"> &bull; <?php echo htmlspecialchars($row['year_level']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                <td><?php echo htmlspecialchars($row['laboratory']); ?></td>
                                <td style="color:#94a3b8;">
                                    <?php echo !empty($row['reservation_date'])
                                        ? date('M j, Y', strtotime($row['reservation_date']))
                                        : '—'; ?>
                                </td>
                                <td>
                                    <?php echo !empty($row['time_in'])
                                        ? date('h:i A', strtotime($row['time_in']))
                                        : '—'; ?>
                                </td>
                                <td>
                                    <?php
                                    $sess = (int)($row['remaining_sessions'] ?? 0);
                                    $color = $sess > 10 ? '#6ee7b7' : ($sess > 5 ? '#fcd34d' : '#fca5a5');
                                    echo '<span style="color:' . $color . ';font-weight:700;">' . $sess . '</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $status = $row['status'] ?? 'pending';
                                    $badgeClass = match($status) {
                                        'approved' => 'badge-approved',
                                        'rejected' => 'badge-rejected',
                                        default    => 'badge-pending',
                                    };
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo ucfirst(htmlspecialchars($status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <?php if ($status === 'pending'): ?>
                                        <a href="?approve_id=<?php echo $row['id']; ?>&filter=<?php echo urlencode($filter); ?>&entries=<?php echo $entries; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>"
                                           class="btn-approve"
                                           onclick="return confirm('Approve this reservation?')">
                                            <i class="fas fa-check"></i> Approve
                                        </a>
                                        <a href="?reject_id=<?php echo $row['id']; ?>&filter=<?php echo urlencode($filter); ?>&entries=<?php echo $entries; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>"
                                           class="btn-reject"
                                           onclick="return confirm('Reject this reservation?')">
                                            <i class="fas fa-times"></i> Reject
                                        </a>
                                        <?php endif; ?>
                                        <a href="?delete_id=<?php echo $row['id']; ?>&filter=<?php echo urlencode($filter); ?>&entries=<?php echo $entries; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>"
                                           class="btn-delete"
                                           onclick="return confirm('Delete this reservation permanently?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer -->
            <div class="table-footer">
                <div class="showing-text">
                    Showing <strong><?php echo $showingFrom; ?></strong>
                    to <strong><?php echo $showingTo; ?></strong>
                    of <strong><?php echo $totalRows; ?></strong> entries
                    <?php if ($search): ?>
                    <span style="color:#475569;"> &mdash; filtered by "<?php echo htmlspecialchars($search); ?>"</span>
                    <?php endif; ?>
                </div>

                <?php if ($totalPages > 1):
                    $baseUrl = '?filter=' . urlencode($filter) . '&entries=' . $entries . '&search=' . urlencode($search) . '&page=';
                ?>
                <div class="pagination">
                    <a href="<?php echo $baseUrl . 1; ?>" class="page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="<?php echo $baseUrl . max(1, $page - 1); ?>" class="page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <i class="fas fa-angle-left"></i> Prev
                    </a>
                    <?php
                    $start = max(1, $page - 2);
                    $end   = min($totalPages, $start + 4);
                    $start = max(1, $end - 4);
                    for ($p = $start; $p <= $end; $p++): ?>
                    <a href="<?php echo $baseUrl . $p; ?>" class="page-btn <?php echo $p === $page ? 'active' : ''; ?>">
                        <?php echo $p; ?>
                    </a>
                    <?php endfor; ?>
                    <a href="<?php echo $baseUrl . min($totalPages, $page + 1); ?>" class="page-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        Next <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="<?php echo $baseUrl . $totalPages; ?>" class="page-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /.dash-card -->
    </div><!-- /.res-main -->
</div><!-- /.res-page -->

<script>
function changeEntries(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('entries', value);
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}
</script>

<?php include '../includes/footer.php'; ?>