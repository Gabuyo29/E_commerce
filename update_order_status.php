<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}


$conn = new mysqli("localhost", "root", "johnjohnjohn", "e_commerce");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Include PHPMailer for sending emails
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $sql = "UPDATE orders SET status = ? WHERE order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $status, $order_id);
    $stmt->execute();
    $stmt->close();

    // Fetch customer email and details if status is Processing, Shipped, Delivered, or Cancelled
    if (in_array($status, ['Processing', 'Shipped', 'Delivered', 'Cancelled'])) {
        $sql = "SELECT u.email, u.username, u.address, u.contact_number, o.total_price
                FROM orders o
                JOIN users u ON o.user_id = u.user_id
                WHERE o.order_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
        $stmt->bind_result($email, $username, $address, $contact, $total_price);
        if ($stmt->fetch()) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'johngabuyo29@gmail.com';
                $mail->Password = 'ncdroggihvlbwdhb';
                $mail->SMTPSecure = 'ssl';
                $mail->Port = 465;

                $mail->setFrom('johngabuyo29@gmail.com', 'E-Commerce Admin');
                $mail->addAddress($email, $username);
                $mail->isHTML(true);
                $mail->Subject = "Order #$order_id Status Update: $status";

                if ($status === 'Cancelled') {
                    $mail->Body = "
                        <div style='font-family: Arial, sans-serif; color: #333;'>
                            <h2 style='color: #d32f2f;'>Hello $username,</h2>
                            <p>We regret to inform you that your order <strong>#$order_id</strong> has been <strong>cancelled</strong>.</p>
                            <p><strong>Order Total:</strong> ₱" . number_format($total_price, 2) . "</p>
                            <p>If you have any questions or believe this was a mistake, please contact our support team.</p>
                            <p style='margin-top: 20px;'>Thank you for considering us.<br><strong>Zell.Co</strong></p>
                            <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                            <p style='font-size: 12px; color: #999;'>This is an automated email. Please do not reply to this message.</p>
                        </div>
                    ";
                } else {
                    $mail->Body = "
                        <div style='font-family: Arial, sans-serif; color: #333;'>
                            <h2 style='color: #007bff;'>Hello $username,</h2>
                            <p>Your order <strong>#$order_id</strong> status has been updated to <strong>$status</strong>.</p>
                            <p><strong>Order Total:</strong> ₱" . number_format($total_price, 2) . "</p>
                            <p><strong>Shipping Address:</strong> $address<br>
                            <strong>Contact Number:</strong> $contact</p>
                            <p>
                                Please confirm your payment and shipping details. If you have already paid, kindly ignore this message.
                                If you have any questions, reply to this email or contact our support.
                            </p>
                            <p style='margin-top: 20px;'>Thank you for shopping with us!<br><strong>Zell.Co</strong></p>
                            <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                            <p style='font-size: 12px; color: #999;'>This is an automated email. Please do not reply to this message.</p>
                        </div>
                    ";
                }
                $mail->send();
            } catch (Exception $e) {
                
            }
        }
        $stmt->close();
    }

    $_SESSION['success_message'] = "Order status updated successfully!";
    header("Location: view_orders.php");
    exit();
}

// Get order ID from query string
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';
$sql = "SELECT status FROM orders WHERE order_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$order) {
    header("Location: view_orders.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Order Status</title>
    <link rel="stylesheet" href="css/update_order_status.css">
</head>
<body>

    <a href="view_orders.php" class="return-btn">← Return</a>

    <h2>Update Order Status</h2>

    <form action="update_order_status.php" method="POST">
        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>">
        <label for="status">Order Status:</label>
        <select id="status" name="status" required>
            <option value="Pending" <?php echo $order['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="Processing" <?php echo $order['status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
            <option value="Shipped" <?php echo $order['status'] === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
            <option value="Delivered" <?php echo $order['status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
            <option value="Cancelled" <?php echo $order['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
        </select>
        <button type="submit">Update Status</button>
    </form>

</body>
</html>
