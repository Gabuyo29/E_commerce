<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "Not logged in";
    exit;
}

require_once 'connection.php';

$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
$items = isset($_POST['items']) ? $_POST['items'] : [];

if (!is_array($items) || count($items) === 0) {
    echo "No items selected";
    exit;
}

$user_id = $_SESSION['user_id'];


$order_ids = [];
foreach ($items as $item) {
    if (isset($item['order_id'])) {
        $order_ids[] = intval($item['order_id']);
    } elseif (is_array($item) && isset($item['order_id'])) {
        $order_ids[] = intval($item['order_id']);
    }
}
$order_ids = array_unique($order_ids);

if (empty($order_ids)) {
    echo "No valid orders selected";
    exit;
}

$conn = OpenCon();
$conn->begin_transaction();

try {

    if ($address && $contact) {
        $stmt = $conn->prepare("UPDATE users SET address = ?, contact_number = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $address, $contact, $user_id);
        $stmt->execute();
        $stmt->close();
    }


    foreach ($items as $item) {
        $order_id = isset($item['order_id']) ? intval($item['order_id']) : (is_array($item) && isset($item['order_id']) ? intval($item['order_id']) : 0);
        $product_id = isset($item['product_id']) ? intval($item['product_id']) : (is_array($item) && isset($item['product_id']) ? intval($item['product_id']) : 0);
        $size = isset($item['size']) ? $item['size'] : (is_array($item) && isset($item['size']) ? $item['size'] : '');

        $stmt = $conn->prepare("SELECT quantity FROM order_items WHERE order_id = ? AND product_id = ? AND size = ?");
        $stmt->bind_param("iis", $order_id, $product_id, $size);
        $stmt->execute();
        $stmt->bind_result($quantity);
        $stmt->fetch();
        $stmt->close();

        if ($quantity === null) {
            throw new Exception('Order item not found.');
        }

   
        $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ? AND sizes = ?");
        $stmt->bind_param("iis", $quantity, $product_id, $size);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            $stmt->close();
            throw new Exception('Insufficient stock or product not found.');
        }
        $stmt->close();
    }


    $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
    $types = str_repeat('i', count($order_ids) + 1);
    $params = array_merge([$user_id], $order_ids);

    $stmt = $conn->prepare("UPDATE orders SET status = 'confirmed' WHERE user_id = ? AND order_id IN ($placeholders)");
    $stmt->bind_param($types, ...$params);
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        $conn->commit();
        echo "Checkout successful";
    } else {
        $conn->rollback();
        echo "Database error";
    }
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

CloseCon($conn);
exit;
