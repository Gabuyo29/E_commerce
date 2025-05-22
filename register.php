<?php
session_start();
ob_start(); // Start output buffering
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = OpenCon();
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password']; 
    $created_at = date('Y-m-d H:i:s'); 
    $user_role = isset($_POST['user_role']) && in_array($_POST['user_role'], ['admin', 'customer']) ? $_POST['user_role'] : 'customer'; // Role validation
    $contact_number = isset($_POST['contact_number']) ? $_POST['contact_number'] : '';
    $address = isset($_POST['address']) ? $_POST['address'] : '';

   
    $passwordPattern = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/";
    if (!preg_match($passwordPattern, $password)) {
        echo "Error: Password must contain at least 1 uppercase letter, 1 lowercase letter, 1 number, 1 special character, and be at least 8 characters long.";
    } else {
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

       
        $checkEmailStmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $checkEmailStmt->bind_param("s", $email);
        $checkEmailStmt->execute();
        $checkEmailStmt->store_result();

        if ($checkEmailStmt->num_rows > 0) {
            echo "Error: Email already exists. <a href='login.php'>Login here</a>";
        } else {
           
            $checkUsernameStmt = $conn->prepare("SELECT username FROM users WHERE username = ?");
            $checkUsernameStmt->bind_param("s", $username);
            $checkUsernameStmt->execute();
            $checkUsernameStmt->store_result();

            if ($checkUsernameStmt->num_rows > 0) {
                echo "Error: Username already exists.";
            } else {
                
                $stmt = $conn->prepare("INSERT INTO users (user_id, username, email, password, created_at, user_role, address, contact_number) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $username, $email, $hashed_password, $created_at, $user_role, $address, $contact_number);

                if ($stmt->execute()) {
                    $_SESSION['username'] = $username;
                    if (!headers_sent()) {
                        header("Location: index.php");
                        exit();
                    } else {
                        echo "<script>window.location.href='index.php';</script>";
                        exit();
                    }
                } else {
                    echo "Error: " . $stmt->error;
                }

                $stmt->close();
            }

            $checkUsernameStmt->close();
        }

        $checkEmailStmt->close();
    }

    CloseCon($conn);
    ob_end_flush();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/register.css">
   
    <title>Register</title>
    
</head>
<body>
    <script src="js/register.js">  </script>
    <div class="form-container">
        <h2>Register</h2>
        <form method="POST" action="register.php" onsubmit="return validatePassword()">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" oninput="validatePassword()" required>
            
            <label for="contact_number">Contact Number:</label>
            <input type="text" id="contact_number" name="contact_number" required>
            
            <label for="address">Address:</label>
            <input type="text" id="address" name="address" required>
            
            <label for="user_role">Role:</label>
            <select id="user_role" name="user_role">
                <option value="customer" selected>Customer</option>
                <option value="admin">Admin</option>
            </select>
            
            <div class="validation-indicator">
                <span id="uppercase" class="invalid">At least 1 uppercase letter</span>
                <span id="lowercase" class="invalid">At least 1 lowercase letter</span>
                <span id="number" class="invalid">At least 1 number</span>
                <span id="special" class="invalid">At least 1 special character</span>
                <span id="length" class="invalid">At least 8 characters long</span>
            </div>
            
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>
