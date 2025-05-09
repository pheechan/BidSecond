<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['roles'] !== 'admin') {
    header("Location: index.php"); // Redirect to home if not admin
    exit();
}

$user = $_SESSION['user']; // Access user information

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

// Fetch data for each section
$users = $pdo->query("SELECT user_id, username, email, email_verified, balance, roles, created_at FROM USERS")->fetchAll();
$auctions = $pdo->query("SELECT auction_id, title, category, start_price, bid_amount, status, end_time, created_at FROM AUCTIONS")->fetchAll();
$topups = $pdo->query("SELECT * FROM TOPUP")->fetchAll();
$withdraws = $pdo->query("SELECT * FROM WITHDRAW")->fetchAll();
$pending_transactions = $pdo->query("SELECT * FROM PENDING_TRANSACTIONS")->fetchAll();
$top_bids = $pdo->query("SELECT auction_id, MAX(bid_amount) AS highest_bid FROM BIDS GROUP BY auction_id ORDER BY highest_bid DESC LIMIT 10")->fetchAll();
$buy_history = $pdo->query("SELECT user_id, bid_amount, end_time FROM BUY_HISTORY")->fetchAll();
$sell_history = $pdo->query("SELECT user_id, bid_amount, end_time FROM SELL_HISTORY")->fetchAll();
$server_info = [
    'os' => php_uname(),
    'php_version' => phpversion(),
    'mysql_version' => $pdo->query("SELECT VERSION()")->fetchColumn(),
    'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
    'server_time' => date('Y-m-d H:i:s'),
    'timezone' => date_default_timezone_get(),
];

// Fetch data for Report and Analytics

