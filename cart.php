<?php
session_start();
require_once 'connection.php';

// Include PHPMailer for sending emails
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once 'connection.php';
    $conn = OpenCon();
    $response = ['success' => false];

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }
    $user_id = $_SESSION['user_id'];

    if ($_POST['action'] === 'update_qty') {
        $order_id = intval($_POST['order_id']);
        $product_id = intval($_POST['product_id']);
        $size = $_POST['size'];
        $qty = intval($_POST['quantity']);

        // Update quantity in order_items
        $stmt = $conn->prepare("UPDATE order_items SET quantity = ? WHERE order_id = ? AND product_id = ? AND size = ?");
        $stmt->bind_param("iiis", $qty, $order_id, $product_id, $size);
        if ($stmt->execute()) {
            // Update order total
            $stmt2 = $conn->prepare("SELECT SUM(price * quantity) FROM order_items WHERE order_id = ?");
            $stmt2->bind_param("i", $order_id);
            $stmt2->execute();
            $stmt2->bind_result($order_total);
            $stmt2->fetch();
            $stmt2->close();

            $stmt3 = $conn->prepare("UPDATE orders SET total_price = ? WHERE order_id = ?");
            $stmt3->bind_param("di", $order_total, $order_id);
            $stmt3->execute();
            $stmt3->close();

            $response['success'] = true;
            $response['order_total'] = $order_total;
        }
        $stmt->close();
        CloseCon($conn);
        echo json_encode($response);
        exit;
    }

    if ($_POST['action'] === 'delete_item') {
        $order_id = intval($_POST['order_id']);
        $product_id = intval($_POST['product_id']);
        $size = $_POST['size'];

        // Delete item from order_items
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ? AND product_id = ? AND size = ?");
        $stmt->bind_param("iis", $order_id, $product_id, $size);
        if ($stmt->execute()) {
            // Update order total
            $stmt2 = $conn->prepare("SELECT SUM(price * quantity) FROM order_items WHERE order_id = ?");
            $stmt2->bind_param("i", $order_id);
            $stmt2->execute();
            $stmt2->bind_result($order_total);
            $stmt2->fetch();
            $stmt2->close();

            $stmt3 = $conn->prepare("UPDATE orders SET total_price = ? WHERE order_id = ?");
            $stmt3->bind_param("di", $order_total, $order_id);
            $stmt3->execute();
            $stmt3->close();

            $response['success'] = true;
            $response['order_total'] = $order_total;
        }
        $stmt->close();
        CloseCon($conn);
        echo json_encode($response);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    require_once 'connection.php';
    $conn = OpenCon();
    $response = ['success' => false];

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }
    $user_id = $_SESSION['user_id'];
    // Receive selected items as array of ['order_id', 'product_id', 'size']
    $selected_items = isset($_POST['selected_items']) ? $_POST['selected_items'] : [];
    $address = trim($_POST['address'] ?? '');
    $contact = trim($_POST['contact'] ?? '');

    if (empty($selected_items) || empty($address) || empty($contact)) {
        echo json_encode(['success' => false, 'error' => 'Missing data']);
        exit;
    }

    // Update address/contact for user
    $stmt = $conn->prepare("UPDATE users SET address = ?, contact_number = ? WHERE user_id = ?");
    $stmt->bind_param("ssi", $address, $contact, $user_id);
    $stmt->execute();
    $stmt->close();

    // Group selected items by order_id
    $items_by_order = [];
    foreach ($selected_items as $item) {
        $oid = intval($item['order_id']);
        $pid = intval($item['product_id']);
        $size = $item['size'];
        $items_by_order[$oid][] = ['product_id' => $pid, 'size' => $size];
    }

    foreach ($items_by_order as $order_id => $items) {
        // Get all items in this order
        $stmt = $conn->prepare("SELECT product_id, size FROM order_items WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $all_items = [];
        while ($row = $result->fetch_assoc()) {
            $all_items[] = $row;
        }
        $stmt->close();

        // Find unselected items
        $selected_set = [];
        foreach ($items as $it) {
            $selected_set[$it['product_id'].'_'.$it['size']] = true;
        }
        $unselected = [];
        foreach ($all_items as $row) {
            $key = $row['product_id'].'_'.$row['size'];
            if (!isset($selected_set[$key])) {
                $unselected[] = $row;
            }
        }

        // If there are unselected items, move them to a new order (status 'cart')
        if (!empty($unselected)) {
            // Create new order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, status, created_at) VALUES (?, 0, 'cart', NOW())");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $new_order_id = $stmt->insert_id;
            $stmt->close();

            // Move unselected items to new order
            foreach ($unselected as $row) {
                // Update order_id for these items
                $stmt = $conn->prepare("UPDATE order_items SET order_id = ? WHERE order_id = ? AND product_id = ? AND size = ?");
                $stmt->bind_param("iiis", $new_order_id, $order_id, $row['product_id'], $row['size']);
                $stmt->execute();
                $stmt->close();
            }

            // Update total_price for new order
            $stmt = $conn->prepare("SELECT SUM(price * quantity) FROM order_items WHERE order_id = ?");
            $stmt->bind_param("i", $new_order_id);
            $stmt->execute();
            $stmt->bind_result($new_total);
            $stmt->fetch();
            $stmt->close();
            $stmt = $conn->prepare("UPDATE orders SET total_price = ? WHERE order_id = ?");
            $stmt->bind_param("di", $new_total, $new_order_id);
            $stmt->execute();
            $stmt->close();
        }

        // Confirm the original order (now only with selected items)
        $stmt = $conn->prepare("UPDATE orders SET status = 'confirmed' WHERE order_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $stmt->close();

        // Update total_price for confirmed order
        $stmt = $conn->prepare("SELECT SUM(price * quantity) FROM order_items WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->bind_result($order_total);
        $stmt->fetch();
        $stmt->close();
        $stmt = $conn->prepare("UPDATE orders SET total_price = ? WHERE order_id = ?");
        $stmt->bind_param("di", $order_total, $order_id);
        $stmt->execute();
        $stmt->close();

        // Decrement stock for selected items only
        foreach ($items as $row) {
            $stmt = $conn->prepare("SELECT quantity FROM order_items WHERE order_id = ? AND product_id = ? AND size = ?");
            $stmt->bind_param("iis", $order_id, $row['product_id'], $row['size']);
            $stmt->execute();
            $stmt->bind_result($qty);
            $stmt->fetch();
            $stmt->close();

            $updateStock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ? AND sizes = ?");
            $updateStock->bind_param("iis", $qty, $row['product_id'], $row['size']);
            $updateStock->execute();
            $updateStock->close();
        }
    }

    // Fetch user email and username for confirmation email
    $stmt = $conn->prepare("SELECT email, username FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($email, $username);
    $stmt->fetch();
    $stmt->close();

    // Send confirmation email
    if (!empty($email)) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'johngabuyo29@gmail.com';
            $mail->Password = 'ncdroggihvlbwdhb';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->setFrom('johngabuyo29@gmail.com', 'Zell.Co');
            $mail->addAddress($email, $username);
            $mail->isHTML(true);
            $mail->Subject = "Order Confirmation - Zell.Co";

            // "Confirm Order" as a button with no link
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h2 style='color: #007bff;'>Hello $username,</h2>
                    <p>Thank you for placing your order with Zell.Co!</p>
                    <p>Your order has been received and is now being processed.</p>
                    <p><strong>Shipping Address:</strong><br>" . nl2br(htmlspecialchars($address)) . "</p>
                    <p><strong>Contact Number:</strong> " . htmlspecialchars($contact) . "</p>
                    <p>
                        Please confirm your payment and shipping details. If you have already paid, kindly ignore this message.
                        If you have any questions, reply to this email or contact our support.
                    </p>
                    <div style='margin: 24px 0; text-align: center;'>
                        <button style='background: #4caf50; color: #fff; padding: 12px 28px; border-radius: 6px; border: none; font-weight: bold; font-size: 16px; cursor: default;'>Confirm Order</button>
                    </div>
                    <p>We will notify you once your order is shipped.</p>
                    <p style='margin-top: 20px;'>Thank you for shopping with us!<br><strong>Zell.Co</strong></p>
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #999;'>This is an automated email. Please do not reply to this message.</p>
                </div>
            ";
            $mail->send();
        } catch (Exception $e) {
            
        }
    }

    CloseCon($conn);
    echo json_encode(['success' => true]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user address and contact for shipping
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

// --- Cart Orders (not delivered) ---
$stmt = $conn->prepare("SELECT order_id, user_id, total_price, status, created_at FROM orders WHERE user_id = ? AND status NOT IN ('confirmed','shipped','delivered','cancelled','processing','Delivered') ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();

// Fetch items for each order
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
            // Group orders by user/shop and calculate grand total
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
                <!-- Select all and cart table -->
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

            <!-- Address modal for shipping info -->
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
      
        document.querySelectorAll('.qty-controls').forEach(function(ctrl) {
            const minusBtn = ctrl.querySelector('.qty-minus');
            const plusBtn = ctrl.querySelector('.qty-plus');
            const input = ctrl.querySelector('.qty-input');
            const tr = ctrl.closest('tr');
            minusBtn.addEventListener('click', function() {
                let val = parseInt(input.value, 10);
                if (val > 1) {
                    input.value = val - 1;
                    updateQty(tr, input.value);
                }
            });
            plusBtn.addEventListener('click', function() {
                let val = parseInt(input.value, 10);
                input.value = val + 1;
                updateQty(tr, input.value);
            });
        });

       
        document.querySelectorAll('.remove-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const tr = btn.closest('tr');
                removeFromCart(
                    tr.getAttribute('data-order-id'),
                    tr.getAttribute('data-product-id'),
                    tr.getAttribute('data-size'),
                    btn
                );
            });
        });

        function updateQty(tr, qty) {
            const order_id = tr.getAttribute('data-order-id');
            const product_id = tr.getAttribute('data-product-id');
            const size = tr.getAttribute('data-size');
            fetch('cart.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=update_qty&order_id=${order_id}&product_id=${product_id}&size=${encodeURIComponent(size)}&quantity=${qty}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    
                    const price = parseFloat(tr.querySelector('.price-cell').textContent.replace(/[^\d.]/g, ''));
                    tr.querySelector('.subtotal-cell').textContent = '₱' + (price * qty).toFixed(2);
                
                    location.reload();
                } else {
                    alert('Error updating quantity.');
                }
            });
        }

        window.removeFromCart = function(order_id, product_id, size, btn) {
            if (!confirm('Remove this item from cart?')) return;
            fetch('cart.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=delete_item&order_id=${order_id}&product_id=${product_id}&size=${encodeURIComponent(size)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                  
                    const tr = btn.closest('tr');
                    tr.parentNode.removeChild(tr);
                   
                    location.reload();
                } else {
                    alert('Error removing item.');
                }
            });
        }

        // Grand total updates based on selected items
        function updateGrandTotal() {
            let total = 0;
            document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
                const tr = cb.closest('tr');
                const price = parseFloat(tr.querySelector('.price-cell').textContent.replace(/[^\d.]/g, ''));
                const qty = parseInt(tr.querySelector('.qty-input').value, 10);
                total += price * qty;
            });
            document.getElementById('grandTotal').textContent = total.toFixed(2);
        }

        // Enable Place Order button if any item is checked
        function updatePlaceOrderBtn() {
            const anyChecked = document.querySelectorAll('.item-checkbox:checked').length > 0;
            document.getElementById('placeOrderBtn').disabled = !anyChecked;
            updateGrandTotal();
        }
        document.querySelectorAll('.item-checkbox').forEach(cb => {
            cb.addEventListener('change', updatePlaceOrderBtn);
        });
        updatePlaceOrderBtn();

        // Update grand total if quantity changes and item is checked
        document.querySelectorAll('.qty-controls').forEach(function(ctrl) {
            const input = ctrl.querySelector('.qty-input');
            input.addEventListener('change', function() {
                if (ctrl.closest('tr').querySelector('.item-checkbox').checked) {
                    updateGrandTotal();
                }
            });
        });

        // Show address modal on Place Order
        document.getElementById('placeOrderBtn').addEventListener('click', function() {
            document.getElementById('addressModal').style.display = 'flex';
        });
        document.getElementById('closeAddressModal').addEventListener('click', function() {
            document.getElementById('addressModal').style.display = 'none';
        });

        document.getElementById('addressForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const address = document.getElementById('address').value.trim();
            const contact = document.getElementById('contact').value.trim();
            const checkedRows = Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => cb.closest('tr'));
            // Collect selected items (order_id, product_id, size)
            const selectedItems = checkedRows.map(tr => ({
                order_id: tr.getAttribute('data-order-id'),
                product_id: tr.getAttribute('data-product-id'),
                size: tr.getAttribute('data-size')
            }));

            if (selectedItems.length === 0) {
                alert('Please select at least one item.');
                return;
            }

            const formData = new URLSearchParams();
            formData.append('action', 'place_order');
            selectedItems.forEach((item, idx) => {
                formData.append(`selected_items[${idx}][order_id]`, item.order_id);
                formData.append(`selected_items[${idx}][product_id]`, item.product_id);
                formData.append(`selected_items[${idx}][size]`, item.size);
            });
            formData.append('address', address);
            formData.append('contact', contact);

            fetch('cart.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Order placed successfully!');
                    window.location.reload();
                } else {
                    alert('Error placing order: ' + (data.error || 'Unknown error'));
                }
            });
        });
    });
    </script>

</body>
</html>
</html>
