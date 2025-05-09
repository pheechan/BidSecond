<?php
session_start();
include_once 'init.php';

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

// Fetch summary data
$total_users = $pdo->query("SELECT COUNT(*) FROM USERS")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM USERS WHERE role = 'admin'")->fetchColumn();
$total_auctions = $pdo->query("SELECT COUNT(*) FROM AUCTIONS")->fetchColumn();
$total_bids = $pdo->query("SELECT COUNT(*) FROM BIDS")->fetchColumn();
$auctions_ending_soon = $pdo->query("
    SELECT title, end_time 
    FROM AUCTIONS 
    WHERE end_time > NOW() AND end_time < DATE_ADD(NOW(), INTERVAL 1 DAY)
    ORDER BY end_time ASC
")->fetchAll();
$revenue_summary = $pdo->query("
    SELECT 
        SUM(topup_amount) AS total_topup, 
        SUM(withdraw_amount) AS total_withdraw, 
        SUM(bid_amount) AS total_bids 
    FROM (
        SELECT topup_amount, NULL AS withdraw_amount, NULL AS bid_amount FROM TOPUP
        UNION ALL
        SELECT NULL, withdraw_amount, NULL FROM WITHDRAW
        UNION ALL
        SELECT NULL, NULL, bid_amount FROM SELL_HISTORY
    ) AS revenue
")->fetch();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BidSecond</title>
    <link rel="stylesheet" href="styles/dashboard.css">
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
        <!-- Dashboard -->
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
                    <div class="summary-box">
                        <p>Total Users: <?php echo $total_users; ?></p>
                        <p>Total Admins: <?php echo $total_admins; ?></p>
                        <p>Total Auctions: <?php echo $total_auctions; ?></p>
                        <p>Total Bids: <?php echo $total_bids; ?></p>
                        <p>Auctions Ending Soon:</p>
                        <ul>
                            <?php foreach ($auctions_ending_soon as $auction): ?>
                                <li><?php echo htmlspecialchars($auction['title']); ?> - Ends at <?php echo $auction['end_time']; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <p>Revenue Summary:</p>
                        <ul>
                            <li>Total Top-Up: ฿<?php echo number_format($revenue_summary['total_topup'], 2); ?></li>
                            <li>Total Withdraw: ฿<?php echo number_format($revenue_summary['total_withdraw'], 2); ?></li>
                            <li>Total Bids: ฿<?php echo number_format($revenue_summary['total_bids'], 2); ?></li>
                        </ul>
                    </div>
                </section>

                <!-- Other sections (User Management, Auction Management, etc.) -->
                <!-- Add similar sections here -->
            </main>
        </div>
    <?php endif; ?>
</body>
</html>