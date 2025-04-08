<?php
include 'config.php';
session_start();

$user_id = $_SESSION['user_id'];

if (!isset($user_id)) {
  header('location:login.php');
}

if (isset($_POST['order_btn'])) {
  $name =  $_POST['name'];
  $number = $_POST['number'];
  $email =  $_POST['email'];
  $method =  $_POST['method'];
  $address =  $_POST['address'];
  $placed_on = date('d-M-Y');

  $cart_total = 0;
  $cart_products = [];

  $cart_query = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'") or die('query failed');

  if (mysqli_num_rows($cart_query) > 0) {
    while ($cart_item = mysqli_fetch_assoc($cart_query)) {
      $product_name = $cart_item['name'];
      $cart_quantity = $cart_item['quantity'];

      // Check stock from products table
      $product_check = mysqli_query($conn, "SELECT * FROM `products` WHERE name = '$product_name'") or die('query failed');
      $product_data = mysqli_fetch_assoc($product_check);

      if ($product_data['quantity'] < $cart_quantity) {
        $message[] = "Not enough stock for '{$product_name}'. Available: {$product_data['quantity']}, Requested: {$cart_quantity}";
        break;
      }

      $cart_products[] = $product_name . ' (' . $cart_quantity . ')';
      $sub_total = $cart_item['price'] * $cart_quantity;
      $cart_total += $sub_total;
    }

    if (empty($message)) {
      $total_products = implode(', ', $cart_products);

      $order_query = mysqli_query($conn, "SELECT * FROM `orders` WHERE name = '$name' AND number = '$number' AND email = '$email' AND method = '$method' AND address = '$address' AND total_products = '$total_products' AND total_price = '$cart_total'") or die('query failed');

      if ($cart_total == 0) {
        $message[] = 'Your cart is empty!';
      } elseif (mysqli_num_rows($order_query) > 0) {
        $message[] = 'Order already placed!';
      } else {
        mysqli_query($conn, "INSERT INTO `orders`(user_id, name, number, email, method, address, total_products, total_price, placed_on) VALUES('$user_id', '$name', '$number', '$email', '$method', '$address', '$total_products', '$cart_total', '$placed_on')") or die('query failed');

        // Deduct product quantities
        mysqli_data_seek($cart_query, 0); // Reset result pointer
        while ($cart_item = mysqli_fetch_assoc($cart_query)) {
          $product_name = $cart_item['name'];
          $cart_quantity = $cart_item['quantity'];
          mysqli_query($conn, "UPDATE `products` SET quantity = quantity - $cart_quantity WHERE name = '$product_name'") or die('Failed to update stock');
        }

        mysqli_query($conn, "DELETE FROM `cart` WHERE user_id = '$user_id'") or die('Cart clear failed');
        $message[] = 'Order placed successfully!';
      }
    }
  } else {
    $message[] = 'Your cart is empty!';
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout Page</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="home.css">
</head>
<body>

<?php include 'user_header.php'; ?>


<?php
if (isset($message)) {
  foreach ($message as $msg) {
    echo '<div class="message"><span>' . $msg . '</span> <i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>';
  }
}
?>

<section class="display_order">
  <h2>Ordered Products</h2>
  <?php
    $grand_total = 0;
    $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'") or die('query failed');

    if (mysqli_num_rows($select_cart) > 0) {
      while ($fetch_cart = mysqli_fetch_assoc($select_cart)) {
        $total_price = $fetch_cart['price'] * $fetch_cart['quantity'];
        $grand_total += $total_price;
  ?>
  <div class="single_order_product">
    <img src="./uploaded_img/<?php echo $fetch_cart['image']; ?>" alt="">
    <div class="single_des">
      <h3><?php echo $fetch_cart['name']; ?></h3>
      <p>Rs. <?php echo $fetch_cart['price']; ?></p>
      <p>Quantity: <?php echo $fetch_cart['quantity']; ?></p>
    </div>
  </div>
  <?php
      }
    } else {
      echo '<p class="empty">Your cart is empty</p>';
    }
  ?>
  <div class="checkout_grand_total">GRAND TOTAL : <span>Rs.<?php echo $grand_total; ?>/-</span></div>
</section>

<section class="contact_us">
  <form action="" method="post">
    <h2>Add Your Details</h2>
    <input type="text" name="name" required placeholder="Enter your name">
    <input type="phone" name="number" required placeholder="Enter your number">
    <input type="email" name="email" required placeholder="Enter your email">

    <select name="method">
      <option value="cash on delivery">Cash on Delivery</option>
      <option value="gpay">GPay</option>
    </select>

    <textarea name="address" placeholder="Enter your address" cols="30" rows="10" required></textarea>

    <input type="submit" value="Place Your Order" name="order_btn" class="product_btn">
  </form>
</section>

<?php include 'footer.php'; ?>

<script src="https://kit.fontawesome.com/eedbcd0c96.js" crossorigin="anonymous"></script>
<script src="script.js"></script>
</body>
</html>
