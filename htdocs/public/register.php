<?php

session_start();
include_once 'init.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

$config = include(__DIR__ . '/../private/config.php');

$error_message = ""; // Initialize an error message variable
$show_otp_form = false; // Control whether to show the OTP form

if (empty($_POST["send"]) && empty($_POST["verify_otp"]) && empty($_POST["generate_otp"])) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="styles/auth.css"> <!-- Link to shared CSS -->
</head>
<body>
    <div class="content-wrapper">
        <!-- Add the brand logo here -->
        <img src="images/logo.png" alt="Brand Logo" class="brand-logo">
        <div class="content-box">
            <h1>Sign up to BidSecond</h1>
            <form action="register.php" method="POST">
                <input type="text" name="name" placeholder="Username" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <div>
                    <button type="reset">Reset</button>
                    <button type="submit" name="send" value="Submit">Sign up</button>
                </div>
            </form>
            <p class="info-message">Already have an account? <a href="login.php">Sign in</a></p>
        </div>
    </div>
</body>
</html>
<?php
} elseif (!empty($_POST["send"])) {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_BCRYPT);
    $otp = rand(100000, 999999); // 6-digit OTP

    $link = mysqli_connect($config['db_host'], $config['db_user'], $config['db_password'], $config['db_name']);

    if (!$link) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    $stmt = $link->prepare("INSERT INTO USERS (username, email, password_hash, otp_code, email_verified) VALUES (?, ?, ?, ?, 0)");
    $stmt->bind_param("ssss", $name, $email, $password, $otp);

    try {
        if ($stmt->execute()) {
            // Send OTP email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $config['mail_username']; 
                $mail->Password = $config['mail_password']; 
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('bidsecondhand+noreply@gmail.com', 'BidSecond');
                $mail->addAddress($email, $name);
                $mail->isHTML(true);
                $mail->Subject = 'Your OTP Code';
                $mail->Body = "Hello $name,<br>Your OTP is: <b>$otp</b>";

                $mail->send();
                $message = "One Time Password (OTP) has been sent via Email to: <b>$email</b>.<br>Enter the OTP below to verify it.";
            } catch (Exception $e) {
                $message = "Registered, but OTP email failed: {$mail->ErrorInfo}";
            }

            $show_otp_form = true; // Show the OTP form after registration
        }
    } catch (mysqli_sql_exception $e) {
        // Check for duplicate email error
        if ($e->getCode() == 1062) { // Error code 1062 is for duplicate entry
            $error_message = "This email is already registered.";
        } else {
            $error_message = "Failed to register. Please try again. Error: " . $e->getMessage();
        }
    }

    $stmt->close();
    $link->close();
} elseif (!empty($_POST["verify_otp"])) {
    $email = $_POST["email"];
    $entered_otp = implode("", $_POST["otp"]); // Combine OTP array into a single string

    $link = mysqli_connect($config['db_host'], $config['db_user'], $config['db_password'], $config['db_name']);

    if (!$link) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    // Fetch OTP and username from the database
    $stmt = $link->prepare("SELECT otp_code, username FROM USERS WHERE email = ? AND email_verified = 0");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($otp_in_db, $name); // Fetch both OTP and username
    $stmt->fetch();

    if ($entered_otp == $otp_in_db) {
        // Update email_verified to 1
        $stmt->close();
        $stmt = $link->prepare("UPDATE USERS SET email_verified = 1 WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        // Store user information in the session (backend process)
        $_SESSION['user'] = [
            'name' => $name, // Use the username fetched from the database
            'email' => $email,
            //'role' => $role,
        ];

        // Render the success page
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Registration Successful</title>
            <link rel="stylesheet" href="styles/auth.css"> <!-- Link to shared CSS -->
            <script>
                // Redirect to the home page after 5 seconds
                setTimeout(function() {
                    window.location.href = "index.php"; // Replace with your home page URL
                }, 5000);
            </script>
        </head>
        <body>
            <div class="content-wrapper">
                <!-- Add the brand logo here -->
                <img src="images/logo.png" alt="Brand Logo" class="brand-logo">
                <div class="content-box">
                    <h1>Registration Successful!</h1>
                    <p class="success-message">Your registration is now complete!</p>
                    <p class="info-message">You will be redirected to our home page in <span id="countdown">5</span> seconds...</p>
                </div>
            </div>
            <script>
                // Countdown timer for the redirect message
                let countdown = 5;
                const countdownElement = document.getElementById("countdown");
                setInterval(function() {
                    countdown--;
                    if (countdownElement) {
                        countdownElement.textContent = countdown;
                    }
                }, 1000);
            </script>
        </body>
        </html>
        <?php
        $show_otp_form = false; // Do not show the OTP form after successful verification
    } else {
        $error_message = "Invalid OTP. Please try again."; // Set the error message
        $show_otp_form = true; // Show the OTP form again
    }

    $stmt->close();
    $link->close();
} elseif (!empty($_POST["generate_otp"])) {
    $email = $_POST["email"];
    $new_otp = rand(100000, 999999); // Generate a new OTP

    $link = mysqli_connect($config['db_host'], $config['db_user'], $config['db_password'], $config['db_name']);

    if (!$link) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    // Update the database with the new OTP
    $stmt = $link->prepare("UPDATE USERS SET otp_code = ? WHERE email = ?");
    $stmt->bind_param("ss", $new_otp, $email);
    $stmt->execute();

    // Send the new OTP via email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $config['mail_username']; 
        $mail->Password = $config['mail_password']; 
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('bidsecondhand+noreply@gmail.com', 'BidSecond');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Your New OTP Code';
        $mail->Body = "Hello,<br>Your new OTP is: <b>$new_otp</b>";

        $mail->send();
        $message = "A new OTP has been sent to your email.";
    } catch (Exception $e) {
        $message = "Failed to send new OTP: {$mail->ErrorInfo}";
    }

    $show_otp_form = true; // Show the OTP form after generating a new OTP

    $stmt->close();
    $link->close();
}

