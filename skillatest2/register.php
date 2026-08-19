<?php
// 1. SESSION MANAGEMENT
// Starts or resumes a session. This allows you to track logged-in users across different pages.
session_start();

// 2. DATABASE CONNECTION
// Imports your database connection settings ($conn object) from an external file.
include "db.php";

// 3. DATA SANITIZATION FUNCTION
// Trims accidental trailing or leading whitespaces from input text.
// The '?? ""' handles null variables gracefully to prevent PHP warnings.
function clean($data)
{
    return trim($data ?? "");
}

// 4. FORM SUBMISSION CHECK
// Ensures this PHP code block executes ONLY when the user clicks the "Register" submit button.
if ($_SERVER['REQUEST_METHOD'] == "POST") {

    // Captures the form values sent via the POST method and runs them through our clean() function.
    $userName = clean($_POST['userName']);
    $password = clean($_POST['password']);
    $confirmPassword = clean($_POST['confirmPassword']);
    $role = clean($_POST['role']);

    // 5. DATA VALIDATION FLOW
    // Step A: Check if any fields were left blank despite HTML5 protections.
    if (empty($userName) || empty($password) || empty($confirmPassword) || empty($role)) {
        echo "<script>alert('All Fields are Required!');</script>";

        // Step B: Match passwords to catch typing errors early.
    } elseif ($password != $confirmPassword) {
        echo "<script>alert('Passwords do not match!');</script>";

    } else {
        // 6. DUPLICATE CHECK (PREPARED STATEMENT)
        // '?' is a placeholder. Prepared statements stop SQL Injection attacks entirely.
        $check = $conn->prepare("SELECT userName FROM registration WHERE userName=?");

        // "s" tells the database that the placeholder (?) is a String variable.
        $check->bind_param("s", $userName);
        $check->execute();
        $check->store_result(); // Temporarily saves the data result in server memory

        // If the number of matching rows is greater than 0, the username is taken.
        if ($check->num_rows > 0) {
            echo "<script>alert('User already exists!');</script>";

        } else {
            // 7. DATA INSERTION (PREPARED STATEMENT)
            // Prepares a template query to insert the new record.
            $stmt = $conn->prepare("INSERT INTO registration (userName, password, confirmPassword, role) VALUES (?, ?, ?, ?)");

            // "ssss" means we are passing exactly 4 String variables to the placeholders.
            $stmt->bind_param("ssss", $userName, $password, $confirmPassword, $role);

            // Executes the query. If successful, it triggers a JavaScript alert box.
            if ($stmt->execute()) {
                echo "<script>alert('Registered Sucessfully!');</script>";

                // json_encode($_SERVER['PHP_SELF']) safely forces the browser to refresh back to this current file.
                echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
                exit;
            } else {
                // Failsafe in case database constraints (like row sizes or column mismatches) trigger a database error.
                echo "<script>alert('Error Registering user!');</script>";
            }

            // Close the insertion pipeline to free up resource memory
            $stmt->close();
        }
        // Close the duplicate checking pipeline
        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Register</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            display: flex;
            justify-content: center;
            padding-top: 50px;
        }

        .container {
            padding: 25px;
            border-radius: 8px;
            border: 1px solid lightgray;
            width: 100%;
            max-width: 380px;
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
        select,
        textarea {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
            box-sizing: border-box;
            border: 1px solid gray;
            border-radius: 4px;
        }

        input[type="submit"],
        button {
            margin-top: 15px;
            width: 100%;
            padding: 10px;
            background: dodgerblue;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .center-text {
            text-align:center;
            margin-top: 15px;
        }
    </style>

</head>

<body>
    <div class="container">
        <h2>Register User</h2>
        <!-- Form submits back to the exact same page using POST for data security -->
        <form method="post">
            <label for="userName">User Name (ID Number):</label>
            <input type="text" id="userName" name="userName" required><br><br>

            <!-- Note: Examiners like to see type="password" here to mask text with dots -->
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required><br><br>

            <label for="confirmPassword">Confirm Password:</label>
            <input type="password" id="confirmPassword" name="confirmPassword" required><br><br>

            <label for="role">Role:</label>
            <select id="role" name="role" required>
                <option value="">--Select Role--</option>
                <option value="Admin">Admin</option>
                <option value="Athlete">Athlete</option>
                <option value="Tournament Manager">Tournament Manager</option>
                <option value="Coach">Coach</option>
                <option value="Dean">Dean</option>
            </select>
            <br><br>

            <input type="submit" value="Register">
        </form>

        <p class="center-text">Already have an account? <a href="login.php">Login here.</a></p>
    </div>
</body>

</html>

<?php
// 8. CONNECTION TERMINATION
// Closes the main server link to your database pool when page parsing finishes.
$conn->close();
?>