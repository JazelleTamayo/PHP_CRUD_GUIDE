<?php
include "db.php";
$title = $author_id = $year = $genre = "";
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
    $title = clean($_POST['title']);
    $author_id = clean($_POST['author_id']);
    $year = clean($_POST['year']);
    $genre = clean($_POST['genre']);

    if (empty($title) || empty($author_id) || $year === "" || empty($genre)) {
        echo "<script>alert('All fields are required!');</script>";
    } elseif (!ctype_digit($year)) {
        echo "<script>alert('Year must be an integer!');</script>";
    } elseif ($year < 1901 || $year > 2155) {
        echo "<script>alert('Year must be between 1901 and 2155!');</script>";
    } else {
        $stmt = $conn->prepare('UPDATE books SET title = ?, author_id = ?, year = ?, genre = ? WHERE id = ?');
        $stmt->bind_param("siisi", $title, $author_id, $year, $genre, $id);

        if ($stmt->execute()) {
            echo "<script>alert('Book Updated!');</script>";
            echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
            exit;
        } else {
            echo "<script>alert('Error Updating Book!');</script>";
        }
        $stmt->close();
    }
}


if (isset($_POST['delete_btn']) && !isset($_POST['update_btn'])) {
    $id = (int) $_POST['id'];

    $stmt = $conn->prepare('DELETE FROM books WHERE id = ?');
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('Book Deleted!');</script>";
        echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
        exit;
    } else {
        echo "<script>alert('Error Deleting Book!');</script>";
    }
    $stmt->close();

}



if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['update_btn']) && !isset($_POST['delete_btn'])) {
    $title = clean($_POST['title']);
    $author_id = clean($_POST['author_id']);
    $year = clean($_POST['year']);
    $genre = clean($_POST['genre']);

    if (empty($title) || empty($author_id) || $year === "" || empty($genre)) {
        echo "<script>alert('All fields are Required!');</script>";
    } elseif (!ctype_digit($year)) {
        echo "<script>alert('Year must be an integer!');</script>";
    } elseif ($year < 1901 || $year > 2155) {
        echo "<script>alert('Year must be between 1901 and 2155!');</script>";
    } else {
        $stmt = $conn->prepare('INSERT INTO books (title, author_id, year, genre) VALUES (?, ?, ?, ?)');
        $stmt->bind_param("siis", $title, $author_id, $year, $genre);

        if ($stmt->execute()) {
            echo "<script>alert('Book Added!');</script>";
            echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
            exit;
        } else {
            echo "<script>alert('Error Adding book!');</script>";
        }
        $stmt->close();
    }
}


if (isset($_GET['edit'])) {
    $edit = (int) $_GET['edit'];

    $stmt = $conn->prepare('SELECT * FROM books WHERE id = ?');
    $stmt->bind_param('i', $edit);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $title = $row['title'];
        $author_id = $row['author_id'];
        $year = $row['year'];
        $genre = $row['genre'];
    }
    $stmt->close();
}


$result = $conn->query("
    SELECT books.*, authors.name AS user_name
    FROM books
    INNER JOIN authors ON books.author_id = authors.id
    ORDER BY books.id DESC
");

?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Book Management</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
        <h2>Books Information</h2>
        <form method="post">
            <fieldset>
                <legend><?= $edit ? 'Edit Book' : 'Add Book'; ?></legend>
                <?php if ($edit) { ?>
                    <input type="hidden" name="id" value="<?=  escape_html($edit); ?>">
                <?php } ?>
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" value="<?= escape_html($title); ?>" required><br><br>
                <label for="author_id">Author</label>
                <select id="author_id" name="author_id" required>
                    <option value="">--Select Author--</option>
                    <?php
                        $author = $conn->query('SELECT id, name FROM authors ORDER BY id DESC');
                        while ($a = $author->fetch_assoc()) {
                            $selected = $author_id == $a['id'] ? 'selected' : '';
                            echo "<option value='{$a['id']}' $selected>" . escape_html($a['name']) . "</option>";
                        }
                    ?>
                </select>
                <br><br>

                <label for="year">Year:</label>
                <input type="number" id="year" name="year" value="<?= escape_html($year); ?>" required><br><br>
                <label for="genre">Genre</label>
                <select id="genre" name="genre" required>
                    <option value="Fiction" <?= $genre === "Fiction" ? 'selected' : ''; ?>>Fiction</option>
                    <option value="Mystery" <?= $genre === "Mystery" ? 'selected' : ''; ?>>Mystery</option>
                    <option value="Science Fiction" <?= $genre === "Science Fiction" ? 'selected' : ''; ?>>Science Fiction</option>
                    <option value="Biography" <?= $genre === "Biography" ? 'selected' : ''; ?>>Biography</option>
                    <option value="Fantasy" <?= $genre === "Fantasy" ? 'selected' : ''; ?>>Fantasy</option>
                </select>
                <br><br>

                <?php if ($edit) { ?>
                    <input type="submit" name="update_btn" Value="Update Book">
                    <a href="<?= escape_html($_SERVER['PHP_SELF']); ?>">Cancel</a>
                <?php } else { ?>
                    <input type="submit" value="Add Book">
                <?php } ?>
            </fieldset>
        </form>

        <h2>All Records</h2>
        <?php if ($result->num_rows > 0) { ?>
            <p><strong>Total Books: <?= $result->num_rows; ?></strong></p>
            <table>
                <tr>
                    <th>ID</th>
                    <th>TITLE</th>
                    <th>AUTHOR</th>
                    <th>YEAR</th>
                    <th>GENRE</th>
                    <th>ACTIONS</th>
                </tr>

                <?php while($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?= escape_html($row['id']); ?></td>
                        <td><?= escape_html($row['title']); ?></td>
                        <td><?= escape_html($row['user_name']); ?></td>
                        <td><?= escape_html($row['year']); ?></td>
                        <td><?= escape_html($row['genre']); ?></td>
                        <td>
                            <a href="?edit=<?= escape_html($row['id']); ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Are you sure you want to delete this book?');" style="display:inline;">
                                <input type="hidden" name="id" value="<?= escape_html($row['id']); ?>">
                                <input type="submit" name="delete_btn" value="Delete">
                            </form>
                        </td>
                    </tr>
                <?php } ?>

            </table>
            
        <?php } else { ?>
            <p><strong>No Books Found!</strong></p>
        <?php } ?>

        <p><a href="authors.php">Manage Authors</a> | <a href="books.php">Manage Books</a></p>
    </body>
</html>


<?php
$conn->close();
?>