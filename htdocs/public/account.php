<?php

session_start();
include_once 'init.php';
if (!isset($_SESSION['user'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

$config = include(__DIR__ . '/../private/config.php');
$user = $_SESSION['user']; // Access user information
$message = ""; // Message to show after updating

// Connect to the database
$link = mysqli_connect($config['db_host'], $config['db_user'], $config['db_password'], $config['db_name']);
if (!$link) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch user details from the database
$stmt = $link->prepare("SELECT user_id, username, email, address, balance, created_at, roles FROM USERS WHERE email = ?");
if (!$stmt) {
    die("Failed to prepare statement: " . $link->error);
}
$stmt->bind_param("s", $user['email']);
$stmt->execute();
$stmt->bind_result($user_id, $username, $email, $address, $balance, $created_at, $roles);
$stmt->fetch();
$stmt->close();

// Pagination for Buy History
$limit = 10; // Number of records per page
$offset = isset($_GET['page']) ? ($_GET['page'] - 1) * $limit : 0;

$stmt = $link->prepare("
    SELECT bh.auction_id, a.title, a.category, bh.bid_amount, bh.end_time
    FROM BUY_HISTORY bh
    LEFT JOIN AUCTIONS a ON bh.auction_id = a.auction_id
    WHERE bh.user_id = ?
    ORDER BY bh.end_time DESC
    LIMIT ? OFFSET ?
");
if (!$stmt) {
    die("Failed to prepare statement: " . $link->error);
}
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$buy_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch Sell History
$stmt = $link->prepare("
    SELECT sh.auction_id, a.title, a.category, sh.bid_amount, sh.end_time
    FROM SELL_HISTORY sh
    LEFT JOIN AUCTIONS a ON sh.auction_id = a.auction_id
    WHERE sh.user_id = ?
    ORDER BY sh.end_time DESC
");
if (!$stmt) {
    die("Failed to prepare statement: " . $link->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$sell_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch My Listings
$stmt = $link->prepare("
    SELECT a.auction_id, a.title, a.start_price, a.bid_amount, a.end_time, a.image, a.created_at, a.status
    FROM AUCTIONS a
    INNER JOIN SELLER s ON a.seller_id = s.seller_id
    WHERE s.user_id = ?
    ORDER BY a.created_at DESC
");
if (!$stmt) {
    die("Failed to prepare statement: " . $link->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch My Bids
$stmt = $link->prepare("
    SELECT 
        a.auction_id, 
        a.title, 
        a.start_price, 
        a.bid_amount AS highest_bid, 
        a.end_time, 
        a.image, 
        MAX(b.bid_amount) AS user_bid, 
        CASE 
            WHEN a.bid_amount = MAX(b.bid_amount) THEN 'highest'
            ELSE 'surpassed'
        END AS bid_status
    FROM BIDS b
    INNER JOIN AUCTIONS a ON b.auction_id = a.auction_id
    INNER JOIN BUYER bu ON b.buyer_id = bu.buyer_id
    WHERE bu.user_id = ?
    GROUP BY a.auction_id
    ORDER BY a.end_time DESC
");
if (!$stmt) {
    die("Failed to prepare statement: " . $link->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_bids = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle form submission to update user information
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $new_username = htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8');
    $new_address = htmlspecialchars($_POST['address'], ENT_QUOTES, 'UTF-8');
    $new_roles = $_POST['roles'];

    $stmt = $link->prepare("UPDATE USERS SET username = ?, address = ?, roles = ? WHERE user_id = ?");
    if (!$stmt) {
        die("Failed to prepare statement: " . $link->error);
    }
    $stmt->bind_param("sssi", $new_username, $new_address, $new_roles, $user_id);

    if ($stmt->execute()) {
        $message = "Account information updated successfully!";
        // Update session data
        $_SESSION['user']['name'] = $new_username;
        $_SESSION['user']['roles'] = $new_roles;

        // Redirect to the same page to refresh the data
        header("Location: account.php");
        exit();
    } else {
        $message = "Failed to update account information.";
    }
    $stmt->close();
}

// Close the database connection
$link->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($username); ?>'s Account - BidSecond</title>
    <link rel="stylesheet" href="styles/auth.css"> <!-- Use shared CSS -->
    <link rel="stylesheet" href="styles/account.css"> <!-- Link to the new account-specific CSS -->
</head>
<body>
    <div class="content-wrapper">
        <div class="content-box">
            <div class="header-container">
                <h1>My Account</h1>
                <a href="index.php" class="back-to-home-button">Back to Home</a>
            </div>
            <div class="tabs">
                <div class="tab active" onclick="showTab('account-info')">Account Information</div>
                <div class="tab" onclick="showTab('history')">History</div>
                <div class="tab" onclick="showTab('my-listings')">My Listings</div>
                <div class="tab" onclick="showTab('my-bids')">My Bids</div>
            </div>

            <!-- Account Information Tab -->
            <div id="account-info" class="tab-content active">
                <?php if (!empty($message)) { ?>
                    <p class="message"><?php echo $message; ?></p>
                <?php } ?>
                <form action="account.php" method="POST">
                    <div class="form-group">
                        <label for="user_id">User ID</label>
                        <input type="text" id="user_id" value="<?php echo htmlspecialchars($user_id); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($address); ?>">
                    </div>
                    <div class="form-group">
                        <label for="balance">Balance</label>
                        <input type="text" id="balance" value="<?php echo htmlspecialchars($balance); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="created_at">Created At</label>
                        <input type="text" id="created_at" value="<?php echo htmlspecialchars($created_at); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="roles">Role</label>
                        <select id="roles" name="roles" required>
                            <option value="user" <?php echo $roles === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo $roles === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <button type="submit" name="update_account" class="update-button">Update</button>
                </form>
            </div>

            <!-- History Tab -->
            <div id="history" class="tab-content">
                <h2>Buy History</h2>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Auction ID</th>
                            <th>Auction Title</th>
                            <th>Category</th>
                            <th>Bid Amount</th>
                            <th>End Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($buy_history)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No buy history found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($buy_history as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['auction_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                                    <td><?php echo htmlspecialchars($row['bid_amount']); ?></td>
                                    <td><?php echo htmlspecialchars($row['end_time']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php
                $total_pages = ceil(count($buy_history) / $limit);
                $current_page = isset($_GET['page']) ? $_GET['page'] : 1;
                ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1; ?>" class="pagination-button">Previous</a>
                    <?php endif; ?>
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?>" class="pagination-button">Next</a>
                    <?php endif; ?>
                </div>

                <h2>Sell History</h2>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Auction ID</th>
                            <th>Auction Title</th>
                            <th>Category</th>
                            <th>Bid Amount</th>
                            <th>End Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sell_history)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No sell history found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sell_history as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['auction_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                                    <td><?php echo htmlspecialchars($row['bid_amount']); ?></td>
                                    <td><?php echo htmlspecialchars($row['end_time']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- My Listings Tab -->
            <div id="my-listings" class="tab-content">
                <h2>My Listings</h2>
                <div class="listings-container">
                    <?php if (empty($my_listings)): ?>
                        <p class="no-listings-message">You have not created any listings yet.</p>
                    <?php else: ?>
                        <?php foreach ($my_listings as $listing): ?>
                            <div class="listing-card">
                                <?php if (!empty($listing['image'])): ?>
                                    <img src="data:image/jpeg;base64,<?php echo base64_encode($listing['image']); ?>" alt="<?php echo htmlspecialchars($listing['title']); ?>" class="listing-image">
                                <?php else: ?>
                                    <img src="images/no-image.jpg" alt="No Image Available" class="listing-image">
                                <?php endif; ?>
                                <div class="listing-details">
                                    <h3><?php echo htmlspecialchars($listing['title']); ?></h3>
                                    <p><strong>Start Price:</strong> ฿<?php echo number_format($listing['start_price'], 2); ?></p>
                                    <p><strong>Current Bid:</strong> ฿<?php echo number_format($listing['bid_amount'], 2); ?></p>
                                    <p><strong>End Time:</strong> <?php echo htmlspecialchars($listing['end_time']); ?></p>
                                    <p><strong>Created At:</strong> <?php echo htmlspecialchars($listing['created_at']); ?></p>
                                    <p><strong>Status:</strong> <?php echo htmlspecialchars($listing['status']); ?></p>
                                    <a href="Product.php?auction_id=<?php echo $listing['auction_id']; ?>" class="view-details-button">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- My Bids Tab -->
            <div id="my-bids" class="tab-content">
                <h2>My Bids</h2>
                <div class="bids-container">
                    <?php if (empty($my_bids)): ?>
                        <p class="no-bids-message">You have not placed any bids yet.</p>
                    <?php else: ?>
                        <?php foreach ($my_bids as $bid): ?>
                            <div class="bid-card <?php echo $bid['bid_status'] === 'highest' ? 'highest-bid' : 'surpassed-bid'; ?>">
                                <?php if (!empty($bid['image'])): ?>
                                    <img src="data:image/jpeg;base64,<?php echo base64_encode($bid['image']); ?>" alt="<?php echo htmlspecialchars($bid['title']); ?>" class="bid-image">
                                <?php else: ?>
                                    <img src="images/no-image.jpg" alt="No Image Available" class="bid-image">
                                <?php endif; ?>
                                <div class="bid-details">
                                    <h3><?php echo htmlspecialchars($bid['title']); ?></h3>
                                    <p><strong>Start Price:</strong> ฿<?php echo number_format($bid['start_price'], 2); ?></p>
                                    <p><strong>Your Bid:</strong> ฿<?php echo number_format($bid['user_bid'], 2); ?></p>
                                    <p><strong>Highest Bid:</strong> ฿<?php echo number_format($bid['highest_bid'], 2); ?></p>
                                    <p><strong>End Time:</strong> <?php echo htmlspecialchars($bid['end_time']); ?></p>
                                    <?php if ($bid['bid_status'] === 'highest'): ?>
                                        <p class="bid-status">You are the highest bidder!</p>
                                    <?php else: ?>
                                        <p class="bid-status">Someone has surpassed your bid.</p>
                                    <?php endif; ?>
                                    <a href="Product.php?auction_id=<?php echo $bid['auction_id']; ?>" class="view-details-button">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Loading Spinner -->
            <div id="loading-spinner" style="display: none; text-align: center;">
                <p>Loading...</p>
            </div>

            <!-- Logout Button -->
            <div class="logout-container">
                <form action="logout.php" method="POST" onsubmit="return confirm('Are you sure you want to log out?');">
                    <button type="submit" class="logout-button">Logout</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabId) {
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            const contentWrapper = document.querySelector('.content-wrapper');
            const loadingSpinner = document.getElementById('loading-spinner');

            // Show loading spinner
            loadingSpinner.style.display = 'block';

            // Remove active class from all tabs and tab contents
            tabs.forEach(tab => tab.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Add active class to the selected tab and its content
            setTimeout(() => {
                document.querySelector(`#${tabId}`).classList.add('active');
                document.querySelector(`.tab[onclick="showTab('${tabId}')"]`).classList.add('active');

                // Adjust margin-top dynamically based on the active tab
                if (tabId === 'account-info' || tabId === 'history' || tabId === 'my-listings' || tabId === 'my-bids') {
                    contentWrapper.style.marginTop = '50px'; // Smaller margin for Account Info, History, My Listings, and My Bids tabs
                }

                // Hide loading spinner
                loadingSpinner.style.display = 'none';
            }, 300); // Simulate a delay for better UX
        }

        // Set default tab on page load
        document.addEventListener('DOMContentLoaded', () => {
            showTab('account-info'); // Default to Account Information tab
        });
    </script>
</body>
</html>