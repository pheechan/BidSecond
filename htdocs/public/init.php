<?php
// Set the default time zone to Indochina Time (ICT)
date_default_timezone_set('Asia/Bangkok'); // Set time zone to Indochina Time (ICT)

// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$config = include(__DIR__ . '/../private/config.php');

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']}",
        $config['db_user'],
        $config['db_password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // Set the database session time zone to Asia/Bangkok
    $pdo->exec("SET time_zone = '+07:00'");

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$current_time = date('Y-m-d H:i:s'); // Current Thai time

// Compare $current_time with end_time and update status to 'pending'
$query = "
    UPDATE AUCTIONS
    SET status = 'pending'
    WHERE status = 'active' AND CAST(end_time AS DATETIME) < :current_time
";
$stmt = $pdo->prepare($query);
$stmt->execute([':current_time' => $current_time]);

// Optional: Insert into PENDING_TRANSACTIONS if needed
$insert_query = "
    INSERT INTO PENDING_TRANSACTIONS (auction_id, seller_id, bid_amount, end_time, payment_status)
    SELECT auction_id, seller_id, bid_amount, end_time, 'unpaid'
    FROM AUCTIONS
    WHERE status = 'pending' AND auction_id NOT IN (
        SELECT auction_id FROM PENDING_TRANSACTIONS
    )
";
$insert_stmt = $pdo->prepare($insert_query);
$insert_stmt->execute();
?>