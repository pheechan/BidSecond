<?php
session_start();
include_once 'init.php';

// Only allow admin
if (!isset($_SESSION['user']) || $_SESSION['user']['roles'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$config = include(__DIR__ . '/../private/config.php');
$pdo = new PDO(
    "mysql:host={$config['db_host']};dbname={$config['db_name']}",
    $config['db_user'],
    $config['db_password'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$message = '';
$auction_id = isset($_GET['auction_id']) ? intval($_GET['auction_id']) : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auction_id'])) {
    $auction_id = intval($_POST['auction_id']);
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $start_price = floatval($_POST['start_price']);
    $bid_amount = floatval($_POST['bid_amount']);
    $min_increment = floatval($_POST['min_increment']);
    $end_time = $_POST['end_time'];
    $status = $_POST['status'];

    // Optional: handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = file_get_contents($_FILES['image']['tmp_name']);
        $stmt = $pdo->prepare("UPDATE AUCTIONS SET image = ? WHERE auction_id = ?");
        $stmt->execute([$image, $auction_id]);
    }

    $stmt = $pdo->prepare("UPDATE AUCTIONS SET title=?, category=?, description=?, start_price=?, bid_amount=?, min_increment=?, end_time=?, status=? WHERE auction_id=?");
    if ($stmt->execute([$title, $category, $description, $start_price, $bid_amount, $min_increment, $end_time, $status, $auction_id])) {
        // Redirect to the same page to prevent resubmission and show updated data
        header("Location: edit_auction.php?auction_id=" . $auction_id . "&success=1");
        exit();
    } else {
        $message = "Failed to update auction.";
    }
}

// Handle image deletion
if (isset($_POST['delete_image']) && isset($_POST['auction_id'])) {
    $auction_id = intval($_POST['auction_id']);
    $stmt = $pdo->prepare("UPDATE AUCTIONS SET image = NULL WHERE auction_id = ?");
    $stmt->execute([$auction_id]);
    // Reload to show the updated state
    header("Location: edit_auction.php?auction_id=" . $auction_id . "&success=1");
    exit();
}

// Fetch auction details
$stmt = $pdo->prepare("SELECT * FROM AUCTIONS WHERE auction_id = ?");
$stmt->execute([$auction_id]);
$auction = $stmt->fetch();

if (!$auction) {
    die("Auction not found.");
}

// List of categories (should match your site)
$categories = [
    "Electronics", "Fashion", "Home and Garden", "Toys and Games", "Automotive",
    "Sports and Outdoors", "Books and Media", "Health and Beauty", "Jewelry and Watches",
    "Music and Instruments", "Collectibles and Antiques", "Art and Craft"
];

// List of statuses
$statuses = ["active", "pending", "ended"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Auction #<?php echo $auction_id; ?> - BidSecond</title>
    <link rel="stylesheet" href="styles/main.css">
    <style>
        .edit-auction-form {
            max-width: 500px;
            margin: 40px auto;
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .edit-auction-form label {
            font-weight: bold;
            display: block;
            margin-top: 16px;
        }
        .edit-auction-form input, .edit-auction-form select, .edit-auction-form textarea {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .edit-auction-form button {
            margin-top: 20px;
            padding: 10px 20px;
            background: #57a05a;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }
        .edit-auction-form button:hover {
            background: #388e3c;
        }
        .edit-auction-form img {
            max-width: 100%;
            margin-top: 10px;
            border-radius: 6px;
        }
        .message {
            color: green;
            text-align: center;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="edit-auction-form">
        <h2>Edit Auction #<?php echo $auction_id; ?></h2>
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="auction_id" value="<?php echo $auction_id; ?>">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($auction['title']); ?>" required>

            <label for="category">Category</label>
            <select name="category" id="category" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php if ($auction['category'] === $cat) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="description">Description</label>
            <textarea name="description" id="description" rows="4" required><?php echo htmlspecialchars($auction['description']); ?></textarea>

            <label for="start_price">Start Price</label>
            <input type="number" step="0.01" name="start_price" id="start_price" value="<?php echo htmlspecialchars($auction['start_price']); ?>" required>

            <label for="bid_amount">Current Bid</label>
            <input type="number" step="0.01" name="bid_amount" id="bid_amount" value="<?php echo htmlspecialchars($auction['bid_amount']); ?>" required>

            <label for="min_increment">Minimum Increment</label>
            <input type="number" step="0.01" name="min_increment" id="min_increment" value="<?php echo htmlspecialchars($auction['min_increment']); ?>" required>

            <label for="end_time">End Time</label>
            <input type="datetime-local" name="end_time" id="end_time" value="<?php echo date('Y-m-d\TH:i', strtotime($auction['end_time'])); ?>" required>

            <label for="status">Status</label>
            <select name="status" id="status" required>
                <?php foreach ($statuses as $stat): ?>
                    <option value="<?php echo $stat; ?>" <?php if ($auction['status'] === $stat) echo 'selected'; ?>>
                        <?php echo ucfirst($stat); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="image">Image (leave blank to keep current)</label>
            <input type="file" name="image" id="image" accept="image/*">
            <?php if (!empty($auction['image'])): ?>
                <img src="data:image/jpeg;base64,<?php echo base64_encode($auction['image']); ?>" alt="Auction Image">
            <?php endif; ?>

            <button type="submit">Save Changes</button>
        </form>
    </div>
</body>
</html>