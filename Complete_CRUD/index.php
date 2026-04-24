<?php
// ========== PART 1: ALWAYS START WITH THESE ==========
include "db.php";
//Initialize variables
$name = $email = "";
// Initialize edit mode flag
// Tracks if we're editing a user
// - null = We're adding a NEW user (shows "Add New User" form)
// - 5, 10, etc. = We're editing EXISTING user with that ID (shows "Edit User" form)
// Also prevents PHP warning about undefined variable when we first load the page without ?edit=ID
$edit = null;

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


// ========== PART 3: UPDATE ==========
// This part runs when someone clicks the "Update" button inside the edit form.
// The form sends a POST request with:
//   - Hidden input named "id" (contains the user ID to update)
//   - Text input named "name" (contains the new name)
//   - Text input named "email" (contains the new email)
//   - Submit button named "update_btn" (identifies this as an update action)

// 1. Check if the update button was clicked
//    isset($_POST['update_btn']) means: "Was the Update button clicked?"
//    This distinguishes update requests from create or delete requests
if (isset($_POST['update_btn'])) {

    // 2. Get the data from the form
    //    $_POST['id'] contains the hidden input with the user ID (like "5")
    //    We use (int) to cast it to an integer for safety
    //    If someone tries to send text, it becomes 0 (won't match any real user)
    $id = (int) $_POST['id'];

    //    Clean the name and email using our sanitization function
    //    Removes extra spaces and handles null values
    $name = clean($_POST["name"] ?? "");
    $email = clean($_POST['email'] ?? "");

    // ========== IMPORTANT: WHY VALIDATE AGAIN? ==========
    // Even though we already have validation in the CREATE section,
    // we MUST validate again here for these reasons:
    //
    // 1. DIFFERENT CONTEXT: The update form is a separate request.
    //    The CREATE validation ran on a different request (when user was added).
    //    PHP doesn't remember previous validations - each request is independent.
    //
    // 2. USER COULD BYPASS: A user could modify the HTML or use browser tools
    //    to submit invalid data directly to update_btn without ever using the
    //    create form. Validation must happen on EVERY submission.
    //
    // 3. SECURITY: Never trust user input, even if it passed validation before.
    //    Always validate the data you're about to use in THIS request.
    //
    // 4. DATA MIGHT CHANGE: The email that was valid when added might now
    //    conflict with another user's email during update.

    // 3. Validate the inputs (REQUIRED even though create already validated)
    if (empty($name) || empty($email)) {
        echo "<script>alert('Name and Email are required!')</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Validate email format using PHP's built-in filter
        // Returns false if email is invalid (missing @, bad domain, etc.)
        echo "<script>alert('Please enter a valid email address!')</script>";
    } else {
        // 4. Prepare the UPDATE statement
        //    We tell the database: "I want to update a user, but I'll tell you which one and what values later."
        //    The "?" are placeholders. This separates SQL code from data,
        //    which prevents SQL injection attacks.
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");

        // 5. Bind the parameters
        //    "ssi" means: string, string, integer (name, email, id)
        //    The database knows to treat each parameter as its correct data type
        $stmt->bind_param("ssi", $name, $email, $id);

        // 6. Execute the query
        //    This actually runs the UPDATE command.
        //    If it works, $stmt->execute() returns true.
        //    If something goes wrong, it returns false.
        if ($stmt->execute()) {
            // Success! Show a popup message.
            echo "<script>alert('User updated successfully!');</script>";

            // Then reload the page to show the updated list
            // window.location.href changes the browser's current address to the same page.
            // $_SERVER['PHP_SELF'] gives the current script's filename (like "crud.php").
            // Escape $_SERVER['PHP_SELF'] to prevent XSS attacks.
            echo "<script>window.location.href='" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES) . "';</script>";
            exit; // Stop script - don't display anything else
        } else {
            // 7. Handle database errors
            //    Check for duplicate email error
            //    1062 is MySQL's error code for duplicate entry (unique constraint violation)
            if ($stmt->errno == 1062) {
                echo "<script>alert('This email is already registered!')</script>";
                // Note: We DON'T clear $email here like in CREATE
                // In UPDATE mode, we keep the email so user can see what they tried
                // and modify it to a unique value
            } else {
                // Generic error for other database issues
                echo "<script>alert('Error updating user!')</script>";
                // ❌ NO redirect here
                // ✅ Let the page stay so user can:
                //    1. See the error message
                //    2. Fix the problem
                //    3. Try again
            }
        }
        // Always close prepared statements when done (prevents memory leaks)
        $stmt->close();
    }
}

