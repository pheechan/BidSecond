<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Access user information from the session
$user = $_SESSION['user'];

// Database connection
$config = include(__DIR__ . '/../private/config.php');

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']}",
        $config['db_user'],
        $config['db_password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch pending items for the logged-in user
$query = "
    SELECT 
        a.title AS auction_title,
        pt.seller_id,
        pt.bid_amount,
        pt.buyer_id,
        pt.address,
        pt.end_time,
        pt.payment_status,
        u.username AS seller_name,
        a.image
    FROM PENDING_TRANSACTIONS pt
    INNER JOIN BUYER b ON pt.buyer_id = b.buyer_id
    INNER JOIN AUCTIONS a ON pt.auction_id = a.auction_id
    INNER JOIN SELLER s ON pt.seller_id = s.seller_id
    INNER JOIN USERS u ON s.user_id = u.user_id
    WHERE b.user_id = ? AND pt.payment_status = 'unpaid'
";
$stmt = $pdo->prepare($query);
$stmt->execute([$user['user_id']]);
$pendingItems = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - BidSecond</title>
    <link rel="stylesheet" href="styles/main.css">
    <style>
        /* Ensure the body and html take up the full height */
        html, body {
            height: 100%;
            margin: 0;
            display: flex;
            flex-direction: column;
        }

        /* Main content should take up all available space */
        #main-content {
            flex: 1;
        }

        /* Footer stays at the bottom */
        #footer {
            text-align: center;
            padding: 10px;
            background-color: #57a05a;
            position: relative;
            bottom: 0;
            width: 100%;
        }

        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 20px; /* Add spacing between boxes */
            padding: 20px;
        }

        .cart-item {
            display: flex; /* Use flexbox for layout */
            justify-content: space-between; /* Space between details and image */
            align-items: center; /* Align items vertically */
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 20px;
            background-color: #f9f9f9;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .cart-item-content {
            display: flex; /* Flex container for details and image */
            gap: 20px; /* Space between details and image */
            width: 100%; /* Ensure it takes full width */
        }

        .cart-item-details {
            flex: 3; /* Take up more space for details */
        }

        .cart-item-image {
            flex: 0; /* Take up less space for the image */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .cart-item-image img {
            width: 200px; /* Set the image width */
            height: 200px; /* Set the image height */
            object-fit: cover; /* Ensure the image fits within the box */
            border-radius: 5px; /* Optional: rounded corners */
        }

        .cart-item-details p {
            margin: 5px 0; /* Add spacing between paragraphs */
            font-size: 16px; /* Adjust font size */
        }

        .cart-item-details button {
            margin-top: 10px;
            padding: 10px 20px;
            background-color: #57a05a; /* Button background color */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .cart-item-details button:hover {
            background-color: #459048; /* Darker green on hover */
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header id="header">
        <div class="banner">
            <div class="logo">
                <a href="index.php">
                    <img src="images/logo.png" alt="BidSecond Logo">
                </a>
            </div>
            <nav class="nav-links">
                <a href="index.php">Home</a>
                <a href="account.php">Account</a>
                <a href="wallet.php">Balance</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main id="main-content">
        <h1>My Cart</h1>
        <?php if (count($pendingItems) > 0): ?>
            <div class="cart-items">
                <?php foreach ($pendingItems as $item): ?>
                    <div class="cart-item">
                        <div class="cart-item-content">
                            <div class="cart-item-details">
                                <p><strong>Title:</strong> <?php echo htmlspecialchars($item['auction_title']); ?></p>
                                <p><strong>Seller Name:</strong> <?php echo htmlspecialchars($item['seller_name']); ?></p>
                                <p><strong>Winning Bid:</strong> ฿<?php echo number_format($item['bid_amount'], 2); ?></p>
                                <p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($item['address']); ?></p>
                                <p><strong>End Time:</strong> <?php echo htmlspecialchars($item['end_time']); ?></p>
                                <p><strong>Status:</strong> <?php echo ucfirst($item['payment_status']); ?></p>
                                <form action="payment.php" method="POST">
                                    <input type="hidden" name="auction_id" value="<?php echo $item['auction_id']; ?>">
                                    <button type="submit" class="pay-button">Pay Now</button>
                                </form>
                            </div>
                            <div class="cart-item-image">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="data:image/jpeg;base64,<?php echo base64_encode($item['image']); ?>" alt="Item Image">
                                <?php else: ?>
                                    <p>No Image</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-items-message">You have no pending items in your cart.</p>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer id="footer">
        <p>&copy; 2025 BidSecond. All rights reserved.</p>
    </footer>
</body>
</html>