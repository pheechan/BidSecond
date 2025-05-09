<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

// Access user information from the session
$user = $_SESSION['user'];

// Database connection
$config = include(__DIR__ . '/../private/config.php'); // Load configuration

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']}",
        $config['db_user'],
        $config['db_password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Enable exceptions for errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch results as associative arrays
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get the auction_id from the query string
$auction_id = isset($_GET['auction_id']) ? intval($_GET['auction_id']) : 0;

// Fetch product details from the database
$query = "
    SELECT 
        a.title, 
        a.category, 
        a.image, 
        a.start_price, 
        a.bid_amount, 
        a.min_increment, 
        a.description, 
        a.end_time, 
        a.created_at, 
        a.status, 
        a.seller_id, 
        s.user_id AS seller_user_id, -- Fetch user_id from SELLER table
        u.username AS seller_name 
    FROM AUCTIONS a
    LEFT JOIN SELLER s ON a.seller_id = s.seller_id
    LEFT JOIN USERS u ON s.user_id = u.user_id
    WHERE a.auction_id = ?
";
$stmt = $pdo->prepare($query);
$stmt->execute([$auction_id]);
$product = $stmt->fetch();

if (!$product) {
    die("Product not found.");
}

// Check if the logged-in user is the seller
$is_seller = intval($product['seller_user_id']) === intval($user['user_id']);

// Handle bid submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bid_amount'])) {
    $bid_amount = floatval($_POST['bid_amount']);

    // Check if the bid is valid
    if ($bid_amount < $product['bid_amount'] + $product['min_increment']) {
        $error = "Your bid must be at least ฿" . number_format($product['bid_amount'] + $product['min_increment'], 2);
    } else {
        // Check user's balance
        $balance_query = "SELECT balance FROM USERS WHERE user_id = ?";
        $balance_stmt = $pdo->prepare($balance_query);
        $balance_stmt->execute([$user['user_id']]);
        $user_balance = $balance_stmt->fetchColumn();

        if ($user_balance === false) {
            $error = "Unable to fetch your balance. Please try again.";
        } elseif ($user_balance < $bid_amount) {
            $error = "You do not have enough balance to place this bid.";
        } else {
            // Update the auction's current bid
            $update_bid_query = "UPDATE AUCTIONS SET bid_amount = ? WHERE auction_id = ?";
            $update_bid_stmt = $pdo->prepare($update_bid_query);
            $update_bid_stmt->execute([$bid_amount, $auction_id]);

            $success = "Your bid has been placed successfully!";

            // Refresh the page to show updated data
            header("Location: Product.php?auction_id=" . $auction_id);
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['title']); ?> - BidSecond</title>
    <!-- Include main.css for global styles -->
    <link rel="stylesheet" href="styles/main.css">
    <!-- Include product.css for product-specific styles -->
    <link rel="stylesheet" href="styles/product.css">
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
            <div class="search-bar">
                <input type="text" placeholder="Search for items...">
                <button type="submit">Search</button>
            </div>
            <nav class="nav-links">
                <a href="Sell.php">Sell</a>
                <a href="#">Balance</a>
                <?php if ($user['role'] === 'admin') { ?> <!-- Show Dashboard only for admin -->
                    <a href="#">Dashboard</a>
                <?php } ?>
                <a href="account.php">Account</a> <!-- Link to account.php -->
                <a href="#">Cart</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main id="main-content">
        <!-- Left Column: Product Image -->
        <div class="product-image-container">
            <?php if (!empty($product['image'])): ?>
                <img src="data:image/jpeg;base64,<?php echo base64_encode($product['image']); ?>" alt="Product Image">
            <?php else: ?>
                <p>No image available for this product.</p>
            <?php endif; ?>
        </div>

        <!-- Right Column: Product Details -->
        <div class="product-details-container">
            <h1><?php echo htmlspecialchars($product['title']); ?></h1>
            <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?></p>
            <p><strong>Seller:</strong> <?php echo htmlspecialchars($product['seller_name']); ?></p>
            <p><strong>Start Price:</strong> ฿<?php echo number_format($product['start_price'], 2); ?></p>
            <p><strong>Current Bid:</strong> ฿<?php echo number_format($product['bid_amount'], 2); ?></p>
            <p><strong>Minimum Increment:</strong> ฿<?php echo number_format($product['min_increment'], 2); ?></p>
            <p><strong>End Time:</strong> <?php echo htmlspecialchars($product['end_time']); ?></p>
            <div class="product-description">
                <p><strong>Description:</strong></p>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>

            <!-- Bid Section -->
            <?php if ($is_seller): ?>
                <div class="bid-section">
                    <p class="seller-message">You are the seller of this listing and cannot place a bid.</p>
                </div>
            <?php elseif ($product['status'] === 'active'): ?>
                <div class="bid-section">
                    <form method="POST">
                        <label for="bid_amount">Your Bid:</label>
                        <input type="number" step="0.01" name="bid_amount" id="bid_amount" required>
                        <button type="submit">Place Bid</button>
                    </form>
                    <?php if (isset($error)): ?>
                        <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
                    <?php endif; ?>
                    <?php if (isset($success)): ?>
                        <p class="success-message"><?php echo htmlspecialchars($success); ?></p>
                    <?php endif; ?>
                </div>
            <?php elseif ($product['status'] === 'pending'): ?>
                <p>This auction is pending and cannot accept bids at this time.</p>
            <?php elseif ($product['status'] === 'ended'): ?>
                <p>This auction has ended.</p>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer id="footer">
        <p>&copy; 2025 BidSecond. All rights reserved.</p>
    </footer>
</body>
</html>