// ========== PART 4: DELETE (POST) ==========
// This part runs when someone clicks the "Delete" button inside a form.
// The form sends a POST request with a hidden input named "delete"
// that contains the ID of the user to delete.

// 1. Check if the form was submitted
//    isset($_POST['delete']) means: "Is there a field called 'delete' in the POST data?"
//    This will be true when the delete button is clicked.
if (isset($_POST['delete_btn'])) {

    // 2. Get the user ID from the form and make sure it's a number
    //    $_POST['id'] contains the value from the hidden input (like "5")
    //    We use (int) to cast it to an integer. This forces it to be a number.
    //    If someone tries to send text, it becomes 0 (which won't match any real user).
    //    This adds an extra layer of safety.
    $id = (int) $_POST['id'] ?? 0;

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

        /// Then reload the page.
        // window.location.href changes the browser's current address to the same page.
        // $_SERVER['PHP_SELF'] gives the current script's filename (like "crud.php").
        // Escape $_SERVER['PHP_SELF'] because attackers can inject malicious code via the URL path.
        // ENT_QUOTES escapes both single AND double quotes - prevents breaking out of the JavaScript string
        echo "<script>window.location.href='" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES) . "';</script>";

        // Stop the script immediately – don't send any more HTML or PHP output.
        // The browser already got the redirect instruction.
        exit;
    } else {
        // Something went wrong (database error, connection issue, etc.)
        echo "<script>alert('Error deleting user!');</script>";
    }
}

// ========== PART 5: CREATE (CREATE ONLY!) ==========
// Only runs for CREATE operations (Add New User)
// Checks: It's a POST request AND not an update AND not a delete
// This prevents the create code from running when we're updating or deleting
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['update_btn']) && !isset($_POST['delete_btn'])) {
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

            // Escape $_SERVER['PHP_SELF'] because users can add malicious code to the URL
            // that could break out of this JavaScript string and cause XSS attacks.
            // attackers can inject malicious code via the URL path.
            echo "<script>window.location.href='" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES) . "';</script>";

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
            } else {
                echo "<script>alert('Error adding user!')</script>";
            }
        }
        // Always close prepared statements when done (prevents memory leaks)
        $stmt->close();
    }

}

// ========== PART 6: EDIT (GET) ==========
// This part runs when someone clicks the "Edit" link next to a user.
// The link sends a GET request with "?edit=ID" in the URL (like "?edit=5").
// 
// WHY GET? (Not POST)
// - GET is for READING data (doesn't change anything in database)
// - POST is for WRITING data (create, update, delete)
// - Edit just FETCHES existing data to display in the form
// - The actual UPDATE (saving changes) uses POST later

