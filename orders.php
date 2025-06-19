<?php
session_start();
require_once 'connection.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = OpenCon();

// Fetch current (not delivered/cancelled) orders
$stmt = $conn->prepare("SELECT order_id, total_price, status, created_at FROM orders WHERE user_id = ? AND LOWER(status) NOT IN ('delivered', 'cancelled') ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();

// Fetch items for each current order
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

// Fetch delivered orders (purchase history)
$stmt = $conn->prepare("SELECT order_id, user_id, total_price, status, created_at FROM orders WHERE user_id = ? AND (status = 'Delivered' OR status = 'delivered') ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$history_orders = [];
while ($row = $result->fetch_assoc()) {
    $history_orders[] = $row;
}
$stmt->close();

// Fetch items for each delivered order
$history_items = [];
foreach ($history_orders as $order) {
    $stmt = $conn->prepare("SELECT 
            oi.product_id, 
            p.name, 
            oi.size, 
            oi.quantity, 
            oi.price, 
            p.image
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
    $history_items[$order['order_id']] = $items;
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
<style>
 
    .purchase-history-container {
        margin: 2rem auto 0 auto;
        max-width: 1100px;
        background: #232323;
        border-radius: 12px;
        padding: 2rem 2.5rem;
        box-shadow: 0 4px 24px #0008;
    }
    .purchase-history-title {
        color: #df9f49;
        margin-bottom: 1.5rem;
        font-size: 2rem;
        text-align: left;
    }
    .history-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 2rem;
        background: #181818;
        border-radius: 8px;
        overflow: hidden;
    }
    .history-table th, .history-table td {
        padding: 0.8rem 0.7rem;
        border-bottom: 1px solid #333;
        text-align: left;
    }
    .history-table th {
        background: #222;
        color: #df9f49;
        font-weight: 600;
    }
    .history-table tr:last-child td {
        border-bottom: none;
    }
    .history-product-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .history-product-info img {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #444;
        background: #fff;
    }
    .history-empty {
        color: #aaa;
        text-align: center;
        margin: 2rem 0;
        font-size: 1.1rem;
    }
    .history-order-header {
        background: #232323;
        color: #df9f49;
        font-size: 1.1rem;
        padding: 0.5rem 0.7rem;
        border-radius: 6px;
        margin: 1.5rem 0 0.5rem 0;
        display: inline-block;
    }
</style>
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
                                <!-- Show order status -->
                                <button class="track-btn" disabled>
                                    <?php
                                        if ($order['status'] == 'Pending') echo 'Pending';
                                        elseif ($order['status'] == 'Processing') echo 'Processing';
                                        elseif ($order['status'] == 'confirmed') echo 'Pending Payment';
                                        elseif ($order['status'] == 'shipped') echo 'Shipped';
                                        elseif (strtolower($order['status']) == 'delivered') echo 'Delivered';
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

    <div class="purchase-history-container">
        <div class="purchase-history-title">Purchase History</div>
        <?php if (empty($history_orders)): ?>
            <div class="history-empty">No delivered orders yet.</div>
        <?php else: ?>
            <?php foreach ($history_orders as $order): ?>
                <div class="history-order-header">
                    Order #<?php echo $order['order_id']; ?> &mdash; 
                    <span>Status: <?php echo htmlspecialchars($order['status']); ?></span> &mdash; 
                    <span>Date: <?php echo htmlspecialchars($order['created_at']); ?></span> &mdash; 
                    <span>Total: ₱<?php echo number_format($order['total_price'], 2); ?></span>
                </div>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Size</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history_items[$order['order_id']] as $item): ?>
                        <tr>
                            <td>
                                <div class="history-product-info">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                                    <span><?php echo htmlspecialchars($item['name']); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($item['size']); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                            <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
    
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.confirm-received-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!confirm('Confirm that you have received this order?')) return;
                var orderId = btn.getAttribute('data-order-id');
                fetch('confirm_received.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'order_id=' + encodeURIComponent(orderId)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Thank you for confirming receipt!');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'Could not confirm.'));
                    }
                });
            });
        });
    });
    </script>
</body>
</html>

