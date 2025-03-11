<?php
include "db.php";

// Get the order_id from the URL
$order_id = $_GET['order_id'] ?? null;

// Check if order_id is provided and valid
if (!$order_id || !is_numeric($order_id)) {
    echo "<p>Invalid or missing order ID. Please go back to the <a href='orders.php'>Orders page</a>.</p>";
    exit();
}

// Fetch order details from the database
$sql = "SELECT * FROM orders WHERE id = $order_id";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo "<p>Order not found. Please go back to the <a href='orders.php'>Orders page</a>.</p>";
    exit();
}

// Fetch the order data
$order = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Bill - Flipkart Style</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
       /* Background settings */
body {
    font-family: 'Poppins', sans-serif;
    background-image: url(b1.png);
    background-size: cover;
    background-position: center;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
    overflow: hidden; /* Prevents scroll while animating */
    position: relative;
}

/* Dark Overlay Effect */
body::before {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 0;
}

/* Popup Animation for Order Summary */
.container {
    max-width: 700px;
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.3);
    position: relative;
    z-index: 1;
    transform: scale(0.8);
    opacity: 0;
    animation: popupFade 0.4s ease-out forwards;
}

/* Keyframes for Popup Effect */
@keyframes popupFade {
    0% {
        transform: scale(0.8);
        opacity: 0;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

/* Table Styling */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background: #f0f0f0;
    color: #333;
    font-weight: bold;
}

tr:hover {
    background: #f9f9f9;
}

/* Payment Button */
.pay-button {
    display: block;
    width: 100%;
    padding: 12px;
    margin-top: 20px;
    background-color: #fb641b;
    color: white;
    font-size: 16px;
    font-weight: bold;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s;
}

.pay-button:hover {
    background-color: #e05b1b;
}

    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>

    <div class="container">
        <h2>Order Summary</h2>
        <table>
            <tr>
                <th>Order ID</th>
                <td><?php echo $order['id']; ?></td>
            </tr>
            <tr>
                <th>Name</th>
                <td><?php echo htmlspecialchars($order['name']); ?></td>
            </tr>
            <tr>
                <th>Address</th>
                <td><?php echo htmlspecialchars($order['address']); ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?php echo htmlspecialchars($order['email']); ?></td>
            </tr>
            <tr>
                <th>Phone</th>
                <td><?php echo htmlspecialchars($order['phone']); ?></td>
            </tr>
            <tr>
                <th>Products</th>
                <td><?php echo htmlspecialchars($order['product_names']); ?></td>
            </tr>
            <tr>
                <th>Quantity</th>
                <td><?php echo $order['quantity']; ?></td>
            </tr>
            <tr>
                <th>Grand Total</th>
                <td id="grand_total">₹<?php echo number_format($order['grand_total'], 2); ?></td>
            </tr>
        </table>

        <!-- Payment Button -->
        <button id="pay-now" class="pay-button">Proceed to Pay</button>
    </div>

<script>
    $('#pay-now').click(function(e) {
        var amount = <?php echo $order['grand_total'] * 100; ?>; // Convert to paisa
        var name = '<?php echo htmlspecialchars($order['name']); ?>';
        var address = '<?php echo htmlspecialchars($order['address']); ?>';
        var email = '<?php echo htmlspecialchars($order['email']); ?>';
        var phone = '<?php echo htmlspecialchars($order['phone']); ?>';
        var product_names = '<?php echo htmlspecialchars($order['product_names']); ?>';
        var quantity = <?php echo $order['quantity']; ?>;
        var order_id = <?php echo $order['id']; ?>;

        if (!name || !address || !email || !phone) {
            alert('Please fill all the fields.');
            return;
        }

        var options = {
            "key": "rzp_test_oFL88BWKa4IHEK", // Replace with your Razorpay Key ID
            "amount": amount,
            "currency": "INR",
            "name": "Flipkart Store",
            "description": "Payment for your purchase",
            "image": "https://via.placeholder.com/150", // Replace with your logo URL
            "prefill": {
                "name": name,
                "email": email,
                "contact": phone
            },
            "theme": {
                "color": "#2874f0"
            },
            "handler": function(response) {
                // AJAX call to process the payment
                $.ajax({
                    url: 'charge.php',
                    type: 'POST',
                    data: {
                        razorpay_payment_id: response.razorpay_payment_id,
                        amount: amount,
                        name: name,
                        address: address,
                        email: email,
                        phone: phone,
                        product_names: product_names,
                        quantity: quantity,
                        order_id: order_id
                    },
                    success: function() {
                        alert('Payment successful!');
                        window.location.href = 'product.php'; // Change as needed
                    },
                    error: function() {
                        alert('Payment failed. Please try again.');
                    }
                });
            }
        };

        var rzp1 = new Razorpay(options);
        rzp1.open();
        e.preventDefault();
    });
</script>

</body>
</html>
