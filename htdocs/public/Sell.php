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
    $description = htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8');
    $starting_price = floatval($_POST['starting_price']);
    $min_increment = floatval($_POST['min_increment']);
    $end_time = $_POST['end_time'];
    $created_at = date('Y-m-d H:i:s'); // Current timestamp
    $status = 'active'; // Set status to active

    // Handle image upload
    $image = null;
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
        $image = 'uploads/' . basename($_FILES['picture']['name']);
        move_uploaded_file($_FILES['picture']['tmp_name'], __DIR__ . '/../' . $image);
    }

    if ($title && $category && $description && $starting_price > 0 && $min_increment > 0 && $end_time && $image) {
        // Insert into AUCTIONS table
        $stmt = $link->prepare("INSERT INTO AUCTIONS (seller_id, title, image, category, description, start_price, bid_amount, min_increment, end_time, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if (!$stmt) {
            die("Failed to prepare statement: " . $link->error);
        }

        // Set bid_amount to starting_price and link seller_id
        $bid_amount = $starting_price;

        // Insert into SELLER table and get the seller_id
        $seller_stmt = $link->prepare("INSERT INTO SELLER (user_id) VALUES (?)");
        if (!$seller_stmt) {
            die("Failed to prepare seller statement: " . $link->error);
        }
        $seller_stmt->bind_param("i", $user['user_id']);
        if ($seller_stmt->execute()) {
            $seller_id = $link->insert_id; // Get the newly created seller_id
        } else {
            die("Failed to insert into SELLER table: " . $seller_stmt->error);
        }
        $seller_stmt->close();

        // Bind parameters for AUCTIONS table
        $stmt->bind_param("issssdddsss", $seller_id, $title, $image, $category, $description, $starting_price, $bid_amount, $min_increment, $end_time, $status, $created_at);

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
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        .content-wrapper {
            padding-top: 20px; /* Reduced padding to move content up */
            max-width: 800px;
            margin: 0 auto;
        }

        .content-box {
            background-color: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 0px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
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

        .message {
            color: green;
            font-weight: bold;
            margin-bottom: 20px;
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

            <form action="sell.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Auction Title</label>
                    <div class="form-group-box">
                        <input type="text" id="title" name="title" placeholder="Enter auction title" required>
                    </div>
                </div>

                <!-- Insert Picture Section -->
                <div class="form-group">
                    <label for="picture">Insert Picture</label>
                    <div class="form-group-box">
                        <input type="file" id="picture" name="picture" accept="image/*" required onchange="previewImage(event)">
                        <div id="image-preview-container" style="margin-top: 10px; max-height: 200px; overflow-y: auto; position: relative;">
                            <div id="image-preview">
                                <!-- Image preview will be displayed here -->
                            </div>
                        </div>
                        <button type="button" id="cancel-upload" style="display: none; margin-top: 10px;" onclick="cancelImageUpload()">Cancel Picture Upload</button>
                    </div>
                </div>

                <!-- Description Section -->
                <div class="form-group">
                    <label for="description">Description</label>
                    <div class="form-group-box">
                        <textarea id="description" name="description" placeholder="Enter auction description" rows="5" required></textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <div class="form-group-box">
                        <select id="category" name="category" required>
                            <option value="Electronics">Electronics</option>
                            <option value="Fashion">Fashion</option>
                            <option value="Home and Garden">Home & Garden</option>
                            <option value="Toys and Games">Toys & Games</option>
                            <option value="Automotive">Automotive</option>
                            <option value="Sports and Outdoors">Sports & Outdoors</option>
                            <option value="Books and Media">Books & Media</option>
                            <option value="Health and Beauty">Health & Beauty</option>
                            <option value="Jewelry and Watches">Jewelry & Watches</option>
                            <option value="Music and Instruments">Music & Instruments</option>
                            <option value="Collectibles and Antiques">Collectibles & Antiques</option>
                            <option value="Art and Craft">Art & Craft</option>
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

    <script>
        function previewImage(event) {
            const previewContainer = document.getElementById('image-preview');
            const cancelButton = document.getElementById('cancel-upload');
            previewContainer.innerHTML = ''; // Clear any existing preview

            const file = event.target.files[0];
            if (file) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.style.maxWidth = '100%';
                img.style.border = '1px solid #ddd';
                img.style.borderRadius = '5px';
                img.style.marginTop = '10px';
                previewContainer.appendChild(img);

                // Show the cancel button
                cancelButton.style.display = 'inline-block';
            }
        }

        function cancelImageUpload() {
            const fileInput = document.getElementById('picture');
            const previewContainer = document.getElementById('image-preview');
            const cancelButton = document.getElementById('cancel-upload');

            // Clear the file input and preview
            fileInput.value = '';
            previewContainer.innerHTML = '';
            cancelButton.style.display = 'none';
        }
    </script>
</body>
</html>