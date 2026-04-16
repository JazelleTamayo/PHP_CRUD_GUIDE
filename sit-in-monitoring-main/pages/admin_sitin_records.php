<?php
session_start();

// Auth guard — matches your admin_dashboard.php pattern exactly
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php?error=" . urlencode("Unauthorized access"));
    exit();
}

require_once __DIR__ . '/../config/database.php';

// ── Flash messages ────────────────────────────────────────────────────────
$flash      = $_SESSION['flash']      ?? '';
$flash_type = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash'], $_SESSION['flash_type']);

// ── Filters ───────────────────────────────────────────────────────────────
$filter_status = $_GET['status'] ?? '';
$filter_lab    = $_GET['lab']    ?? '';
$filter_date   = $_GET['date']   ?? '';

$where  = ['1=1'];
$params = [];
if ($filter_status) { $where[] = 's.status = ?';       $params[] = $filter_status; }
if ($filter_lab)    { $where[] = 's.laboratory = ?';   $params[] = $filter_lab;    }
if ($filter_date)   { $where[] = 's.login_date = ?';   $params[] = $filter_date;   }
$where_sql = implode(' AND ', $where);

// ── Main query
$stmt = $pdo->prepare("
    SELECT s.id,
           s.id_number,
           s.name,
           s.purpose,
           s.laboratory,
           s.login_time,
           s.logout_time,
           s.login_date,
           s.status,
           s.created_at,
           u.course,
           u.year_level
    FROM   sit_in s
    JOIN   users  u ON u.id = s.user_id
    WHERE  $where_sql
    ORDER  BY s.created_at DESC
");
$stmt->execute($params);
$records = $stmt->fetchAll();

// ── Distinct labs for filter
$labs = $pdo->query("SELECT DISTINCT laboratory FROM sit_in ORDER BY laboratory")
             ->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = "Sit-in Records - CCS Admin";
$basePath  = "../";
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/admin_navigation.php'; ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<style>
/* ── CSS Variables ── */
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
    --success:           #10b981;
    --danger:            #ef4444;
    --card-bg:           rgba(10,18,40,0.82);
    --card-bg-hover:     rgba(14,24,52,0.90);
    --card-border:       rgba(255,255,255,0.10);
    --card-border-hover: rgba(14,165,233,0.45);
}

.records-page {
    min-height: 100vh;
    padding: 90px 24px 48px;
    position: relative;
}

.records-page::before {
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

.page-inner { max-width: 1400px; margin: 0 auto; }

.page-hdr {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-light);
}

.page-hdr h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin: 0;
}

.page-hdr h1 i { color: var(--accent); }

.card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius-md);
    backdrop-filter: blur(24px);
    overflow: hidden;
    transition: var(--transition);
}

.card:hover { border-color: var(--card-border-hover); }

.card-hdr {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 1rem 1.4rem;
    background: rgba(37,99,235,0.20);
    border-bottom: 1px solid var(--border-light);
}

.card-hdr h3 { color: var(--text-primary); font-size: 1rem; font-weight: 600; margin: 0; }

/* ── Filter bar ── */
.filter-bar {
    display: flex;
    align-items: flex-end;
    gap: 10px;
    padding: 14px 18px;
    background: rgba(255,255,255,0.02);
    border-bottom: 1px solid var(--border-light);
    flex-wrap: wrap;
}

.fg { display: flex; flex-direction: column; gap: 4px; }
.fg label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); }
.fg select, .fg input[type="date"] {
    padding: 8px 12px;
    background: rgba(255,255,255,0.05);
    border: 1px solid var(--border-light);
    border-radius: var(--radius-sm);
    color: var(--text-primary);
    font-size: 0.84rem;
}

/* ── Buttons ── */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: var(--radius-sm);
    font-size: 0.84rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: var(--transition);
    text-decoration: none;
}

