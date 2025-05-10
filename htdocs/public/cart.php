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
        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 20px;
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 20px;
            background-color: #f9f9f9;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .cart-item-content {
            display: flex;
            gap: 20px;
            width: 100%;
        }
        .cart-item-details {
            flex: 3;
        }
        .cart-item-image {
            flex: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .cart-item-image img {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
        }
        .cart-item-details p {
            margin: 5px 0;
            font-size: 16px;
        }
        .cart-item-details button {
            margin-top: 10px;
            padding: 10px 20px;
            background-color: #57a05a;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .cart-item-details button:hover {
            background-color: #459048;
        }
        .no-items-message {
            text-align: center;
            color: #888;
            margin-top: 40px;
            font-size: 1.2em;
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
</body>
</html>