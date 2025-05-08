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

// Get the category from the query string
$category = isset($_GET['category']) ? htmlspecialchars($_GET['category']) : '';

// Fetch products from the database based on the category
$query = "
    SELECT 
        auction_id, 
        title, 
        image, 
        start_price, 
        bid_amount, 
        end_time 
    FROM AUCTIONS 
    WHERE LOWER(category) = LOWER(?) AND status = 'active'
";
$stmt = $pdo->prepare($query);
$stmt->execute([$category]);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category); ?> - BidSecond</title>
    <link rel="stylesheet" href="styles/main.css">
</head>
<body>
    <!-- Header Section -->
    <header id="header">
        <div class="banner">
            <div class="logo">
                <img src="images/logo.png" alt="BidSecond Logo">
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
        <h1><?php echo htmlspecialchars($category); ?></h1>
        <div class="product-list">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <a href="Product.php?auction_id=<?php echo $product['auction_id']; ?>">
                            <?php if (!empty($product['image'])): ?>
                                <img src="data:image/jpeg;base64,<?php echo base64_encode($product['image']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
                            <?php else: ?>
                                <img src="images/no-image.jpg" alt="No Image Available">
                            <?php endif; ?>
                            <h2><?php echo htmlspecialchars($product['title']); ?></h2>
                            <p>Start Price: $<?php echo number_format($product['start_price'], 2); ?></p>
                            <p>Current Bid: $<?php echo number_format($product['bid_amount'], 2); ?></p>
                            <p>Ends: <?php echo htmlspecialchars($product['end_time']); ?></p>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No products found in this category.</p>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer id="footer">
        <p>&copy; 2025 BidSecond. All rights reserved.</p>
    </footer>
</body>
</html>