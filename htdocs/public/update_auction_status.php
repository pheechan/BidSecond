<?php

$config = include(__DIR__ . '/../private/config.php'); // Load database configuration

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

    // Update auctions where end_time has passed and status is still 'active'
    $query = "
        UPDATE AUCTIONS
        SET status = 'pending'
        WHERE status = 'active' AND end_time < NOW()
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    echo "Auction statuses updated successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}