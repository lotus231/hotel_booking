<?php
include 'breadcrumb.php';

// signin.php - secure login
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
include 'config.php';

$PROJECT_BASE = '/hotel_booking';

$ADMIN_USER = 'admin';
$ADMIN_PASS = 'admin1234';

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function is_safe_return($url){
    if (!$url) return false;
    if (preg_match('#^[a-zA-Z][a-zA-Z0-9+\-.]*://#', $url)) return false;
    if (strpos($url, '//') !== false) return false;
    if (preg_match('/[\r\n]/', $url)) return false;
    return preg_match('#^(/?[A-Za-z0-9_\-\/.?=&%+]*)$#', $url);
}

$error = '';
$return_to = '';
if (!empty($_REQUEST['return'])) $return_to = $_REQUEST['return'];
if (!is_safe_return($return_to)) $return_to = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login === '' || $password === ''){
        $error = 'Please enter username/email and password.';
    } else {
        if ($login === $ADMIN_USER && $password === $ADMIN_PASS){
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['user_name'] = 'Admin';
            $_SESSION['user_id'] = 0;
            $dest = $return_to ?: ($PROJECT_BASE . '/admin.php');
            header('Location: ' . $dest);
            exit;
        }

        $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ? OR username = ? LIMIT 1");
        if (!$stmt){
            $error = 'Database error.';
        } else {
            $stmt->bind_param('ss', $login, $login);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res && $res->num_rows ? $res->fetch_assoc() : null;
            $stmt->close();

            if (!$user){
                $error = 'No account found with that email or username.';
            } else {
                $hash = $user['password'] ?? '';
                $valid = false;
                if ($hash && password_verify($password, $hash)) $valid = true;
                else if ($hash === $password) $valid = true;

                if ($valid){
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['user_name'] = $user['name'] ?: $user['email'];
                    if ($return_to) header('Location: ' . $return_to); else header('Location: ' . $PROJECT_BASE . '/index.php');
                    exit;
                } else {
                    $error = 'Incorrect password.';
                }
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
<title>Sign In - Gokarna Forest Hotel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
  * {margin:0;padding:0;box-sizing:border-box;}
  
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #0c1220 0%, #141824 40%, #1a1f2e 100%);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    position: relative;
    overflow: hidden;
  }

  /* Animated background particles */
  body::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: 
      radial-gradient(circle at 20% 80%, rgba(212, 175, 55, 0.03) 0%, transparent 50%),
      radial-gradient(circle at 80% 20%, rgba(212, 175, 55, 0.03) 0%, transparent 50%),
      radial-gradient(circle at 40% 40%, rgba(212, 175, 55, 0.02) 0%, transparent 30%);
    pointer-events: none;
  }

  /* Luxury Auth Card */
  .auth-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(212, 175, 55, 0.15);
    border-radius: 24px;
    padding: 50px 40px;
    max-width: 420px;
    width: 100%;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6);
    position: relative;
    overflow: hidden;
    z-index: 1;
  }

  .auth-card::before {
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
    margin-bottom: 30px;
  }

  .hotel-brand h2 {
    font-size: 14px;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 3px;
    margin-bottom: 8px;
    font-weight: 600;
  }

  .hotel-brand h1 {
    font-size: 28px;
    font-weight: 900;
    color: #fff;
    position: relative;
    line-height: 1.2;
  }

  /* The blinking gold text effect - Different colors version */
  .hotel-brand h1 span {
    color: transparent;
    background: linear-gradient(
      90deg,
      #B8941F 0%,
      #D4AF37 20%,
      #F4D03F 40%,
      #FFF8DC 50%,  /* Bright champagne gold - the "blink" */
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

  /* Faster blink with color shift */
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

  /* Glow pulse behind text */
  .hotel-brand h1 span::after {
    content: 'Gokarna Forest Hotel';
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    color: transparent;
    background: linear-gradient(90deg, transparent, rgba(244, 208, 63, 0.6), transparent);
    background-size: 200% auto;
    -webkit-background-clip: text;
    background-clip: text;
    animation: glowPulse 2s ease-in-out infinite;
    filter: blur(4px);
    opacity: 0.5;
    pointer-events: none;
  }

  @keyframes glowPulse {
    0%, 100% { opacity: 0.3; background-position: 200% center; }
    50% { opacity: 0.8; background-position: -200% center; }
  }

  /* Sign In Title */
  .signin-title {
    text-align: center;
    color: #D4AF37;
    font-size: 24px;
    font-weight: 800;
    margin-bottom: 8px;
  }

  .muted {
    text-align: center;
    color: #64748b;
    font-size: 14px;
    margin-bottom: 30px;
  }

  /* Alert Messages */
  .message, .error {
    padding: 14px 16px;
    border-radius: 12px;
    font-size: 14px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
  }

  .message {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #86efac;
  }

  .error {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #fca5a5;
  }

  /* Form Styles */
  form {
    display: flex;
    flex-direction: column;
    gap: 20px;
  }

  .form-group {
    position: relative;
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

  .input-wrapper {
    position: relative;
  }

  .input-wrapper i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    font-size: 16px;
    transition: color 0.3s;
  }

  .form-group input {
    width: 100%;
    padding: 14px 16px 14px 48px;
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

  .form-group input:focus + i,
  .input-wrapper:focus-within i {
    color: #D4AF37;
  }

  .form-group input::placeholder {
    color: #475569;
  }

  /* Buttons */
  .btn {
    padding: 14px 24px;
    border: none;
    border-radius: 12px;
    font-weight: 800;
    cursor: pointer;
    font-size: 15px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
  }

  .btn-signin {
    background: linear-gradient(135deg, #D4AF37 0%, #B8941F 100%);
    color: #0f172a;
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
  }

  .btn-signin:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
  }

  .btn-signup {
    background: transparent;
    border: 1px solid rgba(212, 175, 55, 0.3);
    color: #D4AF37;
  }

  .btn-signup:hover {
    background: rgba(212, 175, 55, 0.1);
    border-color: #D4AF37;
  }

  .actions {
    display: flex;
    gap: 12px;
    margin-top: 10px;
  }

  .actions .btn {
    flex: 1;
  }

  /* Forgot Link */
  .forgot-link {
    text-align: center;
    font-size: 14px;
    color: #64748b;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid rgba(212, 175, 55, 0.1);
  }

  .forgot-link a {
    color: #D4AF37;
    text-decoration: none;
    font-weight: 700;
    transition: color 0.3s;
  }

  .forgot-link a:hover {
    color: #F4D03F;
    text-decoration: underline;
  }

  /* Decorative elements */
  .decoration {
    position: absolute;
    width: 100px;
    height: 100px;
    border: 1px solid rgba(212, 175, 55, 0.1);
    border-radius: 50%;
    pointer-events: none;
  }

  .decoration-1 {
    top: -50px;
    right: -50px;
  }

  .decoration-2 {
    bottom: -30px;
    left: -30px;
    width: 60px;
    height: 60px;
  }

  /* Responsive */
  @media(max-width: 480px) {
    .auth-card {
      padding: 40px 24px;
    }
    
    .hotel-brand h1 {
      font-size: 24px;
    }
    
    .hotel-brand h1 span::after {
      display: none; /* Remove glow on mobile for performance */
    }
  }
</style>
</head>
<body>

<div class="auth-card">
  <div class="decoration decoration-1"></div>
  <div class="decoration decoration-2"></div>
  
  <!-- Animated Gold Hotel Brand -->
  <div class="hotel-brand">
    <h2>Welcome to</h2>
    <h1><span>Gokarna Forest Hotel</span></h1>
  </div>

  <h3 class="signin-title"><i class="fas fa-lock"></i> Sign In</h3>
  <div class="muted">Access your account</div>

  <?php if($error): ?>
    <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo e($error); ?></div>
  <?php endif; ?>

  <form method="post" action="signin.php" novalidate>
    <input type="hidden" name="return" value="<?php echo e($return_to); ?>">

    <div class="form-group">
      <label for="login">Username or Email</label>
      <div class="input-wrapper">
        <i class="fas fa-user"></i>
        <input id="login" name="login" type="text" placeholder="Enter username or email" value="<?php echo e($_POST['login'] ?? ''); ?>" required autofocus>
      </div>
    </div>

    <div class="form-group">
      <label for="password">Password</label>
      <div class="input-wrapper">
        <i class="fas fa-lock"></i>
        <input id="password" name="password" type="password" placeholder="Enter your password" required>
      </div>
    </div>

    <div class="actions">
      <button type="submit" class="btn btn-signin"><i class="fas fa-sign-in-alt"></i> Sign In</button>
      <a href="signup.php" class="btn btn-signup"><i class="fas fa-user-plus"></i> Sign Up</a>
    </div>

    <div class="forgot-link">
      Forgot password? <a href="forgot.php">Reset here</a>
    </div>
  </form>
</div>

<script>
document.getElementById('login') && document.getElementById('login').focus();
</script>

</body>
</html>