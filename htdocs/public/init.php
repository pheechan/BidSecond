<?php
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
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Lazy update mechanism for auctions
$query = "
    UPDATE AUCTIONS
    SET status = 'pending'
    WHERE status = 'active' AND end_time < NOW()
";
$stmt = $pdo->prepare($query);
$stmt->execute();

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