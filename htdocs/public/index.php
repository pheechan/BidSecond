<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include_once 'init.php';
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

// Fetch the top 4 products with the most bids
$hotBidsQuery = "
    SELECT 
        a.auction_id, 
        a.title, 
        a.image, 
        a.start_price, 
        a.bid_amount, 
        COUNT(b.bid_id) AS bid_count
    FROM BIDS b
    INNER JOIN AUCTIONS a ON b.auction_id = a.auction_id
    WHERE a.status = 'active'
    GROUP BY b.auction_id
    ORDER BY bid_count DESC
    LIMIT 4
";
$hotBidsStmt = $pdo->prepare($hotBidsQuery);
if (!$hotBidsStmt->execute()) {
    die("Query failed: " . implode(", ", $hotBidsStmt->errorInfo()));
}
$hotBids = $hotBidsStmt->fetchAll();
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
                <!-- Search Bar -->
                <form id="searchForm" class="search-bar" onsubmit="return openSearchPopup();">
                    <input type="text" id="searchInput" name="q" placeholder="Search for items..." required>
                    <button type="submit">Search</button>
                </form>
                <script>
                function openSearchPopup() {
                    const q = encodeURIComponent(document.getElementById('searchInput').value);
                    window.open('search.php?q=' + q, 'searchPopup', 'width=900,height=700,scrollbars=yes');
                    return false; // Prevent normal form submit
                }
                </script>
            </div>
            <nav class="nav-links">
                <a href="sell.php">Sell</a>
                <a href="wallet.php">Balance</a>
                <?php if ($user['roles'] === 'admin') { ?> <!-- Show Dashboard only for admin -->
                    <a href="dashboard.php">Dashboard</a>
                <?php } ?>
                <a href="account.php">Account</a> <!-- Link to account.php -->
                <a href="cart.php">Cart</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main id="main-content">
        <!-- Slideshow Section -->
        <div class="slideshow-container">
            <div class="mySlides fade">
                <img src="images/promo1.png" style="width:50%">
            </div>
            <div class="mySlides fade">
                <img src="images/promo2.png" style="width:50%">
            </div>
            <div class="mySlides fade">
                <img src="images/promo3.png" style="width:50%">
            </div>
        </div>
        <div class="dots-container">
            <span class="dot"></span>
            <span class="dot"></span>
            <span class="dot"></span>
        </div>

        <!-- Hot Bids Section -->
        <h2 class="section-title">Hot Bids</h2>
        <div class="bidding-items">
            <?php if (count($hotBids) > 0): ?>
                <?php foreach ($hotBids as $product): ?>
                    <?php
                        // Fetch end_time for this auction
                        $stmt = $pdo->prepare("SELECT end_time FROM AUCTIONS WHERE auction_id = ?");
                        $stmt->execute([$product['auction_id']]);
                        $endTime = $stmt->fetchColumn();
                        $now = new DateTime("now", new DateTimeZone("Asia/Bangkok")); // Use your server timezone
                        $end = new DateTime($endTime, new DateTimeZone("Asia/Bangkok"));
                        $interval = $now < $end ? $now->diff($end) : false;
                    ?>
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
                        <p>Start Price: ฿<?php echo number_format($product['start_price'], 2); ?></p>
                        <p>Current Bid: ฿<?php echo number_format($product['bid_amount'], 2); ?></p>
                        <?php if (isset($product['bid_count'])): ?>
                            <p>Total Bids: <?php echo $product['bid_count']; ?></p>
                        <?php endif; ?>
                        <div class="countdown-timer <?php echo ($interval && $interval->days > 0) ? 'countdown-green' : 'countdown-red'; ?>">
                            <?php if ($interval): ?>
                                Time left: 
                                <?php
                                    if ($interval->d > 0) echo $interval->d . "d ";
                                    printf("%02dh %02dm %02ds", $interval->h, $interval->i, $interval->s);
                                ?>
                            <?php else: ?>
                                Auction ended
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-products-message">No hot bids available at the moment. Please check back later!</p>
            <?php endif; ?>
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
        // Fetch all active products from the AUCTIONS table
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
        ";
        $stmt = $pdo->prepare($query);
        if (!$stmt->execute()) {
            die("Query failed: " . implode(", ", $stmt->errorInfo()));
        }
        $randomProducts = $stmt->fetchAll();
        ?>

        <h2 class="section-title">Items on Bidding</h2>
        <div class="bidding-items">
            <?php if (count($randomProducts) > 0): ?>
                <?php foreach ($randomProducts as $product): ?>
                    <?php
                        $stmt = $pdo->prepare("SELECT end_time FROM AUCTIONS WHERE auction_id = ?");
                        $stmt->execute([$product['auction_id']]);
                        $endTime = $stmt->fetchColumn();
                        $now = new DateTime("now", new DateTimeZone("Asia/Bangkok"));
                        $end = new DateTime($endTime, new DateTimeZone("Asia/Bangkok"));
                        $interval = $now < $end ? $now->diff($end) : false;
                    ?>
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
                        <p>Start Price: ฿<?php echo number_format($product['start_price'], 2); ?></p>
                        <p>Current Bid: ฿<?php echo number_format($product['bid_amount'], 2); ?></p>
                        <?php if (isset($product['bid_count'])): ?>
                            <p>Total Bids: <?php echo $product['bid_count']; ?></p>
                        <?php endif; ?>
                        <div class="countdown-timer <?php echo ($interval && $interval->days > 0) ? 'countdown-green' : 'countdown-red'; ?>">
                            <?php if ($interval): ?>
                                Time left: 
                                <?php
                                    if ($interval->d > 0) echo $interval->d . "d ";
                                    printf("%02dh %02dm %02ds", $interval->h, $interval->i, $interval->s);
                                ?>
                            <?php else: ?>
                                Auction ended
                            <?php endif; ?>
                        </div>
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
    <script>
        let slideIndex = 0;

        function showSlides(index = null) {
            const slides = document.getElementsByClassName("mySlides");
            const dots = document.getElementsByClassName("dot");

            // If an index is provided (dot clicked), update the slideIndex
            if (index !== null) {
                slideIndex = index;
            } else {
                // Increment slide index for automatic slideshow
                slideIndex++;
                if (slideIndex > slides.length) {
                    slideIndex = 1; // Reset to the first slide
                }
            }

            // Hide all slides
            for (let i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";
            }

            // Remove active class from all dots
            for (let i = 0; i < dots.length; i++) {
                dots[i].className = dots[i].className.replace(" active", "");
            }

            // Show the current slide and activate the corresponding dot
            slides[slideIndex - 1].style.display = "block";
            dots[slideIndex - 1].className += " active";

            // Automatically change slides every 3 seconds if no dot is clicked
            if (index === null) {
                setTimeout(showSlides, 3000);
            }
        }

        // Add click event listeners to dots
        function setupDots() {
            const dots = document.getElementsByClassName("dot");
            for (let i = 0; i < dots.length; i++) {
                dots[i].addEventListener("click", () => showSlides(i + 1));
            }
        }

        // Initialize the slideshow and dots
        setupDots();
        showSlides();
    </script>
</body>
</html>
``` 