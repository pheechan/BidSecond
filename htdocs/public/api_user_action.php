<?php
session_start();
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Edit balance
    if ($_POST['action'] === 'edit_balance' && isset($_POST['user_id'], $_POST['balance'])) {
        $user_id = (int)$_POST['user_id'];
        $balance = floatval($_POST['balance']);
        $stmt = $pdo->prepare("UPDATE USERS SET balance = ? WHERE user_id = ?");
        if ($stmt->execute([$balance, $user_id])) {
            echo "Balance updated successfully.";
        } else {
            echo "Failed to update balance.";
        }
        exit;
    }

    // Delete user
    if ($_POST['action'] === 'delete_user' && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        $stmt = $pdo->prepare("DELETE FROM USERS WHERE user_id = ?");
        if ($stmt->execute([$user_id])) {
            echo "User deleted successfully.";
        } else {
            echo "Failed to delete user.";
        }
        exit;
    }

    // Update user role
    if ($_POST['action'] === 'update_role' && isset($_POST['user_id'], $_POST['role'])) {
        $user_id = (int)$_POST['user_id'];
        $role = $_POST['role'] === 'admin' ? 'admin' : 'user';
        $stmt = $pdo->prepare("UPDATE USERS SET roles = ? WHERE user_id = ?");
        if ($stmt->execute([$role, $user_id])) {
            echo "Role updated successfully.";
        } else {
            echo "Failed to update role.";
        }
        exit;
    }

}

echo "Invalid request.";
?>