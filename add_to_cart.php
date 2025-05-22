<?php
session_start();
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method.');
}

if (!isset($_POST['id'], $_POST['name'], $_POST['price'], $_POST['size'])) {
    die('Invalid form data.');
}

$productId = $_POST['id'];
$productName = $_POST['name'];
$productPrice = $_POST['price'];
$productSize = $_POST['size'];
$quantity = $_POST['quantity'] ?? 1; 


if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}


$_SESSION['cart'][] = [
    'id' => $productId,
    'name' => $productName,
    'price' => $productPrice,
    'size' => $productSize,
    'quantity' => $quantity
];


header('Location: cart.php');
exit();
