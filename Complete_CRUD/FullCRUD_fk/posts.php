<?php
include "db.php";
$user_id = $title = $content = "";
$edit = null;

function escape_html($data) {
    return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, "UTF-8");
}

function clean($data) {
    $data = trim($data ?? "");
    return $data;
}

// UPDATE POST
if (isset($_POST['update_btn']) && !isset($_POST['delete_btn'])) {
    $id = (int) $_POST['id'];
    $user_id = (int) $_POST['user_id'];
    $title = clean($_POST['title'] ?? "");
    $content = clean($_POST['content'] ?? "");

    if (empty($user_id) || empty($title) || empty($content)) {
        echo "<script>alert('All fields (User, Title, Content) are required!');</script>";
    } else {
        $stmt = $conn->prepare("UPDATE posts SET user_id = ?, title = ?, content = ? WHERE id = ?");
        $stmt->bind_param("issi", $user_id, $title, $content, $id);
        if ($stmt->execute()) {
            echo "<script>alert('Post Updated!');</script>";
            echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
            exit;
        } else {
            echo "<script>alert('Error Updating Post!');</script>";
        }
        $stmt->close();
    }
}

// DELETE POST
if (isset($_POST['delete_btn']) && !isset($_POST['update_btn'])) {
    $id = (int) $_POST['id'];
    $stmt = $conn->prepare('DELETE FROM posts WHERE id = ?');
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<script>alert('Post Deleted!');</script>";
        echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
        exit;
    } else {
        echo "<script>alert('Error Deleting Post!');</script>";
    }
    $stmt->close();
}

// INSERT POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST["update_btn"]) && !isset($_POST["delete_btn"])) {
    $user_id = (int) $_POST["user_id"];
    $title = clean($_POST["title"] ?? "");
    $content = clean($_POST["content"] ?? "");

    if (empty($user_id) || empty($title) || empty($content)) {
        echo "<script>alert('All fields (User, Title, Content) are required!');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $title, $content);
        if ($stmt->execute()) {
            echo "<script>alert('Post Added!');</script>";
            echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
            exit;
        } else {
            echo "<script>alert('Error Adding Post!');</script>";
        }
        $stmt->close();
    }
}

// EDIT (load post data)
if (isset($_GET['edit'])) {
    $edit = (int) $_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM posts WHERE id = ?');
    $stmt->bind_param("i", $edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id = $row['user_id'];
        $title = $row['title'];
        $content = $row['content'];
    }
    $stmt->close();
}

// Fetch all posts using JOIN
// Select ALL columns from the 'posts' table, plus the 'name' column from the 'users' table
// Rename 'users.name' to 'user_name' so it's clear in PHP results
// Start from the 'posts' table (the child / "many" side)
// Join the 'users' table (the parent / "one" side) where each post's 'user_id' matches a user's 'id'
$result = $conn->query("
    SELECT posts.*, users.name AS user_name
    FROM posts
    INNER JOIN users ON posts.user_id = users.id
    ORDER BY posts.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Posts CRUD</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <h2><?= $edit ? 'Edit Post' : 'Add New Post'; ?></h2>

    <form method="post" action="">
        <fieldset>
            <legend>Post Information</legend>
            <?php if ($edit) { ?>
                <input type="hidden" name="id" value="<?= escape_html($edit); ?>">
            <?php } ?>
            
            <label for="user_id">User:</label>
            <select name="user_id" id="user_id" required>
                <option value="">-- Select User --</option>
                <?php
                // Fetch all users from database (only id and name columns, sorted alphabetically by name)
                $users = $conn->query("SELECT id, name FROM users ORDER BY name");

                // Loop through each user row one by one
                while ($u = $users->fetch_assoc()) {
    
                // Check if this user's ID matches the ID of the user who wrote the post (only matters when editing)
                // If yes, set $selected to 'selected' (so this option will be pre-selected in the dropdown)
                // If no, set $selected to empty string (no pre-selection for other users)
                $selected = $user_id == $u['id'] ? 'selected' : '';
    
                // Generate an <option> tag for this user:
                // - value = user's ID (sent to server when form is submitted)
                // - $selected = either 'selected' or nothing (pre-selects the correct user when editing)
                // - The text shown between > and </option> is the user's name (escaped for safety)
                 echo "<option value='{$u['id']}' $selected>" . escape_html($u['name']) . "</option>";
                }
                ?>
            </select><br><br>

            <label for="title">Title:</label>
            <input type="text" id="title" name="title" value="<?= escape_html($title); ?>" required><br><br>

            <label for="content">Content:</label>
            <textarea id="content" name="content" rows="4" cols="50" required><?= escape_html($content); ?></textarea><br><br>

            <?php if ($edit) { ?>
                <input type="submit" name="update_btn" value="Update Post">
                <a href="<?= escape_html($_SERVER['PHP_SELF']); ?>">Cancel</a>
            <?php } else { ?>
                <input type="submit" value="Add Post">
            <?php } ?>
        </fieldset>
    </form>

    <h2>All Posts</h2>
    <?php if ($result->num_rows > 0) { ?>
        <p><strong>Total Posts: <?= $result->num_rows; ?></strong></p>
        <table>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Title</th>
                <th>Content</th>
                <th>ACTIONS</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()) { 
            ?>
                <tr>
                    <td><?= escape_html($row['id']); ?></td>
                    <td><?= escape_html($row['user_name']); ?></td>
                    <td><?= escape_html($row['title']); ?></td>
                    <td><?= escape_html($row['content']); ?></td>
                    <td>
                        <a href="?edit=<?= escape_html($row['id']); ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Are you sure you want to delete this post?');">
                            <input type="hidden" name="id" value="<?= escape_html($row['id']); ?>">
                            <input type="submit" name="delete_btn" value="Delete">
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <p><strong>No Posts Found.</strong></p>
    <?php } ?>
    
    <p><a href="index.php">Manage Users</a> | <a href="posts.php">Manage Posts</a></p>
</body>
</html>

<?php 
$conn->close(); 
?>