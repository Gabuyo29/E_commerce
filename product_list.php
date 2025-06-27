<?php
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';

// Handle product deletion
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

// Handle add size to product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'add_size') {
    // Add new size for an existing product
    if (
        isset($_POST['base_product_id'], $_POST['new_size'], $_POST['new_stock'])
        && $_POST['new_size'] !== '' && is_numeric($_POST['new_stock'])
    ) {
        $base_product_id = $_POST['base_product_id'];
        $new_size = htmlspecialchars(trim($_POST['new_size']));
        $new_stock = intval($_POST['new_stock']);

        $conn = OpenCon();
        // Get base product details
        $stmt = $conn->prepare("SELECT name, description, price, category, image, username, user_id FROM products WHERE product_id = ? LIMIT 1");
        $stmt->bind_param("s", $base_product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $base = $result->fetch_assoc();
        $stmt->close();

        if ($base) {
            // Check if size already exists for this product
            $stmt = $conn->prepare("SELECT product_id FROM products WHERE name = ? AND description = ? AND price = ? AND category = ? AND sizes = ?");
            $stmt->bind_param("ssdss", $base['name'], $base['description'], $base['price'], $base['category'], $new_size);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 0) {
                $stmt->close();
                $new_product_id = 'NC-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                $insert = $conn->prepare("INSERT INTO products (product_id, name, description, price, category, stock, sizes, image, username, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insert->bind_param(
                    "sssdsisssi",
                    $new_product_id,
                    $base['name'],
                    $base['description'],
                    $base['price'],
                    $base['category'],
                    $new_stock,
                    $new_size,
                    $base['image'],
                    $base['username'],
                    $base['user_id']
                );
                $insert->execute();
                $insert->close();
                $_SESSION['success_message'] = "New size added successfully.";
            } else {
                $stmt->close();
                $_SESSION['error_message'] = "This size already exists for the product.";
            }
        } else {
            $_SESSION['error_message'] = "Base product not found.";
        }
        CloseCon($conn);
        header("Location: product_list.php?action=edit");
        exit();
    } else {
        $_SESSION['error_message'] = "Please provide a valid size and stock.";
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

// Fetch all products 
$conn = OpenCon();
$result = $conn->query("SELECT * FROM products");
$products = $result->fetch_all(MYSQLI_ASSOC);
CloseCon($conn);

$products = array_filter($products, function($product) {
    $cat = strtolower($product['category']);
    return $cat !== 'sports_wear' && $cat !== 'sportswear' && $cat !== 'sport wear';
});
$products = array_values($products);

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

   
    $products = array_filter($products, function($product) {
        $cat = strtolower($product['category']);
        return $cat !== 'sports_wear' && $cat !== 'sportswear' && $cat !== 'sport wear';
    });
    $products = array_values($products);
}

