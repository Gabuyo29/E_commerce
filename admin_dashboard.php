<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';

$conn = OpenCon();
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$stmt->bind_result($user_id_from_db);
$stmt->fetch();
$stmt->close();
CloseCon($conn);

$user_id = $user_id_from_db ?: 'Unknown';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin_dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <div style="text-align: right; margin-bottom: 1rem;">
            <a href="logout.php" style="text-decoration: none; color: white; background-color: #007bff; padding: 0.5rem 1rem; border-radius: 5px;">Log Out</a>
        </div>
        <h1>Admin Dashboard</h1>
        <p>Logged in as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> (User ID: <strong><?php echo htmlspecialchars($user_id); ?></strong>)</p>
        
        <div class="section">
            <h2>Manage Products</h2>
            <ul>
                <li><a href="add_product_new.php">Add Product</a></li>
                <li><a href="product_list.php">View Products</a></li>
                <li><a href="product_list.php?action=edit">Edit Product</a></li>
        
            </ul>
        </div>

        <div class="section">
            <h2>Manage Orders</h2>
            <ul>
                <li><a href="view_orders.php">View Orders</a></li>
                <li><a href="update_order_status.php">Update Order Status</a></li>
                <li><a href="order_details.php">Order Details</a></li>
                <li><a href="order_history.php">Order History</a></li>
            </ul>
        </div>

        <div class="section">
            <h2>Manage Customers</h2>
            <ul>
                <li><a href="view_customers.php">View Customers</a></li>
                <li><a href="edit_customer.php">Edit Customer</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