// 1. Check if an edit request was made
//    isset($_GET['edit']) means: "Is there '?edit=something' in the URL?"
//    This will be true when someone clicks the Edit link
if (isset($_GET['edit'])) {

    // 2. Get the user ID from the URL and make sure it's a number
    //    $_GET['edit'] contains the ID from the URL (like "5")
    //    We use (int) to cast it to an integer for safety
    //    If someone tries to send text, it becomes 0 (won't match any real user)
    //    This prevents SQL injection and invalid IDs
    //    $edit_id = "We are currently editing THIS user" (for the form)
    //    Simple: $edit_id shows the form, $id saves the data
    $edit = (int) $_GET['edit'] ?? 0;

    // 3. Prepare the SELECT statement
    //    We tell the database: "I want to get a user, but I'll tell you which one later."
    //    The "?" is a placeholder for the ID
    //    This separates SQL code from data, preventing SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");

    // 4. Bind the parameter
    //    "i" means integer - the database knows to treat $edit_id as a number
    $stmt->bind_param("i", $edit);

    // 5. Execute the query
    //    This runs the SELECT command and gets the user data
    $stmt->execute();

    // 6. Get the result set
    //    execute() only returns true/false. We need get_result() to actually retrieve the data.
    $result = $stmt->get_result();

    // 7. Check if a user was found
    //    num_rows > 0 means: "Did we find a user with that ID?"
    if ($result->num_rows > 0) {

        // 8. Fetch the user data into $row array
        //    $row becomes an array like: ['id'=>5, 'name'=>'John', 'email'=>'john@example.com']
        $row = $result->fetch_assoc();

        // 9. Populate the form fields with existing data
        //    These $name and $email variables will be used in the HTML form's "value" attributes
        //    This is what makes the form show the user's current information!
        $name = $row['name'];   // Example: "John Doe" appears in name field
        $email = $row['email']; // Example: "john@example.com" appears in email field

    }
    // 10. Close the statement (frees resources, prevents memory leaks)
    $stmt->close();
}

// ========== PART 7: READ DATA ==========
// Execute SELECT query to get all users
//MySQL, run this query!
$result = $conn->query("SELECT * FROM users");
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD - Create Read Update Delete</title>
</head>

<body>
    <!--This means:
    - If $edit has a value → show "Edit User"
    - If $edit is null → show "Add New User" -->
    <h2><?= $edit ? 'Edit User' : 'Add New User'; ?></h2>

    <form method="post" action="">
        <fieldset>
            <legend>Student Information</legend>
            <?php if ($edit) { ?>
                <input type="hidden" name="id" value="<?= $edit; ?>">
            <?php } ?>
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($name); ?>" required><br><br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email); ?>" required><br><br>
            <!-- Buttons change based on mode: Edit mode shows Update + Cancel, Add mode shows Add User only -->
            <?php if ($edit) { ?>
                <!-- Edit mode: Show Update button - submits form to save changes -->
                <input type="submit" name="update_btn" value="Update User">
                <!-- Edit mode: Show Cancel link - reloads page to exit edit mode -->
                <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">Cancel</a>
                <!-- If NOT in edit mode (null) -->
            <?php } else { ?>
                <!-- Add mode: Show Add User button only -->
                <input type="submit" value="Add User">
                <!-- End of if/else -->
            <?php } ?>
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
                    <td><?= htmlspecialchars($row["id"]); ?></td>
                    <!-- 
                        
                    COLUMN: NAME  
                    - $row["name"]: Accesses the 'name' field from current row
                    - Displays the user's name for this specific record
                    - htmlspecialchars(): Prevents XSS if name contains HTML characters
                    - Example: If current row has name="John", outputs "John"
                 -->
                    <td><?= htmlspecialchars($row["name"]); ?></td>
                    <!-- 
                    COLUMN EMAIL 
                    - $row["email"]: Accesses the 'email' field from current row
                    - Displays the user's email address for this record
                    - Example: If current row has email="john@example.com", outputs that
                -->
                    <td><?= htmlspecialchars($row["email"]); ?></td>

                    <td>
                        <!-- Edit link for each user -->
                        <a href="?edit=<?= htmlspecialchars($row['id']); ?>">Edit</a>

                        <!-- Delete form for each user – sends the user ID to PHP via POST -->
                        <form method="post" onsubmit="return confirm('Are you sure you want to delete this user?');">
                            <!-- Hidden field stores the current user ID from the database -->
                            <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']); ?>">
                            <!-- Visible delete button with clear action name -->
                            <input type="submit" name="delete_btn" value="Delete">
                        </form>

                    </td>
                </tr>
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
