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
    <title>Delivery Address</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Leaflet.js OpenStreetMap API -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

    <!-- <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body {
            background-image: url(b1.png);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        
        .popup-container {
            display: flex;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 70%;
            max-width: 900px;
        }
        .map-section {
            flex: 1;
            position: relative;
        }
        #map {
            width: 100%;
            height: 100%;
        }
        .form-section {
            width: 45%;
            padding: 20px;
            position: relative;
            z-index: 10;
        }
        .form-section h2 {
            margin-bottom: 15px;
            font-size: 20px;
        }
        .input-group {
            margin-bottom: 10px;
            position: relative;
            z-index: 10;
        }
        .input-group label {
            display: block;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .input-group select, .input-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn-container {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
        }
        .btn {
            width: 48%;
            padding: 10px;
            font-size: 16px;
            cursor: pointer;
            border: none;
            color: #fff;
            border-radius: 5px;
            background: #008000;
        }
        .btn:hover {
            background: #006400;
        }
        .dropdown-container {
            position: relative;
            z-index: 10;
        }
        .dropdown-container select {
            z-index: 10;
            position: relative;
        }
    </style> -->
    <style>
        /* Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-image: url(b1.png);
            background-size: cover;
            background-position: center;
            position: relative;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            overflow: hidden;
        }

        /* Background brightness effect (Dimmed background) */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            /* Darker overlay */
            z-index: 0;
        }

        /* Popup container (Form & Map) */
        .popup-container {
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: 10px;
            box-shadow: 0px 5px 20px rgba(0, 0, 0, 0.4);
            /* Stronger shadow */
            overflow: hidden;
            width: 70%;
            max-width: 900px;
            z-index: 1;
            /* Ensures it's above the dimmed background */
            animation: fadeIn 0.5s ease-in-out;
            height: 75%;
        }

        /* Map Section */
        /* Ensure the map section takes full height */
        .map-section {
            flex: 1;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            /* Ensure it takes full height */
        }

        #map {
            width: 100%;
            height: 100%;
            min-height: 400px;
            /* Minimum height for visibility */
            border-radius: 10px;
            /* Keep smooth edges */
        }



        /* Form Section */
        .form-section {
            width: 45%;
            padding: 20px;
            position: relative;
            z-index: 10;
        }

        .form-section h2 {
            margin-bottom: 15px;
            font-size: 20px;
        }

        /* Input Fields */
        .input-group {
            margin-bottom: 10px;
            position: relative;
            z-index: 10;
        }

        .input-group label {
            display: block;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .input-group select,
        .input-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        /* Buttons */
        .btn-container {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
        }

        .btn {
            width: 48%;
            padding: 10px;
            font-size: 16px;
            cursor: pointer;
            border: none;
            color: #fff;
            border-radius: 5px;
            background: #008000;
        }

        .btn:hover {
            background: #006400;
        }

        /* Dropdown */
        .dropdown-container {
            position: relative;
            z-index: 10;
        }

        .dropdown-container select {
            z-index: 10;
            position: relative;
        }

        /* Fade-in Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Custom Leaflet Popup */
        /* .leaflet-popup-content {
    color: white ;         /* Text color */
        /* background: #008000;  /* Green background */
        /* padding: 10px; */
        /* font-weight: bold;
    border-radius: 5px;
    text-align: center;
} */

        /* Optional: Change the popup arrow color */
        /* .leaflet-popup-tip {
    background: #008000 !important;
} */
        #ad {
            font-size: 32px;
            margin-top: -45px;
        }
    </style>
</head>

