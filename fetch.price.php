<?php
require_once 'connection.php';

if (!isset($_GET['product_id']) || !isset($_GET['size'])) {
    echo "Invalid parameters.";
    exit();
}

$product_id = intval($_GET['product_id']);
$size = $_GET['size'];

$conn = OpenCon();
$stmt = $conn->prepare("SELECT price FROM products WHERE product_id = ? AND sizes = ? AND user_id = (SELECT user_id FROM products WHERE product_id = ?)");
$stmt->bind_param("isi", $product_id, $size, $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo $row['price'];
} else {
    echo "Price not found.";
}

$stmt->close();
CloseCon($conn);
?> 
