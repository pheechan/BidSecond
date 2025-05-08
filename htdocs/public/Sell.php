<?php
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

// Handle form submission for creating a new auction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_auction'])) {
    $title = htmlspecialchars($_POST['title'], ENT_QUOTES, 'UTF-8');
    $category = htmlspecialchars($_POST['category'], ENT_QUOTES, 'UTF-8');
    $starting_price = floatval($_POST['starting_price']);
    $min_increment = floatval($_POST['min_increment']);
    $end_time = $_POST['end_time'];

    if ($title && $category && $starting_price > 0 && $min_increment > 0 && $end_time) {
        $stmt = $link->prepare("INSERT INTO AUCTIONS (user_id, title, category, starting_price, min_increment, end_time) VALUES (?, ?, ?, ?, ?, ?)");

        if (!$stmt) {
            die("Failed to prepare statement: " . $link->error);
        }

        $stmt->bind_param("issdds", $user['user_id'], $title, $category, $starting_price, $min_increment, $end_time);

        if ($stmt->execute()) {
            $message = "Auction created successfully!";
        } else {
            $message = "Failed to create auction.";
        }

        $stmt->close();
    } else {
        $message = "Please fill in all fields correctly.";
    }
}

$link->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell - BidSecond</title>
    <link rel="stylesheet" href="styles/auth.css"> <!-- Use shared CSS -->
    <link rel="stylesheet" href="styles/sell.css"> <!-- Add sell-specific CSS -->
    <style>
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #007bff;
            outline: none;
        }

        .form-group-box {
            background-color: #f9f9f9;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .create-button {
            background-color: #007bff;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .create-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <div class="content-box">
            <h1>Create a New Auction</h1>

            <?php if (!empty($message)) { ?>
                <p class="message"><?php echo $message; ?></p>
            <?php } ?>

            <form action="Sell.php" method="POST">
                <div class="form-group">
                    <label for="title">Auction Title</label>
                    <div class="form-group-box">
                        <input type="text" id="title" name="title" placeholder="Enter auction title" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <div class="form-group-box">
                        <select id="category" name="category" required>
                            <option value="Electronics">Electronics</option>
                            <option value="Fashion">Fashion</option>
                            <option value="Home & Garden">Home & Garden</option>
                            <option value="Toys & Games">Toys & Games</option>
                            <option value="Automotive">Automotive</option>
                            <option value="Sports & Outdoors">Sports & Outdoors</option>
                            <option value="Books & Media">Books & Media</option>
                            <option value="Health & Beauty">Health & Beauty</option>
                            <option value="Jewelry & Watches">Jewelry & Watches</option>
                            <option value="Music & Instruments">Music & Instruments</option>
                            <option value="Collectibles & Antiques">Collectibles & Antiques</option>
                            <option value="Art & Craft">Art & Craft</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="starting_price">Starting Price ($)</label>
                    <div class="form-group-box">
                        <input type="number" id="starting_price" name="starting_price" step="0.01" min="0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="min_increment">Minimum Increment ($)</label>
                    <div class="form-group-box">
                        <input type="number" id="min_increment" name="min_increment" step="0.01" min="0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="end_time">End Time</label>
                    <div class="form-group-box">
                        <input type="datetime-local" id="end_time" name="end_time" required>
                    </div>
                </div>

                <button type="submit" name="create_auction" class="create-button">Create Auction</button>
            </form>
        </div>
    </div>
</body>
</html>