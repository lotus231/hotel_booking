<?php
session_start();
include "config.php";

// Hardcoded admin credentials
$admin_username = "admin";
$admin_password = "admin1234";

// -------------------
// Admin login check
// -------------------
if (!isset($_SESSION['admin_logged_in'])) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST['username'];
        $password = $_POST['password'];

        if ($username === $admin_username && $password === $admin_password) {
            $_SESSION['admin_logged_in'] = true;
            header("Location: admin.php");
            exit();
        } else {
            $error = "Incorrect admin credentials!";
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Admin Login</title>
        <style>
            body { font-family: Arial, sans-serif; background: linear-gradient(to right,#3b82f6,#06b6d4); display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
            .login-card { background:#fff; padding:30px 25px; border-radius:12px; box-shadow:0 15px 40px rgba(0,0,0,0.2); max-width:350px; width:100%; text-align:center; }
            h2 { margin-bottom:20px; color:#111827; }
            input { width:100%; padding:12px; margin:8px 0; border-radius:8px; border:1px solid #d1d5db; font-size:14px; box-sizing:border-box; }
            button { width:100%; padding:12px; border:none; border-radius:8px; background:#3b82f6; color:#fff; font-weight:600; cursor:pointer; margin-top:10px; }
            button:hover { background:#2563eb; }
            .error { color:#b91c1c; font-weight:bold; margin-bottom:12px; }
        </style>
    </head>
    <body>
        <div class="login-card">
            <h2>Admin Login</h2>
            <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
            <form method="POST" action="">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// -------------------
// Helper function for flash messages
// -------------------
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}

// -------------------
// ROOM MANAGEMENT (Your existing code)
// -------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['room_number']) && !isset($_POST['user_action'])) {
    $room_number = $conn->real_escape_string($_POST['room_number']);
    $room_type = $conn->real_escape_string($_POST['room_type']);
    $price = floatval($_POST['price']);

    $image_path = '';
    if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['room_image']['name'], PATHINFO_EXTENSION);
        $filename = 'uploads/rooms/' . uniqid() . '.' . $ext;
        if (!is_dir('uploads/rooms')) mkdir('uploads/rooms', 0777, true);
        if (move_uploaded_file($_FILES['room_image']['tmp_name'], $filename)) {
            $image_path = $conn->real_escape_string($filename);
        }
    }

    if (isset($_POST['update_room'])) {
        $rid = intval($_POST['room_id']);
        if ($image_path) {
            $res = $conn->query("SELECT image FROM rooms WHERE id='$rid' LIMIT 1");
            if ($res && $res->num_rows) {
                $old = $res->fetch_assoc()['image'];
                if ($old && file_exists($old)) unlink($old);
            }
            $sql = "UPDATE rooms SET room_number='$room_number', room_type='$room_type', price='$price', image='$image_path' WHERE id='$rid'";
        } else {
            $sql = "UPDATE rooms SET room_number='$room_number', room_type='$room_type', price='$price' WHERE id='$rid'";
        }
        $message = $conn->query($sql) ? "Room updated successfully!" : "Error: ".$conn->error;
    } else {
        $sql = "INSERT INTO rooms (room_number, room_type, price, status, image) VALUES ('$room_number','$room_type','$price','available','$image_path')";
        $message = $conn->query($sql) ? "Room added successfully!" : "Error: ".$conn->error;
    }
}

if (isset($_GET['delete_room'])) {
    $rid = intval($_GET['delete_room']);
    $res = $conn->query("SELECT image FROM rooms WHERE id='$rid' LIMIT 1");
    if ($res && $res->num_rows) {
        $img = $res->fetch_assoc()['image'];
        if ($img && file_exists($img)) unlink($img);
    }
    $conn->query("DELETE FROM rooms WHERE id='$rid'");
    setFlashMessage('success', "Room deleted successfully!");
    header("Location: admin.php#rooms");
    exit();
}

if (isset($_GET['toggle_room'])) {
    $rid = intval($_GET['toggle_room']);
    $res = $conn->query("SELECT status FROM rooms WHERE id='$rid' LIMIT 1");
    if ($res && $res->num_rows) {
        $status = $res->fetch_assoc()['status'];
        $new = $status === 'available' ? 'unavailable' : ($status === 'unavailable' ? 'maintenance' : 'available');
        $conn->query("UPDATE rooms SET status='$new' WHERE id='$rid'");
    }
    header("Location: admin.php#rooms");
    exit();
}

// -------------------
// USER MANAGEMENT (Adapted to your schema)
// -------------------

// Handle user CRUD operations
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_action'])) {
    $action = $_POST['user_action'];
    
    if ($action === 'add' || $action === 'edit') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $name = trim($_POST['name']); // Your column is 'name', not 'full_name'
        $phone = trim($_POST['phone']);
        $is_blocked = isset($_POST['is_blocked']) ? 1 : 0; // Your column is 'is_blocked' (0/1)
        
        // Handle profile picture upload
        $profile_pic = '';
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array(strtolower($ext), $allowed)) {
                $filename = 'uploads/profiles/' . uniqid() . '.' . $ext;
                if (!is_dir('uploads/profiles')) mkdir('uploads/profiles', 0777, true);
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $filename)) {
                    $profile_pic = $filename;
                }
            }
        }
        
        if ($action === 'add') {
            // Check if username or email exists
            $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $check->bind_param("ss", $username, $email);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows > 0) {
                setFlashMessage('error', "Username or email already exists!");
            } else {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                if ($profile_pic) {
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, name, phone, is_blocked, profile_pic) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssis", $username, $email, $password, $name, $phone, $is_blocked, $profile_pic);
                } else {
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, name, phone, is_blocked) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssi", $username, $email, $password, $name, $phone, $is_blocked);
                }
                
                if ($stmt->execute()) {
                    setFlashMessage('success', "User created successfully!");
                } else {
                    setFlashMessage('error', "Error creating user: " . $stmt->error);
                }
                $stmt->close();
            }
            $check->close();
            
        } elseif ($action === 'edit') {
            $user_id = intval($_POST['user_id']);
            
            // Check if username/email exists for other users
            $check = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $check->bind_param("ssi", $username, $email, $user_id);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows > 0) {
                setFlashMessage('error', "Username or email already exists!");
            } else {
                // Build query dynamically based on whether password and profile pic are provided
                $updates = [];
                $params = [];
                $types = "";
                
                $updates[] = "username = ?";
                $params[] = $username;
                $types .= "s";
                
                $updates[] = "email = ?";
                $params[] = $email;
                $types .= "s";
                
                $updates[] = "name = ?";
                $params[] = $name;
                $types .= "s";
                
                $updates[] = "phone = ?";
                $params[] = $phone;
                $types .= "s";
                
                $updates[] = "is_blocked = ?";
                $params[] = $is_blocked;
                $types .= "i";
                
                if (!empty($_POST['password'])) {
                    $updates[] = "password = ?";
                    $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $types .= "s";
                }
                
                if ($profile_pic) {
                    // Delete old profile pic
                    $old_res = $conn->query("SELECT profile_pic FROM users WHERE id='$user_id' LIMIT 1");
                    if ($old_res && $old_res->num_rows) {
                        $old_pic = $old_res->fetch_assoc()['profile_pic'];
                        if ($old_pic && file_exists($old_pic)) unlink($old_pic);
                    }
                    $updates[] = "profile_pic = ?";
                    $params[] = $profile_pic;
                    $types .= "s";
                }
                
                $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
                $params[] = $user_id;
                $types .= "i";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                
                if ($stmt->execute()) {
                    setFlashMessage('success', "User updated successfully!");
                } else {
                    setFlashMessage('error', "Error updating user: " . $stmt->error);
                }
                $stmt->close();
            }
            $check->close();
        }
        
        header("Location: admin.php#users");
        exit();
    }
}

