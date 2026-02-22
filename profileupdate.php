<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_name'])) {
    header('Location: signin.php'); 
    exit();
}

$username = $_SESSION['user_name'];
$user_q = $conn->prepare("SELECT id, name, email, phone, profile_pic FROM users WHERE name=? LIMIT 1");
$user_q->bind_param('s', $username);
$user_q->execute();
$user_q->bind_result($user_id, $name, $email, $phone, $profile_pic);
$user_q->fetch();
$user_q->close();
?>

<h2>My Profile</h2>

<img src="<?php echo $profile_pic ? 'uploads/'.$profile_pic : 'images/default.png'; ?>" width="120" height="120">

<form method="POST" enctype="multipart/form-data" action="profile_update.php">
    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
    <label>Name:</label>
    <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>

    <label>Email:</label>
    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>

    <label>Phone:</label>
    <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>

    <label>Profile Picture:</label>
    <input type="file" name="profile_pic" accept="image/*">

    <button type="submit" name="update_profile">Update Profile</button>
</form>

<h3>Change Password</h3>
<form method="POST" action="change_password.php">
    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
    <label>Current Password:</label>
    <input type="password" name="current_password" required>

    <label>New Password:</label>
    <input type="password" name="new_password" required>

    <label>Confirm New Password:</label>
    <input type="password" name="confirm_password" required>

    <button type="submit" name="change_pass">Change Password</button>
</form>
