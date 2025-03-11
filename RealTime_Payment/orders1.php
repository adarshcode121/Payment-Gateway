<?php
include "db.php";

// Redirect to cart.php if the cart is empty
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

// Calculate cart details
$product_names = [];
$total_quantity = 0;
$grand_total = 0;

foreach ($_SESSION['cart'] as $item) {
    $product_names[] = $item['name'];
    $total_quantity += $item['quantity'];
    $grand_total += $item['price'] * $item['quantity'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = $_POST['name'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $product_names_str = implode(', ', $product_names);

    // Insert data into the database
    $stmt = $conn->prepare("INSERT INTO orders (name, address, email, phone, product_names, quantity, grand_total) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssid", $name, $address, $email, $phone, $product_names_str, $total_quantity, $grand_total);

    if ($stmt->execute()) {
        // Get the inserted order ID
        $order_id = $stmt->insert_id;

        // Clear the cart after successful order
        mysqli_query($conn, "DELETE FROM `cart`");

        // Redirect to the bill page with the order ID
        header("location: payment.php?order_id=" . $order_id);
        exit();

    } else {
        echo "<p>Failed to place the order: " . $stmt->error . "</p>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
        }

        form {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input, textarea, button {
            width: 100%;
            margin-bottom: 15px;
            padding: 10px;
            font-size: 16px;
        }

        button {
            background-color: #28a745;
            color: #fff;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #218838;
        }

        .summary {
            margin: 20px auto;
            max-width: 400px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background-color: #f9f9f9;
        }

        .summary h3 {
            margin-bottom: 10px;
        }

        .summary p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <h1>Place Your Order</h1>

    <!-- Cart Summary -->
    <div class="summary">
        <h3>Cart Summary</h3>
        <p><strong>Products:</strong> <?php echo implode(', ', $product_names); ?></p>
        <p><strong>Total Quantity:</strong> <?php echo $total_quantity; ?></p>
        <p><strong>Grand Total:</strong> ₹<?php echo number_format($grand_total, 2); ?></p>
    </div>

    <!-- Order Form -->
    <form method="POST" action="">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>

        <label for="address">Address:</label>
        <textarea id="address" name="address" required></textarea>

       

        <label for="phone">Phone:</label>
        <input type="tel" id="phone" name="phone" required>

        <button type="submit">Place Order</button>
    </form>