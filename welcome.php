<?php


session_start();
require_once 'connection.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];


$conn = OpenCon();

// Get latest 4 unique product IDs for "New Arrival" per category
$categoryNewArrivals = [
    'mens' => [],
    'womens' => [],
];

// Men's Wear new arrivals
$mensStmt = $conn->prepare("
    SELECT MAX(product_id) as product_id
    FROM products
    WHERE LOWER(category) IN ('mens_wear', 'menswear', \"men's wear\")
    GROUP BY LOWER(name), price, LOWER(image)
    ORDER BY product_id DESC
    LIMIT 4
");
$mensStmt->execute();
$mensResult = $mensStmt->get_result();
while ($row = $mensResult->fetch_assoc()) {
    $categoryNewArrivals['mens'][] = $row['product_id'];
}
$mensStmt->close();

// Womens Wear new arrivals
$womensStmt = $conn->prepare("
    SELECT MAX(product_id) as product_id
    FROM products
    WHERE LOWER(category) IN ('womens_wear', 'womenswear', 'womens wear')
    GROUP BY LOWER(name), price, LOWER(image)
    ORDER BY product_id DESC
    LIMIT 4
");
$womensStmt->execute();
$womensResult = $womensStmt->get_result();
while ($row = $womensResult->fetch_assoc()) {
    $categoryNewArrivals['womens'][] = $row['product_id'];
}
$womensStmt->close();

// Fetch all products
$stmt = $conn->prepare("SELECT product_id, name, price, image, category, active FROM products ORDER BY product_id DESC");
$stmt->execute();
$result = $stmt->get_result();
$allProducts = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
CloseCon($conn);

// Deduplicate products by name, price, and image, and filter only active products
$products = [];
$seen = [];
foreach ($allProducts as $product) {
    if (isset($product['active']) && $product['active'] != 1) continue; // Only show active products
    $key = strtolower(trim($product['name'])) . '|' . $product['price'] . '|' . strtolower(trim($product['image']));
    if (!isset($seen[$key])) {
        $products[] = $product;
        $seen[$key] = true;
    }
}

// Separate products by category and new arrival status
$categoryProducts = [
    'mens' => ['new' => [], 'old' => []],
    'womens' => ['new' => [], 'old' => []],
];
foreach ($products as $product) {
    $cat = '';
    if (
        isset($product['category']) &&
        (strtolower($product['category']) === 'mens_wear' || strtolower($product['category']) === 'menswear' || strtolower($product['category']) === "men's wear")
    ) {
        $cat = 'mens';
    } elseif (
        isset($product['category']) &&
        (strtolower($product['category']) === 'womens_wear' || strtolower($product['category']) === 'womenswear' || strtolower($product['category']) === "womens wear")
    ) {
        $cat = 'womens';
    }
    if ($cat) {
        if (in_array($product['product_id'], $categoryNewArrivals[$cat])) {
            $categoryProducts[$cat]['new'][] = $product;
        } else {
            $categoryProducts[$cat]['old'][] = $product;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ZELL.CLO</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background-color: #fff8f1;
      color: #2d1a05;
      background-image: url('image/bgweb.png');
      background-size: cover;
      background-repeat: no-repeat;
      background-attachment: fixed;
      min-height: 100vh;
      text-shadow: 0 1px 6px rgba(255,248,241,0.7);
    }
    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.2rem 3vw;
      background: rgba(255,255,255,0.92);
      box-shadow: 0 2px 8px rgba(0,0,0,0.07);
      position: sticky;
      top: 0;
      z-index: 10;
      gap: 2rem;
    }
    .logo {
      font-weight: bold;
      font-size: 1.7rem;
      color: #b87c36;
      letter-spacing: 2px;
      font-family: 'Montserrat', Arial, sans-serif;
      margin-right: 1.5rem;
      text-shadow: 0 2px 12px rgba(255,248,241,0.7);
    }
    nav {
      display: flex;
      gap: 2rem;
      font-size: 1.05rem;
    }
    nav a {
      text-decoration: none;
      color: #7b4e00;
      font-weight: 600;
      padding: 0.3rem 0.7rem;
      border-radius: 6px;
      transition: background 0.2s, color 0.2s;
      text-shadow: 0 1px 6px rgba(255,248,241,0.7);
    }
    nav a:hover, nav a:focus {
      background: #df9f49;
      color: #fff;
    }
    nav a[style*="red"] {
      color: #e74c3c !important;
      background: #fff0e6;
      border: 1px solid #e74c3c;
      transition: background 0.2s, color 0.2s;
    }
    nav a[style*="red"]:hover {
      background: #e74c3c;
      color: #fff !important;
    }
    .hero {
      position: relative;
      background: url('image/una.jpg') center/cover no-repeat;
      height: 320px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      text-align: center;
      color: white;
      overflow: hidden;
      text-shadow: 0 2px 12px rgba(60,40,10,0.25), 0 1px 6px rgba(255,248,241,0.7);
    }
    .hero::before {
      content: "";
      position: absolute;
      inset: 0;
      background: rgba(60, 40, 10, 0.32);
      z-index: 1;
    }
    .hero h1 {
      font-size: 2.5rem;
      font-weight: 800;
      text-shadow: 0 2px 12px rgba(60,40,10,0.25), 0 1px 6px rgba(255,248,241,0.7);
      letter-spacing: 1.5px;
      margin-bottom: 1.2rem;
      position: relative;
      z-index: 2;
      color: #fff;
    }
    .search-bar {
      margin-top: 0.5rem;
      background: rgba(255,255,255,0.92);
      border-radius: 30px;
      display: flex;
      align-items: center;
      overflow: hidden;
      width: 70%;
      max-width: 420px;
      box-shadow: 0 4px 18px rgba(223,159,73,0.10), 0 1.5px 8px rgba(0,0,0,0.07);
      position: relative;
      z-index: 2;
    }
    .search-bar input {
      flex: 1;
      padding: 0.7rem 1.2rem;
      border: none;
      outline: none;
      font-size: 1.1rem;
      background: transparent;
      color: #7b4e00;
      text-shadow: none;
    }
    .search-bar button {
      background: #cf9843;
      border: none;
      padding: 0.7rem 1.2rem;
      color: white;
      font-size: 1.2rem;
      cursor: pointer;
      border-radius: 0 30px 30px 0;
      transition: background 0.2s;
      text-shadow: 0 1px 6px rgba(183,124,54,0.18);
    }
    .search-bar button:hover {
      background: #b87c36;
    }
    .content {
      display: flex;
      gap: 2rem;
      padding: 2.2rem 3vw 0 3vw;
      background: rgba(255,255,255,0.92);
      border-radius: 0 0 30px 30px;
      box-shadow: 0 2px 12px rgba(223,159,73,0.07);
      margin-bottom: 1.5rem;
    }
    .filters {
      display: flex;
      flex-direction: row;
      gap: 1.2rem;
      width: auto;
      align-items: center;
      margin-left: 0;
      margin-bottom: 0;
    }
    .filters button {
      padding: 0.8rem 2.1rem;
      font-size: 1.13rem;
      border-radius: 22px;
      border: none;
      background: none;
      color: #b87c36;
      font-weight: bold;
      cursor: pointer;
      box-shadow: none;
      transition: background 0.2s, color 0.2s, transform 0.18s;
      letter-spacing: 0.5px;
      text-shadow: 0 1px 6px rgba(255,248,241,0.7);
    }
    .filters button:hover {
      background: none;
      color: #df9f49;
      transform: translateY(-2px) scale(1.06);
    }
    .products {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 2rem;
      width: 100%;
      margin-bottom: 2.2rem;
    }
    .product {
      background: rgba(255,255,255,0.92);
      border-radius: 14px;
      box-shadow: 0 2px 16px rgba(223,159,73,0.10), 0 1.5px 8px rgba(0,0,0,0.07);
      max-width: 260px;
      min-width: 170px;
      overflow: hidden;
      text-align: center;
      position: relative;
      transition: box-shadow 0.2s, transform 0.18s;
      border: 1.5px solid #f7e2c6;
    }
    .product:hover {
      box-shadow: 0 6px 32px rgba(223,159,73,0.18), 0 4px 16px rgba(0,0,0,0.10);
      transform: translateY(-4px) scale(1.025);
      border-color: #df9f49;
    }
    .product img {
      width: 100%;
      height: 170px;
      object-fit: cover;
      border-bottom: 1px solid #f7e2c6;
      background: #f7e2c6;
    }
    .product h3 {
      margin: 0.7rem 0 0.3rem 0;
      font-size: 1.08rem;
      font-weight: 700;
      color: #7b4e00;
      min-height: 2.2em;
      text-shadow: 0 1px 6px rgba(255,248,241,0.7);
    }
    .product p {
      font-weight: bold;
      color: #b87c36;
      margin-bottom: 0.5rem;
      text-shadow: 0 1px 6px rgba(255,248,241,0.7);
    }
    .product button {
      margin: 0.7rem 0 1rem 0;
      padding: 0.4rem 1.3rem;
      background: linear-gradient(90deg, #df9f49 70%, #b87c36 100%);
      color: #fff;
      border: none;
      border-radius: 16px;
      cursor: pointer;
      font-size: 1.05rem;
      font-weight: 600;
      transition: background 0.2s, transform 0.18s;
      text-shadow: 0 2px 8px rgba(183,124,54,0.18);
    }
    .product button:hover {
      background: linear-gradient(90deg, #b87c36 60%, #df9f49 100%);
      transform: scale(1.07);
    }
    .product span {
      position: absolute;
      background: linear-gradient(90deg, #df9f49 70%, #b87c36 100%);
      color: #fff;
      padding: 3px 12px;
      border-radius: 0 0 12px 0;
      left: 0;
      top: 0;
      font-size: 0.93em;
      font-weight: bold;
      z-index: 2;
      letter-spacing: 0.5px;
      box-shadow: 0 2px 8px rgba(223,159,73,0.10);
      text-shadow: 0 1px 6px rgba(183,124,54,0.18);
    }
    section {
      margin: 2.5rem 3vw 0 3vw;
    }
    section h2 {
      font-size: 2rem;
      font-weight: 800;
      color: #b87c36;
      margin-bottom: 1.2rem;
      letter-spacing: 1px;
      text-shadow: 0 2px 8px rgba(223,159,73,0.08), 0 1px 6px rgba(255,248,241,0.7);
      border-left: 6px solid #df9f49;
      padding-left: 1rem;
    }
    .no-results {
      text-align: center;
      margin-top: 2.5rem;
      font-size: 1.25rem;
      color: #b87c36;
      font-weight: 600;
      letter-spacing: 0.5px;
      text-shadow: 0 1px 6px rgba(255,248,241,0.7);
    }
    
    @media (max-width: 1200px) {
      .products {
        grid-template-columns: repeat(3, 1fr);
      }
    }
    @media (max-width: 900px) {
      .products {
        grid-template-columns: repeat(2, 1fr);
      }
      .content, section {
        padding-left: 2vw;
        padding-right: 2vw;
      }
      header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
      }
      .filters {
        margin-top: 0.7rem;
        margin-bottom: 0;
      }
      nav {
        margin-top: 0.7rem;
      }
    }
    @media (max-width: 600px) {
      header, .content, section {
        padding-left: 1vw;
        padding-right: 1vw;
      }
      .products {
        grid-template-columns: 1fr;
        gap: 1.2rem;
      }
      .filters {
        flex-direction: column;
        gap: 0.7rem;
      }
      .hero h1 {
        font-size: 1.3rem;
      }
      .search-bar {
        width: 98%;
        max-width: 98vw;
      }
    }
   
    @media (max-width: 800px) {
      footer > div {
        flex-direction: column !important;
        gap: 1.2rem !important;
        text-align: left !important;
      }
    }
  </style>
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const searchInput = document.getElementById('search-input');
      const searchForm = document.querySelector('.search-bar');

      searchInput.addEventListener('input', () => {
        filterProducts();
      });

      searchForm.addEventListener('submit', (event) => {
        event.preventDefault();
        filterProducts();
      });
    });

    function addToCart(id, name, price) {
      fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id, name, price }),
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert(`${name} has been added to your cart!`);
        } else {
          alert('Failed to add to cart. Please try again.');
        }
      })
      .catch(error => console.error('Error:', error));
    }

    function filterProducts() {
      const searchInput = document.getElementById('search-input').value.toLowerCase();
      const products = document.querySelectorAll('.product');
      let hasResults = false;

      products.forEach(product => {
        const productName = product.querySelector('h3').textContent.toLowerCase();
        if (productName.includes(searchInput)) {
          product.style.display = '';
          hasResults = true;
        } else {
          product.style.display = 'none';
        }
      });

      const noResultsMessage = document.getElementById('no-results');
      if (hasResults) {
        noResultsMessage.style.display = 'none';
      } else {
        noResultsMessage.style.display = 'block';
      }
    }
  </script>
