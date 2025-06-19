<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
require_once 'connection.php';
$conn = OpenCon();

// Fetch all users (only customers, not admins)
$users = [];
$user_stmt = $conn->prepare("SELECT user_id, username, email, address, contact_number FROM users WHERE user_role != 'admin' ORDER BY username ASC");
$user_stmt->execute();
$user_result = $user_stmt->get_result();
while ($user = $user_result->fetch_assoc()) {
    $users[$user['user_id']] = $user;
    $users[$user['user_id']]['orders'] = [];
}
$user_stmt->close();

// Fetch all orders for all users
$order_stmt = $conn->prepare("SELECT order_id, user_id, total_price, status, created_at FROM orders ORDER BY created_at DESC");
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$orders = [];
while ($order = $order_result->fetch_assoc()) {
    $orders[$order['order_id']] = $order;
    if (isset($users[$order['user_id']])) {
        $users[$order['user_id']]['orders'][] = $order['order_id'];
    }
}
$order_stmt->close();

// Fetch all order items for all orders
$order_items = [];
if (!empty($orders)) {
    $order_ids = implode(',', array_map('intval', array_keys($orders)));
    $sql = "SELECT oi.order_id, oi.product_id, oi.size, oi.quantity, oi.price, p.name AS product_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id AND oi.size = p.sizes
            WHERE oi.order_id IN ($order_ids)
            ORDER BY oi.order_id";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $order_items[$row['order_id']][] = $row;
    }
}
CloseCon($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Customers & Purchase History</title>
    <link rel="stylesheet" href="css/view_customers.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fa; margin: 0; padding: 0; }
        .container { max-width: 1100px; margin: 2rem auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 24px #0001; padding: 2rem; }
        h1 { color: #1976d2; margin-bottom: 2rem; }
        .user-block { margin-bottom: 2.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid #e3e3e3; }
        .user-block:last-child { border-bottom: none; }
        .user-info { margin-bottom: 0.7rem; }
        .user-info strong { color: #1976d2; }
        .orders-table { width: 100%; border-collapse: collapse; margin-bottom: 1.2rem; }
        .orders-table th, .orders-table td { border: 1px solid #e0e0e0; padding: 0.5rem 0.7rem; text-align: left; }
        .orders-table th { background: #e3f2fd; color: #1976d2; }
        .order-items-table { width: 95%; margin: 0.5rem 0 0.5rem 1.5rem; border-collapse: collapse; font-size: 0.97em; }
        .order-items-table th, .order-items-table td { border: 1px solid #f0f0f0; padding: 0.3rem 0.5rem; }
        .order-items-table th { background: #f5f7fa; color: #333; }
        .order-status { font-weight: bold; }
        .order-status.pending { color: #ff9800; }
        .order-status.confirmed { color: #1976d2; }
        .order-status.shipped { color: #009688; }
        .order-status.delivered { color: #388e3c; }
        .order-status.cancelled { color: #e53935; }
        .order-status.processing { color: #7b1fa2; }
        .back-btn { display: inline-block; margin-bottom: 1.5rem; color: #1976d2; text-decoration: none; font-weight: 500; }
        .back-btn:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin_dashboard.php" class="back-btn">&larr; Back to Dashboard</a>
        <h1>Customers & Purchase History</h1>
        <?php if (empty($users)): ?>
            <div>No customers found.</div>
        <?php else: ?>
            <?php foreach ($users as $user): ?>
                <div class="user-block">
                    <div class="user-info">
                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                        (<?php echo htmlspecialchars($user['email']); ?>)
                        <br>
                        Address: <?php echo htmlspecialchars($user['address']); ?> |
                        Contact: <?php echo htmlspecialchars($user['contact_number']); ?>
                    </div>
                    <?php if (!empty($user['orders'])): ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Total Price</th>
                                    <th>Items</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user['orders'] as $oid): ?>
                                    <?php $order = $orders[$oid]; ?>
                                    <tr>
                                        <td><?php echo $order['order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                                        <td>
                                            <span class="order-status <?php echo strtolower($order['status']); ?>">
                                                <?php echo htmlspecialchars($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>₱<?php echo number_format($order['total_price'], 2); ?></td>
                                        <td>
                                            <?php if (!empty($order_items[$oid])): ?>
                                                <table class="order-items-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Product</th>
                                                            <th>Size</th>
                                                            <th>Qty</th>
                                                            <th>Unit Price</th>
                                                            <th>Subtotal</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($order_items[$oid] as $item): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                                <td><?php echo htmlspecialchars($item['size']); ?></td>
                                                                <td><?php echo $item['quantity']; ?></td>
                                                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                                                <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php else: ?>
                                                No items
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="color:#888;">No orders yet.</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
