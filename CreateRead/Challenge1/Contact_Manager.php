<?php

include "db.php";
$first_name = $last_name = $email = $phone = $contact_group = "";

function sanitize($data)
{
    $data = trim($data ?? "");
    return $data;
}

if (isset($_POST['delete'])) {
    $id = (int) $_POST['delete'];
    $stmt = $conn->prepare('DELETE FROM contacts Where id=?');
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('User deleted!');</script>";
        echo "<script>window.location.href='" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES) . "';</script>";
        exit;
    } else {
        echo "<script>alert('Error deleting user!');</script>";
        echo "<script>window.location.href='" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES) . "';</script>";
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $contact_group = sanitize($_POST['contact_group']);

    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($contact_group)) {
        echo "<script>alert('All fields are required')</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Email format is invalid!')</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO contacts (first_name, last_name, email, phone, contact_group) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssss', $first_name, $last_name, $email, $phone, $contact_group);

        if ($stmt->execute()) {
            echo "<script>alert('Contacts Added Successfully!');</script>";
            echo "<script>window.location.href='" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES) . "';</script>";
            exit;
        } else {
            if ($stmt->errno == 1062) {
                echo "<script>alert('This email is already registered!')</script>";
            } else {
                echo "<script>alert('Error Adding Successfully!')</script>";
            }
        }

        $stmt->close();
    }
}

$result = $conn->query('SELECT * FROM contacts');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Contact Manager</title>
</head>

<body>
    <form method="post" action="">
        <fieldset>
            <legend>INPUT INFORMATION</legend>
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($first_name); ?>"
                required><br><br>
            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($last_name); ?>"
                required><br><br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email); ?>" required><br><br>
            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($phone); ?>" required><br><br>
            <label for="contact_group">Contact Group:</label>
            <select id="contact_group" name="contact_group" required>
                <option value="Friends">Friends</option>
                <option value="Family">Family</option>
                <option value="Work">Work</option>
                <option value="Other">Other</option>
            </select>
            <br><br>
            <input type="submit" value="Submit">
        </fieldset>

        <br><br>
        <h2>List of Information</h2>
        <?php if ($result->num_rows > 0) { ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>FIRST NAME</th>
                        <th>LAST NAME</th>
                        <th>EMAIL</th>
                        <th>PHONE</th>
                        <th>CONTACT GROUP</th>
                        <th>ADDED ON</th>
                        <th>ACTION</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']); ?></td>
                            <td><?= htmlspecialchars($row['first_name']); ?></td>
                            <td><?= htmlspecialchars($row['last_name']); ?></td>
                            <td><?= htmlspecialchars($row['email']); ?></td>
                            <td><?= htmlspecialchars($row['phone']); ?></td>
                            <td><?= htmlspecialchars($row['contact_group']); ?></td>
                            <td><?= htmlspecialchars($row['created_at']); ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Delete this user?');">
                                    <input type="hidden" name="delete" value="<?= $row['id'] ?>">
                                    <input type="submit" value="Delete">
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p><strong>No contacts found.</strong></p>

        <?php } ?>
</body>

</html>

<?php
$conn->close();
?>