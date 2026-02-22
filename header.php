<?php
//if (session_status() !== PHP_SESSION_ACTIVE) session_start();
?>

<style>
:root{
  --lux-dark: #0f172a;
  --lux-deep: #111827;
  --lux-gold: #D4AF37;
  --lux-gold-light: #f5d76e;
  --lux-white: #ffffff;
  --lux-muted: #cbd5e1;
  --lux-glass: rgba(255,255,255,0.08);
}


/* HEADER BASE */
.site-header{
  position: fixed;
  width: 100%;
  top: 0;
  z-index: 999;
  backdrop-filter: blur(10px);
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #111827 100%);
  box-shadow: 0 8px 30px rgba(0,0,0,0.4);
  border-bottom: 1px solid rgba(212,175,55,0.2);
}

.header-inner{
  max-width: 1200px;
  margin: auto;
  padding: 14px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* LOGO - Golden Animated */
.brand-link{
  font-family: "Georgia", serif;
  font-size: 34px;
  font-weight: bold;
  text-decoration: none;
  letter-spacing: 1px;
  position: relative;
  
  /* Golden animated text effect */
  color: transparent;
  background: linear-gradient(
    90deg,
    #ff0000 0%,
    #d8fb0f 25%,
    #ffffff 50%,
    #f9ce0d 75%,
    #000000 100%
  );
  background-size: 200% auto;
  -webkit-background-clip: text;
  background-clip: text;
  animation: goldShine 3s linear infinite;
  text-shadow: 0 0 30px rgba(212, 175, 55, 0.3);
}

/* Glow pulse effect behind text */
.brand-link::after{
  content: 'Gokarna Forest Resort';
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  color: transparent;
  background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.4), transparent);
  background-size: 200% auto;
  -webkit-background-clip: text;
  background-clip: text;
  animation: glowPulse 2s ease-in-out infinite;
  pointer-events: none;
  filter: blur(4px);
  opacity: 0.5;
  font-family: "Georgia", serif;
  font-size: 34px;
  font-weight: bold;
  letter-spacing: 1px;
}

@keyframes goldShine {
  0% { background-position: 200% center; }
  100% { background-position: -200% center; }
}

@keyframes glowPulse {
  0%, 100% { opacity: 0.3; }
  50% { opacity: 0.7; }
}

.brand-link:hover{
  animation: goldShine 1.5s linear infinite;
  text-shadow: 0 0 40px rgba(212,175,55,0.6);
}

/* NAVIGATION */
.nav{
  display: flex;
  gap: 25px;
}

.nav a{
  color: var(--lux-white);
  text-decoration: none;
  font-weight: 600;
  position: relative;
  padding: 6px 0;
  transition: 0.3s;
}

.nav a::after{
  content: '';
  position: absolute;
  bottom: -4px;
  left: 0;
  width: 0%;
  height: 2px;
  background: var(--lux-gold);
  transition: width 0.3s ease;
}

.nav a:hover{
  color: var(--lux-gold);
}

.nav a:hover::after{
  width: 100%;
}

/* BUTTONS */
.btn{
  padding: 8px 14px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: bold;
  transition: 0.3s;
}

/* GOLD BUTTON */
.btn-gold{
  background: linear-gradient(45deg, var(--lux-gold), var(--lux-gold-light));
  color: #111;
  box-shadow: 0 4px 20px rgba(212,175,55,0.4);
}

.btn-gold:hover{
  transform: translateY(-3px);
  box-shadow: 0 6px 30px rgba(212,175,55,0.6);
}

/* LOGIN BUTTON */
.btn-outline{
  border: 1px solid var(--lux-gold);
  color: var(--lux-gold);
}

.btn-outline:hover{
  background: var(--lux-gold);
  color: #111;
}