// Delete user
if (isset($_GET['delete_user'])) {
    $uid = intval($_GET['delete_user']);
    
    // Prevent self-deletion
    if (isset($_SESSION['user_id']) && $uid == $_SESSION['user_id']) {
        setFlashMessage('error', "You cannot delete your own account!");
    } else {
        // Delete profile picture if exists
        $res = $conn->query("SELECT profile_pic FROM users WHERE id='$uid' LIMIT 1");
        if ($res && $res->num_rows) {
            $pic = $res->fetch_assoc()['profile_pic'];
            if ($pic && file_exists($pic)) unlink($pic);
        }
        
        $conn->query("DELETE FROM users WHERE id='$uid'");
        setFlashMessage('success', "User deleted successfully!");
    }
    header("Location: admin.php#users");
    exit();
}

// Toggle block status (using your is_blocked column)
if (isset($_GET['toggle_block'])) {
    $uid = intval($_GET['toggle_block']);
    $res = $conn->query("SELECT is_blocked FROM users WHERE id='$uid' LIMIT 1");
    if ($res && $res->num_rows) {
        $current = $res->fetch_assoc()['is_blocked'];
        $new = $current ? 0 : 1; // Toggle between 0 and 1
        $conn->query("UPDATE users SET is_blocked='$new' WHERE id='$uid'");
    }
    header("Location: admin.php#users");
    exit();
}

