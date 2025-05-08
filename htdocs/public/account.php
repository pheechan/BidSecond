<?php
session_start();
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
$stmt = $link->prepare("SELECT user_id, username, email, address, balance, created_at, role FROM USERS WHERE email = ?");
if (!$stmt) {
    die("Failed to prepare statement: " . $link->error);
}
$stmt->bind_param("s", $user['email']);
$stmt->execute();
$stmt->bind_result($user_id, $username, $email, $address, $balance, $created_at, $role);
$stmt->fetch();
$stmt->close();

// Pagination for Buy History
$limit = 10; // Number of records per page
$offset = isset($_GET['page']) ? ($_GET['page'] - 1) * $limit : 0;

$stmt = $link->prepare("
    SELECT bh.*, a.title, a.category
    FROM BUY_HISTORY bh
    LEFT JOIN AUCTIONS a ON bh.auction_id = a.auction_id
    WHERE bh.user_id = ?
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
    SELECT sh.*, a.title, a.category
    FROM SELL_HISTORY sh
    LEFT JOIN AUCTIONS a ON sh.auction_id = a.auction_id
    WHERE sh.user_id = ?
");
if (!$stmt) {
    die("Failed to prepare statement: " . $link->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$sell_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle form submission to update user information
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $new_username = htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8');
    $new_address = htmlspecialchars($_POST['address'], ENT_QUOTES, 'UTF-8');
    $new_role = $_POST['role'];

    $stmt = $link->prepare("UPDATE USERS SET username = ?, address = ?, role = ? WHERE user_id = ?");
    if (!$stmt) {
        die("Failed to prepare statement: " . $link->error);
    }
    $stmt->bind_param("sssi", $new_username, $new_address, $new_role, $user_id);

    if ($stmt->execute()) {
        $message = "Account information updated successfully! Please refresh the page to see changes.";
        // Update session data
        $_SESSION['user']['name'] = $new_username;
        $_SESSION['user']['role'] = $new_role;
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
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
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
                if (tabId === 'history') {
                    contentWrapper.style.marginTop = '50px'; // Smaller margin for the History tab
                } else {
                    contentWrapper.style.marginTop = '250px'; // Larger margin for the Account Information tab
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