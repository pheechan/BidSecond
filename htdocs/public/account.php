<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

$config = include(__DIR__ . '/../private/config.php');
$user = $_SESSION['user']; // Access user information
$message = ""; // Message to show after updating

// Connect to the database
$link = mysqli_connect($config['db_host'], $config['db_user'], $config['db_password'], $config['db_name']);
if (!$link) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch user details from the database
$stmt = $link->prepare("SELECT user_id, username, email, address, balance, created_at, role FROM USERS WHERE email = ?");
$stmt->bind_param("s", $user['email']);
$stmt->execute();
$stmt->bind_result($user_id, $username, $email, $address, $balance, $created_at, $role);
$stmt->fetch();
$stmt->close();

// Handle form submission to update user information
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $new_username = $_POST['username'];
    $new_address = $_POST['address'];
    $new_role = $_POST['role'];

    $stmt = $link->prepare("UPDATE USERS SET username = ?, address = ?, role = ? WHERE user_id = ?");
    $stmt->bind_param("sssi", $new_username, $new_address, $new_role, $user_id);

    if ($stmt->execute()) {
        $message = "Account information updated successfully!";
        // Update session data
        $_SESSION['user']['name'] = $new_username;
        $_SESSION['user']['role'] = $new_role;
    } else {
        $message = "Failed to update account information.";
    }
    $stmt->close();
}

// Close the database connection
$link->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account - BidSecond</title>
    <link rel="stylesheet" href="styles/auth.css"> <!-- Use shared CSS -->
    <link rel="stylesheet" href="styles/account.css"> <!-- Link to the new account-specific CSS -->
</head>
<body>
    <div class="content-wrapper">
        <div class="content-box">
            <h1>Account Settings</h1>
            <div class="tabs">
                <div class="tab active" onclick="showTab('account-info')">Account Information</div>
                <div class="tab" onclick="showTab('history')">History</div>
            </div>

            <!-- Account Information Tab -->
            <div id="account-info" class="tab-content active">
                <?php if (!empty($message)) { ?>
                    <p class="message"><?php echo $message; ?></p>
                <?php } ?>
                <form action="account.php" method="POST">
                    <div class="form-group">
                        <label for="user_id">User ID</label>
                        <input type="text" id="user_id" value="<?php echo htmlspecialchars($user_id); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($address); ?>">
                    </div>
                    <div class="form-group">
                        <label for="balance">Balance</label>
                        <input type="text" id="balance" value="<?php echo htmlspecialchars($balance); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="created_at">Created At</label>
                        <input type="text" id="created_at" value="<?php echo htmlspecialchars($created_at); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <button type="submit" name="update_account" class="update-button">Update</button>
                </form>
            </div>

            <!-- History Tab -->
            <div id="history" class="tab-content">
                <p>History content will go here...</p>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabId) {
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => tab.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            document.querySelector(`#${tabId}`).classList.add('active');
            document.querySelector(`.tab[onclick="showTab('${tabId}')"]`).classList.add('active');
        }
    </script>
</body>
</html>