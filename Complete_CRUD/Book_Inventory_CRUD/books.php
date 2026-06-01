<?php

include "db.php";
$title = $author = $year = $genre = "";
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

if (isset($_POST["update_btn"]) && !isset($_POST["delete_btn"])) {
    $id = (int) $_POST["id"];
    $title = clean($_POST["title"] ?? "");
    $author = clean($_POST["author"] ?? "");
    $year = clean($_POST["year"] ?? "");
    $genre = clean($_POST["genre"] ?? "");

    if (empty($title) || empty($author) || empty($year) || empty($genre)) {
        echo "<script>alert('All fields are required!');</script>";
    } elseif (!ctype_digit($year)) {
        echo "<script>alert('Year must be a number!');</script>";
    } elseif ($year < 1900 || $year > 2026) {
         echo "<script>alert('Year must be between 1900 and 2026!');</script>";
    } else {
        $year_int = (int) $year;  // ← CAST HERE
        
        $stmt = $conn->prepare("UPDATE books SET title = ?, author = ?, year = ?, genre = ? WHERE id = ?");
        $stmt->bind_param("ssisi", $title, $author, $year_int, $genre, $id);

        if ($stmt->execute()) {
            echo "<script>alert('Book Updated Sucessfully!');</script>";
            echo "<script>window.location.href=" . json_encode($_SERVER["PHP_SELF"]) . ";</script>";
            exit;
        } else {
            echo "<script>alert('Error Updating Book!');</script>";
        }
        $stmt->close();
    }
}

if (isset($_POST["delete_btn"]) && !isset($_POST["update_btn"])) {
    $id = (int) $_POST["id"];

    $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('Book Deleted!');</script>";
        echo "<script>window.location.href=" . json_encode($_SERVER["PHP_SELF"]) . ";</script>";
        exit;
    } else {
         echo "<script>alert('Error Deleting Book!');</script>";
    }

    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST["update_btn"]) && !isset($_POST["delete_btn"])) {
    $title = clean($_POST["title"] ?? "");
    $author = clean($_POST["author"] ?? "");
    $year = clean($_POST["year"] ?? "");
    $genre = clean($_POST["genre"] ?? "");

    if (empty($title) || empty($author) || empty($year) || empty($genre)) {
        echo "<script>alert('All fields are required!');</script>";
    } elseif (!ctype_digit($year)) {
        echo "<script>alert('Year must be a number and integer!');</script>";
    } elseif ($year < 1900 || $year > 2026) {
        echo "<script>alert('Year must be between 1900 and 2026!');</script>";
    } else {
        $year_int = (int) $year;  // ← CAST HERE
        
        $stmt = $conn->prepare('INSERT INTO books (title, author, year, genre) VALUES (?, ?, ?, ?)');
        $stmt->bind_param("ssis", $title, $author, $year_int, $genre);

        if ($stmt->execute()) {
            echo "<script>alert('Book Added Sucessfully!');</script>";
            echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
            exit;
        } else {
            echo "<script>alert('Error Adding Book!');</script>";
        }
        $stmt->close();
    }
}

if (isset($_GET["edit"])) {
    $edit = (int) $_GET["edit"];

    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param("i", $edit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $title = $row["title"];
        $author = $row["author"];
        $year = $row["year"];
        $genre = $row["genre"];
    }
    $stmt->close();
}

$result = $conn->query('SELECT * FROM books');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Book Inventory CRUD</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <h2><?= $edit ? 'Edit Book' : 'Add Book'; ?></h2>
    <form method="post" action="">
        <fieldset>
            <legend>Book Information</legend>
            <?php if ($edit) { ?>
            <input type="hidden" name="id" value="<?= escape_html($edit); ?>">
            <?php } ?>

            <label for="title">Title:</label>
            <input type="text" id="title" name="title" value="<?= escape_html($title); ?>" required><br><br>
            <label for="author">Author:</label>
            <input type="text" id="author" name="author" value="<?= escape_html($author); ?>" required><br><br>
            <label for="year">Year:</label>
            <input type="number" id="year" name="year" value="<?= escape_html($year); ?>" required><br><br>
            <label for="genre">Genre:</label>
            <select id="genre" name="genre" required>
                <option value="Fiction" <?= $genre === "Fiction" ? 'selected' : ""; ?>>Fiction</option>
                <option value="Mystery" <?= $genre === "Mystery" ? 'selected' : ""; ?>>Mystery</option>
                <option value="Science Fiction" <?= $genre === "Science Fiction" ? 'selected' : ""; ?>>Science Fiction
                </option>
                <option value="Biography" <?= $genre === "Biography" ? 'selected' : ""; ?>>Biography</option>
                <option value="Fantasy" <?= $genre === "Fantasy" ? 'selected' : ""; ?>>Fantasy</option>
            </select>
            <br><br>

            <?php if ($edit) { ?>
            <input type="submit" name="update_btn" value="Update Book">
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

        <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?= escape_html($row["id"]); ?></td>
            <td><?= escape_html($row["title"]); ?></td>
            <td><?= escape_html($row["author"]); ?></td>
            <td><?= escape_html($row["year"]); ?></td>
            <td><?= escape_html($row["genre"]); ?></td>
            <td>
                <a href="?edit=<?= escape_html($row["id"]); ?>">Edit</a>
                <form method="post" onsubmit="return confirm('Are you sure you want to delete this Book?');">
                    <input type="hidden" name="id" value="<?= escape_html($row["id"]); ?>">
                    <input type="submit" name="delete_btn" value="Delete">
                </form>
            </td>
        </tr>
        <?php } ?>
    </table>
    <?php } else { ?>
    <p><strong>No Books Found!</strong></p>
    <?php } ?>

</body>

</html>

<?php
$conn->close();
?>