// -------------------
// BOOKING MANAGEMENT (Your existing code)
// -------------------
if (isset($_GET['approve_booking'])) {
    $id = intval($_GET['approve_booking']);
    $conn->query("UPDATE bookings SET status='approved' WHERE id='$id'");
    header("Location: admin.php#bookings");
    exit();
}

if (isset($_GET['reject_booking'])) {
    $id = intval($_GET['reject_booking']);
    $conn->query("UPDATE bookings SET status='rejected' WHERE id='$id'");
    header("Location: admin.php#bookings");
    exit();
}

if (isset($_GET['delete_booking'])) {
    $id = intval($_GET['delete_booking']);
    $conn->query("DELETE FROM bookings WHERE id='$id'");
    header("Location: admin.php#bookings");
    exit();
}

// -------------------
// FETCH DATA
// -------------------
$rooms_res = $conn->query("SELECT * FROM rooms ORDER BY id DESC");
$bookings_res = $conn->query("
    SELECT bookings.*, rooms.room_number 
    FROM bookings 
    JOIN rooms ON bookings.room_id = rooms.id 
    ORDER BY bookings.id DESC
");
$users_res = $conn->query("SELECT * FROM users ORDER BY id DESC");

// Get user data for editing
$edit_user = null;
if (isset($_GET['edit_user'])) {
    $edit_id = intval($_GET['edit_user']);
    $edit_res = $conn->query("SELECT * FROM users WHERE id='$edit_id' LIMIT 1");
    if ($edit_res && $edit_res->num_rows) {
        $edit_user = $edit_res->fetch_assoc();
    }
}

// Get room data for editing (existing functionality)
$edit_room = null;
if (isset($_GET['edit_room'])) {
    $edit_id = intval($_GET['edit_room']);
    $edit_res = $conn->query("SELECT * FROM rooms WHERE id='$edit_id' LIMIT 1");
    if ($edit_res && $edit_res->num_rows) {
        $edit_room = $edit_res->fetch_assoc();
    }
}

$flash = getFlashMessage();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel - Gokarna Forest Hotel</title>
<style>
body { font-family: Arial, sans-serif; margin:0; padding:0; background:#f3f4f6; }
.container { max-width:1200px; margin:20px auto; padding:0 16px; }
h2 { color:#111827; margin-top:0; }
.message { 
    padding:12px; 
    border-left:4px solid; 
    margin-bottom:15px; 
    border-radius:6px; 
    font-weight:500;
}
.message.success { color:#065f46; background:#dcfce7; border-color:#22c55e; }
.message.error { color:#991b1b; background:#fee2e2; border-color:#ef4444; }
table { width:100%; border-collapse: collapse; margin-bottom:20px; background:#fff; }
table th, table td { border:1px solid #d1d5db; padding:10px; text-align:left; }
table th { background:#3b82f6; color:#fff; }
table tr:hover { background:#f9fafb; }
.btn { padding:6px 10px; border-radius:6px; text-decoration:none; font-weight:600; margin-right:5px; display:inline-block; font-size:13px; border:none; cursor:pointer; }
.btn-edit { background:#facc15; color:#111827; }
.btn-delete { background:#ef4444; color:#fff; }
.btn-toggle { background:#3b82f6; color:#fff; }
.btn-block { background:#dc2626; color:#fff; }
.btn-unblock { background:#16a34a; color:#fff; }
.btn-approve { background:#22c55e; color:#fff; }
.btn-reject { background:#f97316; color:#fff; }
.btn:hover { opacity:0.85; }
.card { background:#fff; padding:20px; border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,0.1); margin-bottom:30px; }
input, select { padding:10px; border-radius:8px; border:1px solid #d1d5db; width:100%; margin-bottom:12px; box-sizing:border-box; }
button.submit-btn { background:#3b82f6; color:#fff; border:none; padding:12px 20px; border-radius:8px; font-weight:600; cursor:pointer; }
button.submit-btn:hover { background:#2563eb; }
.room-image, .profile-pic { max-width:80px; max-height:80px; border-radius:6px; object-fit:cover; }
.top-nav { display:flex; justify-content:space-between; margin-bottom:20px; }
.top-nav a { text-decoration:none; padding:8px 12px; background:#3b82f6; color:#fff; border-radius:6px; font-weight:600; }
.top-nav a:hover { background:#2563eb; }
.nav-tabs { display:flex; gap:10px; margin-bottom:20px; border-bottom:2px solid #e5e7eb; padding-bottom:10px; flex-wrap:wrap; }
.nav-tabs a { text-decoration:none; padding:10px 20px; background:#e5e7eb; color:#374151; border-radius:6px 6px 0 0; font-weight:600; cursor:pointer; }
.nav-tabs a.active { background:#3b82f6; color:#fff; }
.status-active { color:#16a34a; font-weight:bold; }
.status-blocked { color:#dc2626; font-weight:bold; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:15px; }
.form-grid input, .form-grid select { margin-bottom:0; }
.checkbox-wrapper { display:flex; align-items:center; gap:8px; margin:10px 0; }
.checkbox-wrapper input[type="checkbox"] { width:auto; margin:0; }
@media(max-width:768px){ 
    .top-nav{ flex-direction:column; gap:10px; } 
    .form-grid { grid-template-columns:1fr; }
    table { font-size:14px; }
    table th, table td { padding:6px; }
    .nav-tabs a { padding:8px 12px; font-size:14px; }
}
</style>
</head>
<body>
<div class="container">
    <div class="top-nav">
        <div>
            <a href="index.php">Go to Home</a>
        </div>
        <a href="logout.php">Logout Admin</a>
    </div>

    <?php if ($flash): ?>
        <div class="message <?php echo $flash['type']; ?>"><?php echo htmlspecialchars($flash['message']); ?></div>
    <?php endif; ?>

    <!-- Navigation Tabs -->
    <div class="nav-tabs">
        <a href="#rooms" class="active" onclick="showSection('rooms', this)">Rooms</a>
        <a href="#users" onclick="showSection('users', this)">Users</a>
        <a href="#bookings" onclick="showSection('bookings', this)">Bookings</a>
    </div>

    <!-- ROOMS SECTION -->
    <div id="rooms-section" class="section">
        <div class="card">
            <h2><?php echo $edit_room ? 'Edit' : 'Add'; ?> Room</h2>
            <?php if(isset($message)) echo "<div class='message success'>$message</div>"; ?>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="room_id" value="<?php echo $edit_room['id'] ?? ''; ?>">
                <div class="form-grid">
                    <input type="text" name="room_number" placeholder="Room Number" value="<?php echo htmlspecialchars($edit_room['room_number'] ?? ''); ?>" required>
                    <input type="text" name="room_type" placeholder="Room Type" value="<?php echo htmlspecialchars($edit_room['room_type'] ?? ''); ?>" required>
                    <input type="number" name="price" placeholder="Price" value="<?php echo htmlspecialchars($edit_room['price'] ?? ''); ?>" required>
                    <input type="file" name="room_image" accept="image/*">
                </div>
                <button type="submit" class="submit-btn" name="<?php echo $edit_room ? 'update_room' : ''; ?>" style="margin-top:10px;">
                    <?php echo $edit_room ? 'Update Room' : 'Save Room'; ?>
                </button>
                <?php if ($edit_room): ?>
                    <a href="admin.php#rooms" class="btn" style="background:#6b7280; color:#fff; margin-left:10px;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h2>Rooms Management</h2>
            <?php if($rooms_res && $rooms_res->num_rows > 0): ?>
            <div style="overflow-x:auto;">
                <table>
                    <tr>
                        <th>Room#</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                    <?php while($r=$rooms_res->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['room_number']); ?></td>
                        <td><?php echo htmlspecialchars($r['room_type']); ?></td>
                        <td>Rs <?php echo htmlspecialchars($r['price']); ?></td>
                        <td><?php echo htmlspecialchars($r['status']); ?></td>
                        <td><?php echo $r['image'] ? "<img src='".htmlspecialchars($r['image'])."' class='room-image'>" : "No image"; ?></td>
                        <td>
                            <a class='btn btn-edit' href='admin.php?edit_room=<?php echo $r['id']; ?>#rooms'>Edit</a>
                            <a class='btn btn-delete' href='admin.php?delete_room=<?php echo $r['id']; ?>' onclick="return confirm('Delete this room?');">Delete</a>
                            <a class='btn btn-toggle' href='admin.php?toggle_room=<?php echo $r['id']; ?>'>Toggle</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
            <?php else: ?>
                <p>No rooms found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- USERS SECTION (Adapted to your schema) -->
    <div id="users-section" class="section" style="display:none;">
        <div class="card">
            <h2><?php echo $edit_user ? 'Edit' : 'Add'; ?> User</h2>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="user_action" value="<?php echo $edit_user ? 'edit' : 'add'; ?>">
                <?php if ($edit_user): ?>
                    <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($edit_user['username'] ?? ''); ?>" required>
                    <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>" required>
                    <input type="password" name="password" placeholder="<?php echo $edit_user ? 'Leave blank to keep current' : 'Password'; ?>" <?php echo $edit_user ? '' : 'required'; ?>>
                    <input type="text" name="name" placeholder="Full Name" value="<?php echo htmlspecialchars($edit_user['name'] ?? ''); ?>" required>
                    <input type="text" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($edit_user['phone'] ?? ''); ?>">
                    <div>
                        <input type="file" name="profile_pic" accept="image/*" style="padding:8px;">
                        <?php if ($edit_user && $edit_user['profile_pic']): ?>
                            <small>Current: <img src="<?php echo htmlspecialchars($edit_user['profile_pic']); ?>" style="max-width:40px; vertical-align:middle;"></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="checkbox-wrapper">
                    <input type="checkbox" name="is_blocked" id="is_blocked" <?php echo ($edit_user['is_blocked'] ?? 0) ? 'checked' : ''; ?>>
                    <label for="is_blocked" style="font-weight:600; color:<?php echo ($edit_user['is_blocked'] ?? 0) ? '#dc2626' : '#16a34a'; ?>;">
                        Block this user (prevent login)
                    </label>
                </div>
                
                <button type="submit" class="submit-btn">
                    <?php echo $edit_user ? 'Update User' : 'Create User'; ?>
                </button>
                <?php if ($edit_user): ?>
                    <a href="admin.php#users" class="btn" style="background:#6b7280; color:#fff; margin-left:10px;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h2>User Management</h2>
            <?php if($users_res && $users_res->num_rows > 0): ?>
            <div style="overflow-x:auto;">
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Profile</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                    <?php while($u=$users_res->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td>
                            <?php if ($u['profile_pic']): ?>
                                <img src="<?php echo htmlspecialchars($u['profile_pic']); ?>" class="profile-pic">
                            <?php else: ?>
                                <div style="width:40px;height:40px;background:#e5e7eb;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#6b7280;font-size:12px;">No Pic</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['name']); ?></td>
                        <td><?php echo htmlspecialchars($u['phone'] ?? 'N/A'); ?></td>
                        <td class="<?php echo $u['is_blocked'] ? 'status-blocked' : 'status-active'; ?>">
                            <?php echo $u['is_blocked'] ? 'Blocked' : 'Active'; ?>
                        </td>
                        <td><?php echo date('Y-m-d', strtotime($u['created_at'])); ?></td>
                        <td>
                            <a class='btn btn-edit' href='admin.php?edit_user=<?php echo $u['id']; ?>#users'>Edit</a>
                            <a class='btn btn-delete' href='admin.php?delete_user=<?php echo $u['id']; ?>' onclick="return confirm('Delete user <?php echo htmlspecialchars($u['username']); ?>? This cannot be undone!');">Delete</a>
                            <a class='btn <?php echo $u['is_blocked'] ? 'btn-unblock' : 'btn-block'; ?>' href='admin.php?toggle_block=<?php echo $u['id']; ?>'>
                                <?php echo $u['is_blocked'] ? 'Unblock' : 'Block'; ?>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
            <?php else: ?>
                <p>No users found. Create your first user above.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- BOOKINGS SECTION -->
    <div id="bookings-section" class="section" style="display:none;">
        <div class="card">
            <h2>Booking Requests</h2>
            <?php if($bookings_res && $bookings_res->num_rows > 0): ?>
            <div style="overflow-x:auto;">
                <table>
                    <tr>
                        <th>Room</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Dates</th>
                        <th>Guests</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php while($b=$bookings_res->fetch_assoc()): 
                        $status_class = "status-".$b['status'];
                    ?>
                    <tr>
                        <td>Room <?php echo htmlspecialchars($b['room_number']); ?></td>
                        <td><?php echo htmlspecialchars($b['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($b['email']); ?><br><?php echo htmlspecialchars($b['phone']); ?></td>
                        <td><?php echo $b['check_in']; ?> → <?php echo $b['check_out']; ?></td>
                        <td><?php echo $b['guests']; ?></td>
                        <td class="<?php echo $status_class; ?>"><?php echo ucfirst($b['status']); ?></td>
                        <td>
                            <?php if($b['status']=="pending"): ?>
                                <a class='btn btn-approve' href='admin.php?approve_booking=<?php echo $b['id']; ?>#bookings'>Approve</a>
                                <a class='btn btn-reject' href='admin.php?reject_booking=<?php echo $b['id']; ?>#bookings'>Reject</a>
                            <?php endif; ?>
                            <a class='btn btn-delete' href='admin.php?delete_booking=<?php echo $b['id']; ?>' onclick="return confirm('Delete booking?');">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
            <?php else: ?>
                <p>No booking requests.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function showSection(section, element) {
    // Hide all sections
    document.querySelectorAll('.section').forEach(s => s.style.display = 'none');
    // Show selected section
    document.getElementById(section + '-section').style.display = 'block';
    
    // Update active tab
    document.querySelectorAll('.nav-tabs a').forEach(a => a.classList.remove('active'));
    if (element) element.classList.add('active');
    
    // Update URL hash
    window.location.hash = section;
}

// Show correct section based on URL hash on page load
window.onload = function() {
    const hash = window.location.hash.substring(1) || 'rooms';
    const tab = document.querySelector('.nav-tabs a[href="#' + hash + '"]');
    if (tab) {
        // Trigger click to show correct section
        showSection(hash, tab);
    }
};
</script>

</body>
</html>