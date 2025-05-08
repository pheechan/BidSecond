<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}
$user = $_SESSION['user']; // Access user information

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
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - BidSecond</title>
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
            <div class="search-bar">
                <input type="text" placeholder="Search for items...">
                <button type="submit">Search</button>
            </div>
            <nav class="nav-links">
                <a href="sell.php">Sell</a>
                <a href="wallet.php">Balance</a>
                <?php if ($user['roles'] === 'admin') { ?> <!-- Show Dashboard only for admin -->
                    <a href="#">Dashboard</a>
                <?php } ?>
                <a href="account.php">Account</a> <!-- Link to account.php -->
                <a href="#">Cart</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main id="main-content">
        <!-- Slideshow Section -->
        <div class="slideshow-container">
            <div class="mySlides fade">
                <img src="images/promo1.jpg" style="width:100%">
            </div>
            <div class="mySlides fade">
                <img src="images/promo2.jpg" style="width:100%">
            </div>
            <div class="mySlides fade">
                <img src="images/promo3.jpg" style="width:100%">
            </div>
        </div>
        <div class="dots-container">
            <span class="dot"></span>
            <span class="dot"></span>
            <span class="dot"></span>
        </div>

        <!-- Hot Bids Section -->
        <h2 class="section-title">Hot Bids</h2>
        <div class="hot-bids">
            <button class="arrow left-arrow" onclick="scrollHotBids(-1)">&#10094;</button>
            <div class="hot-bids-container">
                <div class="bid-item">Hot Bid 1</div>
                <div class="bid-item">Hot Bid 2</div>
                <div class="bid-item">Hot Bid 3</div>
                <div class="bid-item">Hot Bid 4</div>
            </div>
            <button class="arrow right-arrow" onclick="scrollHotBids(1)">&#10095;</button>
        </div>

        <!-- Categories Section -->
        
        <h2 class="section-title">Categories</h2>
        <div class="categories">
            <div class="category-card">
                <a href="category.php?category=Electronics">
                    
                    <p>Electronics</p>
                </a>
            </div>
            <div class="category-card">
                <a href="category.php?category=Fashion">
                    
                    <p>Fashion</p>
                </a>
            </div>
            <div class="category-card">
                <a href="category.php?category=Home and Garden">
                    
                    <p>Home and Garden</p>
                </a>
            </div>
            <div class="category-card">
                <a href="category.php?category=Toys and Games">
                    
                    <p>Toys and Games</p>
                </a>
            </div>
            <div class="category-card">
                <a href="category.php?category=Automotive">
                    
                    <p>Automotive</p>
                </a>
            </div>
            <div class="category-card">
                <a href="category.php?category=Sports and Outdoors">
                    
                    <p>Sports and Outdoors</p>
                </a>
            </div>
            <div class="category-card">
                <a href="category.php?category=Books and Media">
                    
                    <p>Books and Media</p>
                </a>
            </div>
            <div class="category-card">
                <a href="category.php?category=Health and Beauty">
                    
                    <p>Health and Beauty</p>
                </a>
            </div>
            <div class="category-card">
                <a href="category.php?category=Jewelry and Watches">
                    
                    <p>Jewelry and Watches</p>
                </a>
            </div>
            <div class="category-card">
                <a href="category.php?category=Music and Instruments">
                    
                    <p>Music and Instruments</p>
                </a>
            </div>
            <div class="category-card">
                <a href="category.php?category=Collectibles and Antiques">
                    
                    <p>Collectibles and Antiques</p>
                </a>
            </div>
            <div class="category-card">
                <a href="category.php?category=Art and Craft">
                    
                    <p>Art and Craft</p>
                </a>
            </div>
        </div>

        <!-- Items on Bidding Section -->
        <?php
        // Fetch random active products from the AUCTIONS table
        $query = "
            SELECT 
                auction_id, 
                title, 
                image, 
                start_price, 
                bid_amount 
            FROM AUCTIONS 
            WHERE status = 'active' 
            ORDER BY RAND() 
            LIMIT 4
        ";
        $stmt = $pdo->prepare($query);
        if (!$stmt->execute()) {
            die("Query failed: " . implode(", ", $stmt->errorInfo()));
        }
        $stmt->execute();
        $randomProducts = $stmt->fetchAll();
        ?>
        <?php
        echo "Checkpoint 1"; // Add this after the PHP block
        ?>
        <!-- Items on Bidding Section -->
        <!-- Items on Bidding Section -->
        <h2 class="section-title">Items on Bidding</h2>
        <div class="bidding-items">
            <?php if (count($randomProducts) > 0): ?>
                <?php foreach ($randomProducts as $product): ?>
                    <div class="bidding-item">
                        <a href="Product.php?auction_id=<?php echo $product['auction_id']; ?>">
                            <?php if (!empty($product['image'])): ?>
                                <img src="data:image/jpeg;base64,<?php echo base64_encode($product['image']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
                            <?php else: ?>
                                <img src="images/no-image.jpg" alt="No Image Available">
                            <?php endif; ?>
                            <p><?php echo htmlspecialchars($product['title']); ?></p>
                            <p>Start Price: $<?php echo number_format($product['start_price'], 2); ?></p>
                            <p>Current Bid: $<?php echo number_format($product['bid_amount'], 2); ?></p>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-products-message">No active products available for bidding at the moment. Please check back later!</p>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer id="footer">
        <p>&copy; 2025 BidSecond. All rights reserved.</p>
    </footer>

    <script src="scripts/main.js"></script>
</body>
</html>