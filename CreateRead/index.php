<?php
// ========== PART 1: ALWAYS START WITH THESE ==========
include "db.php";
$name = $email = "";
$success_message = "";
$error_message = "";

// ========== PART 2: SANITIZATION FUNCTION ==========
function clean($data)
{
    // trim - remove any spaces/newlines from the start and end
    // null coalescing -SAFE converts null to empty string
    $data = trim($data ?? "");
    // Removes backslashes that were added to escape quotes/special characters
    // Example: Converts "O\'Connor" to "O'Connor"
    $data = stripslashes($data);
    // Sanitize output: Prevent XSS, encode all quotes, UTF-8 safe
    // Now it displays as TEXT, won't execute as script
    // ENT_QUOTES: Encode both single and double quotes
    // UTF-8: Ensure proper handling of international characters
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    // With ENT_QUOTES:
    // Result: <input value="&#039;; alert(&#039;HACKED!&#039;); //">
    // SAFE! The ' becomes &#039; (just text, not code)
    return $data;
}

// ========== PART 3: CREATE (CREATE ONLY!) ==========
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Clean inputs
    $name = clean($_POST["name"]);
    $email = clean($_POST["email"]);

    // 2. Simple validation
    if (empty($name) || empty($email)) {
        $error_message = "<script>alert('Name and Email are required!')</script>";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Validate email format using PHP's built-in filter
        // Returns false if email is invalid (missing @, bad domain, etc.)
        $error_message = "<script>alert('Please enter a valid email address!')</script>";
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
            // Success: Query ran without errors
            $success_message = "<script>alert('User Added successfully!')</script>";
            //Clear form after success
            $name = $email = "";

        } else {
            //Failure: Check for specific errors
            // Check for duplicate email error
            //Think of 1062 as a "Data Quality Guardian"
            // Translation: "If $conn's error number is 1062"
            // 1062 = "email already exists"
            if ($conn->errno == 1062) {
                $error_message = "<script>alert('This email is already registered!')</script>";
            } else {
                $error_message = "<script>alert('Error adding user!')</script>";
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
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($name); ?>" required><br><br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email); ?>" required><br><br>
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