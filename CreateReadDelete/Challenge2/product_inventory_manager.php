<?php
include "db.php";
$product_name = $price = $quantity = $category = "";

function clean($data) {
    $data = trim($data ?? "");
    return $data;
}

if (isset($_POST['delete'])) {
    $id = (int)$_POST['delete'];

    $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('Info Deleted!');</script>";
        echo "<script>window.location.href='" . htmlspecialchars($_SERVER['PHP_SELF']) . "';</script>";
        exit;
    } else {
        echo "<script>alert('Error Deleting!');<script>";
        echo "<script>window.location.href='" . htmlspecialchars($_SERVER['PHP_SELF']) . "';</script>";
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = clean($_POST['product_name'] ?? "");
    $price = clean($_POST['price'] ?? "");
    $quantity = clean($_POST['quantity'] ?? "");
    $category = clean($_POST['category'] ?? "");

    if (empty($product_name) || empty($price) || empty($quantity) || empty($category)) {
        echo "<script>alert('All fields are required');</script>";
    // 2. Validate price: must be numeric, > 0, and have at most two decimals
    } elseif (!is_numeric($price) || $price <= 0) {
        echo "<script>alert('Price must be a number and greater than 0!');</script>";
    // Validate quantity: must be an integer >= 0
    } elseif (!is_numeric($quantity) || $quantity <= 0 || (int) $quantity != $quantity) {
    // (int)$quantity != $quantity checks for a fractional part
    // Example: if original $quantity = 5.5, (int)$quantity = 5, so 5 != 5.5 → true → error.  
        echo "<script>alert('Quantity must be a whole number!');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO products (product_name, price, quantity, category) VALUES (?,?,?,?)");
        $stmt->bind_param("siis", $product_name, $price, $quantity, $category);

        if ($stmt->execute()) {
            echo "<script>alert('Product Added!');</script>";
            echo "<script>window.location.href='" . htmlspecialchars($_SERVER['PHP_SELF']) . "';</script>";
        } else {
            echo "<script>alert('Error Adding Product');</script>";
        }
        $stmt->close();
    }
}

$result = $conn->query("SELECT * FROM products");

?>


<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Inventory Manager</title>
</head>

<body>
    <form method="post">
        <fieldset>
            <legend>Product Inventory Manager</legend>
            <label for="product_name">Product Name:</label>
            <input type="text" id="product_name" name="product_name" value="<?= $product_name; ?>" required><br><br>
            <label for="price">Price:</label>
            <input type="text" id="price" name="price" value="<?= $price; ?>" required><br><br>
            <label for="quantity">Quantity:</label>
            <input type="text" id="quantity" name="quantity" value="<?= $quantity; ?>" required><br><br>
            <label for="category">Category:</label>
            <select id="category" name="category" required><br><br>
                <option value="Electronics">Electronics</option>
                <option value="Clothing">Clothing</option>
                <option value="Books">Books</option>
                <option value="Other">Other</option>
            </select>
            <br><br>
            <input type="submit" value="submit">
        </fieldset>
    </form>
    <br><br>
    <h2>Products Table</h2>
    <?php if ($result->num_rows > 0) { ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Category</th>
                    <th>Added on</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?= $row['id']; ?></td>
                        <td><?= $row['product_name']; ?></td>
                        <td><?= $row['price']; ?></td>
                        <td><?= $row['quantity']; ?></td>
                        <td><?= $row['category']; ?></td>
                        <td><?= $row['created_at']; ?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('Delete thses data?');">
                                <input type="hidden" name="delete" value="<?= $row['id']; ?>">
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