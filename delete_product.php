<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';

if (isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];

    $conn = OpenCon();

  
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->bind_param("s", $product_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Product successfully deleted.";
    } else {
        $_SESSION['error_message'] = "Failed to delete the product.";
    }

    $stmt->close();
    CloseCon($conn);


    header("Location: product_list.php");
    exit();
} else {
    $_SESSION['error_message'] = "Invalid product ID.";
    header("Location: product_list.php");
    exit();
}
