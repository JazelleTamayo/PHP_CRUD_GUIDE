<?php
session_start();

// Auth guard
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=" . urlencode("Please login first"));
    exit();
}

require_once __DIR__ . '/../config/database.php';

$is_admin   = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
$user_id    = $_SESSION['user_id'];
$user_name  = $_SESSION['user_name'];
$id_number  = $_SESSION['id_number'];

$pageTitle  = "Sit-in History - CCS Sit-in System";
$extraCSS   = "dashboard";
$basePath   = "../";

// ── Pagination & search parameters ────────────────────────────────────────
$entriesRaw = isset($_GET['entries']) ? (int)$_GET['entries'] : 10;
$entries    = in_array($entriesRaw, [10, 25, 50, 100]) ? $entriesRaw : 10;
$page       = max(1, (int)($_GET['page'] ?? 1));
$offset     = ($page - 1) * $entries;
$search     = trim($_GET['search'] ?? '');

// ── Build WHERE clause ────────────────────────────────────────────────────
// Admin sees ALL records; student sees only their own
$params = [];
$where  = [];

if (!$is_admin) {
    $where[]  = "s.user_id = ?";
    $params[] = $user_id;
}

// Only show completed sit-ins in history
$where[] = "s.status = 'completed'";

if ($search !== '') {
    $where[]  = "(s.purpose LIKE ? OR s.laboratory LIKE ? OR s.name LIKE ? OR s.id_number LIKE ?)";
    $like     = "%$search%";
    $params   = array_merge($params, [$like, $like, $like, $like]);
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ── Count total rows ───────────────────────────────────────────────────────
$countSql  = "SELECT COUNT(*) FROM sit_in s $whereSql";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRows  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $entries));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $entries;

// ── Fetch rows with reward_points_given ────────────────────────────────────
$dataSql  = "
    SELECT s.id, s.id_number, s.name, s.purpose, s.laboratory,
           s.login_time, s.logout_time, s.login_date, s.reward_points_given,
           u.course, u.year_level
    FROM sit_in s
    JOIN users u ON s.user_id = u.id
    $whereSql
    ORDER BY s.login_date DESC, s.login_time DESC
    LIMIT ? OFFSET ?
";
$dataStmt = $pdo->prepare($dataSql);
$paramIndex = 1;
foreach ($params as $val) {
    $dataStmt->bindValue($paramIndex++, $val);
}
$dataStmt->bindValue($paramIndex++, (int)$entries, PDO::PARAM_INT);
$dataStmt->bindValue($paramIndex++, (int)$offset,  PDO::PARAM_INT);
$dataStmt->execute();
$rows = $dataStmt->fetchAll();

$showingFrom = $totalRows === 0 ? 0 : $offset + 1;
$showingTo   = min($offset + $entries, $totalRows);
?>
<?php include '../includes/header.php'; ?>
<?php include ($is_admin ? '../includes/admin_navigation.php' : '../includes/user_navigation.php'); ?>

<style>
/* ── Page layout ────────────────────────────────────────────────────── */
.history-page {
    min-height: 100vh;
    padding: 1.5rem 24px 48px;
    position: relative;
}

.history-page::before {
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

.history-main {
    max-width: 1300px;
    margin: 0 auto;
    position: relative;
    z-index: 2;
}

/* ── Page header ────────────────────────────────────────────────────── */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.10);
}

.page-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #f1f5f9;
    letter-spacing: -0.02em;
}

.date-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 18px;
    background: rgba(10,18,40,0.70);
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 999px;
    color: #cbd5e1;
    font-size: 0.875rem;
    backdrop-filter: blur(12px);
}
.date-badge i { color: #0ea5e9; }

/* ── Card ───────────────────────────────────────────────────────────── */
.history-card {
    background: rgba(10,18,40,0.82);
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 16px;
    backdrop-filter: blur(24px);
    overflow: hidden;
}

/* ── Controls bar ───────────────────────────────────────────────────── */
.controls-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    flex-wrap: wrap;
    gap: 14px;
}

.entries-control {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #94a3b8;
    font-size: 0.875rem;
}

.entries-select {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    color: #f1f5f9;
    padding: 7px 12px;
    border-radius: 10px;
    font-size: 0.875rem;
    cursor: pointer;
    transition: border-color 0.2s;
}
.entries-select:focus {
    outline: none;
    border-color: #0ea5e9;
}

.search-form {
    display: flex;
    gap: 8px;
}

