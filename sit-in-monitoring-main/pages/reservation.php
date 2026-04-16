<?php
session_start();

// Auth guard - redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=" . urlencode("Please login first"));
    exit();
}

// Redirect admin to admin dashboard
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header("Location: admin_dashboard.php");
    exit();
}

$pageTitle = "Reservation - CCS Sit-in System";
$extraCSS  = "dashboard";
$basePath  = "../";

require_once __DIR__ . '/../config/database.php';

// ── Get fresh user data from DB ───────────────────────────────────────────
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Always use DB value for sessions
$remaining_sessions = (int)($user['sessions'] ?? 0);

// ── Handle form submission ────────────────────────────────────────────────
$success_message = '';
$error_message   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve'])) {
    $purpose  = trim($_POST['purpose']  ?? '');
    $lab      = trim($_POST['lab']      ?? '');
    $time_in  = trim($_POST['time_in']  ?? '');
    $date     = trim($_POST['date']     ?? '');

    $errors = [];

    if (empty($purpose))  $errors[] = "Purpose is required";
    if (empty($lab))      $errors[] = "Laboratory is required";
    if (empty($time_in))  $errors[] = "Time in is required";
    if (empty($date))     $errors[] = "Date is required";

    // Validate and convert dd/mm/yyyy → YYYY-MM-DD
    $formatted_date = '';
    if (!empty($date)) {
        $date_parts = explode('/', $date);
        if (count($date_parts) === 3 && checkdate((int)$date_parts[1], (int)$date_parts[0], (int)$date_parts[2])) {
            $formatted_date = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
        } else {
            $errors[] = "Invalid date format. Please use dd/mm/yyyy";
        }
    }

    if ($remaining_sessions <= 0) {
        $errors[] = "You have no remaining sessions left";
    }

    if (empty($errors)) {
        try {
            // Insert into reservations table
            $insert_stmt = $pdo->prepare("
                INSERT INTO reservations (user_id, id_number, name, purpose, laboratory, time_in, reservation_date, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $insert_stmt->execute([
                $user_id,
                $user['id_number'],
                trim($user['first_name'] . ' ' . $user['last_name']),
                $purpose,
                $lab,
                $time_in,
                $formatted_date
            ]);

            $success_message = "Reservation submitted successfully! Please wait for admin approval.";

            // Refresh sessions from DB after insert
            $stmt = $pdo->prepare("SELECT sessions FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $remaining_sessions = (int)($stmt->fetchColumn() ?? 0);

        } catch (PDOException $e) {
            error_log("Reservation error: " . $e->getMessage());
            $error_message = "Failed to submit reservation. Please try again.";
        }
    } else {
        $error_message = implode(", ", $errors);
    }
}

// ── Fetch this student's existing reservations ────────────────────────────
$my_reservations = $pdo->prepare("
    SELECT * FROM reservations
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");
$my_reservations->execute([$user_id]);
$my_reservations = $my_reservations->fetchAll();

$laboratories = ['524', '526', '528', '530', 'Lab 517'];
$purposes     = ['C Programming','Java','PHP','ASP.Net','C#','Python','Research','Thesis','Capstone','Other'];
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/user_navigation.php'; ?>

<style>
.res-wrap {
    min-height: 100vh;
    padding: 1.5rem 24px 48px;
}

.res-inner {
    max-width: 820px;
    margin: 0 auto;
}

/* header */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.dashboard-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #f1f5f9;
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

/* alerts */
.res-alert {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}
.res-alert.success { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25); color: #6ee7b7; }
.res-alert.error   { background: rgba(239,68,68,0.12);  border: 1px solid rgba(239,68,68,0.25);  color: #fca5a5; }

/* form card */
.res-card {
    background: rgba(10,18,40,0.82);
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 20px;
    backdrop-filter: blur(24px);
    padding: 32px;
    margin-bottom: 20px;
}

.res-card-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 28px;
    padding-bottom: 18px;
    border-bottom: 1px solid rgba(255,255,255,0.07);
}
.res-card-title i  { color: #0ea5e9; font-size: 1.1rem; }
.res-card-title h2 { color: #f1f5f9; font-size: 1.15rem; font-weight: 700; margin: 0; }

/* form fields */
.form-group { margin-bottom: 20px; }

.form-label {
    display: block;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 8px;
}
.form-label i { margin-right: 5px; color: #60a5fa; }

.form-control {
    width: 100%;
    padding: 13px 16px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 14px;
    color: #f1f5f9;
    font-size: 0.92rem;
    font-family: inherit;
    transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
    appearance: none;
}
.form-control:focus {
    outline: none;
    border-color: rgba(96,165,250,0.45);
    background: rgba(255,255,255,0.08);
    box-shadow: 0 0 0 3px rgba(96,165,250,0.12);
}
.form-control::placeholder { color: #475569; }
.form-control[readonly],
.form-control[disabled] {
    background: rgba(255,255,255,0.03);
    color: #64748b;
    cursor: not-allowed;
    border-color: rgba(255,255,255,0.05);
}

.sessions-display {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 14px;
    padding: 13px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* buttons */
.btn-row {
    display: flex;
    gap: 15px;
    justify-content: center;
    align-items: center;
    margin-top: 28px;
}

.btn-reserve {
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    color: #fff;
    border: none;
    padding: 14px 48px;
    border-radius: 999px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 20px rgba(37,99,235,0.4);
    transition: all 0.25s ease;
    font-family: inherit;
}
.btn-reserve:hover { transform: translateY(-2px); box-shadow: 0 12px 36px rgba(37,99,235,0.55); }
.btn-reserve:disabled {
    background: rgba(100,116,139,0.3);
    color: #64748b;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}

.btn-cancel {
    background: rgba(255,255,255,0.07);
    color: #94a3b8;
    text-decoration: none;
    padding: 14px 32px;
    border-radius: 999px;
    font-size: 1rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 1px solid rgba(255,255,255,0.08);
    transition: all 0.25s ease;
}
.btn-cancel:hover { background: rgba(255,255,255,0.12); color: #f1f5f9; }

/* info card */
.info-card {
    background: rgba(37,99,235,0.06);
    border: 1px solid rgba(37,99,235,0.18);
    border-radius: 16px;
    padding: 20px;
    display: flex;
    align-items: flex-start;
    gap: 15px;
    margin-bottom: 28px;
}
.info-card i { color: #60a5fa; font-size: 1.4rem; flex-shrink: 0; margin-top: 2px; }
.info-card h4 { color: #f1f5f9; margin-bottom: 6px; font-size: 0.95rem; }
.info-card ul { color: #94a3b8; font-size: 0.85rem; margin-left: 18px; line-height: 1.7; }

/* recent reservations mini table */
.recent-card {
    background: rgba(10,18,40,0.82);
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 20px;
    backdrop-filter: blur(24px);
    overflow: hidden;
}
.recent-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 16px 24px;
    background: rgba(37,99,235,0.20);
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.recent-header i  { color: #0ea5e9; }
.recent-header h3 { color: #f1f5f9; font-size: 0.95rem; font-weight: 600; margin: 0; }

.recent-table { width: 100%; border-collapse: collapse; }
.recent-table th {
    padding: 11px 16px;
    text-align: left;
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    background: rgba(255,255,255,0.02);
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.recent-table td {
    padding: 11px 16px;
    color: #cbd5e1;
    font-size: 0.85rem;
    border-bottom: 1px solid rgba(255,255,255,0.04);
}
.recent-table tr:last-child td { border-bottom: none; }
.recent-table tr:hover td { background: rgba(255,255,255,0.03); }

.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
}
.badge-pending  { background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.30); color: #fcd34d; }
.badge-approved { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25); color: #6ee7b7; }
.badge-rejected { background: rgba(239,68,68,0.12);  border: 1px solid rgba(239,68,68,0.25);  color: #fca5a5; }

.no-recent {
    padding: 30px;
    text-align: center;
    color: #475569;
    font-size: 0.875rem;
}
</style>

<div class="res-wrap">
    <div class="res-inner">

        <!-- Page Header -->
        <div class="dashboard-header">
            <h1>Lab Reservation</h1>
            <div class="date-badge">
                <i class="far fa-calendar-alt"></i>
                <?php echo date('F j, Y'); ?>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success_message): ?>
        <div class="res-alert success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
        <div class="res-alert error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <!-- Reservation Form Card -->
        <div class="res-card">
            <div class="res-card-title">
                <i class="fas fa-calendar-check"></i>
                <h2>Reserve a Laboratory Slot</h2>
            </div>

            <form method="POST" action="">

                <!-- ID Number -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-id-card"></i> ID Number</label>
                    <input type="text"
                           class="form-control"
                           value="<?php echo htmlspecialchars($user['id_number'] ?? $_SESSION['id_number']); ?>"
                           readonly disabled>
                </div>

                <!-- Student Name -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-user"></i> Student Name</label>
                    <input type="text"
                           class="form-control"
                           value="<?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])); ?>"
                           readonly disabled>
                </div>

                <!-- Purpose -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-tasks"></i> Purpose <span style="color:#f87171;">*</span></label>
                    <select name="purpose" class="form-control" required>
                        <option value="" style="background:#1e293b;">Select Purpose</option>
                        <?php foreach ($purposes as $p): ?>
                        <option value="<?php echo $p; ?>"
                                style="background:#1e293b;"
                                <?php echo (isset($_POST['purpose']) && $_POST['purpose'] === $p) ? 'selected' : ''; ?>>
                            <?php echo $p; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Laboratory -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-flask"></i> Laboratory <span style="color:#f87171;">*</span></label>
                    <select name="lab" class="form-control" required>
                        <option value="" style="background:#1e293b;">Select Laboratory</option>
                        <?php foreach ($laboratories as $l): ?>
                        <option value="<?php echo $l; ?>"
                                style="background:#1e293b;"
                                <?php echo (isset($_POST['lab']) && $_POST['lab'] === $l) ? 'selected' : ''; ?>>
                            <?php echo $l; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Time In -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-clock"></i> Time In <span style="color:#f87171;">*</span></label>
                    <input type="time"
                           name="time_in"
                           class="form-control"
                           value="<?php echo $_POST['time_in'] ?? date('H:i'); ?>"
                           required>
                </div>

                <!-- Date -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-calendar-alt"></i> Date (dd/mm/yyyy) <span style="color:#f87171;">*</span></label>
                    <input type="text"
                           name="date"
                           class="form-control"
                           placeholder="dd/mm/yyyy"
                           value="<?php echo $_POST['date'] ?? date('d/m/Y'); ?>"
                           pattern="\d{2}/\d{2}/\d{4}"
                           title="Please enter date in dd/mm/yyyy format"
                           required>
                    <small style="color:#475569;font-size:0.75rem;margin-top:5px;display:block;">
                        Format: dd/mm/yyyy (e.g., <?php echo date('d/m/Y'); ?>)
                    </small>
                </div>

                <!-- Remaining Sessions -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-hourglass-half"></i> Remaining Sessions</label>
                    <div class="sessions-display">
                        <span style="color: <?php echo $remaining_sessions > 10 ? '#6ee7b7' : ($remaining_sessions > 5 ? '#fcd34d' : '#fca5a5'); ?>; font-size: 1.2rem; font-weight: 700;">
                            <?php echo $remaining_sessions; ?>
                        </span>
                        <span style="color:#94a3b8;font-size:0.9rem;">sessions left</span>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="btn-row">
                    <button type="submit" name="reserve" class="btn-reserve"
                            <?php echo $remaining_sessions <= 0 ? 'disabled' : ''; ?>>
                        <i class="fas fa-calendar-check"></i> Reserve
                    </button>
                    <a href="dashboard.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>

            </form>
        </div>

        <!-- Guidelines -->
        <div class="info-card">
            <i class="fas fa-info-circle"></i>
            <div>
                <h4>Reservation Guidelines</h4>
                <ul>
                    <li>Reservations are subject to admin approval</li>
                    <li>Please arrive on time for your reservation</li>
                    <li>Cancellations must be made at least 1 hour before the scheduled time</li>
                    <li>Maximum of 2 reservations per day</li>
                </ul>
            </div>
        </div>

        <!-- Recent Reservations -->
        <?php if (!empty($my_reservations)): ?>
        <div class="recent-card">
            <div class="recent-header">
                <i class="fas fa-history"></i>
                <h3>My Recent Reservations</h3>
            </div>
            <table class="recent-table">
                <thead>
                    <tr>
                        <th>Purpose</th>
                        <th>Laboratory</th>
                        <th>Date</th>
                        <th>Time In</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($my_reservations as $r): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['purpose']); ?></td>
                        <td><?php echo htmlspecialchars($r['laboratory']); ?></td>
                        <td style="color:#94a3b8;">
                            <?php echo $r['reservation_date']
                                ? date('M j, Y', strtotime($r['reservation_date']))
                                : '—'; ?>
                        </td>
                        <td>
                            <?php echo $r['time_in']
                                ? date('h:i A', strtotime($r['time_in']))
                                : '—'; ?>
                        </td>
                        <td>
                            <?php
                            $s   = $r['status'] ?? 'pending';
                            $cls = match($s) {
                                'approved' => 'badge-approved',
                                'rejected' => 'badge-rejected',
                                default    => 'badge-pending',
                            };
                            ?>
                            <span class="badge <?php echo $cls; ?>"><?php echo ucfirst($s); ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
// Auto-format date input as dd/mm/yyyy
document.querySelector('input[name="date"]').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) value = value.substring(0,2) + '/' + value.substring(2);
    if (value.length >= 5) value = value.substring(0,5) + '/' + value.substring(5,9);
    e.target.value = value.substring(0,10);
});
</script>

<?php include '../includes/footer.php'; ?>