<body>

    <body>
        <div class="popup-container">
            <div class="map-section">
                <div id="map"></div>
            </div>
            <div class="form-section">
                <form method="POST" action="">
                    <div class="input-group">
                        <h2 id="ad">Enter complete address</h2>
                    </div>
                    <div class="input-group">
                        <label>Name</label>
                        <input type="text" id="name" placeholder="Enter your name" name="name">
                    </div>

                    <div class="input-group">
                        <label>Address</label>
                        <input type="text" id="home_address" placeholder="Enter your address" name="address">
                    </div>

                    <div class="input-group">
                        <label>Mobile</label>
                        <input type="text" id="mobile" placeholder="Enter your phone number" name="phone"> <!-- ✅ Added name="phone" -->
                    </div>

                    <div class="input-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required placeholder="Enter your Email">
                    </div>

                    <div class="input-group">
                        <label>City *</label>
                        <select id="city" onchange="updateStreets()">
                            <option value="">Select City</option>
                            <option value="Silvassa">Silvassa</option>
                            <option value="Vapi">Vapi</option>
                            <option value="Daman">Daman</option>
                            <option value="Valsad">Valsad</option>
                        </select>
                    </div>

                    <div class="input-group dropdown-container">
                        <label>Street *</label>
                        <select id="street">
                            <option value="">Select Street</option>
                        </select>
                    </div>
                    <div class="btn-container">
                        <button type="button" class="btn" onclick="updateMap()">Confirm</button> <!-- ✅ Fixed button type -->
                        <button type="submit" class="btn">Place Order</button>
                    </div>
                </form>
            </div>
        </div>


        <script>
            let map = L.map('map').setView([20.2765, 73.0202], 9);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            let marker = L.marker([20.2765, 73.0202], {
                draggable: true
            }).addTo(map);

            const locations = {
                "Silvassa": [{
                        name: "kilvani naka ",
                        lat: 20.2733,
                        lng: 73.0050
                    },
                    {
                        name: "Dadra Road",
                        lat: 20.2745,
                        lng: 73.0250
                    },
                    {
                        name: "Balaji mandir Amli",
                        lat: 20.2803,
                        lng: 73.0233
                    },
                    {
                        name: "Vasona",
                        lat: 20.2690,
                        lng: 73.0335
                    },
                    {
                        name: "Silvassa - Daman Road",
                        lat: 20.2830,
                        lng: 73.0125
                    },
                    {
                        name: "Khanvel",
                        lat: 20.2080,
                        lng: 73.0500
                    }
                ],
                "Vapi": [{
                        name: "Rofel BCA college GIDC Vapi",
                        lat: 20.38244150914201,
                        lng: 72.91827915513903 , 
                    },

                    {
                        name: "Chala",
                        lat: 20.3800,
                        lng: 72.9100
                    },
                    {
                        name: "Gunjan char rasta",
                        lat: 20.374768417851968, 
                        lng: 72.91911726678033
                    },
                    {
                        name: "KBS college",
                        lat: 20.347411509340297, 
                        lng: 72.93408408212292
                    },
                    {
                        name: "Ambe Mata Mandir ",
                        lat: 20.377953797057057, 
                        lng: 72.9261408686792
                    }
                ],
                "Daman": [{
                        name: "Moti Daman",
                        lat: 20.3974,
                        lng: 72.8328
                    },
                    {
                        name: "Nani Daman",
                        lat: 20.4170,
                        lng: 72.8310
                    },
                    {
                        name: "Devka Beach Road",
                        lat: 20.4285,
                        lng: 72.8220
                    },
                    {
                        name: "Coast Guard Colony",
                        lat: 20.4150,
                        lng: 72.8355
                    },
                    {
                        name: "Mashal Chowk",
                        lat: 20.4200,
                        lng: 72.8280
                    }
                ],
                "Valsad": [{
                        name: "Tithal Road",
                        lat: 20.6107,
                        lng: 72.9258
                    },
                    {
                        name: "Dharampur Road",
                        lat: 20.6000,
                        lng: 72.9150
                    },
                    {
                        name: "Halar",
                        lat: 20.6200,
                        lng: 72.9350
                    },
                    {
                        name: "Parnera",
                        lat: 20.6305,
                        lng: 72.9405
                    },
                    {
                        name: "Abrama",
                        lat: 20.6155,
                        lng: 72.9200
                    }
                ]
            };


            function updateStreets() {
                let city = document.getElementById("city").value;
                let streetDropdown = document.getElementById("street");
                streetDropdown.innerHTML = '<option value="">Select Street</option>';
                if (city && locations[city]) {
                    locations[city].forEach(street => {
                        let option = document.createElement("option");
                        option.value = JSON.stringify(street);
                        option.textContent = street.name;
                        streetDropdown.appendChild(option);
                    });
                }
            }

            // function updateMap() {
            //     let streetData = document.getElementById("street").value;
            //     if (streetData) {
            //         let street = JSON.parse(streetData);
            //         marker.setLatLng([street.lat, street.lng]);
            //         map.setView([street.lat, street.lng], 14);
            //     }
            // }

            //         function updateMap() {
            //     let streetData = document.getElementById("street").value;
            //     if (streetData) {
            //         let street = JSON.parse(streetData);
            //         marker.setLatLng([street.lat, street.lng]);
            //         map.setView([street.lat, street.lng], 14);

            //         // ✅ Add popup to the marker
            //         marker.bindPopup("<b>Your order will be delivered here!</b>").openPopup();
            //     }
            // }

            // function updateMap() {
            //     let streetData = document.getElementById("street").value;
            //     if (streetData) {
            //         let street = JSON.parse(streetData);
            //         marker.setLatLng([street.lat, street.lng]);

            //         // ✅ Move map smoothly to the marker's location
            //         map.panTo([street.lat, street.lng]);

            //         // ✅ Add popup with notification
            //         marker.bindPopup("<b>Your order will be delivered here!</b>").openPopup();

            //         // ✅ Delay map adjustment to ensure popup visibility
            //         setTimeout(() => {
            //             map.setView([street.lat, street.lng], 15);
            //         }, 500);
            //     }
            // }

            function updateMap() {
                let streetData = document.getElementById("street").value;
                if (streetData) {
                    let street = JSON.parse(streetData);
                    marker.setLatLng([street.lat, street.lng]);

                    // ✅ Smooth transition to new location
                    map.flyTo([street.lat, street.lng], 15, {
                        duration: 0.8, // Smooth transition duration (in seconds)
                        easeLinearity: 0.25
                    });

                    // ✅ Show delivery notification on marker
                    marker.bindPopup("<b>Your order will be delivered here!</b>").openPopup();
                }
            }
        </script>
    </body>

</html>