.search-input {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    color: #f1f5f9;
    padding: 9px 15px;
    border-radius: 10px;
    width: 260px;
    font-size: 0.875rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.search-input::placeholder { color: #64748b; }
.search-input:focus {
    outline: none;
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(14,165,233,0.15);
}

.btn-search {
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    color: #fff;
    border: none;
    padding: 9px 20px;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 7px;
    transition: opacity 0.2s, transform 0.2s;
    font-family: inherit;
}
.btn-search:hover { opacity: 0.88; transform: translateY(-1px); }

/* ── Table ──────────────────────────────────────────────────────────── */
.table-wrapper { overflow-x: auto; }

.history-table {
    width: 100%;
    border-collapse: collapse;
}

.history-table thead tr {
    background: rgba(255,255,255,0.03);
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.history-table th {
    padding: 14px 18px;
    text-align: left;
    color: #64748b;
    font-weight: 600;
    font-size: 0.72rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    white-space: nowrap;
}

.history-table tbody tr {
    border-bottom: 1px solid rgba(255,255,255,0.04);
    transition: background 0.18s;
}
.history-table tbody tr:last-child { border-bottom: none; }
.history-table tbody tr:hover { background: rgba(255,255,255,0.04); }

.history-table td {
    padding: 13px 18px;
    color: #cbd5e1;
    font-size: 0.875rem;
    vertical-align: middle;
    white-space: nowrap;
}

.td-id   { color: #7dd3fc !important; font-weight: 700; font-size: 0.82rem; }
.td-name { color: #f1f5f9 !important; font-weight: 600; }

/* Reward Points Badge */
.reward-points {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(250,204,21,0.12);
    border: 1px solid rgba(250,204,21,0.25);
    border-radius: 999px;
    padding: 3px 10px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #fde68a;
}
.reward-points i {
    font-size: 0.7rem;
    color: #facc15;
}

/* ── Badges ─────────────────────────────────────────────────────────── */
.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.03em;
}
.badge-completed {
    background: rgba(14,165,233,0.12);
    border: 1px solid rgba(14,165,233,0.25);
    color: #7dd3fc;
}
.badge-active {
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.25);
    color: #6ee7b7;
}

/* ── Empty state ────────────────────────────────────────────────────── */
.empty-state {
    padding: 60px 20px;
    text-align: center;
}
.empty-state i {
    font-size: 3rem;
    color: #475569;
    display: block;
    margin-bottom: 16px;
}
.empty-state h3 { color: #f1f5f9; margin-bottom: 6px; }
.empty-state p  { color: #64748b; font-size: 0.875rem; }

/* ── Footer (pagination) ────────────────────────────────────────────── */
.table-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 24px;
    border-top: 1px solid rgba(255,255,255,0.06);
    flex-wrap: wrap;
    gap: 12px;
}

.showing-text { color: #64748b; font-size: 0.875rem; }
.showing-text strong { color: #94a3b8; }

.pagination {
    display: flex;
    gap: 4px;
    align-items: center;
}

.page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 7px 13px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 8px;
    color: #64748b;
    font-size: 0.85rem;
    text-decoration: none;
    transition: all 0.18s;
    cursor: pointer;
    font-family: inherit;
    font-weight: 500;
    white-space: nowrap;
}
.page-btn:hover:not(.disabled):not(.active) {
    background: rgba(255,255,255,0.08);
    color: #cbd5e1;
    border-color: rgba(255,255,255,0.14);
}
.page-btn.active {
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    color: #fff;
    border-color: transparent;
    cursor: default;
}
.page-btn.disabled {
    opacity: 0.35;
    cursor: not-allowed;
    pointer-events: none;
}

/* ── Duration pill ──────────────────────────────────────────────────── */
.duration-pill {
    display: inline-block;
    background: rgba(124,58,237,0.15);
    border: 1px solid rgba(124,58,237,0.25);
    border-radius: 999px;
    padding: 2px 9px;
    font-size: 0.75rem;
    color: #c4b5fd;
    font-weight: 600;
}
</style>

<div class="history-page">
    <div class="history-main">

        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <?php if ($is_admin): ?>
                    <i class="fas fa-history" style="color:#0ea5e9;margin-right:8px;"></i>Sit-in History
                <?php else: ?>
                    Sit-in History
                <?php endif; ?>
            </h1>
            <div class="date-badge">
                <i class="far fa-calendar-alt"></i>
                <?php echo date('F j, Y'); ?>
            </div>
        </div>

        <!-- Card -->
        <div class="history-card">

            <!-- Controls -->
            <div class="controls-bar">
                <div class="entries-control">
                    <span>Show</span>
                    <select class="entries-select" onchange="changeEntries(this.value)">
                        <?php foreach ([10,25,50,100] as $opt): ?>
                        <option value="<?php echo $opt; ?>"
                            <?php echo $entries === $opt ? 'selected' : ''; ?>
                            style="background:#1e293b;">
                            <?php echo $opt; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <span>entries</span>
                </div>

                <form method="GET" action="" class="search-form">
                    <input type="hidden" name="entries" value="<?php echo $entries; ?>">
                    <input type="hidden" name="page" value="1">
                    <input type="text"
                           name="search"
                           class="search-input"
                           placeholder="Search by name, purpose, lab..."
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn-search">
                        <i class="fas fa-search"></i> Search
                    </button>
                </form>
            </div>

            <!-- Table -->
            <div class="table-wrapper">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ID Number</th>
                            <th>Name</th>
                            <?php if ($is_admin): ?>
                            <th>Course / Year</th>
                            <?php endif; ?>
                            <th>Purpose</th>
                            <th>Laboratory</th>
                            <th>Date</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Duration</th>
                            <th>Reward Points</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="<?php echo $is_admin ? 13 : 12; ?>">
                                <div class="empty-state">
                                    <i class="fas fa-database"></i>
                                    <h3>No data available</h3>
                                    <p>
                                        <?php if ($search): ?>
                                            No records match your search. <a href="history.php" style="color:#0ea5e9;">Clear search</a>
                                        <?php else: ?>
                                            No completed sit-in sessions found.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $i => $row):
                                // Calculate duration
                                $duration = '';
                                if ($row['login_time'] && $row['logout_time']) {
                                    $in  = new DateTime($row['login_time']);
                                    $out = new DateTime($row['logout_time']);
                                    $diff = $in->diff($out);
                                    if ($diff->h > 0) {
                                        $duration = $diff->h . 'h ' . $diff->i . 'm';
                                    } else {
                                        $duration = $diff->i . 'm';
                                    }
                                }
                                // Get reward points from database
                                $rewardPoints = isset($row['reward_points_given']) ? $row['reward_points_given'] : 1;
                            ?>
                            <tr>
                                <td style="color:#475569;"><?php echo $offset + $i + 1; ?></td>
                                <td class="td-id"><?php echo htmlspecialchars($row['id_number']); ?></td>
                                <td class="td-name"><?php echo htmlspecialchars($row['name']); ?></td>
                                <?php if ($is_admin): ?>
                                <td style="color:#94a3b8;font-size:0.82rem;">
                                    <?php echo htmlspecialchars($row['course'] ?? '—'); ?>
                                    <?php if (!empty($row['year_level'])): ?>
                                    <span style="color:#475569;"> &bull; <?php echo htmlspecialchars($row['year_level']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                <td><?php echo htmlspecialchars($row['laboratory']); ?></td>
                                <td style="color:#94a3b8;">
                                    <?php echo $row['login_date']
                                        ? date('M j, Y', strtotime($row['login_date']))
                                        : '—'; ?>
                                </td>
                                <td>
                                    <?php echo $row['login_time']
                                        ? date('h:i A', strtotime($row['login_time']))
                                        : '—'; ?>
                                </td>
                                <td>
                                    <?php echo $row['logout_time']
                                        ? date('h:i A', strtotime($row['logout_time']))
                                        : '<span style="color:#475569;">—</span>'; ?>
                                </td>
                                <td>
                                    <?php if ($duration): ?>
                                        <span class="duration-pill"><?php echo $duration; ?></span>
                                    <?php else: ?>
                                        <span style="color:#475569;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="reward-points">
                                        <i class="fas fa-star"></i>
                                        +<?php echo $rewardPoints; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $status = $row['status'] ?? 'completed';
                                    $cls    = $status === 'active' ? 'badge-active' : 'badge-completed';
                                    echo '<span class="badge ' . $cls . '">' . ucfirst(htmlspecialchars($status)) . '</span>';
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer: showing text + pagination -->
            <div class="table-footer">
                <div class="showing-text">
                    Showing <strong><?php echo $showingFrom; ?></strong>
                    to <strong><?php echo $showingTo; ?></strong>
                    of <strong><?php echo $totalRows; ?></strong> entries
                    <?php if ($search): ?>
                        <span style="color:#475569;"> &mdash; filtered by "<?php echo htmlspecialchars($search); ?>"</span>
                    <?php endif; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php
                    $baseUrl = '?entries=' . $entries . '&search=' . urlencode($search) . '&page=';
                    ?>

                    <!-- First -->
                    <a href="<?php echo $baseUrl . 1; ?>"
                       class="page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <i class="fas fa-angle-double-left"></i>
                    </a>

                    <!-- Prev -->
                    <a href="<?php echo $baseUrl . max(1, $page - 1); ?>"
                       class="page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <i class="fas fa-angle-left"></i> Prev
                    </a>

                    <?php
                    // Show up to 5 page buttons centred around current page
                    $start = max(1, $page - 2);
                    $end   = min($totalPages, $start + 4);
                    $start = max(1, $end - 4);

                    for ($p = $start; $p <= $end; $p++):
                    ?>
                    <a href="<?php echo $baseUrl . $p; ?>"
                       class="page-btn <?php echo $p === $page ? 'active' : ''; ?>">
                        <?php echo $p; ?>
                    </a>
                    <?php endfor; ?>

                    <!-- Next -->
                    <a href="<?php echo $baseUrl . min($totalPages, $page + 1); ?>"
                       class="page-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        Next <i class="fas fa-angle-right"></i>
                    </a>

                    <!-- Last -->
                    <a href="<?php echo $baseUrl . $totalPages; ?>"
                       class="page-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /.history-card -->
    </div><!-- /.history-main -->
</div><!-- /.history-page -->

<script>
function changeEntries(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('entries', value);
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}
</script>

<?php include '../includes/footer.php'; ?>