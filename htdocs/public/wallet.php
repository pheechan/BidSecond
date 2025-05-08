<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

$user = $_SESSION['user']; // Access user information
if (!isset($user['user_id'])) {
    header("Location: login.php");
    exit();
}

$config = include(__DIR__ . '/../private/config.php');
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

// Handle Topup Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topup_amount'])) {
    $topup_amount = floatval($_POST['topup_amount']);
    if ($topup_amount > 0) {
        $stmt = $link->prepare("INSERT INTO TOPUP (user_id, topup_amount, status) VALUES (?, ?, 0)");
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
    $stmt = $link->prepare("UPDATE TOPUP SET status = 1 WHERE user_id = ? AND status = 0");
    if (!$stmt) {
        die("Failed to prepare statement: " . $link->error);
    }
    $stmt->bind_param("i", $user['user_id']);
    if ($stmt->execute()) {
        $stmt = $link->prepare("
            UPDATE USERS u
            JOIN TOPUP t ON u.user_id = t.user_id
            SET u.balance = u.balance + t.topup_amount
            WHERE t.user_id = ? AND t.status = 1
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
            $stmt = $link->prepare("UPDATE USERS SET balance = balance - ? WHERE user_id = ?");
            if (!$stmt) {
                die("Failed to prepare statement: " . $link->error);
            }
            $stmt->bind_param("di", $withdraw_amount, $user['user_id']);
            if ($stmt->execute()) {
                // Add record to the WITHDRAW table
                $stmt = $link->prepare("INSERT INTO WITHDRAW (user_id, withdraw_amount) VALUES (?, ?)");
                if (!$stmt) {
                    die("Failed to prepare statement: " . $link->error);
                }
                $stmt->bind_param("id", $user['user_id'], $withdraw_amount);
                if ($stmt->execute()) {
                    $message = "Withdrawal successful! Your balance has been updated.";
                } else {
                    $message = "Failed to record withdrawal.";
                }
                $stmt->close();
            } else {
                $message = "Failed to process withdrawal.";
            }
            $stmt->close();
        } else {
            $message = "You don't have enough money to withdraw.";
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
        <h1>My Wallet</h1>
        <p class="balance">Current Balance: $<?php echo number_format($balance, 2); ?></p>
        <?php if (!empty($message)) { ?>
            <p class="message"><?php echo $message; ?></p>
        <?php } ?>
        
        <!-- Topup Form -->
        <form action="wallet.php" method="POST" class="topup-form">
            <label for="topup_amount">Topup Amount:</label>
            <input type="number" id="topup_amount" name="topup_amount" step="0.01" min="0" required>
            <button type="submit">Submit</button>
        </form>
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topup_amount'])) { ?>
            <div class="confirmation">
                <p>Did you complete the transaction via your preferred method?</p>
                <form action="wallet.php" method="POST">
                    <button type="submit" name="confirm_payment">Yes, I have</button>
                </form>
            </div>
        <?php } ?>

        <!-- Withdraw Form -->
        <form action="wallet.php" method="POST" class="withdraw-form">
            <label for="withdraw_amount">Withdraw Amount:</label>
            <input type="number" id="withdraw_amount" name="withdraw_amount" step="0.01" min="0" required>
            <button type="submit">Withdraw</button>
        </form>
    </div>
</body>
</html>