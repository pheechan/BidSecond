<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
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

// Fetch pending items won by the user
$stmt = $link->prepare("
    SELECT bh.auction_id, a.title, a.bid_amount, a.end_time
    FROM BUY_HISTORY bh
    LEFT JOIN AUCTIONS a ON bh.auction_id = a.auction_id
    WHERE bh.user_id = ? AND bh.payment_status = 'pending'
");
if (!$stmt) {
    die("Failed to prepare statement: " . $link->error);
}
$stmt->bind_param("i", $user['user_id']);
$stmt->execute();
$pending_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle payment confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $selected_items = $_POST['selected_items'] ?? [];
    if (!empty($selected_items)) {
        $total_amount = 0;

        // Calculate total amount
        foreach ($selected_items as $auction_id) {
            foreach ($pending_items as $item) {
                if ($item['auction_id'] == $auction_id) {
                    $total_amount += $item['bid_amount'];
                }
            }
        }

        // Check if user has enough balance
        $stmt = $link->prepare("SELECT balance FROM USERS WHERE user_id = ?");
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        $stmt->bind_result($balance);
        $stmt->fetch();
        $stmt->close();

        if ($balance >= $total_amount) {
            // Deduct total amount from user's balance
            $stmt = $link->prepare("UPDATE USERS SET balance = balance - ? WHERE user_id = ?");
            $stmt->bind_param("di", $total_amount, $user['user_id']);
            $stmt->execute();
            $stmt->close();

            // Mark selected items as paid
            $stmt = $link->prepare("UPDATE BUY_HISTORY SET payment_status = 'paid' WHERE auction_id = ? AND user_id = ?");
            foreach ($selected_items as $auction_id) {
                $stmt->bind_param("ii", $auction_id, $user['user_id']);
                $stmt->execute();
            }
            $stmt->close();

            $message = "Payment successful! Total amount paid: ฿" . number_format($total_amount, 2);
        } else {
            $message = "Insufficient balance to complete the payment.";
        }
    } else {
        $message = "No items selected for payment.";
    }
}

$link->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - BidSecond</title>
    <link rel="stylesheet" href="styles/cart.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

        .cart-container {
            max-width: 800px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        .message {
            text-align: center;
            color: green;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .cart-table th, .cart-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        .cart-table th {
            background-color: #f4f4f4;
        }

        .confirm-button {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .confirm-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="cart-container">
        <h1>Pending Items</h1>
        <?php if (!empty($message)) { ?>
            <p class="message"><?php echo $message; ?></p>
        <?php } ?>

        <form action="cart.php" method="POST">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Select</th>
                        <th>Item</th>
                        <th>Bid Amount</th>
                        <th>End Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pending_items)) { ?>
                        <?php foreach ($pending_items as $item) { ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_items[]" value="<?php echo $item['auction_id']; ?>">
                                </td>
                                <td><?php echo htmlspecialchars($item['title']); ?></td>
                                <td>฿<?php echo number_format($item['bid_amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($item['end_time']); ?></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No pending items found.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <?php if (!empty($pending_items)) { ?>
                <button type="submit" name="confirm_payment" class="confirm-button">Confirm Payment</button>
            <?php } ?>
        </form>
    </div>
</body>
</html>