// Handle product edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'edit') {
    if (
        isset($_POST['edit_product_id'], $_POST['name'], $_POST['description'], 
              $_POST['price'], $_POST['category'], $_POST['stock'], $_POST['sizes'], $_POST['active'])
    ) {
        $product_id = $_POST['edit_product_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category = $_POST['category'];
        $stock = $_POST['stock'];
        $sizes = htmlspecialchars(trim($_POST['sizes']));
        $active = intval($_POST['active']);
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

        $query = "UPDATE products SET name = ?, description = ?, price = ?, category = ?, stock = ?, sizes = ?, active = ?" . ($image ? ", image = ?" : "") . " WHERE product_id = ?";
        $stmt = $conn->prepare($query);

        if ($image) {
            $stmt->bind_param("ssdsssisi", $name, $description, $price, $category, $stock, $sizes, $active, $image, $product_id);
        } else {
            $stmt->bind_param("ssdssssi", $name, $description, $price, $category, $stock, $sizes, $active, $product_id);
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
        <!-- Product edit/delete table -->
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
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="10">No products available.</td>
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
                                <td><?php echo ($product['active'] == 1) ? 'Active' : 'Inactive'; ?></td>
                                <td>
                                    <button type="button"
                                        onclick="openEditModal(
                                            '<?php echo htmlspecialchars($product['product_id']); ?>',
                                            '<?php echo htmlspecialchars(addslashes($product['name'])); ?>',
                                            '<?php echo htmlspecialchars(addslashes($product['description'])); ?>',
                                            '<?php echo htmlspecialchars($product['price']); ?>',
                                            '<?php echo htmlspecialchars(addslashes($product['category'])); ?>',
                                            '<?php echo htmlspecialchars($product['stock']); ?>',
                                            '<?php echo htmlspecialchars($product['sizes']); ?>',
                                            '<?php echo htmlspecialchars($product['active']); ?>'
                                        )"
                                        style="background:#007bff;color:white;padding:5px 10px;border:none;border-radius:4px;">Edit</button>
                                    <a href="delete_product.php?product_id=<?php echo htmlspecialchars($product['product_id']); ?>" 
                                       style="text-decoration: none; background-color: #dc3545; color: white; padding: 5px 10px; border-radius: 4px; margin-left: 5px;"
                                       onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                                    <button type="button" onclick="showAddSizeForm('<?php echo htmlspecialchars($product['product_id']); ?>')" style="margin-left:5px;background:#28a745;color:white;padding:5px 10px;border:none;border-radius:4px;">Add Size</button>
                                    <form action="product_list.php?action=add_size" method="POST" style="display:none;margin-top:5px;" class="add-size-form" id="add-size-form-<?php echo htmlspecialchars($product['product_id']); ?>">
                                        <input type="hidden" name="base_product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
                                        <select name="new_size" required>
                                            <option value="">Select Size</option>
                                            <option value="XS">XS</option>
                                            <option value="S">S</option>
                                            <option value="M">M</option>
                                            <option value="L">L</option>
                                            <option value="XL">XL</option>
                                            <option value="XXL">XXL</option>
                                        </select>
                                        <input type="number" name="new_stock" placeholder="Stock" min="1" required style="width:70px;">
                                        <button type="submit" style="background:#28a745;color:white;padding:2px 8px;border:none;border-radius:3px;">Add</button>
                                        <button type="button" onclick="hideAddSizeForm('<?php echo htmlspecialchars($product['product_id']); ?>')" style="background:#ccc;color:black;padding:2px 8px;border:none;border-radius:3px;">Cancel</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <button type="submit" style="margin-top: 10px; background-color: #dc3545; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;" onclick="return confirm('Are you sure you want to delete the selected products?');">Delete Selected</button>
        </form>

        <!-- Edit Modal -->
        <div id="editModal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);z-index:1000;align-items:center;justify-content:center;">
            <div style="background:#fff;padding:30px 20px;border-radius:8px;max-width:400px;margin:60px auto;position:relative;">
                <form id="editProductForm" action="product_list.php?action=edit" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="edit_product_id" id="edit_product_id">
                    <div>
                        <label>Name:</label>
                        <input type="text" name="name" id="edit_name" required>
                    </div>
                    <div>
                        <label>Description:</label>
                        <input type="text" name="description" id="edit_description" required>
                    </div>
                    <div>
                        <label>Price:</label>
                        <input type="number" name="price" id="edit_price" step="0.01" required>
                    </div>
                    <div>
                        <label>Category:</label>
                        <input type="text" name="category" id="edit_category" required>
                    </div>
                    <div>
                        <label>Stock:</label>
                        <input type="number" name="stock" id="edit_stock" required>
                    </div>
                    <div>
                        <label>Sizes:</label>
                        <input type="text" name="sizes" id="edit_sizes" required>
                    </div>
                    <div>
                        <label>Active:</label>
                        <select name="active" id="edit_active" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label>Image:</label>
                        <input type="file" name="image" accept="image/*">
                    </div>
                    <div style="margin-top:10px;">
                        <button type="submit" style="background:#007bff;color:white;padding:5px 15px;border:none;border-radius:4px;">Save</button>
                        <button type="button" onclick="closeEditModal()" style="background:#ccc;color:black;padding:5px 15px;border:none;border-radius:4px;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Product view table -->
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
                    <th>Active</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8">No products available.</td>
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
                            <td><?php echo ($product['active'] == 1) ? 'Active' : 'Inactive'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <script>
        // Select all checkboxes
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('input[name="delete_product_ids[]"]');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }
        // Show add size form
        function showAddSizeForm(productId) {
            document.querySelectorAll('.add-size-form').forEach(f => f.style.display = 'none');
            var form = document.getElementById('add-size-form-' + productId);
            if (form) form.style.display = 'inline-block';
        }
        function hideAddSizeForm(productId) {
            var form = document.getElementById('add-size-form-' + productId);
            if (form) form.style.display = 'none';
        }
        function openEditModal(id, name, description, price, category, stock, sizes, active) {
            document.getElementById('edit_product_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_stock').value = stock;
            document.getElementById('edit_sizes').value = sizes;
            document.getElementById('edit_active').value = active;
            document.getElementById('editModal').style.display = 'flex';
        }
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>

</body>
</html>
