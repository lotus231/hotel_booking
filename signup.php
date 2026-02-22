<?php
include "config.php";
include 'breadcrumb.php';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!$name || !$email || !$username || !$password) {
        $message = "Please fill in all fields.";
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO users (name, email, username, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $username, $hashed_password);

        if ($stmt->execute()) {
            $message = "Signup successful! <a href='signin.php'>Sign in here</a>";
        } else {
            $message = "Error: " . htmlspecialchars($stmt->error);
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup - Gokarna Forest Hotel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {margin:0; padding:0; box-sizing:border-box;}

        html {
            min-height: 100%;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0c1220 0%, #141824 40%, #1a1f2e 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 40px 20px;
            position: relative;
        }

        /* Animated background particles */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(212, 175, 55, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(212, 175, 55, 0.03) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        /* Luxury Signup Card */
        .signup-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(212, 175, 55, 0.15);
            border-radius: 24px;
            padding: 40px 35px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6);
            position: relative;
            overflow: hidden;
            z-index: 1;
            margin: auto;
        }

        .signup-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #B8941F, #D4AF37, #F4D03F, #D4AF37, #B8941F);
            background-size: 200% auto;
            animation: goldShine 3s linear infinite;
        }

        @keyframes goldShine {
            0% { background-position: 200% center; }
            100% { background-position: -200% center; }
        }

        /* Animated Gold Hotel Name */
        .hotel-brand {
            text-align: center;
            margin-bottom: 25px;
        }

        .hotel-brand h2 {
            font-size: 13px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .hotel-brand h1 {
            font-size: 26px;
            font-weight: 900;
            color: #fff;
            position: relative;
            line-height: 1.2;
        }

        .hotel-brand h1 span {
            color: transparent;
            background: linear-gradient(
                90deg,
                #B8941F 0%,
                #D4AF37 20%,
                #F4D03F 40%,
                #FFF8DC 50%,
                #F4D03F 60%,
                #D4AF37 80%,
                #B8941F 100%
            );
            background-size: 300% auto;
            -webkit-background-clip: text;
            background-clip: text;
            display: block;
            animation: goldBlink 2.5s ease-in-out infinite;
            text-shadow: 0 0 30px rgba(212, 175, 55, 0.4);
            position: relative;
        }

        @keyframes goldBlink {
            0%, 100% { 
                background-position: 0% center;
                filter: brightness(1);
            }
            50% { 
                background-position: 100% center;
                filter: brightness(1.3);
            }
        }

        /* Signup Title */
        .signup-title {
            text-align: center;
            color: #D4AF37;
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .muted {
            text-align: center;
            color: #64748b;
            font-size: 13px;
            margin-bottom: 25px;
        }

        /* Alert Messages */
        .message {
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }

        .message.success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .message a {
            color: #D4AF37;
            text-decoration: underline;
        }

        /* Form Styles */
        form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .form-group {
            position: relative;
        }

        .form-group label {
            display: block;
            color: #D4AF37;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 14px;
            transition: color 0.3s;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #D4AF37;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.15);
        }

        .form-group input:focus + i,
        .input-wrapper:focus-within i {
            color: #D4AF37;
        }

        .form-group input::placeholder {
            color: #475569;
        }

        /* Submit Button */
        button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #D4AF37 0%, #B8941F 100%);
            color: #0f172a;
            font-weight: 800;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
        }

        /* Signin Link */
        .signin-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(212, 175, 55, 0.1);
            font-size: 13px;
            color: #64748b;
        }

        .signin-link a {
            color: #D4AF37;
            text-decoration: none;
            font-weight: 700;
            transition: color 0.3s;
        }

        .signin-link a:hover {
            color: #F4D03F;
            text-decoration: underline;
        }

        /* Decorative elements */
        .decoration {
            position: absolute;
            width: 80px;
            height: 80px;
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 50%;
            pointer-events: none;
        }

        .decoration-1 {
            top: -40px;
            right: -40px;
        }

        .decoration-2 {
            bottom: -20px;
            left: -20px;
            width: 50px;
            height: 50px;
        }

        /* Responsive */
        @media(max-width: 480px) {
            body {
                padding: 20px 15px;
            }
            
            .signup-card {
                padding: 35px 20px;
            }
            
            .hotel-brand h1 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>

<div class="signup-card">
    <div class="decoration decoration-1"></div>
    <div class="decoration decoration-2"></div>
    
    <!-- Animated Gold Hotel Brand -->
    <div class="hotel-brand">
        <h2>Join</h2>
        <h1><span>Gokarna Forest Hotel</span></h1>
    </div>

    <h3 class="signup-title"><i class="fas fa-user-plus"></i> Create Account</h3>
    <div class="muted">Start your luxury experience</div>

    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, 'successful') !== false ? 'success' : 'error'; ?>">
            <i class="fas <?php echo strpos($message, 'successful') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="name">Full Name</label>
            <div class="input-wrapper">
                <i class="fas fa-user"></i>
                <input type="text" name="name" id="name" placeholder="Your full name" required>
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <div class="input-wrapper">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" id="email" placeholder="you@example.com" required>
            </div>
        </div>

        <div class="form-group">
            <label for="username">Username</label>
            <div class="input-wrapper">
                <i class="fas fa-id-card"></i>
                <input type="text" name="username" id="username" placeholder="Choose a username" required>
            </div>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrapper">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Create a strong password" required>
            </div>
        </div>

        <button type="submit">
            <i class="fas fa-user-plus"></i> Sign Up
        </button>
    </form>

    <div class="signin-link">
        Already have an account? <a href="signin.php">Sign In</a>
    </div>
</div>

</body>
</html>