.btn-primary { background: linear-gradient(135deg, var(--primary), #1d4ed8); color: #fff; box-shadow: 0 4px 12px rgba(37,99,235,0.35); }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(37,99,235,0.50); color: #fff; }
.btn-ghost { background: rgba(255,255,255,0.05); color: var(--text-secondary); border: 1px solid var(--border-light); }
.btn-success { background: linear-gradient(135deg, #059669, #047857); color: #fff; }
.btn-danger { background: linear-gradient(135deg, #dc2626, #b91c1c); color: #fff; }
.btn-sm { padding: 5px 10px; font-size: 0.75rem; }

/* ── Table ── */
.table-wrap { padding: 16px; overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
thead th {
    background: rgba(37,99,235,0.22);
    color: var(--text-label);
    padding: 11px 13px;
    font-size: 0.74rem;
    text-transform: uppercase;
    border-bottom: 1px solid var(--border-light);
    text-align: left;
}
tbody td { padding: 11px 13px; border-bottom: 1px solid rgba(255,255,255,0.04); font-size: 0.83rem; color: var(--text-secondary); }
.badge { padding: 3px 10px; border-radius: 999px; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; }
.b-active { background: rgba(16,185,129,0.18); color: #6ee7b7; border: 1px solid rgba(16,185,129,0.3); }
.b-completed { background: rgba(14,165,233,0.18); color: #7dd3fc; border: 1px solid rgba(14,165,233,0.3); }

/* DataTables dark override */
.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select {
    background: rgba(255,255,255,0.05);
    border: 1px solid var(--border-light);
    color: var(--text-primary);
    border-radius: var(--radius-sm);
}
.dt-bar { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; }
</style>

<div class="records-page">
    <div class="page-inner">

        <div class="page-hdr">
            <h1>
                <i class="fas fa-list-alt"></i>
                View Sit-in Records
            </h1>
        </div>

        <?php if ($flash): ?>
        <div class="flash flash-<?= htmlspecialchars($flash_type) ?>" id="flashMsg" style="padding: 12px; border-radius: 8px; margin-bottom: 15px; background: rgba(255,255,255,0.05); color: #fff;">
            <?= htmlspecialchars($flash) ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-hdr">
                <i class="fas fa-table"></i>
                <h3>All Sit-in Records</h3>
            </div>

            <form method="GET" class="filter-bar">
                <div class="fg">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="completed" <?= $filter_status === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $filter_status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="fg">
                    <label>Laboratory</label>
                    <select name="lab">
                        <option value="">All Labs</option>
                        <?php foreach ($labs as $lab): ?>
                        <option value="<?= htmlspecialchars($lab) ?>" <?= $filter_lab === (string)$lab ? 'selected' : '' ?>>
                            Lab <?= htmlspecialchars($lab) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label>Date</label>
                    <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>">
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="admin_sitin_records.php" class="btn btn-ghost">Reset</a>
            </form>

            <div class="table-wrap">
                <table id="recordsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ID Number</th>
                            <th>Name</th>
                            <th>Course</th>
                            <th>Purpose</th>
                            <th>Lab</th>
                            <th>Date</th>
                            <th>Login</th>
                            <th>Logout</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $i => $r): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= htmlspecialchars($r['id_number']) ?></strong></td>
                            <td><?= htmlspecialchars($r['name']) ?></td>
                            <td><?= htmlspecialchars($r['course'] . ' ' . $r['year_level']) ?></td>
                            <td><?= htmlspecialchars($r['purpose']) ?></td>
                            <td>Lab <?= htmlspecialchars($r['laboratory']) ?></td>
                            <td><?= date('M d, Y', strtotime($r['login_date'])) ?></td>
                            <td><?= htmlspecialchars($r['login_time']) ?></td>
                            <td><?= $r['logout_time'] ?? '—' ?></td>
                            <td><span class="badge b-<?= htmlspecialchars($r['status']) ?>"><?= ucfirst($r['status']) ?></span></td>
                            <td>
                                <div style="display:flex; gap:5px;">
                                    <?php if ($r['status'] === 'active'): ?>
                                    <form method="POST" action="../process/sitin_action.php">
                                        <input type="hidden" name="action" value="timeout">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <button class="btn btn-success btn-sm"><i class="fas fa-sign-out-alt"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="POST" action="../process/sitin_action.php">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <button class="btn btn-danger btn-sm" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(function () {
    $('#recordsTable').DataTable({
        responsive: true,
        order: [[6, 'desc']],
        dom: '<"dt-bar"lf>rtip'
    });
});
</script>

<?php include '../includes/footer.php'; ?>