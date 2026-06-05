<?php
include "db.php";
$name = $birth_year = $nationality = "";
$edit = null;

function escape_html($data) {
    return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

function clean($data) {
    $data = trim($data ?? "");
    return $data;
}

if (isset($_POST['update_btn']) && !isset($_POST['delete_btn'])) {
    $id = (int) $_POST['id'];
    $name = clean($_POST['name'] ?? "");
    $birth_year = clean($_POST['birth_year'] ?? "");
    $nationality = clean($_POST['nationality'] ?? "");

    if (empty($name) || $birth_year === "" || empty($nationality)) {
        echo "<script>alert('All fields are Required!');</script>";
    } elseif (!ctype_digit($birth_year)) {
        echo "<script>alert('Year must be a number and integer!');</script>";
    } elseif ($birth_year < 1901 || $birth_year > 2155) {
        echo "<script>alert('Year must be between 1901 and 2155!');</script>";
    } else {
    
        $stmt = $conn->prepare('UPDATE authors SET name = ?, birth_year = ?, nationality = ? WHERE id = ?');
        $stmt->bind_param("sisi", $name, $birth_year, $nationality, $id);

        if ($stmt->execute()) {
            echo "<script>alert('Author Updated!');</script>";
            echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
            exit;
        } else {
            echo "<script>alert('Error updating author!');</script>";
        }
        $stmt->close();
    }
}



if (isset($_POST['delete_btn']) && !isset($_POST['update_btn'])) {
    $id = (int) $_POST['id'];

    $stmt = $conn->prepare('DELETE FROM authors WHERE id = ?');
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('Author Deleted!');</script>";
        echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
        exit;
    } elseif ($stmt->errno === 1451) {
        echo "<script>alert('Cannot delete: This author has books!');</script>";
    } else {
        echo "<script>alert('Error deleting author!');</script>";
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['update_btn']) && !isset($_POST['delete_btn'])) {
    $name = clean($_POST['name'] ?? "");
    $birth_year = clean($_POST['birth_year'] ?? "");
    $nationality = clean($_POST['nationality'] ?? "");

    if (empty($name) || $birth_year === "" || empty($nationality)) {
        echo "<script>alert('All fields are Required!');</script>";
    } elseif (!ctype_digit($birth_year)) {
        echo "<script>alert('Year must be a number and integer!');</script>";
    } elseif ($birth_year < 1901 || $birth_year > 2155) {
        echo "<script>alert('Year must be between 1901 and 2155!');</script>";
    } else {
        
        $stmt = $conn->prepare('INSERT INTO authors (name, birth_year, nationality) VALUES (?, ?, ?)');
        $stmt->bind_param("sis", $name, $birth_year, $nationality);

        if ($stmt->execute()) {
            echo "<script>alert('Author Added!');</script>";
            echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
            exit();
        } else {
            echo "<script>alert('Error Adding Author!');</script>";
        }
        $stmt->close();
    }
}

if (isset($_GET['edit'])) {
    $edit = (int) $_GET['edit'];

    $stmt = $conn->prepare('SELECT * FROM authors WHERE id = ?');
    $stmt->bind_param("i", $edit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $name = $row['name'];
        $birth_year = $row['birth_year'];
        $nationality = $row['nationality'];
    }
    $stmt->close();
}

$result = $conn->query('SELECT * FROM authors ORDER BY id DESC');

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Author Management</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
        <h2>Author Information</h2>
        <form method="post">
            <fieldset>
                <legend><?= $edit ? 'Edit Author' : 'Add Author'; ?></legend>
                <?php if ($edit) { ?>
                    <input type="hidden" name="id" value="<?= escape_html($edit); ?>">
                <?php } ?>

                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?= escape_html($name); ?>" required><br><br>
                <label for="birth_year">Birth Year:</label>
                <input type="number" id="birth_year" name="birth_year" value="<?=  escape_html($birth_year); ?>" required><br><br>
                <label for="nationality">Nationality</label>
                <input type="text" id="nationality" name="nationality" value="<?= escape_html($nationality); ?>" required><br><br>

                <?php if ($edit) { ?>
                    <input type="submit" name="update_btn" value="Update Author">
                    <a href="<?= escape_html($_SERVER['PHP_SELF']); ?>">Cancel</a>
                <?php } else { ?>
                    <input type="submit" value="Add Author">
                <?php } ?>
            </fieldset>
        </form>

        <br>

        <h2>All Information</h2>
        <?php if ($result->num_rows > 0) { ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>NAME</th>
                    <th>BIRTH YEAR</th>
                    <th>NATIONALITY</th>
                    <th>ACTIONS</th>
                </tr>

                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?=  escape_html($row['id']); ?></td>
                        <td><?=  escape_html($row['name']); ?></td>
                        <td><?=  escape_html($row['birth_year']); ?></td>
                        <td><?=  escape_html($row['nationality']); ?></td>
                        <td>
                            <a href="?edit=<?= escape_html($row['id']); ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Are you sure you want to delete this author?')">
                                <input type="hidden" name="id" value="<?= escape_html($row['id']); ?>">
                                <input type="submit" name="delete_btn" value="Delete">
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </table>

        <?php } else { ?>
            <p><strong>No Authors Found!</strong></p>
        <?php } ?>

        <p><a href="authors.php">Manage Authors</a> | <a href="books.php">Manage Books</a></p>
    </body>
</html>
   
<?php
$conn->close();
?>