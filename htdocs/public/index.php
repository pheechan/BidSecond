<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();//test
}
$user = $_SESSION['user']; // Access user information
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
                <img src="images/logo.png" alt="BidSecond Logo">
            </div>
            <div class="search-bar">
                <input type="text" placeholder="Search for items...">
                <button type="submit">Search</button>
            </div>
            <nav class="nav-links">
                <a href="#">Sell</a>
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
                <img src="images/category1.jpg" alt="Category 1">
                <p>Category 1</p>
            </div>
            <div class="category-card">
                <img src="images/category2.jpg" alt="Category 2">
                <p>Category 2</p>
            </div>
            <div class="category-card">
                <img src="images/category3.jpg" alt="Category 3">
                <p>Category 3</p>
            </div>
            <div class="category-card">
                <img src="images/category4.jpg" alt="Category 4">
                <p>Category 4</p>
            </div>
        </div>

        <!-- Items on Bidding Section -->
        <h2 class="section-title">Items on Bidding</h2>
        <div class="bidding-items">
            <div class="bidding-item">
                <img src="images/item1.jpg" alt="Item 1">
                <p>Item 1</p>
            </div>
            <div class="bidding-item">
                <img src="images/item2.jpg" alt="Item 2">
                <p>Item 2</p>
            </div>
            <div class="bidding-item">
                <img src="images/item3.jpg" alt="Item 3">
                <p>Item 3</p>
            </div>
            <div class="bidding-item">
                <img src="images/item4.jpg" alt="Item 4">
                <p>Item 4</p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer id="footer">
        <p>&copy; 2025 BidSecond. All rights reserved.</p>
    </footer>

    <script src="scripts/main.js"></script>
</body>
</html>