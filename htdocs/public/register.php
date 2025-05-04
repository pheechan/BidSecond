<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

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
    <link rel="stylesheet" href="main.css"> <!-- Link to shared CSS -->
</head>
<body>
    <div class="content-wrapper">
        <div class="content-box">
            <h1>Register</h1>
            <form action="register.php" method="POST">
                <input type="text" name="name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <div>
                    <button type="submit" name="send" value="Submit">Register</button>
                    <button type="reset">Reset</button>
                </div>
            </form>
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

    $link = mysqli_connect("sql210.infinityfree.com", "if0_38762438", "4sigmaboys", "if0_38762438_bidseconddb");

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
                $mail->Username = 'bidsecondhand@gmail.com'; // Your Gmail
                $mail->Password = 'nqql ezlj jjxk ryey'; // Use an App Password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('bidsecondhand+noreply@gmail.com', 'BidSecond');
                $mail->addAddress($email, $name);
                $mail->isHTML(true);
                $mail->Subject = 'Your OTP Code';
                $mail->Body = "Hello $name,<br>Your OTP is: <b>$otp</b>";

                $mail->send();
                echo "Registration successful! Check your email for the OTP.<br>";
            } catch (Exception $e) {
                echo "Registered, but OTP email failed: {$mail->ErrorInfo}<br>";
            }

            $show_otp_form = true; // Show the OTP form after registration
        }
    } catch (mysqli_sql_exception $e) {
        // Check for duplicate email error
        if ($e->getCode() == 1062) { // Error code 1062 is for duplicate entry
            $error_message = "This email is already registered.";
        } else {
            $error_message = "Failed to register. Error: " . $e->getMessage();
        }
    }

    $stmt->close();
    $link->close();
} elseif (!empty($_POST["verify_otp"])) {
    $email = $_POST["email"];
    $entered_otp = $_POST["otp"];

    $link = mysqli_connect("sql210.infinityfree.com", "if0_38762438", "4sigmaboys", "if0_38762438_bidseconddb");

    if (!$link) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    // Check OTP in the database
    $stmt = $link->prepare("SELECT otp_code FROM USERS WHERE email = ? AND email_verified = 0");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($otp_in_db);
    $stmt->fetch();

    if ($entered_otp == $otp_in_db) {
        // Update email_verified to 1
        $stmt->close();
        $stmt = $link->prepare("UPDATE USERS SET email_verified = 1 WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        echo "Registration complete!";
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

    $link = mysqli_connect("sql210.infinityfree.com", "if0_38762438", "4sigmaboys", "if0_38762438_bidseconddb");

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
        $mail->Username = 'bidsecondhand@gmail.com'; // Your Gmail
        $mail->Password = 'nqql ezlj jjxk ryey'; // Use an App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('bidsecondhand+noreply@gmail.com', 'BidSecond');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Your New OTP Code';
        $mail->Body = "Hello,<br>Your new OTP is: <b>$new_otp</b>";

        $mail->send();
        echo "A new OTP has been sent to your email.<br>";
    } catch (Exception $e) {
        echo "Failed to send new OTP: {$mail->ErrorInfo}<br>";
    }

    $stmt->close();
    $link->close();

    $show_otp_form = true; // Show the OTP form after generating a new OTP
}

// Display the OTP verification form if needed
if ($show_otp_form || !empty($error_message)) {
?>
<form action="register.php" method="POST">
    <p>
        Enter OTP: <input type="text" name="otp"><br>
        <button type="submit" name="verify_otp" value="Verify">Verify OTP</button>
        <button type="submit" name="generate_otp" value="Generate">Send New Code</button>
    </p>
    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
    <?php if (!empty($error_message)) { ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php } ?>
</form>
<?php
}
?>
