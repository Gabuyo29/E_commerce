<?php

session_start();
require_once 'connection.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['selected_items'])) {
    header("Location: cart.php");
    exit();
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$selectedItems = $_SESSION['selected_items'];
$address = $_POST['address'] ?? '';
$contact = $_POST['contact'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $address && $contact) {
    $conn = OpenCon();

    $stmt = $conn->prepare("INSERT INTO orders (user_id, address, contact) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $address, $contact);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    foreach ($_SESSION['cart'] as $item) {
        if (in_array($item['name'], $selectedItems)) {
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, sizes, price) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $order_id, $item['id'], $item['name'], $item['size'], $item['price']);
            $stmt->execute();
            $stmt->close();
        }
    }

    $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($selectedItems) {
        return !in_array($item['name'], $selectedItems);
    });

    unset($_SESSION['selected_items']);

    CloseCon($conn);

    header("Location: order_details.php?order_id=" . $order_id);
    exit();
} else {
    header("Location: checkout.php");
    exit();
}
?>