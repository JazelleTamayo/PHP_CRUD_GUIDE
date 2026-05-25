<<?php
// ========== PART 1: ALWAYS START WITH THESE ==========
include "db.php";
$name = $email = "";

// ========== HELPER FUNCTION FOR SAFE HTML OUTPUT ==========
// Descriptive name: escape_html()
// Converts special characters to HTML entities to prevent XSS attacks.
// - ENT_QUOTES: escapes both double and single quotes
// - ENT_SUBSTITUTE: replaces invalid UTF-8 sequences with � (safe)
// - ENT_HTML5: uses HTML5 entity rules
// - 'UTF-8': matches your page encoding (see meta charset)
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

// ========== PART 3: CREATE (CREATE ONLY!) ==========
if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
        //   json_encode() safely embeds a PHP string into JavaScript:
        / json_encode() wraps string in double quotes and escapes ", \, /, \n, \t, etc.
        // It does NOT convert & or ' – safe for URLs inside JavaScript.
        //   - Unlike htmlspecialchars(), it does NOT convert & to &amp; (which would break URLs).
        //   - Use escape_html() for HTML contexts (attributes, tags); use json_encode() for <script>.
            echo "<script>alert('Contacts Added Successfully!');</script>";
            echo "<script>window.location.href=" . json_encode($_SERVER['PHP_SELF']) . ";</script>";
            exit;

        } else {
            //Failure: Check for specific errors
            // Check for duplicate email error
            //Think of 1062 as a "Data Quality Guardian"
            // Translation: "If $conn's error number is 1062"
            // 1062 = "email already exists"
            //=== checks both value and type
            //Use === – it's more explicit, type-safe, and follows best practices. == also works, but === is preferred.

            if ($stmt->errno === 1062) {
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
    <title>CreateRead</title>
</head>

<body>
    <h2>Add New Record</h2>
    <form method="post" action="">
        <fieldset>
            <legend>Student Information</legend>
            <label for="name">Name:</label>
            <!--value is essential for persisting data between requests and for editing-->
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
