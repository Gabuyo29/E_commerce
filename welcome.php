<?php


session_start();
require_once 'connection.php';


if (!isset($_SESSION['username'])) {
    // Redirect to login page if the session is not set
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Fetch products grouped by name and user_id
$conn = OpenCon();
$stmt = $conn->prepare("SELECT MIN(product_id) AS product_id, name, price, image FROM products GROUP BY name, user_id");
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
CloseCon($conn);
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
      font-family: Arial, sans-serif;
      background-color: #fff8f1;
      color: #333;
      background-image: url('bgweb.png');
      background-size: cover;
      background-repeat: no-repeat;
      background-attachment: fixed;
    }
    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 2rem;
      background-color: white;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .logo {
      font-weight: bold;
      font-size: 1.4rem;
      color: #7b4e00;
    }
    nav {
      display: flex;
      gap: 1.5rem;
      font-size: 0.9rem;
    }
    nav a {
      text-decoration: none;
      color: #000;
      font-weight: bold;
    }
    .hero {
      position: relative;
      background: url('una.jpg') center/cover no-repeat;
      height: 300px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      text-align: center;
      color: white;
    }
    .hero h1 {
      font-size: 2rem;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.4);
    }
    .search-bar {
      margin-top: 1rem;
      background: white;
      border-radius: 25px;
      display: flex;
      align-items: center;
      overflow: hidden;
      width: 60%;
      max-width: 400px;
    }
    .search-bar input {
      flex: 1;
      padding: 0.5rem 1rem;
      border: none;
      outline: none;
    }
    .search-bar button {
      background: #cf9843;
      border: none;
      padding: 0.5rem 1rem;
      color: white;
      cursor: pointer;
    }
    .content {
      display: flex;
      gap: 2rem;
      padding: 1.5rem;
    }
    .filters {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      width: 200px;
      align-items: flex-start;
      margin-left: 24px; /* Added left margin */
    }
    .filters button {
      padding: 0.7rem 1.5rem; /* Increased size */
      font-size: 1.1rem;      /* Slightly bigger text */
      border-radius: 20px;
      border: none;
      background-color: #df9f49;
      color: white;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .filters button:nth-child(4) {
      font-size: 1rem;
      padding-left: 0.8rem;
      padding-right: 0.8rem;
      letter-spacing: 0.5px;
      word-break: keep-all;
      white-space: nowrap;
    }
    .filters button:hover {
      background-color: #b87c36; 
      transform: scale(1.05); 
    }
    .products {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      flex: 1;
    }
    .product {
      background: white;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      width: 200px;
      overflow: hidden;
      text-align: center;
    }
    .product img {
      width: 100%;
      height: 160px;
      object-fit: cover;
    }
    .product h3 {
      margin: 0.5rem 0;
    }
    .product p {
      font-weight: bold;
      color: #000;
    }
    .product button {
      margin: 0.5rem;
      padding: 0.3rem 1rem;
      background-color: #df9f49;
      color: white;
      border: none;
      border-radius: 15px;
      cursor: pointer;
    }
    .no-results {
      text-align: center;
      margin-top: 2rem;
      font-size: 1.2rem;
      color: #555;
    }
  </style>
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const searchInput = document.getElementById('search-input');
      const searchForm = document.querySelector('.search-bar');

      searchInput.addEventListener('input', () => {
        console.log(`User is typing: ${searchInput.value}`);
      });

      searchForm.addEventListener('submit', (event) => {
        console.log(`Search submitted: ${searchInput.value}`);
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
          product.style.display = 'block';
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
    <form class="search-bar" method="GET" action="search.php">
      <input type="text" id="search-input" name="search" placeholder="Search" />
      <button type="submit">🔍</button>
    </form>
  </section>
  
  <div class="content">
    <div class="filters">
      <button>New Collection</button>
      <button>Special Promo</button>
      <button>UNISEX</button>
      <button>NEUTRAL COLORS</button>
    </div>
    <div class="products">
      <?php if (!empty($products)): ?>
        <?php foreach ($products as $product): ?>
          <div class="product">
            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
            <p>₱<?php echo htmlspecialchars($product['price']); ?></p>
            <button onclick="window.location.href='product_details.php?id=<?php echo $product['product_id']; ?>'">🛒 Buy</button>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="no-results">No products available.</p>
      <?php endif; ?>
    </div>
  </div>

  <p id="no-results" class="no-results" style="display: none;">No results found.</p>
</body>
</html>

<?php
