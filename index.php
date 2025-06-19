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
  <style>
    body {
      background-image: url('image/bgweb.png');
      background-size: cover;
      background-repeat: no-repeat;
      background-attachment: fixed;
      min-height: 100vh;
      color: #2d1a05;
      font-family: 'Segoe UI', Arial, sans-serif;
    }
    header {
      background: rgba(255, 255, 255, 0.92);
      box-shadow: 0 2px 8px rgba(0,0,0,0.07);
      padding: 1.2rem 3vw;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .logo input[type="image"] {
      width: 120px;
      height: auto;
      filter: drop-shadow(0 2px 8px rgba(0,0,0,0.08));
    }
    .buttons span, .buttons a, .buttons button {
      color: #7b4e00;
      font-weight: 600;
      font-size: 1.08rem;
      text-shadow: 0 1px 6px rgba(255,248,241,0.7);
    }
    .btn-outline, .btn-white {
      border-radius: 18px;
      padding: 0.5rem 1.3rem;
      font-size: 1rem;
      font-weight: bold;
      margin-left: 0.5rem;
      border: 2px solid #b87c36;
      background: rgba(255,255,255,0.85);
      color: #b87c36;
      transition: background 0.2s, color 0.2s;
      box-shadow: 0 1px 6px rgba(223,159,73,0.07);
    }
    .btn-outline:hover, .btn-white:hover {
      background: #b87c36;
      color: #fff;
    }
    .btn-white {
      background: #fff8f1;
      border: 2px solid #df9f49;
      color: #b87c36;
    }
    .container {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 80vh;
    }
    .hero {
      background: rgba(255, 255, 255, 0.92);
      border-radius: 22px;
      box-shadow: 0 4px 24px rgba(223,159,73,0.10);
      padding: 3rem 2.5rem;
      text-align: center;
      max-width: 520px;
      margin: 2rem auto;
    }
    .hero h1 {
      color: #b87c36;
      font-size: 2.2rem;
      font-weight: 800;
      margin-bottom: 1.2rem;
      letter-spacing: 1.2px;
      text-shadow: 0 2px 12px rgba(255,248,241,0.7);
    }
    .hero p {
      color: #7b4e00;
      font-size: 1.13rem;
      margin-bottom: 2rem;
      text-shadow: 0 1px 6px rgba(255,248,241,0.7);
    }
    .hero button {
      background: linear-gradient(90deg, #df9f49 70%, #b87c36 100%);
      color: #fff;
      border: none;
      border-radius: 16px;
      padding: 0.7rem 2.2rem;
      font-size: 1.13rem;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 2px 12px rgba(223,159,73,0.13);
      transition: background 0.2s, transform 0.18s;
      letter-spacing: 0.5px;
      text-shadow: 0 2px 8px rgba(183,124,54,0.18);
    }
    .hero button:hover {
      background: linear-gradient(90deg, #b87c36 60%, #df9f49 100%);
      transform: scale(1.07);
    }
    @media (max-width: 700px) {
      .hero {
        padding: 1.5rem 0.7rem;
      }
      .hero h1 {
        font-size: 1.2rem;
      }
      .container {
        min-height: 60vh;
      }
    }
  </style>
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

