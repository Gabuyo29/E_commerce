<?php
session_start();
// Check admin authentication
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}


$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = "Successfully added to the Product list";
    unset($_SESSION['success_message']);
}

require_once 'connection.php';

// Fetch admin username and user_id
$conn = OpenCon();
$stmt = $conn->prepare("SELECT username, user_id FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($username_from_db, $user_id_from_db);
$stmt->fetch();
$stmt->close();
CloseCon($conn);

$username = $username_from_db ?: 'Admin';
$user_id = $user_id_from_db;


if ($user_id === null) {
    die("Error: User ID is not available. Please ensure the user is logged in and has a valid user ID.");
}

// Handle product add form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $sizes = $_POST['sizes'];
    $stocks = $_POST['stocks'];
    $image_path = '';

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $image_path = $upload_dir . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
    }

    $conn = OpenCon();

    // For each size, update stock or insert new product
    foreach ($sizes as $index => $size) {
        $stock = $stocks[$index];

        // Check if product with same details and size exists
        $stmt = $conn->prepare("SELECT stock FROM products WHERE name = ? AND description = ? AND price = ? AND category = ? AND sizes = ?");
        $stmt->bind_param("ssdss", $name, $description, $price, $category, $size);
        $stmt->execute();
        $stmt->bind_result($existing_stock);

        if ($stmt->fetch()) {
            // Update stock if product exists
            $stmt->close();
            $update_stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE name = ? AND description = ? AND price = ? AND category = ? AND sizes = ?");
            $update_stmt->bind_param("issdss", $stock, $name, $description, $price, $category, $size);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            // Insert new product if not exists
            $stmt->close();
            $unique_product_id = 'NC-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $insert_stmt = $conn->prepare("INSERT INTO products (product_id, name, description, price, category, stock, sizes, image, username, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("sssdsisssi", $unique_product_id, $name, $description, $price, $category, $stock, $size, $image_path, $username, $user_id);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
    }

    CloseCon($conn);

    // Redirect with success message
    $_SESSION['success_message'] = "Successfully added to the Product list";
    header("Location: product_list.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product</title>
    <link rel="stylesheet" href="css/add_product_new.css">
</head>
<body>

    <h2>Add New Product</h2>
    <p>Logged in as: <strong><?php echo htmlspecialchars($username); ?></strong></p>

    <a href="admin_dashboard.php">Back to Dashboard</a>

    <?php if (!empty($success_message)): ?>
        <p style="color: green; font-weight: bold;"><?php echo htmlspecialchars($success_message); ?></p>
    <?php endif; ?>

    <form action="add_product_new.php" method="POST" enctype="multipart/form-data">
        <!-- Product ID is auto-generated and readonly -->
        <label for="product_id">Product ID:</label>
        <input type="text" id="product_id" name="product_id" value="<?php echo 'NC-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT); ?>" readonly>

        <label for="name">Product Name:</label>
        <input type="text" id="name" name="name" required>

        <label for="description">Description:</label>
        <textarea id="description" name="description" rows="4" required></textarea>

        <label for="price">Price (₱):</label>
        <input type="number" id="price" name="price" step="1" required>

        <label for="category">Category:</label>
        <select id="category" name="category" required>
            <option value="">--Select Category--</option>
            <option value="Womens_Wear" <?php echo isset($_POST['category']) && $_POST['category'] === 'womens_wear' ? 'selected' : ''; ?>>Womens Wear</option>
            <option value="Mens_Wear" <?php echo isset($_POST['category']) && $_POST['category'] === 'mens_wear' ? 'selected' : ''; ?>>Men's Wear</option>
        </select>

        <label for="sizes">Choose Sizes and Stock:</label>
        <div id="size-stock-container">
            <div>
                <select name="sizes[]" required>
                    <option value="XS">XS</option>
                    <option value="S">S</option>
                    <option value="M">M</option>
                    <option value="L">L</option>
                    <option value="XL">XL</option>
                    <option value="XXL">XXL</option>
                </select>
                <input type="number" name="stocks[]" placeholder="Stock Quantity" required>
            </div>
        </div>

        <label for="image">Image:</label>
        <input type="file" id="image" name="image" accept="image/*">

        <button type="submit">Add Product</button>
    </form>
</body>
</html>
</html>
</html>
