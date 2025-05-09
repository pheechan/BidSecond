<?php
session_start();
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

$user = $_SESSION['user'] ?? null;

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];
if ($q !== '') {
    // Escape special regex characters for MySQL REGEXP
    $safe_q = preg_replace('/([\\\\^$.|?*+(){}\[\]])/', '\\\\$1', $q);
    $stmt = $pdo->prepare("SELECT auction_id, title, image, start_price, bid_amount, category FROM AUCTIONS WHERE status = 'active' AND (title REGEXP ? OR category REGEXP ?)");
    $stmt->execute([$safe_q, $safe_q]);
    $results = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Search Results - BidSecond</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/search.css">
</head>
<body>
    <!-- Header Section (copy from index.php) -->
    <header id="header">
        <div class="banner">
            <div class="logo">
                <a href="index.php">
                    <img src="images/logo.png" alt="BidSecond Logo">
                </a>
            </div>
            <div class="search-bar">
                <form id="searchForm" class="search-bar" action="search.php" method="get">
                    <input type="text" id="searchInput" name="q" placeholder="Search for items..." value="<?php echo htmlspecialchars($q); ?>" required>
                    <button type="submit">Search</button>
                </form>
            </div>
            <nav class="nav-links">
                <a href="sell.php">Sell</a>
                <a href="wallet.php">Balance</a>
                <?php if ($user && $user['roles'] === 'admin') { ?>
                    <a href="dashboard.php">Dashboard</a>
                <?php } ?>
                <a href="account.php">Account</a>
                <a href="cart.php">Cart</a>
            </nav>
        </div>
    </header>

    <main id="main-content">
        <div class="search-title">
            Result for "<?php echo htmlspecialchars($q); ?>"
        </div>
        <div class="bidding-items">
            <?php if ($q === ''): ?>
                <div class="no-results">Please enter a search term.</div>
            <?php elseif (count($results) === 0): ?>
                <div class="no-results">No products found for "<?php echo htmlspecialchars($q); ?>".</div>
            <?php else: ?>
                <?php foreach ($results as $product): ?>
                    <div class="bidding-item">
                        <?php if (!empty($product['image'])): ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($product['image']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
                        <?php else: ?>
                            <img src="images/no-image.jpg" alt="No Image Available">
                        <?php endif; ?>
                        <p class="item-title">
                            <a href="Product.php?auction_id=<?php echo $product['auction_id']; ?>">
                                <?php echo htmlspecialchars($product['title']); ?>
                            </a>
                        </p>
                        <p>Category: <?php echo htmlspecialchars($product['category']); ?></p>
                        <p>Start Price: ฿<?php echo number_format($product['start_price'], 2); ?></p>
                        <p>Current Bid: ฿<?php echo number_format($product['bid_amount'], 2); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer (copy from index.php) -->
    <footer id="footer">
        <p>&copy; 2025 BidSecond. All rights reserved.</p>
    </footer>
    <script src="scripts/main.js"></script>
</body>
</html>