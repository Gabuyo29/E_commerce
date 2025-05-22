<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['user_id'])) {
    echo "Not logged in";
    exit;
}

if (!isset($_POST['order_id'], $_POST['product_id'], $_POST['size'])) {
    echo "Missing parameters";
    exit;
}

$order_id = intval($_POST['order_id']);
$product_id = intval($_POST['product_id']);
$size = $_POST['size'];

$conn = OpenCon();

// Delete the item from order_items
$stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ? AND product_id = ? AND size = ?");
$stmt->bind_param("iis", $order_id, $product_id, $size);
$stmt->execute();
$deleted = $stmt->affected_rows > 0;
$stmt->close();

if ($deleted) {
    // Recalculate total price for the order
    $stmt = $conn->prepare("SELECT SUM(price * quantity) AS total FROM order_items WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->bind_result($new_total);
    $stmt->fetch();
    $stmt->close();

    $new_total = $new_total ?: 0;

    if ($new_total == 0) {

        $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
        echo "Order deleted";
    } else {
 
        $stmt = $conn->prepare("UPDATE orders SET total_price = ? WHERE order_id = ?");
        $stmt->bind_param("di", $new_total, $order_id);
        $stmt->execute();
        $stmt->close();
        echo "Item removed. New total: $new_total";
    }
} else {
    echo "Item not found or already deleted";
}

CloseCon($conn);
