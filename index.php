<?php
include 'connection.php'; 
session_start(); 


$conn = OpenCon(); 


$sql = "SELECT * FROM products";
$result = $conn->query($sql);


CloseCon($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="css/index.css">
  <title>ZELL.CLO</title>
</head>
<body>
  <header>
    <div class="logo">
        <input type="image" src="zell.png" alt="">
    </div>
    <div class="buttons">
      <?php if (isset($_SESSION['username'])): ?>
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
        <a href="logout.php"><button class="btn-outline">Log out</button></a>
      <?php else: ?>
        <a href="login.php"><button class="btn-outline">Log in</button></a>
        <a href="register.php"><button class="btn-white">Register</button></a>
      <?php endif; ?>
    </div>
  </header>
  <div class="container">
    <div class="hero">
      <h1>STAY AT HOME AND SHOP ONLINE</h1>
      <p>Giving you shopping convenience between your hectic schedule, so you can save time and be safe.</p>
      <a href="welcome.php"><button>Browse</button></a>
    </div>

  </div>
</body>
</html>

