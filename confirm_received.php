<?php
session_start();

header('Content-Type: application/json');
require_once 'connection.php';


if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;


if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
    exit;
}

$conn = OpenCon();
// Check order status for this user and order
$stmt = $conn->prepare("SELECT status FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$stmt->bind_result($status);
if ($stmt->fetch()) {
    // Only allow confirmation if status is 'delivered'
    if (strtolower($status) === 'delivered') {
        $stmt->close();
        // Update order status to 'Delivered'
        $update = $conn->prepare("UPDATE orders SET status = 'Delivered' WHERE order_id = ? AND user_id = ?");
        $update->bind_param("ii", $order_id, $user_id);
        $update->execute();
        $update->close();
        CloseCon($conn);
        echo json_encode(['success' => true]);
        exit;
    } else {
        $stmt->close();
        CloseCon($conn);
        echo json_encode(['success' => false, 'error' => 'Order not in delivered status']);
        exit;
    }
} else {
    $stmt->close();
    CloseCon($conn);
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}
?>
