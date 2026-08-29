<?php
// 1. SESSION MANAGEMENT
// Starts or resumes a session. This allows you to track logged-in users across different pages.
session_start();

// 2. DATABASE CONNECTION
// Imports your database connection settings ($conn object) from an external file.
include "db.php";
$userName = $password = "";

// 3. DATA SANITIZATION FUNCTION
// Trims accidental trailing or leading whitespaces from input text.
// The '?? ""' handles null variables gracefully to prevent PHP warnings.
function clean($data)
{
    return trim($data ?? "");
}

// 4. FORM SUBMISSION CHECK
// Ensures this PHP code block executes ONLY when the user clicks the "Login" submit button.
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Captures the form values sent via the POST method and runs them through our clean() function.
    $userName = clean($_POST['userName']);
    $password = clean($_POST['password']);

    // 5. DATA VALIDATION FLOW
    // Step A: Check if any fields were left blank despite HTML5 protections.
    if (empty($userName) || empty($password)) {
        echo "<script>alert('All fields are required!');</script>";
    } else {
        // 6. CREDENTIAL CHECK (PREPARED STATEMENT)
        // '?' is a placeholder. Prepared statements stop SQL Injection attacks entirely.
        $stmt = $conn->prepare("SELECT * FROM registration WHERE userName = ? AND password = ?");

        // "ss" tells the database both placeholders (?) are String variables.
        $stmt->bind_param("ss", $userName, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        // If a matching row was found, the credentials are correct.
        if ($result->num_rows > 0) {
            // 7. SESSION CREATION
            // Stores the logged-in user's info in the session so other pages can check it.
            $row = $result->fetch_assoc();
            $_SESSION['userName'] = $row['userName'];
            $_SESSION['role'] = $row['role'];

            // 8. ROLE-BASED REDIRECT
            // Sends each role straight to their own page (no shared dashboard needed).
            switch ($row['role']) {
                case 'Admin':
                    $page = "admin.php";
                    break;
                case 'Athlete':
                    $page = "athlete.php";
                    break;
                case 'Tournament Manager':
                    $page = "tournamentmanager.php";
                    break;
                case 'Coach':
                    $page = "coach.php";
                    break;
                case 'Dean':
                    $page = "dean.php";
                    break;
                default:
                    $page = "login.php";
            }

            echo "<script>alert('Login Successful!');</script>";
            echo "<script>window.location.href=" . json_encode($page) . ";</script>";
            exit;
        } else {
            // Failsafe if username/password combination doesn't match any row.
            echo "<script>alert('Invalid username or password!');</script>";
        }

        // Close the login checking pipeline
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Login</title>
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
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Login</h2>
        <!-- Form submits back to the exact same page using POST for data security -->
        <form method="post">
            <label for="userName">User Name (ID Number):</label>
            <input type="text" id="userName" name="userName" required><br><br>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required><br><br>

            <input type="submit" value="Login">
        </form>

        <p class="center-text">No account yet? <a href="register.php">Register</a></p>
    </div>
</body>

</html>

<?php
// 9. CONNECTION TERMINATION
// Closes the main server link to your database pool when page parsing finishes.
$conn->close();
?>
