<?php
include "db.php";
$first_name = $last_name = $email = $department = $salary = "";
$edit = null;

function escape_html($data)
{
    return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, "UTF-8");
}

function clean($data)
{
    $data = trim($data ?? "");
    return $data;
}

if (isset($_POST['update_btn']) && !isset($_POST['delete_btn'])) {
    $id = (int) $_POST["id"];
    $first_name = clean($_POST["first_name"] ?? "");
    $last_name = clean($_POST["last_name"] ?? "");
    $email = clean($_POST["email"] ?? "");
    $department = clean($_POST["department"] ?? "");
    $salary = clean($_POST["salary"] ?? "");

    if (empty($first_name) || empty($last_name) || empty($email) || empty($department) || $salary === "") {
        echo "<script>alert('All Fields are Required!');</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid Email!');</script>";
    } elseif (!is_numeric($salary)) {
        echo "<script>alert('Salary must be a number!');</script>";
    } else {
        $salary = round((double) $salary, 2);
        if ($salary > 99999999.99) {
            echo "<script>alert('Salary must not exceed in 10 digits!');</script>";
        } else {
            $stmt = $conn->prepare("UPDATE employees SET first_name = ?, last_name = ?, email = ?, department = ?, salary = ? WHERE id = ?");
            $stmt->bind_param("ssssdi", $first_name, $last_name, $email, $department, $salary, $id);

            if ($stmt->execute()) {
                echo "<script>alert('Employee Updated!');</script>";
                echo "<script>window.location.href= " . json_encode($_SERVER["PHP_SELF"]) . ";</script>";
                exit;
            } elseif ($stmt->errno === 1062) {
                echo "<script>alert('Email Already Exists!');</script>";
            } else {
                echo "<script>alert('Error Updating Employee!');</script>";
            }
            $stmt->close();
        }
    }
}

if (isset($_POST["delete_btn"]) && !isset($_POST["update_btn"])) {
    $id = (int) $_POST["id"];

    $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('Employee Deleted!');</script>";
        echo "<script>window.location.href=" . json_encode($_SERVER["PHP_SELF"]) . ";</script>";
        exit;
    } else {
        echo "<script>alert('Error Deleting Employee!');</script>";
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST["update_btn"]) && !isset($_POST["delete_btn"])) {
    $first_name = clean($_POST["first_name"] ?? "");
    $last_name = clean($_POST["last_name"] ?? "");
    $email = clean($_POST["email"] ?? "");
    $department = clean($_POST["department"] ?? "");
    $salary = clean($_POST["salary"] ?? "");

    // Salary required-field check: Use $salary === "" (allows 0, rejects blank).
    // Do NOT use empty($salary) because empty("0") returns true, falsely rejecting 0.
    if (empty($first_name) || empty($last_name) || empty($email) || empty($department) || $salary === "") {
        echo "<script>alert('All Fields are Required!');</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid Email!');</script>";
    } elseif (!is_numeric($salary)) {
        // NOTE: Use is_numeric() for salary because it allows decimal points (e.g., 5000.50).
        // ctype_digit() only accepts whole digits (0-9) with no dot, so it would reject cents.
        // Since salary may have 2 decimal places (DECIMAL(10,2)), is_numeric() is the correct choice.
        echo "<script>alert('Salary must be a number!');</script>";
    } else {
        $salary = round((double) $salary, 2);

        if ($salary > 99999999.99) {
            echo "<script>alert('Salary must not exceed in 10 digits!');</script>";
        } else {

            $stmt = $conn->prepare("INSERT INTO employees (first_name, last_name, email,department, salary) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssd", $first_name, $last_name, $email, $department, $salary);

            if ($stmt->execute()) {
                echo "<script>alert('Employee Added Successfully!');</script>";
                echo "<script>window.location.href=" . json_encode($_SERVER["PHP_SELF"]) . ";</script>";
                exit;
            } elseif ($stmt->errno === 1062) {
                echo "<script>alert('Email already exist!');</script>";
            } else {
                echo "<script>alert('Error Adding Employee!');</script>";
            }
            $stmt->close();

        }
    }

}

if (isset($_GET["edit"])) {
    $edit = (int) $_GET["edit"];

    $stmt = $conn->prepare('SELECT * FROM employees WHERE id = ?');
    $stmt->bind_param("i", $edit);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $first_name = $row["first_name"];
        $last_name = $row["last_name"];
        $email = $row["email"];
        $department = $row["department"];
        $salary = $row["salary"];

    }
    $stmt->close();
}

$result = $conn->query("SELECT * FROM employees");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Employee Directory CRUD</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <h2>Employee Information</h2>
    <form method="post" action="">
        <fieldset>
            <legend><?= $edit ? 'Edit Employee' : 'Add Employee'; ?></legend>
            <?php if ($edit) { ?>
                <input type="hidden" name="id" value="<?= escape_html($edit); ?>">
            <?php } ?>

            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" value="<?= escape_html($first_name); ?>"
                required><br><br>
            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" value="<?= escape_html($last_name); ?>" required><br><br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= escape_html($email); ?>" required><br><br>
            <label for="department">Department:</label>

            <select id="department" name="department" required>
                <option value="HR" <?= $department === "HR" ? 'selected' : ""; ?>>HR</option>
                <option value="IT" <?= $department === "IT" ? 'selected' : ""; ?>>IT</option>
                <option value="Sales" <?= $department === "Sales" ? 'selected' : ""; ?>>Sales</option>
                <option value="Marketing" <?= $department === "Marketing" ? 'selected' : ""; ?>>Marketing</option>
                <option value="Finance" <?= $department === "Finance" ? 'selected' : ""; ?>>Finance</option>
            </select>
            <br><br>
            <label for="salary">Salary:</label>
            <input type="number" id="salary" name="salary" value="<?= escape_html($salary); ?>" required step="any"
                min="0"><br><br>
            <!-- Salary: step="any" enables decimals, min="0" allows zero (fixes browser blocking 0 and decimals) -->
            <?php if ($edit) { ?>
                <input type="submit" name="update_btn" value="Update Employee">
                <a href="<?= escape_html($_SERVER["PHP_SELF"]); ?>">Cancel</a>
            <?php } else { ?>
                <input type="submit" value="Add Employee">
            <?php } ?>
        </fieldset>
    </form>

    <h2>Employee Information</h2>
    <?php if ($result->num_rows > 0) { ?>
        <p><strong>Total Employees: <?= $result->num_rows; ?></strong></p>
        <table>'
            <tr>
                <th>ID</th>
                <th>FIRST NAME</th>
                <th>LAST NAME</th>
                <th>EMAIL</th>
                <th>DEPARTMENT</th>
                <th>SALARY</th>
                <th>ACTIONS</th>
            </tr>

            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?= escape_html($row["id"]); ?></td>
                    <td><?= escape_html($row["first_name"]); ?></td>
                    <td><?= escape_html($row["last_name"]); ?></td>
                    <td><?= escape_html($row["email"]); ?></td>
                    <td><?= escape_html($row["department"]); ?></td>
                    <td><?= escape_html($row["salary"]); ?></td>
                    <td>
                        <a href="?edit=<?= escape_html($row["id"]); ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Are you sure you want to delete this Employee?');">
                            <input type="hidden" name="id" value="<?= escape_html($row["id"]); ?>">
                            <input type="submit" name="delete_btn" value="Delete">
                        </form>
                    </td>
                </tr>

            <?php } ?>

        </table>
    <?php } else { ?>
        <p><strong>Employee Not Found!</strong></p>
    <?php } ?>
</body>

</html>

<?php
$conn->close();
?>