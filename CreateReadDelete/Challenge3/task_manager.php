<?php
include 'db.php';
$title = $description = $priority = $due_date = $status = "";

function clean($data)
{
    $data = trim($data ?? "");
    return $data;
}
// You are CREATING your own format by writing a string
// 'Y-m-d' is a string argument that tells the function how to interpret the date string you provide.
// Y = 4-digit year (2024) | y = 2-digit year (24)
// m = 2-digit month (01-12) | d = 2-digit day (01-31)
function isValidDate($due_date, $format = 'Y-m-d')
{
    // Try to convert the date string into a DateTime object using the specified format
    // Built-in function: Converts string to DateTime object using your custom 
    // createFromFormat just READS it using your format pattern
    // Returns: DateTime object (if valid) OR false (if invalid format/date)
    $d = DateTime::createFromFormat($format, $due_date);
    // Returns true ONLY IF: (1) DateTime object exists AND (2) reformatted date matches original exactly
    // $d->format() = Convert DateTime object back to string using same format
    // === = Strict comparison (same value AND same type) - prevents auto-corrected dates from passing what do you mean by auto corrected?
    // Some PHP versions say: "You probably meant March 1 or March 2"
    // Auto-corrects to: March 1, 2024 or March 2, 2024
    // Returns a DateTime object (NOT false!) because it can interpret "2024-02-30" as "2024-03-01" or "2024-03-02"
    return $d && $d->format($format) === $due_date;
}


if (isset($_POST['delete_btn'])) {
    $id = (int) $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('Task Deleted Successfully');</script>";
        echo "<script>window.location.href='" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES) . "';</script>";
        exit;
    } else {
        echo "<script>alert('Error deleting task!');</script>";
        echo "<script>window.location.href='" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES) . "';</script>";
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = clean($_POST['title']);
    $description = clean($_POST['description']);
    $priority = clean($_POST['priority']);
    $due_date = clean($_POST['due_date']);
    $status = clean($_POST['status']);

    if (empty($title) || empty($priority) || empty($due_date) || empty($status)) {
        echo "<script>alert('Please fill in all fields!');</script>";
        echo "<script>window.location.href='" . htmlspecialchars($_SERVER['PHP_SELF']) . "';</script>";
        exit;
    } elseif (!isValidDate($due_date)) {
        echo "<script>alert('Please enter a valid date in YYYY-MM-DD format!');</script>";
        echo "<script>window.location.href='" . htmlspecialchars($_SERVER['PHP_SELF']) . "';</script>";
        exit;
    } else {
        $stmt = $conn->prepare("INSERT INTO tasks (title, description, priority, due_date, status) VALUES (?, ?, ?, ?,?)");
        $stmt->bind_param("sssss", $title, $description, $priority, $due_date, $status);

        if ($stmt->execute()) {
            echo "<script>alert('Task Added Successfully');</script>";
            echo "<script>window.location.href='" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES) . "';</script>";
            exit;
        } else {
            echo "<script>alert('Error adding task!');</script>";
            echo "<script>window.location.href='" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES) . "';</script>";
            exit;
        }

        $stmt->close();
    }
}

$result = $conn->query("SELECT * FROM tasks ORDER BY due_date ASC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager System</title>
</head>

<body>
    <h2>Task Manager System</h2>
    <form method="post" action="">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($title); ?>" required><br><br>
        <label for="description">Description</label>
        <textarea id="description" name="description"><?= htmlspecialchars($description); ?></textarea><br><br>
        <label for="priority">Priority</label>
        <select id="priority" name="priority" required>
            <option value="High" <?= $priority === 'High' ? 'selected' : ''; ?>>🔴High</option>
            <option value="Medium" <?= $priority === 'Medium' ? 'selected' : ''; ?>>🟡Medium</option>
            <option value="Low" <?= $priority === 'Low' ? 'selected' : ''; ?>>🟢Low</option>
        </select><br><br>
        <label for="due_date">Due Date</label>
        <input type="date" id="due_date" name="due_date" value="<?= htmlspecialchars($due_date); ?>" required><br><br>
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="Pending" <?= $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="In Progress" <?= $status === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
            <option value="Completed" <?= $status === 'Completed' ? 'selected' : ''; ?>>Complete</option>
        </select>
        <br><br>
        <input type="submit" value="Submit">
    </form>
    <br>
    <h2>Task List</h2>
    <br>
    <?php if ($result->num_rows > 0) { ?>
        <table>
            <tr>
                <th>ID</th>
                <th>TITLE</th>
                <th>DESCRIPTION</th>
                <th>PRIORITY</th>
                <th>DUE DATE</th>
                <th>STATUS</th>
                <th>CREATED AT</th>
                <th>ACTIONS</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <th><?= htmlspecialchars($row['id']); ?></th>
                    <th><?= htmlspecialchars($row['title']); ?></th>
                    <th><?= htmlspecialchars($row['description']); ?></th>
                    <th><?= htmlspecialchars($row['priority']); ?></th>
                    <th><?= htmlspecialchars($row['due_date']); ?></th>
                    <th><?= htmlspecialchars($row['status']); ?></th>
                    <th><?= htmlspecialchars($row['created_at']); ?></th>
                    <th>
                        <form method="post" action="" onsubmit="return confirm('Are you sure you want to delete this task?');">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']); ?>">
                            <input type="submit" name="delete_btn" value="Delete">
                        </form>
                    </th>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <p>No task was found. Add your first task above!</p>
    <?php } ?>
</body>

</html>

<?php
$conn->close();
?>