<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
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

// Hanwallet Submission
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
        <p class="balance">Current Balance: $<?php echo number_format($balance, 2); ?></p>
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
    </div>
</body>
</html>