<?php
// ========== PART 1: ALWAYS START WITH THESE ==========
include "db.php";
//Initialize variables
$name = $email = "";

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

// ========== DELETE (POST) ==========
// This part runs when someone clicks the "Delete" button inside a form.
// The form sends a POST request with a hidden input named "id"
// that contains the ID of the user to delete, and a submit button named "delete_btn".

// 1. Check if the delete button was clicked
if (isset($_POST['delete_btn'])) {

    // 2. Get the user ID from the form and make sure it's a number
    //    $_POST['id'] contains the value from the hidden input (like "5")
    //    We use (int) to cast it to an integer. This forces it to be a number.
    //    If someone tries to send text, it becomes 0 (which won't match any real user).
    //    This adds an extra layer of safety.
    $id = (int) $_POST['id'];

    // 3. Prepare the DELETE statement
    //    We tell the database: "I want to delete a user, but I'll tell you which one later."
    //    The "?" is a placeholder. This separates the SQL code from the actual data,
    //    which prevents SQL injection attacks.
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");

    // 4. Bind the parameter
    //    Now we give the database the actual ID number.
    //    "i" means the data type is integer. The database knows to treat $id as a number,
    //    even if it originally came from a form.
    $stmt->bind_param("i", $id);

    // 5. Execute the query
    //    This actually runs the DELETE command.
    //    If it works, $stmt->execute() returns true. If something goes wrong, it returns false.
    if ($stmt->execute()) {
        // Success! Show a popup message.
        echo "<script>alert('User deleted!');</script>";

        // Then reload the page using json_encode for a safe JavaScript string.
        // json_encode() wraps the URL in double quotes and escapes any dangerous characters.
        // This is safer than htmlspecialchars() inside <script>.
        echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";

        // Stop the script immediately – don't send any more HTML or PHP output.
        // The browser already got the redirect instruction.
        exit;
    } else {
        // Something went wrong (database error, connection issue, etc.)
        echo "<script>alert('Error deleting user!');</script>";
    }
    $stmt->close();
}

// ========== PART 3: CREATE (CREATE ONLY!) ==========
// This block runs when the form is submitted AND the delete button was NOT clicked.
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['delete_btn'])) {
    // 1. Clean inputs
    // ?? "" means: use the form input if it exists, otherwise use an empty string (prevents undefined index warnings)
    $name = clean($_POST["name"] ?? "");
    $email = clean($_POST["email"] ?? "");

    // 2. Simple validation
    if (empty($name) || empty($email)) {
        echo "<script>alert('Name and Email are required!')</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Validate email format using PHP's built-in filter
        // Returns false if email is invalid (missing @, bad domain, etc.)
        echo "<script>alert('Please enter a valid email address!')</script>";
    } else {
        // 3. Prepared statement for security
        //prepare() separates CODE from DATA
        // Database gets ONLY the structure/template: "INSERT INTO users (name, email) VALUES (?, ?)"
        // ? = Placeholder = "I'll give you this value later"
        // 1. PREPARE = Get the recipe card
        // Recipe says: "Make sandwich with [BREAD], [FILLING], [CONDIMENT]"
        $stmt = $conn->prepare("INSERT INTO users (name, email) VALUES (?, ?)");

        // Then you send data separately:
        // 2. BIND_PARAM = Gather ingredients
        // Ingredients: ?1 = "Whole Wheat", ?2 = "Turkey", ?3 = "Mayo"

        $stmt->bind_param("ss", $name, $email);
        // Even if $name = "John'; DROP TABLE users; --"
        // Database treats it as: "Oh, this is just TEXT for the name field"
        // NOT as: "This is SQL code to execute"

        // Step 3: Execute the query and check if successful
        // Returns true if successful, false if failed
        if ($stmt->execute()) {
            // window.location.href holds the current page URL.
            // Assigning a new value makes the browser load that page immediately.
            // Here we redirect to the exact same script using $_SERVER['PHP_SELF'].
            // The PHP part: '" . $_SERVER['PHP_SELF'] . "' builds the URL string:
            // - The outer quotes are part of the PHP string (echo).
            // - Inside, we open a JavaScript string with a single quote.
            // - Then we concatenate the PHP value ($_SERVER['PHP_SELF']).
            // - Finally we close the JavaScript string with another single quote and a semicolon.
            // The final JavaScript line becomes: window.location.href = '/current/script.php';
            echo "<script>alert('User Added Successfully!');</script>";

            // Use json_encode for safe JavaScript redirect (handles all special characters).
            echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";

            // Stop script execution – nothing else (like the HTML form or contact list) will be sent,
            // because the browser will immediately follow the redirect after showing the alert.
            exit;

        } else {
            //Failure: Check for specific errors
            // Check for duplicate email error
            //Think of 1062 as a "Data Quality Guardian"
            // Translation: "If $conn's error number is 1062"
            // 1062 = "email already exists"

            if ($stmt->errno == 1062) {
                echo "<script>alert('This email is already registered!')</script>";
                $email = "";
            } else {
                echo "<script>alert('Error adding user!')</script>";
            }
        }
        // Always close prepared statements when done (prevents memory leaks)
        $stmt->close();
    }

}
// ========== PART 4: READ DATA ==========
// Execute SELECT query to get all users
//MySQL, run this query!
$result = $conn->query("SELECT * FROM users");
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CreateReadDelete</title>
</head>

