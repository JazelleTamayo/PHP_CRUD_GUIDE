<?php
// ========== PART 1: ALWAYS START WITH THESE ==========
include "db.php";
//Initialize variables
$name = $email = "";
// ========== EDIT MODE FLAG ==========
// $edit tells the form whether we're adding a new user or editing an existing one:
//
// - When $edit = null   → The form is in "Add New User" mode (empty fields).
// - When $edit = 5, 10, etc. → The form is in "Edit User" mode (fills fields with that user's data).
//
// Why initialize it as null?
// - Prevents PHP "undefined variable" warning when the page loads without ?edit=ID in the URL.
// - Makes it easy to check: "if ($edit)" means we're editing; "if (!$edit)" means we're adding.
$edit = null;

// ========== HELPER FUNCTION FOR SAFE HTML OUTPUT ==========
// Use this function EVERY time you output dynamic data inside HTML.
// Parameter: $data - any scalar value (string, int, float, bool, or null)
function escape_html($data) {
    return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

// ========== PART 2: SANITIZATION FUNCTION ==========
function clean($data)
{
    // Trim: remove spaces, tabs, newlines from the beginning and end of the string.
    // The null coalescing operator (??) ensures we never pass null to trim,
    // converting null to an empty string.
    $data = trim($data ?? "");

    // No need for stripslashes() – modern PHP doesn’t add magic quotes.
    // No need for htmlspecialchars() here – input data should be stored raw.
    // Output escaping (to prevent XSS) will be done separately when displaying data.
    return $data;
}


// ========== PART 3: UPDATE ==========
// This part runs when someone clicks the "Update" button inside the edit form.
// The form sends a POST request with:
//   - Hidden input named "id" (contains the user ID to update)
//   - Text input named "name" (contains the new name)
//   - Text input named "email" (contains the new email)
//   - Submit button named "update_btn" (identifies this as an update action)

// 1. Check if the update button was clicked AND delete button is NOT clicked
if (isset($_POST['update_btn']) && !isset($_POST['delete_btn'])) {

    // 2. Get the data from the form
    $id = (int) $_POST['id'];
    $name = clean($_POST["name"] ?? "");
    $email = clean($_POST['email'] ?? "");

    // 3. Validate the inputs
    if (empty($name) || empty($email)) {
        echo "<script>alert('Name and Email are required!')</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Please enter a valid email address!')</script>";
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $email, $id);

        if ($stmt->execute()) {
            echo "<script>alert('User updated successfully!');</script>";
            echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
            exit;
        } else {
            if ($stmt->errno == 1062) {
                echo "<script>alert('This email is already registered!')</script>";
            } else {
                echo "<script>alert('Error updating user!')</script>";
            }
        }
        $stmt->close();
    }
}

// ========== PART 4: DELETE (POST) ==========
// This part runs when someone clicks the "Delete" button inside a form.
// The form sends a POST request with a hidden input named "id"
// that contains the ID of the user to delete, and a submit button named "delete_btn".

// 1. Check if the delete button was clicked AND update button is NOT clicked
if (isset($_POST['delete_btn']) && !isset($_POST['update_btn'])) {

    $id = (int) $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('User deleted!');</script>";
        echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
        exit;
    } else {
        echo "<script>alert('Error deleting user!');</script>";
    }
    $stmt->close();
}

// ========== PART 5: CREATE (CREATE ONLY!) ==========
// Only runs for CREATE operations (Add New User)
// Checks: It's a POST request AND not an update AND not a delete
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['update_btn']) && !isset($_POST['delete_btn'])) {
    $name = clean($_POST["name"] ?? "");
    $email = clean($_POST["email"] ?? "");

    if (empty($name) || empty($email)) {
        echo "<script>alert('Name and Email are required!')</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Please enter a valid email address!')</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $email);

        if ($stmt->execute()) {
            echo "<script>alert('User Added Successfully!');</script>";
            echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
            exit;
        } else {
            if ($stmt->errno == 1062) {
                echo "<script>alert('This email is already registered!')</script>";
            } else {
                echo "<script>alert('Error adding user!')</script>";
            }
        }
        $stmt->close();
    }
}

// ========== PART 6: EDIT (GET) ==========
if (isset($_GET['edit'])) {
    $edit = (int) $_GET['edit'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $edit);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $name = $row['name'];
        $email = $row['email'];
    }
    $stmt->close();
}

// ========== PART 7: READ DATA ==========
$result = $conn->query("SELECT * FROM users");
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD - Create Read Update Delete</title>
</head>

<body>
    <h2><?= $edit ? 'Edit User' : 'Add New User'; ?></h2>

    <form method="post" action="">
        <fieldset>
            <legend>Student Information</legend>
            <?php if ($edit) { ?>
                <input type="hidden" name="id" value="<?= escape_html($edit); ?>">
            <?php } ?>
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?= escape_html($name); ?>" required><br><br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= escape_html($email); ?>" required><br><br>

            <?php if ($edit) { ?>
                <input type="submit" name="update_btn" value="Update User">
                <a href="<?= escape_html($_SERVER['PHP_SELF']); ?>">Cancel</a>
            <?php } else { ?>
                <input type="submit" value="Add User">
            <?php } ?>
        </fieldset>
    </form>

    <hr>

    <h2>All Record</h2>

    <?php if ($result->num_rows > 0) { ?>
        <p><strong>Total Records: <?php echo $result->num_rows; ?></strong></p>

        <table border="1">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Action</th>
            </tr>

            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?= escape_html($row["id"]); ?></td>
                    <td><?= escape_html($row["name"]); ?></td>
                    <td><?= escape_html($row["email"]); ?></td>

                    <td>
                        <a href="?edit=<?= escape_html($row['id']); ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Are you sure you want to delete this user?');">
                            <input type="hidden" name="id" value="<?= escape_html($row['id']); ?>">
                            <input type="submit" name="delete_btn" value="Delete">
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <p>No users found. Add your first user above!</p>
    <?php } ?>

</body>

</html>

<?php
$conn->close();
?>
