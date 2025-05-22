<?php
session_start();
require_once 'connection.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = OpenCon();

    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, username, password, user_role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($db_user_id, $db_username, $db_password, $db_user_role);
        $stmt->fetch();

        if (password_verify($password, $db_password)) {
            $_SESSION['username'] = $db_username;
            $_SESSION['user_role'] = $db_user_role;
            $_SESSION['user_id'] = $db_user_id;
            $_SESSION['logged_in'] = true;

            if ($db_user_role === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } elseif ($password === $db_password) {
         
            $error = "Warning: Password is not hashed. Please update your password.";
            $_SESSION['username'] = $db_username;
            $_SESSION['user_role'] = $db_user_role;
            $_SESSION['user_id'] = $db_user_id;
            $_SESSION['logged_in'] = true;

            if ($db_user_role === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No user found with that username.";
    }

    $stmt->close();
    CloseCon($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/login.css">
    <title>Login</title>
  
</head>
<body>
    <div class="form-container">
        <h2>Login</h2>
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>
