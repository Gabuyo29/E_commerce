<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    if (isset($_POST['delete_product_ids']) && is_array($_POST['delete_product_ids'])) {
        $product_ids = $_POST['delete_product_ids'];

        $conn = OpenCon();
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $types = str_repeat('i', count($product_ids));
        $stmt = $conn->prepare("DELETE FROM products WHERE product_id IN ($placeholders)");
        $stmt->bind_param($types, ...$product_ids);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Selected products deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Error deleting products: " . $stmt->error;
        }

        $stmt->close();
        CloseCon($conn);

        header("Location: product_list.php?action=edit");
        exit();
    } else {
        $_SESSION['error_message'] = "Error: No products selected for deletion.";
    }
}

if (isset($_GET['product_id']) && isset($_GET['size'])) {
    $product_id = intval($_GET['product_id']);
    $size = $_GET['size'];

    $conn = OpenCon();
    $stmt = $conn->prepare("SELECT price FROM products WHERE product_id = ? AND sizes = ?");
    $stmt->bind_param("is", $product_id, $size);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    CloseCon($conn);

    if ($data) {
        echo json_encode(['success' => true, 'price' => $data['price']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Price not found']);
    }
    exit();
}

$conn = OpenCon();
$result = $conn->query("SELECT * FROM products");
$products = $result->fetch_all(MYSQLI_ASSOC);
CloseCon($conn);

$is_edit_mode = isset($_GET['action']) && $_GET['action'] === 'edit';

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search_query !== '') {
    $conn = OpenCon();
    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ? OR category LIKE ?");
    $search_term = '%' . $search_query . '%';
    $stmt->bind_param("sss", $search_term, $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    CloseCon($conn);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'edit') {
    if (
        isset($_POST['edit_product_id'], $_POST['name'], $_POST['description'], 
              $_POST['price'], $_POST['category'], $_POST['stock'], $_POST['sizes'])
    ) {
        $product_id = $_POST['edit_product_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category = $_POST['category'];
        $stock = $_POST['stock'];
        $sizes = htmlspecialchars(trim($_POST['sizes']));
        $image = null;

        $conn = OpenCon();

        $stmt = $conn->prepare("SELECT image FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_product = $result->fetch_assoc();
        $current_image = $current_product['image'];
        $stmt->close();

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024;

            if (in_array($_FILES['image']['type'], $allowed_types) && $_FILES['image']['size'] <= $max_size) {
                $image = 'uploads/' . basename($_FILES['image']['name']);
                move_uploaded_file($_FILES['image']['tmp_name'], $image);

                if (!empty($current_image) && file_exists($current_image)) {
                    unlink($current_image);
                }
            } else {
                $_SESSION['error_message'] = "Invalid image file. Only JPG, PNG, and GIF files under 2MB are allowed.";
                header("Location: product_list.php?action=edit");
                exit();
            }
        }

        $query = "UPDATE products SET name = ?, description = ?, price = ?, category = ?, stock = ?, sizes = ?" . ($image ? ", image = ?" : "") . " WHERE product_id = ?";
        $stmt = $conn->prepare($query);

        if ($image) {
            $stmt->bind_param("ssdssssi", $name, $description, $price, $category, $stock, $sizes, $image, $product_id);
        } else {
            $stmt->bind_param("ssdssss", $name, $description, $price, $category, $stock, $sizes, $product_id);
        }

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Product updated successfully.";
        } else {
            $_SESSION['error_message'] = "Error updating product: " . $stmt->error;
        }

        $stmt->close();
        CloseCon($conn);

        header("Location: product_list.php?action=edit");
        exit();
    } else {
        $_SESSION['error_message'] = "Error: Missing required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product List</title>
    <link rel="stylesheet" href="css/product_list.css">
        
</head>
<body>

    <a href="admin_dashboard.php" style="text-decoration: none; background-color: #007bff; color: white; padding: 10px 15px; border-radius: 5px; margin-bottom: 20px; display: inline-block;">Return to Dashboard</a>

    <?php if ($is_edit_mode): ?>
     
        <div style="text-align: right; margin-bottom: 20px;">
            <a href="add_product_new.php" style="text-decoration: none; background-color: #007bff; color: white; padding: 10px 15px; border-radius: 4px; font-weight: bold;">Add Product</a>
        </div>
    <?php endif; ?>

    <h2><?php echo $is_edit_mode ? 'Edit Products' : 'View Products'; ?></h2>

   
    <form method="GET" action="product_list.php" style="margin-bottom: 20px;">
        <input type="hidden" name="action" value="<?php echo $is_edit_mode ? 'edit' : ''; ?>">
        <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search_query); ?>" style="padding: 8px; width: 300px; border: 1px solid #ccc; border-radius: 4px;">
        <button type="submit" style="padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Search</button>
    </form>

    <?php if (isset($_SESSION['success_message'])): ?>
        <p style="color: green; font-weight: bold;"><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if ($is_edit_mode): ?>
        <form method="POST" action="product_list.php?action=delete" id="deleteForm">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"></th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price ($)</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Image</th>
                        <th>Sizes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="9">No products available.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><input type="checkbox" name="delete_product_ids[]" value="<?php echo htmlspecialchars($product['product_id']); ?>"></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['description']); ?></td>
                                <td><?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td><?php echo intval($product['stock']); ?></td>
                                <td>
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image" style="width: 50px; height: 50px;">
                                    <?php else: ?>
                                        No Image
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['sizes']); ?></td>
                                <td>
                                    <form action="product_list.php?action=edit" method="POST" enctype="multipart/form-data" style="display: inline;">
                                        <input type="hidden" name="edit_product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                                        <input type="text" name="description" value="<?php echo htmlspecialchars($product['description']); ?>" required>
                                        <input type="number" name="price" step="0.01" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                                        <input type="text" name="category" value="<?php echo htmlspecialchars($product['category']); ?>" required>
                                        <input type="number" name="stock" value="<?php echo htmlspecialchars($product['stock']); ?>" required>
                                        <input type="text" name="sizes" value="<?php echo htmlspecialchars($product['sizes']); ?>" required>
                                        <input type="file" name="image" accept="image/*">
                                        <button type="submit">Save</button>
                                    </form>
                                    <a href="delete_product.php?product_id=<?php echo htmlspecialchars($product['product_id']); ?>" 
                                       style="text-decoration: none; background-color: #dc3545; color: white; padding: 5px 10px; border-radius: 4px; margin-left: 5px;"
                                       onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <button type="submit" style="margin-top: 10px; background-color: #dc3545; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;" onclick="return confirm('Are you sure you want to delete the selected products?');">Delete Selected</button>
        </form>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price ($)</th>
                    <th>Category</th>
                    <th>Stock</th>
                    <th>Image</th>
                    <th>Sizes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7">No products available.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['description']); ?></td>
                            <td><?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td><?php echo intval($product['stock']); ?></td>
                            <td>
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image" style="width: 50px; height: 50px;">
                                <?php else: ?>
                                    No Image
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['sizes']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <script>
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('input[name="delete_product_ids[]"]');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }
    </script>

</body>
</html>