/* USER MENU */
.user-area{
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-btn{
  background: var(--lux-glass);
  border: 1px solid rgba(255,255,255,0.2);
  color: var(--lux-gold);
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
}

.user-menu{
  position: absolute;
  top: 60px;
  right: 20px;
  background: #1e293b;
  border-radius: 8px;
  padding: 8px;
  display: none;
  box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}

.user-menu a{
  display: block;
  padding: 8px 14px;
  color: var(--lux-white);
  text-decoration: none;
}

.user-menu a:hover{
  background: rgba(212,175,55,0.2);
  color: var(--lux-gold);
}

.user-menu.open{
  display: block;
}

/* MOBILE */
.nav-toggle{
  display: none;
  font-size: 22px;
  background: none;
  border: none;
  color: var(--lux-gold);
  cursor: pointer;
}

/* NAVIGATION ARROWS */
.nav-arrows{
  display: flex;
  gap: 8px;
  margin-left: 15px;
}

.nav-arrow{
  background: var(--lux-glass);
  border: 1px solid rgba(212,175,55,0.3);
  color: var(--lux-gold);
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 16px;
  transition: all 0.3s ease;
  text-decoration: none;
}

.nav-arrow:hover{
  background: var(--lux-gold);
  color: #111;
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(212,175,55,0.4);
}

.nav-arrow:disabled{
  opacity: 0.4;
  cursor: not-allowed;
  transform: none;
}

@media(max-width:768px){
  .nav{
    display: none;
    flex-direction: column;
    background: #111827;
    position: absolute;
    top: 70px;
    left: 0;
    width: 100%;
    padding: 20px;
  }

  .nav.open{
    display: flex;
  }

  .nav-toggle{
    display: block;
  }
}

/* Hide on scroll animation */
.site-header{
  transition: transform 0.4s ease, box-shadow 0.3s ease;
}

/* Hidden state */
.site-header.hide{
  transform: translateY(-100%);
}

/* Add stronger shadow after scroll */
.site-header.scrolled{
  box-shadow: 0 12px 40px rgba(0,0,0,0.6);
}

body {
    padding-top: 90px;
}
</style>

<header class="site-header" id="mainHeader">
  <div class="header-inner">

    <a class="brand-link" href="index.php">
      Gokarna Forest Resort
    </a>

    <button class="nav-toggle" id="navToggle">☰</button>

    <nav class="nav" id="navMenu">
      <a href="index.php">Home</a>
      <a href="rooms.php">Rooms</a>
      <a href="contact.php">Contact</a>
      <a href="about.php">About Us</a>
    </nav>

    <div class="user-area">
      <?php if(!empty($_SESSION['user_name'])): ?>
        <button class="user-btn" id="userBtn">
          <?php echo htmlspecialchars($_SESSION['user_name']); ?> ▾
        </button>
        <div class="user-menu" id="userMenu">
          <a href="profile.php">Profile</a>
          <a href="mybooking.php">My Bookings</a>
          <a href="logout.php">Logout</a>
        </div>
      <?php else: ?>
        <a href="signin.php" class="btn btn-outline">Login</a>
        <a href="signup.php" class="btn btn-gold">Sign Up</a>
      <?php endif; ?>
    </div>

  </div>
</header>

<script>
// Hide on scroll effect
let lastScrollTop = 0;
const header = document.getElementById("mainHeader");

window.addEventListener("scroll", function() {
  let scrollTop = window.pageYOffset || document.documentElement.scrollTop;

  if (scrollTop > lastScrollTop && scrollTop > 80) {
    header.classList.add("hide");
  } else {
    header.classList.remove("hide");
  }

  if (scrollTop > 10) {
    header.classList.add("scrolled");
  } else {
    header.classList.remove("scrolled");
  }

  lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
});

// Mobile menu toggle
document.getElementById('navToggle')?.addEventListener('click', function(){
  document.getElementById('navMenu').classList.toggle('open');
});

// User dropdown
const userBtn = document.getElementById('userBtn');
const userMenu = document.getElementById('userMenu');
if(userBtn){
  userBtn.addEventListener('click', function(){
    userMenu.classList.toggle('open');
  });
}
</script>