<body>
    <h2>Add New Record</h2>
    <form method="post" action="">
        <fieldset>
            <legend>Student Information</legend>
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?= escape_html($name); ?>" required><br><br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= escape_html($email); ?>" required><br><br>
            <input type="submit" value="submit">
        </fieldset>
    </form>

    <hr>

    <!-- ========== READ/DISPLAY SECTION ========== -->
    <h2>All Record</h2>

    <!-- 
            RECORD COUNT DISPLAY
            - $result->num_rows: Returns number of rows in result set
            - Shows user how many records exist in database
            - Good UX: Provides feedback about data volume
            - Read it like "if the number of rows in the result set is greater than 0".
     -->

    <?php if ($result->num_rows > 0) { ?>
        <p><strong>Total Records: <?php echo $result->num_rows; ?></strong></p>

        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Action</th>
            </tr>
            <!-- 
                    DATA ROW LOOP
                    - fetch_assoc(): Gets next row as associative array
                    - Loop continues until all rows are processed
                    - Each iteration creates one table row (<tr>)
                    - Read it like "Get one result at a time and assign it to $row".
            -->
            <?php while ($row = $result->fetch_assoc()) { ?>

                <!-- 
                ====================================================================
                IMPORTANT: When to use htmlspecialchars()
                - Use htmlspecialchars() EVERY time you output user-supplied data 
                (or any data that might contain HTML characters) into an HTML context.
                - Why? It converts characters like <, >, &, ", ' into HTML entities 
                (e.g., < becomes &lt;). This prevents the browser from interpreting 
                them as code, thus stopping Cross-Site Scripting (XSS) attacks.
                - Always apply it at the moment of output, NOT before storing in DB.
                - We now use the helper escape_html() which includes full escaping (ENT_QUOTES, UTF-8).
                ====================================================================
                 -->

                <tr>
                    <!-- 
                     COLUMN: ID
                    - $row["id"]: Accesses the 'id' field from current row's data
                    - Equivalent to: "From the current record, get the value in the 'id' column"
                    - Column name must match database column name exactly
                    - Example: If current row has id=5, outputs "5"
                    -->
                    <td><?= escape_html($row["id"]); ?></td>
                    <!-- 
                        
                    COLUMN: NAME  
                    - $row["name"]: Accesses the 'name' field from current row
                    - Displays the user's name for this specific record
                    - htmlspecialchars(): Prevents XSS if name contains HTML characters
                    - Example: If current row has name="John", outputs "John"
                 -->
                    <td><?= escape_html($row["name"]); ?></td>
                    <!-- 
                    COLUMN EMAIL 
                    - $row["email"]: Accesses the 'email' field from current row
                    - Displays the user's email address for this record
                    - Example: If current row has email="john@example.com", outputs that
                -->
                    <td><?= escape_html($row["email"]); ?></td>

                    <td>
                        <!-- Delete form for each user – sends the user ID to PHP via POST -->
                        <form method="post" onsubmit="return confirm('Are you sure you want to delete this user?');">
                            <!-- Hidden field stores the current user ID from the database -->
                            <input type="hidden" name="id" value="<?= escape_html($row['id']); ?>">
                            <!-- Visible delete button with clear action name -->
                            <input type="submit" name="delete_btn" value="Delete">
                        </form>
                    </td>
                </table>
            <?php } ?>
        </table>
    <?php } else { ?>
        <!-- 
            EMPTY STATE MESSAGE
            - Shows when database table has no records
            - Good UX: Guides user on what to do next
            - Alternative: Could show "No records found" for search results
        -->
        <p>No users found. Add your first user above!</p>
    <?php } ?>
    <!-- 
        CONDITIONAL DISPLAY END
        - Clean separation of "has data" vs "no data" states
        - Only one branch executes based on $result->num_rows
    -->

</body>

</html>

<?php
// ========== DATABASE CLEANUP ==========
// 
// CLOSE DATABASE CONNECTION
// - Frees up database server resources
// - Prevents connection limit issues
// - Good practice even though PHP auto-closes at script end
// - Note: Should be after all database operations are complete
$conn->close();
?>
