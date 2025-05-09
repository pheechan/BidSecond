<?php

session_start();
// include_once 'init.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = include(__DIR__ . '/../private/config.php');

$message = ""; // Initialize a message variable

if (!empty($_POST["login"])) {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $link = mysqli_connect($config['db_host'], $config['db_user'], $config['db_password'], $config['db_name']);

    if (!$link) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    // Check if the email exists and verify the password
    $stmt = $link->prepare("SELECT user_id, username, password_hash, roles FROM USERS WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($userid, $username, $password_hash, $roles);

    if ($stmt->fetch() && password_verify($password, $password_hash)) {
        // Login successful, store user data in session
        $_SESSION['user'] = [
            'user_id' => $userid,
            'name' => $username,
            'email' => $email,
            'roles' => $roles,
        ];
        header("Location: index.php"); // Redirect to the home page
        exit();
    } else {
        $message = "Invalid email or password. Please try again.";
    }

    $stmt->close();
    $link->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in</title>
    <link rel="stylesheet" href="styles/auth.css"> <!-- Link to shared CSS -->
</head>
<body>
    <div class="content-wrapper">
        <!-- Add the brand logo here -->
        <img src="images/logo.png" alt="Brand Logo" class="brand-logo">
        <div class="content-box">
            <h1>Sign in to BidSecond</h1>
            <?php if (!empty($message)) { ?>
                <p class="error-message"><?php echo $message; ?></p>
            <?php } ?>
            <form action="login.php" method="POST">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <div>
                    <button type="reset">Reset</button>
                    <button type="submit" name="login" value="Login">Sign in</button>
                </div>
            </form>
            <p class="info-message">Don't have an account? <a href="register.php">Sign up</a></p>
        </div>
    </div>
</body>
</html>