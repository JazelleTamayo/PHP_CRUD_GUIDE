<?php

include "db.php";
$name = $email = "";
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
    $id = (int) $_POST['id'];
    $name = clean($_POST['name'] ?? "");
    $email = clean($_POST['email'] ?? "");

    if (empty($name) && empty($email)) {
        echo "<script>alert('Update User!');</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid Email!');</script>";
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $email, $id);

        if ($stmt->execute()) {
            echo "<script>alert('User Updated!');</script>";
            echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
            exit;
        } elseif ($stmt->errno === 1062) {
            echo "<script>alert('Email Already Exists!');</script>";
        } else {
            echo "<script>alert('Error Updating User!');</script>";
        }

        $stmt->close();
    }
}

if (isset($_POST['delete_btn']) && !isset($_POST['update_btn'])) {
    $id = (int) $_POST['id'];

    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('User Deleted!');</script>";
        echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
        exit;
    } else {
        echo "<script>alert('Error Deleting User!');</script>";
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST["update_btn"]) && !isset($_POST["delete_btn"])) {
    $name = clean($_POST["name"] ?? "");
    $email = clean($_POST["email"] ?? "");

    if (empty($name) || empty($email)) {
        echo "<script>alert('All Fields are Required!');</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid Email!');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $email);

        if ($stmt->execute()) {
            echo "<script>alert('User Added!');</script>";
            echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
            exit;
        } elseif ($stmt->errno === 1062) {
            echo "<script>alert('Email Already Exists!');</script>";
        } else {
            echo "<script>alert('Error Adding User!');</script>";
        }
        $stmt->close();
    }
}

if (isset($_GET['edit'])) {
    $edit = (int) $_GET['edit'];

    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
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

$result = $conn->query('SELECT * FROM users ORDER BY id DESC');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Full CRUD</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <h2><?= $edit ? 'Edit User' : 'Add New User'; ?></h2>

    <form method="post" action="">
        <fieldset>
            <legend>User Information</legend>
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

    <h2>All Records</h2>
    <?php if ($result->num_rows > 0) { ?>
        <p><strong>Total Users: <?= $result->num_rows; ?></strong></p>

        <table>
            <tr>
                <th>ID</th>
                <th>NAME</th>
                <th>EMAIL</th>
                <th>ACTIONS</th>
            </tr>

            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?= escape_html($row['id']); ?></td>
                    <td><?= escape_html($row['name']); ?></td>
                    <td><?= escape_html($row['email']); ?></td>
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
        <p><strong>No Users Found.</strong></p>
    <?php } ?>

    <p><a href="index.php">Manage Users</a> | <a href="posts.php">Manage Posts</a></p>

</body>

</html>

<?php
$conn->close();
?>