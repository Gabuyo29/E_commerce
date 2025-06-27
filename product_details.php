<?php
session_start();
require_once 'connection.php';


if (!isset($_GET['id'])) {
    header("Location: welcome.php");
    exit();
}

$product_id = $_GET['id'];

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['size'], $_POST['price'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['id']);
    $size = $_POST['size'];
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity'] ?? 1);

    $conn = OpenCon();

    // Find or create pending order
    $stmt = $conn->prepare("SELECT order_id FROM orders WHERE user_id = ? AND status = 'pending' LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($order_id);
    if ($stmt->fetch()) {
        $stmt->close();
    } else {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, status, created_at) VALUES (?, 0, 'pending', NOW())");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $order_id = $stmt->insert_id;
        $stmt->close();
    }

    // Add or update item in order_items
    $stmt = $conn->prepare("SELECT order_item_id, quantity FROM order_items WHERE order_id = ? AND product_id = ? AND size = ?");
    $stmt->bind_param("iis", $order_id, $product_id, $size);
    $stmt->execute();
    $stmt->bind_result($order_item_id, $existing_qty);
    if ($stmt->fetch()) {
        $stmt->close();
        $new_qty = $existing_qty + $quantity;
        $stmt = $conn->prepare("UPDATE order_items SET quantity = ? WHERE order_item_id = ?");
        $stmt->bind_param("ii", $new_qty, $order_item_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, size, quantity, price) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisid", $order_id, $product_id, $size, $quantity, $price);
        $stmt->execute();
        $stmt->close();
    }

    // Update order total
    $stmt = $conn->prepare("SELECT SUM(price * quantity) FROM order_items WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->bind_result($total_price);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE orders SET total_price = ? WHERE order_id = ?");
    $stmt->bind_param("di", $total_price, $order_id);
    $stmt->execute();
    $stmt->close();

    CloseCon($conn);

    header("Location: cart.php");
    exit();
}

// Fetch product details
$conn = OpenCon();
$stmt = $conn->prepare("SELECT product_id, name, price, image, description, user_id FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    echo "Product not found.";
    exit();
}

// Fetch available sizes
$stmt = $conn->prepare("SELECT product_id, sizes, stock, price, image FROM products WHERE name = ? AND user_id = ? AND active = 1");
$stmt->bind_param("si", $product['name'], $product['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$sizes = [];
while ($row = $result->fetch_assoc()) {
    $sizes[$row['sizes']] = [
        'product_id' => $row['product_id'],
        'stock' => $row['stock'],
        'price' => $row['price'],
        'image' => $row['image']
    ];
}
$stmt->close();

// Fetch all images for product
$stmt = $conn->prepare("SELECT image FROM products WHERE name = ? AND user_id = ?");
$stmt->bind_param("si", $product['name'], $product['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$product_images = [];
while ($row = $result->fetch_assoc()) {
    $product_images[] = $row['image'];
}
$stmt->close();
CloseCon($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Details</title>
  <link rel="stylesheet" href="css/product_details.css">
</head>
<body>
  <a href="javascript:history.back()" class="back-button">← Back</a>
  <div class="container">
    <div class="gallery">
     
      <div class="slider">
        <div class="slides" id="slides">
          <?php foreach ($product_images as $index => $image): ?>
            <img src="<?php echo htmlspecialchars($image); ?>" alt="Product Image" data-size-index="<?php echo $index; ?>">
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="details">
      <h1><?php echo htmlspecialchars($product['name']); ?></h1>
      <p><strong>Description:</strong></p>
      <p class="description"><?php echo htmlspecialchars($product['description']); ?></p>
      <p class="price" id="productPrice">
        ₱<?php
          // Show price for first available size
          $firstAvailable = null;
          foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $size) {
              if (isset($sizes[$size])) {
                  $firstAvailable = $sizes[$size]['price'];
                  break;
              }
          }
          echo htmlspecialchars($firstAvailable ?? $product['price']);
        ?>
      </p>
      <div class="sizes">
        <p><strong>Sizes:</strong></p>
        <?php foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $size): ?>
          <button 
            class="<?php echo ($sizes[$size]['stock'] ?? 0) > 0 ? '' : 'disabled'; ?>" 
            data-size="<?php echo $size; ?>"
            <?php echo ($sizes[$size]['stock'] ?? 0) > 0 ? '' : 'disabled'; ?>>
            <?php echo $size; ?>
          </button>
        <?php endforeach; ?>
      </div>
  
      <form id="addToCartForm" action="product_details.php?id=<?php echo htmlspecialchars($product['product_id']); ?>" method="POST">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
        <input type="hidden" name="name" value="<?php echo htmlspecialchars($product['name']); ?>">
        <input type="hidden" name="price" id="selectedPrice" value="<?php echo htmlspecialchars($product['price']); ?>">
        <input type="hidden" name="size" id="selectedSize" value="">
        <input type="hidden" name="quantity" value="1">
        <button type="submit" class="add-to-bag" id="addToBagButton" disabled>ADD TO BAG</button>
      </form>
    </div>
  </div>
  <script>
   
    const slides = document.getElementById('slides');
   
    let selectedSize = null;
    const sizesData = <?php echo json_encode($sizes); ?>;

    document.querySelectorAll('.sizes button').forEach((button) => {
      button.addEventListener('click', function () {
        if (!this.classList.contains('disabled')) {
          document.querySelectorAll('.sizes button').forEach(btn => btn.classList.remove('selected'));
          this.classList.add('selected');
          const selectedSize = this.getAttribute('data-size');
          const selectedSizeData = sizesData[selectedSize];
          document.getElementById('selectedSize').value = selectedSize;
          document.getElementById('selectedPrice').value = selectedSizeData.price;
          document.getElementById('addToCartForm').elements['id'].value = selectedSizeData.product_id;

          document.getElementById('productPrice').textContent = `₱${selectedSizeData.price}`;

          const addToBagButton = document.getElementById('addToBagButton');
          addToBagButton.disabled = false;
          addToBagButton.textContent = `ADD TO BAG (${selectedSize})`;

          const slides = document.getElementById('slides');
          Array.from(slides.children).forEach((img, idx) => {
            if (img.getAttribute('src') === selectedSizeData.image) {
              slides.style.transform = `translateX(-${idx * img.clientWidth}px)`;
            }
          });
        }
      });
    });
  </script>
</body>
</html>

  </script>
</body>
</html>
