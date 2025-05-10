<?php

session_start();
include_once 'init.php';

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

// --- PAYMENT PROCESSING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auction_id'])) {
    $auction_id = $_POST['auction_id'];
    $current_time = date('Y-m-d H:i:s');

    // 1. Update PENDING_TRANSACTIONS status to 'paid'
    $pdo->prepare("
        UPDATE PENDING_TRANSACTIONS
        SET payment_status = 'paid'
        WHERE auction_id = ? AND payment_status = 'unpaid'
    ")->execute([$auction_id]);

    // 2. Fetch transaction details, including SELLER.user_id and BUYER.user_id
    $stmt = $pdo->prepare("
        SELECT pt.*, 
               s.user_id AS seller_user_id, 
               b.user_id AS buyer_user_id
        FROM PENDING_TRANSACTIONS pt
        JOIN SELLER s ON pt.seller_id = s.seller_id
        JOIN BUYER b ON pt.buyer_id = b.buyer_id
        WHERE pt.auction_id = ?
    ");
    $stmt->execute([$auction_id]);
    $txn = $stmt->fetch();

    if ($txn) {
        // 3. Insert into SELL_HISTORY
        $pdo->prepare("
            INSERT INTO SELL_HISTORY (user_id, buyer_id, auction_id, bid_amount, end_time, transaction_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            $txn['seller_user_id'],
            $txn['buyer_id'],
            $txn['auction_id'],
            $txn['bid_amount'],
            $txn['end_time'],
            $current_time
        ]);

        // 4. Insert into BUY_HISTORY
        $pdo->prepare("
            INSERT INTO BUY_HISTORY (user_id, seller_id, auction_id, bid_amount, end_time, transaction_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            $txn['buyer_user_id'],
            $txn['seller_id'],
            $txn['auction_id'],
            $txn['bid_amount'],
            $txn['end_time'],
            $current_time
        ]);

        // 5. Update balances
        // Add to seller
        $pdo->prepare("UPDATE USERS SET balance = balance + ? WHERE user_id = ?")
            ->execute([$txn['bid_amount'], $txn['seller_user_id']]);
        // Subtract from buyer
        $pdo->prepare("UPDATE USERS SET balance = balance - ? WHERE user_id = ?")
            ->execute([$txn['bid_amount'], $txn['buyer_user_id']]);

        // Optional: Success message
        header("Location: cart.php?success=1");
        exit();
    } else {
        header("Location: cart.php?error=notfound");
        exit();
    }
}

// Fetch pending items for the logged-in user
$query = "
    SELECT 
        pt.auction_id,
        a.title AS auction_title,
        pt.seller_id,
        pt.bid_amount,
        pt.buyer_id,
        u.address,
        pt.end_time,
        pt.payment_status,
        us.username AS seller_name,
        a.image
    FROM PENDING_TRANSACTIONS pt
    INNER JOIN AUCTIONS a ON pt.auction_id = a.auction_id
    INNER JOIN SELLER s ON pt.seller_id = s.seller_id
    INNER JOIN USERS us ON s.user_id = us.user_id
    INNER JOIN BUYER b ON pt.buyer_id = b.buyer_id -- Join with BUYER table
    INNER JOIN USERS u ON b.user_id = u.user_id -- Get user_id from BUYER table
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
            <div class="search-bar">
                <!-- Search Bar -->
                <form id="searchForm" class="search-bar" action="search.php" method="get">
                    <input type="text" id="searchInput" name="q" placeholder="Search for items..." required>
                    <button type="submit">Search</button>
                    <button type="button" id="filterBtn" style="background: #fff; border: 1px solid #ccc; border-left: none; border-radius: 0 5px 5px 0; padding: 0 12px; cursor:pointer; display:flex; align-items:center;">
                        <!-- SVG filter icon -->
                        <svg width="20" height="20" fill="#57a05a" viewBox="0 0 24 24"><path d="M3 5h18v2H3zm4 7h10v2H7zm2 7h6v2H9z"/></svg>
                    </button>
                    <div id="filterDropdown" style="display:none; position:absolute; top:110%; right:0; background:#fff; border:1px solid #ccc; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.08); padding:15px; z-index:10; min-width:200px;">
                        <div style="font-weight:bold; margin-bottom:8px;">Category</div>
                        <?php
                        // List of categories (should match your site)
                        $categories = [
                            "Electronics", "Fashion", "Home and Garden", "Toys and Games", "Automotive",
                            "Sports and Outdoors", "Books and Media", "Health and Beauty", "Jewelry and Watches",
                            "Music and Instruments", "Collectibles and Antiques", "Art and Craft"
                        ];
                        $selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';
                        foreach ($categories as $cat): ?>
                            <div>
                                <label>
                                    <input type="radio" name="category" value="<?php echo htmlspecialchars($cat); ?>" <?php if ($selectedCategory === $cat) echo 'checked'; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <div style="margin-top:8px;">
                            <button type="button" id="closeFilter" style="background:#57a05a; color:#fff; border:none; border-radius:4px; padding:4px 12px; cursor:pointer;">OK</button>
                        </div>
                    </div>
                </form>
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
                                <form action="cart.php" method="POST">
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
    <script src="scripts/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var filterBtn = document.getElementById('filterBtn');
        var dropdown = document.getElementById('filterDropdown');
        var closeFilter = document.getElementById('closeFilter');

        if (filterBtn && dropdown) {
            filterBtn.onclick = function(e) {
                e.preventDefault();
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            };
        }
        if (closeFilter && dropdown) {
            closeFilter.onclick = function() {
                dropdown.style.display = 'none';
            };
        }
        document.addEventListener('click', function(e) {
            if (
                filterBtn && dropdown &&
                !filterBtn.contains(e.target) &&
                !dropdown.contains(e.target)
            ) {
                dropdown.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>