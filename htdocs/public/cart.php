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
        pt.auction_id,
        pt.seller_id,
        pt.bid_amount,
        pt.buyer_id,
        pt.address,
        pt.end_time,
        pt.payment_status
    FROM PENDING_TRANSACTIONS pt
    WHERE pt.buyer_id = ? AND pt.payment_status = 'unpaid'
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
                        <div class="cart-item-details">
                            <p><strong>Auction ID:</strong> <?php echo htmlspecialchars($item['auction_id']); ?></p>
                            <p><strong>Seller ID:</strong> <?php echo htmlspecialchars($item['seller_id']); ?></p>
                            <p><strong>Winning Bid:</strong> ฿<?php echo number_format($item['bid_amount'], 2); ?></p>
                            <p><strong>Buyer ID:</strong> <?php echo htmlspecialchars($item['buyer_id']); ?></p>
                            <p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($item['address']); ?></p>
                            <p><strong>End Time:</strong> <?php echo htmlspecialchars($item['end_time']); ?></p>
                            <p><strong>Status:</strong> <?php echo ucfirst($item['payment_status']); ?></p>
                            <form action="payment.php" method="POST">
                                <input type="hidden" name="auction_id" value="<?php echo $item['auction_id']; ?>">
                                <button type="submit" class="pay-button">Pay Now</button>
                            </form>
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