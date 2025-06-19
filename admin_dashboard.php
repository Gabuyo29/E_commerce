<?php
session_start();
// Restrict access to admin users only
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'connection.php';

// Fetch user ID for the logged-in admin
$conn = OpenCon();
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$stmt->bind_result($user_id_from_db);
$stmt->fetch();
$stmt->close();
CloseCon($conn);

$user_id = $user_id_from_db ?: 'Unknown';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Responsive admin dashboard styling -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body {
            
            background: linear-gradient(135deg, #2196f3 0%, #21cbf3 100%);
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before, body::after {
            content: "";
            position: absolute;
            z-index: 0;
            border-radius: 50%;
            opacity: 0.18;
        }
        body::before {
            width: 350px;
            height: 350px;
            background: #fff;
            top: -120px;
            left: -120px;
        }
        body::after {
            width: 250px;
            height: 250px;
            background: #1976d2;
            bottom: -80px;
            right: -80px;
        }

        .dashboard-container {
            max-width: 800px;
            margin: 2.5rem auto;
            background: #fff;
            border-radius: 16px;
            position: relative;
            z-index: 1;
            box-shadow: 0 8px 32px rgba(33, 150, 243, 0.18), 0 1.5px 8px rgba(33, 203, 243, 0.10);
            transition: box-shadow 0.2s;
            padding: 2.5rem 2.5rem 2rem 2.5rem;
        }
        .dashboard-container:hover {
            box-shadow: 0 12px 40px rgba(33, 150, 243, 0.22), 0 2px 12px rgba(33, 203, 243, 0.13);
        }
        h1 {
            color: #1976d2;
            margin-top: 0.5rem;
            margin-bottom: 2rem;
            text-align: center;
            letter-spacing: 1px;
            font-size: 2.2rem;
            text-shadow: 0 2px 8px rgba(33, 150, 243, 0.10);
        }
        .section {
            margin-bottom: 2rem;
            padding: 1.2rem 1.5rem 1rem 1.5rem;
            border-bottom: 2px dashed #b3e5fc;
            position: relative;
            border-radius: 8px;
            background: rgba(33, 203, 243, 0.06);
            box-shadow: 0 1px 6px rgba(33, 150, 243, 0.04);
        }
        .section:last-child {
            border-bottom: none;
        }
        .section h2 {
            color: #2196f3;
            margin-bottom: 1.2rem;
            font-size: 1.25rem;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .section h2::before {
          
            content: "🛠️";
            font-size: 1.1em;
            margin-right: 0.3em;
            opacity: 0.7;
        }
        .section:nth-child(3) h2::before { content: "📦"; }
        .section:nth-child(4) h2::before { content: "🧑"; }

        ul {
            list-style: none;
            padding: 0;
        }
        ul li {
            margin-bottom: 0.7rem;
            padding-left: 1.2em;
            position: relative;
        }
        ul li::before {
            content: "›";
            position: absolute;
            left: 0;
            color: #21cbf3;
            font-weight: bold;
            font-size: 1.1em;
            top: 0.1em;
        }
        a {
            color: #1976d2;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s, background 0.2s;
            border-radius: 4px;
            padding: 0.2rem 0.5rem;
            background: rgba(33, 203, 243, 0.07);
            box-shadow: 0 1px 4px rgba(33, 150, 243, 0.04);
        }
        a:hover {
            color: #fff;
            background: linear-gradient(90deg, #1976d2 60%, #21cbf3 100%);
            box-shadow: 0 2px 8px rgba(33, 150, 243, 0.10);
            text-decoration: none;
        }
        .dashboard-container p {
            background: linear-gradient(90deg, #e3f2fd 80%, #b3e5fc 100%);
            padding: 0.7rem 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
            color: #1565c0;
            font-size: 1rem;
            border-left: 5px solid #21cbf3;
            box-shadow: 0 1px 4px rgba(33, 150, 243, 0.07);
        }
        .dashboard-container > div[style*="text-align: right"] a {
            background: linear-gradient(90deg, #2196f3 60%, #21cbf3 100%);
            color: #fff !important;
            border: none;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(33, 150, 243, 0.13);
            font-size: 1rem;
            padding: 0.5rem 1.2rem;
            transition: background 0.2s, color 0.2s;
        }
        .dashboard-container > div[style*="text-align: right"] a:hover {
            background: linear-gradient(90deg, #1976d2 60%, #21cbf3 100%);
            color: #fff !important;
            box-shadow: 0 4px 16px rgba(33, 150, 243, 0.18);
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
        }
        .dashboard-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.3rem;
            font-weight: 600;
            color: #1976d2;
            letter-spacing: 1px;
        }
        .dashboard-title {
            font-size: 1.1rem;
            font-weight: 500;
        }
        .logout-btn {
            background: linear-gradient(90deg, #2196f3 60%, #21cbf3 100%);
            color: #fff !important;
            border: none;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(33, 150, 243, 0.10);
            transition: background 0.2s, color 0.2s;
            letter-spacing: 0.5px;
            border-radius: 6px;
            font-size: 1rem;
            padding: 0.5rem 1.2rem;
            text-decoration: none;
            cursor: pointer;
        }
        .logout-btn:hover {
            background: linear-gradient(90deg, #1976d2 60%, #21cbf3 100%);
            color: #fff !important;
            box-shadow: 0 4px 16px rgba(33, 150, 243, 0.18);
        }
        .dashboard-divider {
            border: none;
            border-top: 2px solid #b3e5fc;
            margin-bottom: 1.5rem;
        }
        .dashboard-footer {
            text-align: center;
            color: #1976d2;
            font-size: 0.95rem;
            margin-top: 2.5rem;
            opacity: 0.8;
            letter-spacing: 0.5px;
        }
        @media (max-width: 600px) {
            .dashboard-container {
                padding: 1rem;
            }
            h1 {
                font-size: 1.3rem;
            }
            .section {
                padding: 0.7rem 0.5rem 0.7rem 0.7rem;
            }
            .section h2 {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-bg-shape"></div>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="dashboard-logo">
                <!-- Dashboard logo and title -->
                <span>🛒</span>
                <span class="dashboard-title">E-Commerce Admin</span>
            </div>
           
            <a href="logout.php" class="logout-btn">Log Out</a>
        </div>
        <hr class="dashboard-divider">
        <h1>Admin Dashboard</h1>
        <!-- Display logged-in admin info -->
        <p>Logged in as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> (User ID: <strong><?php echo htmlspecialchars($user_id); ?></strong>)</p>
        
        <div class="section">
            <h2>Manage Products</h2>
            <ul>
                <!-- Product management links -->
                <li><a href="add_product_new.php">Add Product</a></li>
                <li><a href="product_list.php">View Products</a></li>
                <li><a href="product_list.php?action=edit">Edit Product</a></li>
            </ul>
        </div>
        <div class="section">
            <h2>Manage Orders</h2>
            <ul>
                <!-- Order management link -->
                <li><a href="update_order_status.php">Update Order Status</a></li>
            </ul>
        </div>

        <div class="section">
            <h2>Manage Customers</h2>
            <ul>
                <!-- Customer management link -->
                <li><a href="view_customers.php">View Customers</a></li>
            </ul>
        </div>
        <footer class="dashboard-footer">
            &copy; <?php echo date('Y'); ?> E-Commerce Admin Panel
        </footer>
    </div>
</body>
</html>
