<?php
session_start();
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_name'])) {
    header('Location: signin.php');
    exit();
}

$username = $_SESSION['user_name'];
$user_id = null;
$name = $email = $phone = $profile_pic = '';
$error = $success = '';

// --- FETCH USER INFO ---
$stmt = $conn->prepare("SELECT id, name, email, phone, profile_pic FROM users WHERE name=? LIMIT 1");
if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->bind_result($user_id, $name, $email, $phone, $profile_pic);
$stmt->fetch();
$stmt->close();

// --- HANDLE POST REQUESTS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ============================================
    // PASSWORD CHANGE LOGIC
    // ============================================
    if (isset($_POST['change_password']) && $_POST['change_password'] === '1') {
        
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $error = "All password fields are required.";
        } 
        elseif ($new_password !== $confirm_password) {
            $error = "New password and confirmation do not match.";
        }
        elseif (strlen($new_password) < 6) {
            $error = "New password must be at least 6 characters.";
        }
        else {
            $pass_check = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $pass_check->bind_param('i', $user_id);
            $pass_check->execute();
            $pass_check->bind_result($current_hash);
            $pass_check->fetch();
            $pass_check->close();
            
            if (!password_verify($old_password, $current_hash)) {
                $error = "Current password is incorrect.";
            }
            elseif (password_verify($new_password, $current_hash)) {
                $error = "New password must be different from current password.";
            }
            else {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_pass->bind_param('si', $new_hash, $user_id);
                
                if ($update_pass->execute()) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Failed to update password.";
                }
                $update_pass->close();
            }
        }
    }
    // ============================================
    // PROFILE UPDATE LOGIC
    // ============================================
    else {
        $new_name = trim($_POST['name'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $new_phone = trim($_POST['phone'] ?? '');
        $profile_file = $_FILES['profile_pic'] ?? null;

        if (empty($new_name) || empty($new_email) || empty($new_phone)) {
            $error = "Name, email, and phone are required.";
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif (!preg_match('/^[0-9]{7,15}$/', $new_phone)) {
            $error = "Invalid phone number.";
        } else {
            // Handle profile picture upload
            if ($profile_file && $profile_file['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($profile_file['name'], PATHINFO_EXTENSION);
                $allowed = ['jpg','jpeg','png','webp'];
                if (!in_array(strtolower($ext), $allowed)) {
                    $error = "Only JPG, PNG, and WEBP images allowed.";
                } else {
                    $new_file = 'uploads/profile_' . $user_id . '.' . $ext;
                    move_uploaded_file($profile_file['tmp_name'], $new_file);
                    $profile_pic = $new_file;
                }
            }

            if (empty($error)) {
                $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, profile_pic=? WHERE id=?");
                $stmt->bind_param('ssssi', $new_name, $new_email, $new_phone, $profile_pic, $user_id);

                if ($stmt->execute()) {
                    $success = "Profile updated successfully!";
                    $_SESSION['user_name'] = $new_name;
                    $name = $new_name; 
                    $email = $new_email; 
                    $phone = $new_phone;
                } else {
                    $error = "Database error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile - Gokarna Forest Hotel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #0c1220 0%, #141824 40%, #1a1f2e 100%);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px 20px;
}

body::before {
    content: '';
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background-image: 
        radial-gradient(circle at 20% 80%, rgba(212, 175, 55, 0.03) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(212, 175, 55, 0.03) 0%, transparent 50%);
    pointer-events: none;
}

.profile-container {
    width: 100%;
    max-width: 480px;
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(212, 175, 55, 0.15);
    border-radius: 24px;
    padding: 40px;
    position: relative;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6);
}

.profile-container::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, #B8941F, #D4AF37, #F4D03F, #D4AF37, #B8941F);
    background-size: 200% auto;
    animation: goldShine 3s linear infinite;
}

@keyframes goldShine {
    0% { background-position: 200% center; }
    100% { background-position: -200% center; }
}

.profile-header {
    text-align: center;
    margin-bottom: 30px;
}

.profile-header h2 {
    font-size: 28px;
    font-weight: 900;
    color: transparent;
    background: linear-gradient(90deg, #B8941F, #D4AF37, #F4D03F, #D4AF37, #B8941F);
    background-size: 200% auto;
    -webkit-background-clip: text;
    background-clip: text;
    animation: goldShine 3s linear infinite;
    margin-bottom: 8px;
}

.profile-header p {
    color: #94a3b8;
    font-size: 14px;
}

.avatar-wrapper {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto 20px;
}

.avatar-wrapper img, .avatar-placeholder {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid rgba(212, 175, 55, 0.3);
    box-shadow: 0 0 30px rgba(212, 175, 55, 0.2);
}

.avatar-placeholder {
    background: rgba(212, 175, 55, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    color: #D4AF37;
}

.alert {
    padding: 14px 18px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 600;
}

.alert.success {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #86efac;
}

.alert.error {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #fca5a5;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    color: #D4AF37;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.form-group input {
    width: 100%;
    padding: 14px 16px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(212, 175, 55, 0.2);
    border-radius: 12px;
    color: #fff;
    font-size: 15px;
    transition: all 0.3s ease;
}

.form-group input:focus {
    outline: none;
    border-color: #D4AF37;
    background: rgba(255, 255, 255, 0.08);
    box-shadow: 0 0 20px rgba(212, 175, 55, 0.15);
}

.form-group input::placeholder {
    color: #475569;
}

.form-group input[type="file"] {
    padding: 10px;
    cursor: pointer;
}

.form-group input[type="file"]::file-selector-button {
    background: rgba(212, 175, 55, 0.2);
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    color: #D4AF37;
    cursor: pointer;
    margin-right: 12px;
    font-weight: 600;
}

.btn {
    width: 100%;
    padding: 16px;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-top: 10px;
    text-decoration: none;
}

.btn-gold {
    background: linear-gradient(135deg, #D4AF37 0%, #B8941F 100%);
    color: #0f172a;
}

.btn-gold:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(212, 175, 55, 0.4);
}

.btn-outline {
    background: transparent;
    border: 1px solid rgba(212, 175, 55, 0.3);
    color: #D4AF37;
}

.btn-outline:hover {
    background: rgba(212, 175, 55, 0.1);
}

.btn-red {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    color: #fff;
}

.btn-red:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(220, 38, 38, 0.4);
}

.btn-gray {
    background: transparent;
    border: 1px solid rgba(255,255,255,0.2);
    color: #94a3b8;
}

.password-section {
    margin-top: 30px;
    padding-top: 30px;
    border-top: 1px solid rgba(212, 175, 55, 0.2);
    display: none;
}

.password-section.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.back-link {
    text-align: center;
    margin-top: 20px;
}

.back-link a {
    color: #94a3b8;
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s;
}

.back-link a:hover {
    color: #D4AF37;
}

@media(max-width: 480px) {
    .profile-container {
        padding: 30px 20px;
    }
}
</style>
</head>
<body>

<div class="profile-container">
    <div class="profile-header">
        <h2><i class="fas fa-user-circle"></i> Your Profile</h2>
        <p>Manage your account settings</p>
    </div>

    <?php if($error) echo '<div class="alert error"><i class="fas fa-exclamation-circle"></i> '.htmlspecialchars($error).'</div>'; ?>
    <?php if($success) echo '<div class="alert success"><i class="fas fa-check-circle"></i> '.htmlspecialchars($success).'</div>'; ?>

    <div class="avatar-wrapper">
        <?php if($profile_pic && file_exists($profile_pic)): ?>
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture">
        <?php else: ?>
            <div class="avatar-placeholder">
                <i class="fas fa-user"></i>
            </div>
        <?php endif; ?>
    </div>

    <!-- MAIN PROFILE FORM -->
    <form method="POST" enctype="multipart/form-data" id="profileForm">
        <div class="form-group">
            <label><i class="fas fa-user"></i> Full Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
        </div>

        <div class="form-group">
            <label><i class="fas fa-envelope"></i> Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>

        <div class="form-group">
            <label><i class="fas fa-phone"></i> Phone</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
        </div>

        <div class="form-group">
            <label><i class="fas fa-camera"></i> Profile Picture</label>
            <input type="file" name="profile_pic" accept="image/*">
        </div>

        <button type="submit" class="btn btn-gold">
            <i class="fas fa-save"></i> Update Profile
        </button>

        <!-- CHANGE PASSWORD BUTTON -->
        <button type="button" class="btn btn-outline" onclick="togglePassword()">
            <i class="fas fa-key"></i> Change Password
        </button>
    </form>

    <!-- PASSWORD FORM (SEPARATE FORM - TOGGLED) -->
    <form method="POST" id="passwordForm" class="password-section">
        <input type="hidden" name="change_password" value="1">
        
        <h3 style="color: #D4AF37; font-size: 18px; margin-bottom: 20px;">
            <i class="fas fa-key"></i> Change Password
        </h3>
        
        <div class="form-group">
            <label><i class="fas fa-lock"></i> Current Password</label>
            <input type="password" name="old_password" placeholder="Enter your current password" required>
        </div>

        <div class="form-group">
            <label><i class="fas fa-lock"></i> New Password</label>
            <input type="password" name="new_password" placeholder="Enter new password" required>
        </div>

        <div class="form-group">
            <label><i class="fas fa-lock"></i> Confirm New Password</label>
            <input type="password" name="confirm_password" placeholder="Retype new password" required>
        </div>
        
        <button type="submit" class="btn btn-red">
            <i class="fas fa-save"></i> Update Password
        </button>
        
        <button type="button" class="btn btn-gray" onclick="togglePassword()">
            <i class="fas fa-times"></i> Cancel
        </button>
    </form>

    <div class="back-link">
        <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
    </div>
</div>

<script>
function togglePassword() {
    var passwordForm = document.getElementById('passwordForm');
    var profileForm = document.getElementById('profileForm');
    
    if (passwordForm.classList.contains('active')) {
        passwordForm.classList.remove('active');
        profileForm.style.display = 'block';
    } else {
        passwordForm.classList.add('active');
        profileForm.style.display = 'none';
        passwordForm.scrollIntoView({behavior: 'smooth'});
    }
}
</script>

</body>
</html>