</head>
<body>
  <header>
    <div class="logo">ZELL.CLO</div>
    <div class="filters" style="margin: 0;">
      <button onclick="location.href='#mens-wear-section'">Men's Wear</button>
      <button onclick="location.href='#womens-wear-section'">Womens Wear</button>
    </div>
    <nav>
      <a href="#">HOME</a>
      <a href="cart.php">CART</a>
      <a href="orders.php">ORDER</a>
      <a href="#">ABOUT US</a>
      <a href="logout.php" style="color: red; font-weight: bold;">LOGOUT</a>
    </nav>
  </header>

  <section class="hero">
  <h1>Our New Collection</h1>
    <form class="search-bar">
      <input type="text" id="search-input" name="search" placeholder="Search" />
      <button type="submit">🔍</button>
    </form>
  </section>
  
  <div class="content">
    
  </div>

  <section id="mens-wear-section">
    <h2>Men's Wear</h2>
    <!-- New Arrivals Row -->
    <?php
      $count = 0;
      foreach ($categoryProducts['mens']['new'] as $product):
          if ($count % 4 === 0) {
            if ($count > 0) echo '</div>';
            echo '<div class="products">';
          }
    ?>
      <div class="product">
        <span style="position:absolute;background:#df9f49;color:#fff;padding:2px 8px;border-radius:0 0 8px 0;left:0;top:0;font-size:0.9em;font-weight:bold;z-index:2;">New Arrival</span>
        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
        <button onclick="window.location.href='product_details.php?id=<?php echo $product['product_id']; ?>'">🛒 Buy</button>
      </div>
    <?php
          $count++;
      endforeach;
      if ($count > 0) echo '</div>';
    ?>
    <!-- Old Products Row -->
    <?php
      $count = 0;
      foreach ($categoryProducts['mens']['old'] as $product):
          if ($count % 4 === 0) {
            if ($count > 0) echo '</div>';
            echo '<div class="products">';
          }
    ?>
      <div class="product">
        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
        <button onclick="window.location.href='product_details.php?id=<?php echo $product['product_id']; ?>'">🛒 Buy</button>
      </div>
    <?php
          $count++;
      endforeach;
      if ($count > 0) echo '</div>';
    ?>
  </section>
  <section id="womens-wear-section">
    <h2>Womens Wear</h2>
    <!-- New Arrivals Row -->
    <?php
      $count = 0;
      foreach ($categoryProducts['womens']['new'] as $product):
          if ($count % 4 === 0) {
            if ($count > 0) echo '</div>';
            echo '<div class="products">';
          }
    ?>
      <div class="product">
        <span style="position:absolute;background:#df9f49;color:#fff;padding:2px 8px;border-radius:0 0 8px 0;left:0;top:0;font-size:0.9em;font-weight:bold;z-index:2;">New Arrival</span>
        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
        <button onclick="window.location.href='product_details.php?id=<?php echo $product['product_id']; ?>'">🛒 Buy</button>
      </div>
    <?php
          $count++;
      endforeach;
      if ($count > 0) echo '</div>';
    ?>
    <!-- Old Products Row -->
    <?php
      $count = 0;
      foreach ($categoryProducts['womens']['old'] as $product):
          if ($count % 4 === 0) {
            if ($count > 0) echo '</div>';
            echo '<div class="products">';
          }
    ?>
      <div class="product">
        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
        <button onclick="window.location.href='product_details.php?id=<?php echo $product['product_id']; ?>'">🛒 Buy</button>
      </div>
    <?php
          $count++;
      endforeach;
      if ($count > 0) echo '</div>';
    ?>
  </section>

  <p id="no-results" class="no-results" style="display: none;">No results found.</p>

  <!-- Footer -->
  <footer style="background: #fff8f1; color: #7b4e00; padding: 2.5rem 0 1.2rem 0; border-top: 1px solid #e0c9a6; margin-top: 2rem;">
    <div style="max-width: 1100px; margin: 0 auto; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 2rem; text-align: left;">
     
      <div style="flex: 1 1 250px; min-width: 220px;">
        <h3 style="margin-bottom: 0.7rem; color: #b87c36;">About Us</h3>
        <p style="font-size: 1rem; line-height: 1.6;">
         Welcome to Zell.Clo, where fashion meets quality and accessibility. Inspired by the spirit of modern style, we offer a curated collection of apparel and accessories for women and men, ensuring something special for every fashionable life of yours. Our range includes trendy dresses, and versatile everyday wear created to enhance your unique style.
      </div>
      
      <div style="flex: 1 1 220px; min-width: 200px;">
        <h3 style="margin-bottom: 0.7rem; color: #b87c36;">Contact Us</h3>
        <p style="font-size: 1rem; line-height: 1.6;">
          <strong>Email:</strong> <a href="mailto:support@zellclo.com" style="color:#7b4e00;text-decoration:underline;">support@zellclo.com</a><br>
          <strong>Phone:</strong> <a href="tel:+639123456789" style="color:#7b4e00;text-decoration:underline;">+63 912 345 6789</a><br>
          <strong>Address:</strong> 123 Fashion Ave, Manila, PH
        </p>
      </div>
     
      <div style="flex: 1 1 220px; min-width: 200px;">
        <h3 style="margin-bottom: 0.7rem; color: #b87c36;">Help</h3>
        <ul style="list-style: none; padding: 0; font-size: 1rem; line-height: 1.8;">
          <li><a href="faq.php" style="color:#7b4e00;text-decoration:underline;">FAQ</a></li>
          <li><a href="shipping.php" style="color:#7b4e00;text-decoration:underline;">Shipping & Delivery</a></li>
          <li><a href="contact.php" style="color:#7b4e00;text-decoration:underline;">Customer Support</a></li>
        </ul>
      </div>
    </div>
    <div style="margin-top: 2rem; text-align: center; font-size: 0.95rem; color: #b87c36;">
      &copy; <?php echo date('Y'); ?> ZELL.CLO. All rights reserved.
    </div>
  </footer>
</body>
</html>
</body>
</html>
</body>
</html>

