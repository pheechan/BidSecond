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

// Update PENDING_TRANSACTIONS table with buyer_id
$update_pending_query = "
    UPDATE PENDING_TRANSACTIONS pt
    JOIN (
        SELECT 
            b.auction_id, 
            b.buyer_id -- Use buyer_id directly from the BIDS table
        FROM BIDS b
        WHERE b.bid_amount = (
            SELECT MAX(b2.bid_amount)
            FROM BIDS b2
            WHERE b2.auction_id = b.auction_id
        )
    ) AS winning_bids
    ON pt.auction_id = winning_bids.auction_id
    SET pt.buyer_id = winning_bids.buyer_id
    WHERE pt.buyer_id IS NULL
";
$update_pending_stmt = $pdo->prepare($update_pending_query);
$update_pending_stmt->execute();

// Insert into PENDING_TRANSACTIONS if needed
$insert_query = "
    INSERT INTO PENDING_TRANSACTIONS (auction_id, seller_id, bid_amount, buyer_id, address, end_time, payment_status)
    SELECT 
        a.auction_id, 
        a.seller_id, 
        winning_bids.bid_amount, 
        winning_bids.buyer_id, -- Use buyer_id from the BUYER table
        u.address, 
        a.end_time, 
        'unpaid'
    FROM AUCTIONS a
    JOIN (
        SELECT 
            b.auction_id, 
            b.buyer_id, -- Use buyer_id directly from the BIDS table
            MAX(b.bid_amount) AS bid_amount
        FROM BIDS b
        GROUP BY b.auction_id
    ) AS winning_bids
    ON a.auction_id = winning_bids.auction_id
    JOIN BUYER br ON winning_bids.buyer_id = br.buyer_id -- Ensure buyer_id is valid
    JOIN USERS u ON br.user_id = u.user_id
    WHERE a.status = 'pending' AND a.auction_id NOT IN (
        SELECT auction_id FROM PENDING_TRANSACTIONS
    )
";
$insert_stmt = $pdo->prepare($insert_query);
$insert_stmt->execute();
?>