<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_name'])) exit('Not logged in');

if (isset($_POST['change_pass'])) {
    $user_id = intval($_POST['user_id']);
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    // Fetch current hash
    $res = $conn->query("SELECT password FROM users WHERE id='$user_id' LIMIT 1");
    $row = $res->fetch_assoc();

    if (!password_verify($current, $row['password'])) {
        die("Current password is wrong.");
    }
    if ($new !== $confirm) {
        die("Passwords do not match.");
    }

    $new_hash = password_hash($new, PASSWORD_DEFAULT);
    $conn->query("UPDATE users SET password='$new_hash' WHERE id='$user_id'");
    $_SESSION['message'] = 'Password changed successfully!';
    header('Location: profile.php');
    exit();
}
?>