// Display the OTP verification form if needed
if ($show_otp_form || !empty($error_message)) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <link rel="stylesheet" href="styles/auth.css"> <!-- Link to shared CSS -->
    <script>
        function removeOtpRequirement() {
            document.querySelectorAll('.otp-box').forEach(function(input) {
                input.removeAttribute('required');
            });
        }
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const otpInputs = document.querySelectorAll(".otp-box");
            otpInputs.forEach((input, index) => {
                input.addEventListener("input", (e) => {
                    if (e.target.value.length === 1 && index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus(); // Move to the next input
                    }
                });

                input.addEventListener("keydown", (e) => {
                    if (e.key === "Backspace" && index > 0 && !e.target.value) {
                        otpInputs[index - 1].focus(); // Move to the previous input
                    }
                });
            });
        });
    </script>
</head>
<body>
    <div class="content-wrapper">
        <!-- Add the brand logo here -->
        <img src="images/logo.png" alt="Brand Logo" class="brand-logo">
        <div class="content-box">
            <h1>Verify OTP</h1>
            <?php if (!empty($message)) { ?>
                <p class="info-message"><?php echo $message; ?></p>
            <?php } ?>
            <?php if (!empty($error_message)) { ?>
                <p class="error-message"><?php echo $error_message; ?></p>
            <?php } ?>
            <form action="register.php" method="POST" id="otp-form">
                <div class="otp-input-container">
                    <input type="text" name="otp[]" maxlength="1" class="otp-box" required>
                    <input type="text" name="otp[]" maxlength="1" class="otp-box" required>
                    <input type="text" name="otp[]" maxlength="1" class="otp-box" required>
                    <input type="text" name="otp[]" maxlength="1" class="otp-box" required>
                    <input type="text" name="otp[]" maxlength="1" class="otp-box" required>
                    <input type="text" name="otp[]" maxlength="1" class="otp-box" required>
                </div>
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <div>
                    <button type="button" name="generate_otp" value="Generate" onclick="removeOtpRequirement()">Send New Code</button>
                    <button type="submit" name="verify_otp" value="Verify">Verify OTP</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php } ?>