// Top 10 Highest Bids
$top_highest_bids = $pdo->query("
    SELECT auction_id, MAX(bid_amount) AS highest_bid 
    FROM BIDS 
    GROUP BY auction_id 
    ORDER BY highest_bid DESC 
    LIMIT 10
")->fetchAll();

// Top 10 Lowest Bids
$top_lowest_bids = $pdo->query("
    SELECT auction_id, MIN(bid_amount) AS lowest_bid 
    FROM BIDS 
    GROUP BY auction_id 
    ORDER BY lowest_bid ASC 
    LIMIT 10
")->fetchAll();

// Top 10 Most Bids
$top_most_bids = $pdo->query("
    SELECT auction_id, COUNT(bid_id) AS bid_count 
    FROM BIDS 
    GROUP BY auction_id 
    ORDER BY bid_count DESC 
    LIMIT 10
")->fetchAll();

// Ranking of Categories by Highest Bid
$category_ranking = $pdo->query("
    SELECT 
        category, 
        MAX(bid_amount) AS highest_bid 
    FROM AUCTIONS 
    GROUP BY category 
    ORDER BY highest_bid DESC
")->fetchAll();

// Average Starting Price per Category
$average_start_price = $pdo->query("
    SELECT 
        category, 
        AVG(start_price) AS avg_start_price 
    FROM AUCTIONS 
    GROUP BY category
")->fetchAll();

// Average Bid Price per Category
$average_bid_price = $pdo->query("
    SELECT 
        a.category, 
        AVG(b.bid_amount) AS avg_bid_price 
    FROM BIDS b
    INNER JOIN AUCTIONS a ON b.auction_id = a.auction_id
    GROUP BY a.category
")->fetchAll();

// Total Revenue Generated per Category
$total_revenue_per_category = $pdo->query("
    SELECT 
        category, 
        SUM(bid_amount) AS total_revenue 
    FROM AUCTIONS 
    GROUP BY category
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BidSecond</title>
    <link rel="stylesheet" href="styles/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        html {
            scroll-behavior: smooth;
        }
        .welcome-screen {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #57a05a;
            color: white;
            text-align: center;
        }
        .connect-button {
            padding: 10px 20px;
            font-size: 18px;
            background-color: white;
            color: #57a05a;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .connect-button:hover {
            background-color: #ffffff;
        }
        .summary-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .summary-card {
            flex: 1;
            min-width: 200px;
            padding: 20px;
            background-color: #f4f4f4;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .styled-table {
            width: 100%;
            border-collapse: collapse;
        }
        .styled-table th, .styled-table td {
            padding: 10px;
            text-align: left;
        }
        .styled-table th {
            background-color: #f4f4f4;
        }
        .pagination a {
            margin: 0 5px;
            text-decoration: none;
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .pagination a.active {
            background-color: #57a05a;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
    <script>
        function showTab(tabId) {
            // Hide all tab content
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.style.display = 'none');

            // Show the selected tab
            const selectedTab = document.getElementById(tabId);
            if (selectedTab) {
                selectedTab.style.display = 'block';
            }

            // Update active button in the sidebar
            const buttons = document.querySelectorAll('.sidebar-menu button');
            buttons.forEach(button => button.classList.remove('active'));
            const activeButton = document.querySelector(`.sidebar-menu button[onclick="showTab('${tabId}')"]`);
            if (activeButton) {
                activeButton.classList.add('active');
            }
        }

        // Automatically show the correct tab based on the URL parameter
        window.onload = function () {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab') || 'summary'; // Default to 'summary' if no tab is specified
            showTab(tab);
        };

        function updateRole(select) {
            const userId = select.dataset.userId;
            const role = select.value;
            fetch('api_user_action.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=update_role&user_id=${userId}&role=${role}`
            }).then(r=>r.text()).then(alert);
        }

        function deleteUser(userId) {
            if(confirm('Delete this user?')) {
                fetch('api_user_action.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=delete_user&user_id=${userId}`
                }).then(()=>location.reload());
            }
        }

        function resetPassword(userId) {
            if(confirm('Reset password for this user?')) {
                fetch('api_user_action.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=reset_password&user_id=${userId}`
                }).then(r=>r.text()).then(alert);
            }
        }

        function deleteAuction(auctionId) {
            if(confirm('Delete this auction?')) {
                fetch('api_auction_action.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=delete_auction&auction_id=${auctionId}`
                }).then(()=>location.reload());
            }
        }

        function removeImage(auctionId) {
            if(confirm('Remove image for this auction?')) {
                fetch('api_auction_action.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=remove_image&auction_id=${auctionId}`
                }).then(()=>location.reload());
            }
        }

        function editAuction(auctionId) {
            window.open('edit_auction.php?auction_id=' + auctionId, '_blank', 'width=600,height=600');
        }

        // Transaction tab switch
        function showTransactionTable(type) {
            document.querySelectorAll('.transaction-table').forEach(e=>e.style.display='none');
            document.getElementById(type+'-table').style.display='block';
        }
    </script>
    <script>
        // Pass PHP arrays to JS
        window.analyticsData = {
            highestBids: {
                labels: <?php echo json_encode(array_column($top_highest_bids, 'auction_id')); ?>,
                data: <?php echo json_encode(array_map('floatval', array_column($top_highest_bids, 'highest_bid'))); ?>
            },
            lowestBids: {
                labels: <?php echo json_encode(array_column($top_lowest_bids, 'auction_id')); ?>,
                data: <?php echo json_encode(array_map('floatval', array_column($top_lowest_bids, 'lowest_bid'))); ?>
            },
            mostBids: {
                labels: <?php echo json_encode(array_column($top_most_bids, 'auction_id')); ?>,
                data: <?php echo json_encode(array_map('intval', array_column($top_most_bids, 'bid_count'))); ?>
            },
            categoryRanking: {
                labels: <?php echo json_encode(array_column($category_ranking, 'category')); ?>,
                data: <?php echo json_encode(array_map('floatval', array_column($category_ranking, 'highest_bid'))); ?>
            },
            averageStartPrice: {
                labels: <?php echo json_encode(array_column($average_start_price, 'category')); ?>,
                data: <?php echo json_encode(array_map('floatval', array_column($average_start_price, 'avg_start_price'))); ?>
            },
            averageBidPrice: {
                labels: <?php echo json_encode(array_column($average_bid_price, 'category')); ?>,
                data: <?php echo json_encode(array_map('floatval', array_column($average_bid_price, 'avg_bid_price'))); ?>
            },
            totalRevenue: {
                labels: <?php echo json_encode(array_column($total_revenue_per_category, 'category')); ?>,
                data: <?php echo json_encode(array_map('floatval', array_column($total_revenue_per_category, 'total_revenue'))); ?>
            }
        };
    </script>
</head>
<body>
    <!-- Welcome Screen -->
    <?php if (!isset($_GET['connect'])): ?>
        <div class="welcome-screen">
            <h1>Welcome to BidSecond Dashboard System, <?php echo htmlspecialchars($user['name']); ?>!</h1>
            <form method="GET">
                <button type="submit" name="connect" value="true" class="connect-button">Connect</button>
            </form>
        </div>
    <?php else: ?>
        <div class="dashboard-container">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="sidebar-header">
                    <p><?php echo htmlspecialchars($user['name']); ?></p>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <nav class="sidebar-menu">
                    <button onclick="showTab('summary'); window.history.pushState({}, '', '?tab=summary');">Summary</button>
                    <button onclick="showTab('user-management'); window.history.pushState({}, '', '?tab=user-management');">User Management</button>
                    <button onclick="showTab('auction-management'); window.history.pushState({}, '', '?tab=auction-management');">Auction Management</button>
                    <button onclick="showTab('transactions'); window.history.pushState({}, '', '?tab=transactions');">Transactions</button>
                    <button onclick="showTab('report-analytics'); window.history.pushState({}, '', '?tab=report-analytics');">Report and Analytics</button>
                    <button onclick="showTab('server-info'); window.history.pushState({}, '', '?tab=server-info');">Server Information</button>
                    <a href="index.php" class="back-to-home-button">Back to Home</a>
                    <a href="logout.php" class="logout-button">Logout</a>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="main-content">
                <!-- Summary Section -->
                <section id="summary" class="tab-content active">
                    <h2>Summary</h2>
                    <div class="summary-container">
                        <div class="summary-card">
                            <h3>Total Users</h3>
                            <p><?php echo count($users); ?></p>
                        </div>
                        <div class="summary-card">
                            <h3>Total Auctions</h3>
                            <p><?php echo count($auctions); ?></p>
                        </div>
                        <div class="summary-card">
                            <h3>Total Bids</h3>
                            <p><?php echo count($top_bids); ?></p>
                        </div>
                        <div class="summary-card">
                            <h3>Top-Ups</h3>
                            <p>฿<?php echo number_format(array_sum(array_column($topups, 'topup_amount')), 2); ?></p>
                        </div>
                        <div class="summary-card">
                            <h3>Withdrawals</h3>
                            <p>฿<?php echo number_format(array_sum(array_column($withdraws, 'withdraw_amount')), 2); ?></p>
                        </div>
                    </div>
                </section>

                <!-- User Management Section -->
                <section id="user-management" class="tab-content">
                    <h2>User Management</h2>
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Email Verified</th>
                                <th>Balance</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $users_per_page = 10;
                            $total_users = count($users);
                            $total_pages = ceil($total_users / $users_per_page);
                            $current_page = isset($_GET['user_page']) ? (int)$_GET['user_page'] : 1;
                            $start_index = ($current_page - 1) * $users_per_page;
                            $paginated_users = array_slice($users, $start_index, $users_per_page);

                            foreach ($paginated_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo $user['email_verified'] ? 'Yes' : 'No'; ?></td>
                                    <td>฿<?php echo number_format($user['balance'], 2); ?></td>
                                    <td>
                                        <select class="role-dropdown" data-user-id="<?php echo $user['user_id']; ?>" onchange="updateRole(this)">
                                            <option value="admin" <?php echo $user['roles'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            <option value="user" <?php echo $user['roles'] === 'user' ? 'selected' : ''; ?>>User</option>
                                        </select>
                                        <button onclick="deleteUser(<?php echo $user['user_id']; ?>)">Delete</button>
                                        <button onclick="resetPassword(<?php echo $user['user_id']; ?>)">Reset Password</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?connect=true&tab=user-management&user_page=<?php echo $i; ?>" class="<?php echo $i === $current_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                </section>

                <!-- Auction Management Section -->
                <section id="auction-management" class="tab-content">
                    <h2>Auction Management</h2>
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Start Price</th>
                                <th>Current Bid</th>
                                <th>Status</th>
                                <th>End Time</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $auctions_per_page = 10;
                            $total_auctions = count($auctions);
                            $total_pages = ceil($total_auctions / $auctions_per_page);
                            $current_page = isset($_GET['auction_page']) ? (int)$_GET['auction_page'] : 1;
                            $start_index = ($current_page - 1) * $auctions_per_page;
                            $paginated_auctions = array_slice($auctions, $start_index, $auctions_per_page);

                            foreach ($paginated_auctions as $auction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($auction['title']); ?></td>
                                    <td><?php echo htmlspecialchars($auction['category']); ?></td>
                                    <td>฿<?php echo number_format($auction['start_price'], 2); ?></td>
                                    <td>฿<?php echo number_format($auction['bid_amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($auction['status']); ?></td>
                                    <td><?php echo $auction['end_time']; ?></td>
                                    <td><?php echo $auction['created_at']; ?></td>
                                    <td>
                                        <button class="delete-auction" data-auction-id="<?php echo $auction['auction_id']; ?>" onclick="deleteAuction(<?php echo $auction['auction_id']; ?>)">Delete</button>
                                        <button class="edit-auction" data-auction-id="<?php echo $auction['auction_id']; ?>" onclick="editAuction(<?php echo $auction['auction_id']; ?>)">Edit</button>
                                        <button class="remove-image" data-auction-id="<?php echo $auction['auction_id']; ?>" onclick="removeImage(<?php echo $auction['auction_id']; ?>)">Remove Image</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?connect=true&tab=auction-management&auction_page=<?php echo $i; ?>" class="<?php echo $i === $current_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                </section>

                <!-- Transactions Section -->
                <section id="transactions" class="tab-content">
                    <h2>Transactions</h2>
                    <label for="transaction-type">Select Transaction Type:</label>
                    <select id="transaction-type" onchange="showTransactionTable(this.value)">
                        <option value="topups">Top-Ups</option>
                        <option value="withdraws">Withdraw</option>
                        <option value="spend">Spend</option>
                        <option value="income">Income</option>
                    </select>

                    <div id="topups-table" class="transaction-table">
                        <!-- Top-Ups Table Here -->
                    </div>
                    <div id="withdraws-table" class="transaction-table" style="display:none">
                        <!-- Withdraw Table Here -->
                    </div>
                    <div id="spend-table" class="transaction-table" style="display:none">
                        <h3>Spend (BUY_HISTORY)</h3>
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Bid Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($buy_history as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                                    <td>฿<?php echo number_format($row['bid_amount'], 2); ?></td>
                                    <td><?php echo $row['end_time']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="income-table" class="transaction-table" style="display:none">
                        <h3>Income (SELL_HISTORY)</h3>
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Bid Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sell_history as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                                    <td>฿<?php echo number_format($row['bid_amount'], 2); ?></td>
                                    <td><?php echo $row['end_time']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Top-Up Table -->
                    <h3>Top-Ups</h3>
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Top-Up Amount</th>
                                <th>Status</th>
                                <th>Top-Up Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $topups_per_page = 10;
                            $total_topups = count($topups);
                            $total_pages = ceil($total_topups / $topups_per_page);
                            $current_page = isset($_GET['topup_page']) ? (int)$_GET['topup_page'] : 1;
                            $start_index = ($current_page - 1) * $topups_per_page;
                            $paginated_topups = array_slice($topups, $start_index, $topups_per_page);

                            foreach ($paginated_topups as $topup): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($topup['user_id']); ?></td>
                                    <td>฿<?php echo number_format($topup['topup_amount'], 2); ?></td>
                                    <td><?php echo $topup['status'] ? 'Approved' : 'Pending'; ?></td>
                                    <td><?php echo $topup['topup_date']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination for Top-Ups -->
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?connect=true&tab=transactions&topup_page=<?php echo $i; ?>" class="<?php echo $i === $current_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                </section>

                <!-- Report and Analytics Section -->
                <section id="report-analytics" class="tab-content">
                    <h2>Report and Analytics</h2>
                    <label for="analytics-dropdown">Select a Report:</label>
                    <select id="analytics-dropdown" onchange="showGraph(this.value)">
                        <option value="">-- Select a Report --</option>
                        <option value="highest-bids">Top 10 Highest Bids</option>
                        <option value="lowest-bids">Top 10 Lowest Bids</option>
                        <option value="most-bids">Top 10 Most Bids</option>
                        <option value="category-ranking">Ranking of Categories by Highest Bid</option>
                        <option value="average-start-price">Average Starting Price per Category</option>
                        <option value="average-bid-price">Average Bid Price per Category</option>
                        <option value="total-revenue">Total Revenue Generated per Category</option>
                    </select>

                    <!-- Graph Container -->
                    <div id="graph-container" style="width: 100%; max-width: 800px; margin: 20px auto;">
                        <canvas id="analytics-graph"></canvas>
                    </div>
                </section>

                <!-- Server Information Section -->
                <section id="server-info" class="tab-content">
                    <h2>Server Information</h2>
                    <ul>
                        <li>OS: <?php echo $server_info['os']; ?></li>
                        <li>PHP Version: <?php echo $server_info['php_version']; ?></li>
                        <li>MySQL Version: <?php echo $server_info['mysql_version']; ?></li>
                        <li>Server IP: <?php echo $server_info['server_ip']; ?></li>
                        <li>Server Time: <?php echo $server_info['server_time']; ?></li>
                        <li>Timezone: <?php echo $server_info['timezone']; ?></li>
                    </ul>
                </section>
            </main>
        </div>
    <?php endif; ?>
    <script src="scripts/analytics-graph.js"></script>
</body>
</html>