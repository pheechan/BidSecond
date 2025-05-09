<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include_once 'init.php';
if (!isset($_SESSION['user'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}



$config = include(__DIR__ . '/../private/config.php');
$user = $_SESSION['user']; // Access user information
$message = ""; // Message to show after actions

// Connect to the database
$link = mysqli_connect($config['db_host'], $config['db_user'], $config['db_password'], $config['db_name']);
if (!$link) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch current balance
$stmt = $link->prepare("SELECT balance FROM USERS WHERE user_id = ?");
if (!$stmt) {
    die("Failed to prepare statement: " . $link->error);
}
$stmt->bind_param("i", $user['user_id']);
$stmt->execute();
$stmt->bind_result($balance);
$stmt->fetch();
$stmt->close();

// Pagination setup
$limit = 10; // Number of records per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Fetch wallet history with pagination
$order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

$query = "
    SELECT 'Top-Up' AS type, topup_amount AS amount, topup_date AS date
    FROM TOPUP
    WHERE user_id = ?
    UNION ALL
    SELECT 'Sell' AS type, bid_amount AS amount, end_time AS date
    FROM SELL_HISTORY
    WHERE user_id = ?
    UNION ALL
    SELECT 'Buy' AS type, -bid_amount AS amount, end_time AS date
    FROM BUY_HISTORY
    WHERE user_id = ?
    UNION ALL
    SELECT 'Withdraw' AS type, -withdraw_amount AS amount, withdraw_date AS date
    FROM WITHDRAW
    WHERE user_id = ?
    ORDER BY date $order
    LIMIT ? OFFSET ?
";

$stmt = $link->prepare($query);
if (!$stmt) {
    die("Failed to prepare statement: " . $link->error);
}
$stmt->bind_param("iiiiii", $user['user_id'], $user['user_id'], $user['user_id'], $user['user_id'], $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$transactions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count total transactions for pagination
$count_query = "
    SELECT COUNT(*) AS total
    FROM (
        SELECT topup_date AS date FROM TOPUP WHERE user_id = ?
        UNION ALL
        SELECT end_time AS date FROM SELL_HISTORY WHERE user_id = ?
        UNION ALL
        SELECT end_time AS date FROM BUY_HISTORY WHERE user_id = ?
        UNION ALL
        SELECT withdraw_date AS date FROM WITHDRAW WHERE user_id = ?
    ) AS combined
";

$stmt = $link->prepare($count_query);
if (!$stmt) {
    die("Failed to prepare statement: " . $link->error);
}
$stmt->bind_param("iiii", $user['user_id'], $user['user_id'], $user['user_id'], $user['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$total_transactions = $result->fetch_assoc()['total'];
$stmt->close();

$total_pages = ceil($total_transactions / $limit);

// Handle Topup Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topup_amount'])) {
    $topup_amount = floatval($_POST['topup_amount']);
    if ($topup_amount > 0) {
        $stmt = $link->prepare("INSERT INTO TOPUP (user_id, topup_amount, status, topup_date) VALUES (?, ?, 0, NOW())");
        if (!$stmt) {
            die("Failed to prepare statement: " . $link->error);
        }
        $stmt->bind_param("id", $user['user_id'], $topup_amount);
        if ($stmt->execute()) {
            $message = "Topup request submitted! Please confirm the transaction.";
        } else {
            $message = "Failed to submit topup request.";
        }
        $stmt->close();
    } else {
        $message = "Invalid topup amount.";
    }
}

// Handle Confirmation of Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    // Update TOPUP status to 1
    $stmt = $link->prepare("UPDATE TOPUP SET status = 1 WHERE user_id = ? AND status = 0");
    if (!$stmt) {
        die("Failed to prepare statement: " . $link->error);
    }
    $stmt->bind_param("i", $user['user_id']);
    if ($stmt->execute()) {
        // Update user's balance using only the most recent topup based on date
        $stmt = $link->prepare("
            UPDATE USERS u
            JOIN (
                SELECT user_id, topup_amount
                FROM TOPUP
                WHERE user_id = ? AND status = 1
                ORDER BY topup_date DESC
                LIMIT 1
            ) t ON u.user_id = t.user_id
            SET u.balance = u.balance + t.topup_amount
        ");
        if (!$stmt) {
            die("Failed to prepare statement: " . $link->error);
        }
        $stmt->bind_param("i", $user['user_id']);
        if ($stmt->execute()) {
            $message = "Topup confirmed and balance updated!";
        } else {
            $message = "Failed to update balance.";
        }
        $stmt->close();

        // Redirect to the same page to prevent form resubmission
        header("Location: wallet.php");
        exit();
    } else {
        $message = "Failed to confirm payment.";
    }
}

// Handle Withdraw Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw_amount'])) {
    $withdraw_amount = floatval($_POST['withdraw_amount']);
    if ($withdraw_amount > 0) {
        if ($withdraw_amount <= $balance) {
            // Deduct the amount from the user's balance
            $stmt_update_balance = $link->prepare("UPDATE USERS SET balance = balance - ? WHERE user_id = ?");
            if (!$stmt_update_balance) {
                die("Failed to prepare statement: " . $link->error);
            }
            $stmt_update_balance->bind_param("di", $withdraw_amount, $user['user_id']);
            if ($stmt_update_balance->execute()) {
                // Add record to the WITHDRAW table
                $stmt_insert_withdraw = $link->prepare("INSERT INTO WITHDRAW (user_id, withdraw_amount) VALUES (?, ?)");
                if (!$stmt_insert_withdraw) {
                    die("Failed to prepare statement: " . $link->error);
                }
                $stmt_insert_withdraw->bind_param("id", $user['user_id'], $withdraw_amount);
                if ($stmt_insert_withdraw->execute()) {
                    $message = "Withdrawal successful! Your balance has been updated.";
                } else {
                    $message = "Failed to record withdrawal.";
                }
                $stmt_insert_withdraw->close();
            } else {
                $message = "Failed to process withdrawal.";
            }
            $stmt_update_balance->close();

            // Redirect to the same page to prevent form resubmission
            header("Location: wallet.php");
            exit();
        } else {
            $message = "Insufficient amount of money.";
        }
    } else {
        $message = "Invalid withdrawal amount.";
    }
}

$link->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet - BidSecond</title>
    <link rel="stylesheet" href="styles/wallet.css">
</head>
<body>
    <div class="wallet-container">
        <!-- Back to Home Button -->
        <a href="index.php" class="back-to-home-button">Back to Home</a>

        <h1>My Wallet</h1>
        <p class="balance">Current Balance: ฿<?php echo number_format($balance, 2); ?></p>
        <?php if (!empty($message)) { ?>
            <p class="message"><?php echo $message; ?></p>
        <?php } ?>
        
        <!-- Topup Form -->
        <form action="wallet.php" method="POST" class="wallet-form">
            <label for="topup_amount">Top-Up Amount:</label>
            <input type="number" id="topup_amount" name="topup_amount" step="0.01" min="0" required>
            <button type="submit">Submit</button>
        </form>
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topup_amount'])) { ?>
            <div class="confirmation">
                <p>Did you complete the transaction via your preferred method? aka ชำระเงินแล้ว(สมมติ)</p>
                <form action="wallet.php" method="POST">
                    <button type="submit" name="confirm_payment">Yes, I have</button>
                </form>
            </div>
        <?php } ?>

        <!-- Withdraw Form -->
        <form action="wallet.php" method="POST" class="wallet-form" id="wallet-form">
            <label for="withdraw_amount">Withdraw Amount:</label>
            <input type="number" id="withdraw_amount" name="withdraw_amount" step="0.01" min="0" required>
            <button type="submit" id="withdraw-button">Withdraw</button>
            <p id="withdraw-error" style="color: red; display: none;">Insufficient amount of money.</p>
        </form>

        <script>
            const withdrawInput = document.getElementById('withdraw_amount');
            const withdrawButton = document.getElementById('withdraw-button');
            const withdrawError = document.getElementById('withdraw-error');
            const currentBalance = <?php echo $balance; ?>; // Pass the current balance from PHP

            withdrawInput.addEventListener('input', () => {
                const withdrawAmount = parseFloat(withdrawInput.value);
                if (withdrawAmount > currentBalance) {
                    withdrawError.style.display = 'block'; // Show error message
                    withdrawButton.disabled = true; // Disable the button
                } else {
                    withdrawError.style.display = 'none'; // Hide error message
                    withdrawButton.disabled = false; // Enable the button
                }
            });
        </script>

        <!-- Wallet History -->
        <div class="wallet-history">
            <h2>Wallet History</h2>
            <div class="history-controls">
                <a href="wallet.php?order=asc" class="history-order-button">Ascending</a>
                <a href="wallet.php?order=desc" class="history-order-button">Descending</a>
            </div>
            <div class="history-list">
                <?php if (!empty($transactions)) { ?>
                    <?php foreach ($transactions as $transaction) { ?>
                        <div class="history-item">
                            <p class="history-date"><?php echo date("Y-m-d H:i:s", strtotime($transaction['date'])); ?></p>
                            <p class="history-type">
                                <?php if ($transaction['amount'] > 0) { ?>
                                    <span class="history-amount positive">+฿<?php echo number_format($transaction['amount'], 2); ?></span>
                                    <span class="history-source">(<?php echo $transaction['type']; ?>)</span>
                                <?php } else { ?>
                                    <span class="history-amount negative">-฿<?php echo number_format(abs($transaction['amount']), 2); ?></span>
                                    <span class="history-source">(<?php echo $transaction['type']; ?>)</span>
                                <?php } ?>
                            </p>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <p>No transactions found.</p>
                <?php } ?>
            </div>
            <div class="pagination">
                <?php if ($page > 1) { ?>
                    <a href="wallet.php?page=<?php echo $page - 1; ?>&order=<?php echo $order; ?>" class="pagination-button">Previous</a>
                <?php } ?>
                <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                    <a href="wallet.php?page=<?php echo $i; ?>&order=<?php echo $order; ?>" class="pagination-button <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php } ?>
                <?php if ($page < $total_pages) { ?>
                    <a href="wallet.php?page=<?php echo $page + 1; ?>&order=<?php echo $order; ?>" class="pagination-button">Next</a>
                <?php } ?>
            </div>
        </div>
    </div>
</body>
</html>