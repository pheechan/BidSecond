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
$server_info = [
    'os' => php_uname(),
    'php_version' => phpversion(),
    'mysql_version' => $pdo->query("SELECT VERSION()")->fetchColumn(),
    'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
    'server_time' => date('Y-m-d H:i:s'),
    'timezone' => date_default_timezone_get(),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BidSecond</title>
    <link rel="stylesheet" href="styles/dashboard.css">
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
    </style>
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
                    <a href="#summary">Summary</a>
                    <a href="#user-management">User Management</a>
                    <a href="#auction-management">Auction Management</a>
                    <a href="#transactions">Transactions</a>
                    <a href="#report-analytics">Report and Analytics</a>
                    <a href="#server-info">Server Information</a>
                    <a href="index.php" class="back-to-home-button">Back to Home</a>
                    <a href="logout.php" class="logout-button">Logout</a>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="main-content">
                <!-- Summary Section -->
                <section id="summary">
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
                <section id="user-management">
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
                                        <select class="role-dropdown" data-user-id="<?php echo $user['user_id']; ?>">
                                            <option value="admin" <?php echo $user['roles'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            <option value="user" <?php echo $user['roles'] === 'user' ? 'selected' : ''; ?>>User</option>
                                        </select>
                                    </td>
                                    <td><?php echo $user['created_at']; ?></td>
                                    <td>
                                        <button class="delete-user" data-user-id="<?php echo $user['user_id']; ?>">Delete</button>
                                        <button class="reset-password" data-user-id="<?php echo $user['user_id']; ?>">Reset Password</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?connect=true&user_page=<?php echo $i; ?>" class="<?php echo $i === $current_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                </section>

                <!-- Auction Management Section -->
                <section id="auction-management">
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
                                        <button class="delete-auction" data-auction-id="<?php echo $auction['auction_id']; ?>">Delete</button>
                                        <button class="edit-auction" data-auction-id="<?php echo $auction['auction_id']; ?>">Edit</button>
                                        <button class="remove-image" data-auction-id="<?php echo $auction['auction_id']; ?>">Remove Image</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?connect=true&auction_page=<?php echo $i; ?>" class="<?php echo $i === $current_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                </section>

                <!-- Transactions Section -->
                <section id="transactions">
                    <h2>Transactions</h2>
                    <h3>Top-Ups</h3>
                    <ul>
                        <?php foreach ($topups as $topup): ?>
                            <li><?php echo htmlspecialchars($topup['user_id']); ?> - ฿<?php echo number_format($topup['topup_amount'], 2); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>

                <!-- Report and Analytics Section -->
                <section id="report-analytics">
                    <h2>Report and Analytics</h2>
                    <h3>Top 10 Highest Bids</h3>
                    <ul>
                        <?php foreach ($top_bids as $bid): ?>
                            <li>Auction ID: <?php echo $bid['auction_id']; ?> - ฿<?php echo number_format($bid['highest_bid'], 2); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>

                <!-- Server Information Section -->
                <section id="server-info">
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
</body>
</html>