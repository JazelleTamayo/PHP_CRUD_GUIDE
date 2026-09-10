<?php
session_start();

// Block access if not logged in or role is not Admin
if (!isset($_SESSION['userName']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

include "db.php"; // $conn = mysqli connection
$deptID = $deptName = "";
$edit = null;

// UPDATE: runs only when the Update button was clicked (not delete)
if (isset($_POST['update_btn']) && !isset($_POST['delete_btn'])) {
    $deptID = $_POST['deptID'];
    $deptName = $_POST['deptName'];

    if ($deptID == "" || empty($deptName)) {
        echo "<script>alert('All fields are Required!');</script>";
    } else {
        $stmt = $conn->prepare("UPDATE department SET deptName = ? WHERE deptID = ?");
        $stmt->bind_param('si', $deptName, $deptID); // s=string, i=int

        if ($stmt->execute()) {
            echo "<script>alert('Department updated!');</script>";
            echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
            exit;
        } else {
            echo "<script>alert('Error Updating!');</script>";
        }
        $stmt->close();
    }

}


// DELETE: runs only when the delete button was clicked (not update)
if (isset($_POST['delete_btn']) && !isset($_POST['update_btn'])) {
    $deptID = $_POST['deptID'];

    $stmt = $conn->prepare('DELETE FROM department WHERE deptID = ?');
    $stmt->bind_param('i', $deptID);

    if ($stmt->execute()) {
        echo "<script>alert('Department deleted!');</script>";
        echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
        exit;
    } else {
        echo "<script>alert('Error Deleting Department!');</script>";
    }
    $stmt->close();

}


// ADD: plain POST with no update_btn/delete_btn (Add button has no name="")
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['update_btn']) && !isset($_POST['delete_btn'])) {
    $deptID = $_POST['deptID'];
    $deptName = $_POST['deptName'];

    if ($deptID == "" || empty($deptName)) {
        echo "<script>alert('All Fields are required!');</script>";
    } else {
        $stmt = $conn->prepare('INSERT INTO department (deptID, deptName) VALUES (?,?)');
        $stmt->bind_param('is', $deptID, $deptName);

        if ($stmt->execute()) {
            echo "<script>alert('Department Added!');</script>";
            echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
            exit;
        } else {
            echo "<script>alert('Error Adding Department! ID may already exist.');</script>";
        }
        $stmt->close();
    }
}


// EDIT MODE: when ?edit=<id> is in the URL, fetch the record to preload the form
if (isset($_GET['edit'])) {
    $edit = $_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM department WHERE deptID = ?');
    $stmt->bind_param('i', $edit);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $deptID = $row['deptID'];
        $deptName = $row['deptName'];
    }
    $stmt->close();
}

// All records for the table below (overwrites $result from the edit block above)
$result = $conn->query('SELECT * FROM department');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>ADMIN UI</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 50px;
        }

        .container {
            width: 100%;
            max-width: 380px;
            padding: 25px;
            border: solid 1px lightgray;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        h2 {
            text-align: center;
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }

        input,
        button,
        textarea {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            box-sizing: border-box;
            border: 1px solid gray;
            margin-top: 4px;
        }

        input[type='submit'],
        button {
            width: auto;
            padding: 10px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background: dodgerblue;
            margin-top: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid lightgray;
            padding: 8px;
            text-align: left;
        }

        td form {
            display: inline;
        }

    </style>

</head>

<body>
    <div class="container">
        <!-- Single form used for both add and edit, depending on $edit -->
        <form method="post">
            <h2><?= $edit ? 'Edit Department' : 'Add Department'; ?></h2>


            <label for="deptID">Department ID:</label>
            <!-- readonly in edit mode so the primary key can't be changed -->
            <input type="number" id="deptID" name="deptID" value="<?= $deptID; ?>" <?= $edit ? 'readonly' : ''; ?>
                required><br><br>
            <label for="deptName">Department Name:</label>
            <input type="text" id="deptName" name="deptName" value="<?= $deptName; ?>" required><br><br>

            <?php if ($edit) { ?>
                <input type="submit" name="update_btn" value="Update Department">
                <a href="<?= $_SERVER['PHP_SELF']; ?>">Cancel</a>
            <?php } else { ?>
                <!-- no name="" here so it's detected as a plain add by the PHP logic above -->
                <input type="submit" value="Add Department">
            <?php } ?>
        </form>
        <br><br>
    </div>
    <div class="container2">
        <h2>All Records</h2>
        <?php if ($result->num_rows) { ?>
            <p><strong>Total Records: <?php echo $result->num_rows; ?></strong></p>

            <table>
                <tr>
                    <th>Department ID</th>
                    <th>Department Name</th>
                    <th>Actions</th>
                </tr>

                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?= $row['deptID']; ?></td>
                        <td><?= $row['deptName']; ?></td>
                        <td>
                            <a href="?edit=<?= $row['deptID']; ?>">Edit</a>
                            <!-- separate mini-form per row so delete only affects that row's deptID -->
                            <form method="post" onsubmit="return confirm('Are you sure you want to delete this?');">
                                <input type="hidden" name="deptID" value="<?= $row['deptID']; ?>">
                                <input type="submit" name="delete_btn" value="delete">
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </table>

        <?php } else { ?>
            <p>No Department Found! Add Department Above.</p>
        <?php } ?>
    </div>

</body>

</html>

<?php
$conn->close();
?>
