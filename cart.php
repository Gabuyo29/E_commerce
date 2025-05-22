<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = OpenCon();

$user_address = '';
$user_contact = '';
$stmt = $conn->prepare("SELECT address, contact_number FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_address, $user_contact);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("SELECT order_id, user_id, total_price, status, created_at FROM orders WHERE user_id = ? AND status NOT IN ('confirmed','shipped','delivered','cancelled') ORDER BY created_at DESC");
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
    <link rel="stylesheet" href="css/cart.css">
</head>
<body>
    <div class="cart-container">
        <a href="welcome.php" class="back-button">← Continue Shopping</a>
        <h1>Your Orders</h1>
        <?php if (empty($orders)): ?>
            <div class="empty">You have no orders.</div>
        <?php else: ?>
            <?php
            $shops = [];
            foreach ($orders as $order) {
                $shops[$order['user_id']][] = $order;
            }
            $grand_total = 0;
            foreach ($orders as $order) {
                $grand_total += $order['total_price'];
            }
            ?>
            <form id="cartForm">
                <div style="margin-bottom: 1rem;">
                    <label class="select-all-label">
                        <input type="checkbox" id="selectAllGlobal" style="vertical-align:middle;margin-right:6px;">
                        Select All
                    </label>
                </div>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th class="select-cell"></th>
                            <th>Product</th>
                            <th>Size</th>
                            <th class="qty-cell">Qty</th>
                            <th class="price-cell">Unit Price</th>
                            <th class="subtotal-cell">Total Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($shops as $shop_id => $shop_orders): ?>
                        <?php foreach ($shop_orders as $order): ?>
                            <?php if (!empty($order_items[$order['order_id']])): ?>
                                <?php foreach ($order_items[$order['order_id']] as $idx => $item): ?>
                                <tr data-order-id="<?php echo $order['order_id']; ?>" data-product-id="<?php echo $item['product_id']; ?>" data-size="<?php echo htmlspecialchars($item['size'], ENT_QUOTES); ?>">
                                    <td class="select-cell">
                                        <input type="checkbox"
                                            class="item-checkbox"
                                            data-price="<?php echo $item['price'] * $item['quantity']; ?>"
                                            id="item-<?php echo $order['order_id'] . '-' . $idx; ?>">
                                    </td>
                                    <td>
                                        <div class="product-info">
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                                            <span class="product-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                        </div>
                                        <div class="variation">
                                            Variations: <?php echo htmlspecialchars($item['size']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="size-badge"><?php echo htmlspecialchars($item['size']); ?></span>
                                    </td>
                                    <td class="qty-cell">
                                        <div class="qty-controls">
                                            <button type="button" class="qty-btn qty-minus">-</button>
                                            <input type="number" min="1" class="qty-input" value="<?php echo htmlspecialchars($item['quantity']); ?>" style="width:40px;text-align:center;">
                                            <button type="button" class="qty-btn qty-plus">+</button>
                                        </div>
                                    </td>
                                    <td class="price-cell">₱<?php echo number_format($item['price'], 2); ?></td>
                                    <td class="subtotal-cell">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    <td>
                                        <button type="button"
                                            class="remove-btn"
                                            onclick="removeFromCart('<?php echo $order['order_id']; ?>', '<?php echo $item['product_id']; ?>', '<?php echo htmlspecialchars($item['size'], ENT_QUOTES); ?>', this)">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="margin-top: 1.5rem; text-align: right;">
                    <button type="button" id="placeOrderBtn" class="back-button" style="background:#4caf50;min-width:160px;" disabled>
                        Place Order
                    </button>
                </div>
            </form>

            <div id="addressModal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:#000a;z-index:1000;align-items:center;justify-content:center;">
                <div style="background:#232323;padding:2rem 2.5rem;border-radius:12px;max-width:400px;width:90%;box-shadow:0 4px 24px #0008;position:relative;">
                    <button type="button" id="closeAddressModal" style="position:absolute;top:10px;right:15px;background:none;border:none;color:#fff;font-size:1.5rem;cursor:pointer;">&times;</button>
                    <h2 style="color:#df9f49;margin-bottom:1.2rem;">Shipping Address</h2>
                    <form id="addressForm">
                        <div style="margin-bottom:1rem;">
                            <label for="address" style="display:block;margin-bottom:0.3rem;">Address</label>
                            <textarea id="address" name="address" rows="3" style="width:100%;padding:0.5rem;border-radius:6px;border:1px solid #444;" required><?php echo htmlspecialchars($user_address); ?></textarea>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label for="contact" style="display:block;margin-bottom:0.3rem;">Contact Number</label>
                            <input type="text" id="contact" name="contact" style="width:100%;padding:0.5rem;border-radius:6px;border:1px solid #444;" required value="<?php echo htmlspecialchars($user_contact); ?>">
                        </div>
                        <button type="submit" class="back-button" style="background:#4caf50;width:100%;">Submit & Place Order</button>
                    </form>
                </div>
            </div>

            <div class="grand-total">
                Grand Total: ₱<span id="grandTotal"><?php echo number_format($grand_total, 2); ?></span>
            </div>
        <?php endif; ?>
    </div>
   
    
</body>
</html>
