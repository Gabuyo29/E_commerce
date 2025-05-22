<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = OpenCon();


$stmt = $conn->prepare("SELECT order_id, total_price, status, created_at FROM orders WHERE user_id = ? AND status IN ('Pending', 'Processing', 'confirmed', 'shipped', 'delivered', 'cancelled') ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();


$order_items = [];
foreach ($orders as $order) {
    $stmt = $conn->prepare("SELECT oi.product_id, p.name, oi.size, oi.quantity, oi.price, p.image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id AND oi.size = p.sizes
        WHERE oi.order_id = ?
        ORDER BY p.name, oi.size");
    $stmt->bind_param("i", $order['order_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $order_items[$order['order_id']] = $items;
    $stmt->close();
}
CloseCon($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Orders</title>
    <link rel="stylesheet" href="css/orders.css">
</head>
<body>
    <div class="orders-container">
        <a href="welcome.php" class="back-button">← Back to Shop</a>
        <h1>Your Orders</h1>
        <?php if (empty($orders)): ?>
            <div class="empty">You have no placed orders.</div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="order-items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Size</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $order): ?>
                        <?php foreach ($order_items[$order['order_id']] as $item): ?>
                        <tr>
                            <td>
                                <div class="product-info">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                                    <span class="product-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                </div>
                            </td>
                            <td><span class="size-badge"><?php echo htmlspecialchars($item['size']); ?></span></td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                            <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="5" style="text-align:right;padding-top:0;">
                                <button class="track-btn" disabled>
                                    <?php
                                        if ($order['status'] == 'Pending') echo 'Pending';
                                        elseif ($order['status'] == 'Processing') echo 'Processing';
                                        elseif ($order['status'] == 'confirmed') echo 'Confirmed';
                                        elseif ($order['status'] == 'shipped') echo 'Shipped';
                                        elseif ($order['status'] == 'delivered') echo 'Delivered';
                                        elseif ($order['status'] == 'cancelled') echo 'Cancelled';
                                    ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
