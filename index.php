<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
include "config.php";

$pageTitle = 'Gokarna Forest Hotel';

// Fetch rooms for homepage display
$res = $conn->query("SELECT * FROM rooms ORDER BY id DESC LIMIT 4");
$rooms = [];
if ($res && $res->num_rows > 0) {
    while($r = $res->fetch_assoc()) $rooms[] = $r;
}

// --- Weather widget: server-side fetch using OpenWeatherMap ---
$weather_html = '';
$default_city = 'Gokarna';
$city = $default_city;

// Allow override via GET but restrict to a short whitelist to avoid abuse
$allowed_cities = ['Gokarna','Kathmandu','Pokhara'];
if (!empty($_GET['city'])) {
  $sel = trim((string)$_GET['city']);
  if (in_array($sel, $allowed_cities, true)) {
    $city = $sel;
  }
}

// Force a fixed city for the widget per request: always show Kathmandu
$default_city = 'Kathmandu';
$city = $default_city;

// Obtain API key from config or env (or use provided key)
$owm_key = '';
if (defined('OPENWEATHER_API_KEY')) $owm_key = OPENWEATHER_API_KEY;
if (empty($owm_key)) $owm_key = getenv('OPENWEATHER_API_KEY') ?: '';

if (empty($owm_key)) {
  $owm_key = '7d6faa311352661061d318dff3aeb652';
}

if (!empty($owm_key)) {
    $q = urlencode($city);
    $url = "https://api.openweathermap.org/data/2.5/weather?q={$q}&units=metric&appid=" . urlencode($owm_key);
    $opts = [
        'http'=>[
            'method'=>'GET',
            'timeout'=>4,
            'header'=>'User-Agent: HotelBooking/1.0\r\n'
        ]
    ];
    $context = stream_context_create($opts);
    $resp = @file_get_contents($url, false, $context);
    $fetch_error = '';

    if ($resp === false && function_exists('curl_version')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'HotelBooking/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $curl_resp = curl_exec($ch);
        if ($curl_resp === false) {
            $fetch_error = 'Network error: ' . curl_error($ch);
        } else {
            $resp = $curl_resp;
        }
        curl_close($ch);
    } elseif ($resp === false) {
        $fetch_error = 'Network error or allow_url_fopen disabled';
    }

    if ($resp !== false && $resp !== null) {
        $d = json_decode($resp, true);
        $ok = false;
        if (is_array($d) && (isset($d['cod']) && ((int)$d['cod'] === 200))) $ok = true;
        if ($ok && isset($d['main']['temp'])) {
            $temp = round($d['main']['temp']);
            $desc = ucfirst($d['weather'][0]['description'] ?? '');
            $icon = $d['weather'][0]['icon'] ?? '';
            $icon_url = $icon ? "https://openweathermap.org/img/wn/{$icon}@2x.png" : '';

            ob_start();
            ?>
            <aside class="weather-card" aria-label="Current weather" aria-live="polite">
              <div class="weather-content">
                <?php if ($icon_url): ?>
                  <img src="<?php echo htmlspecialchars($icon_url); ?>" alt="<?php echo htmlspecialchars($desc); ?>">
                <?php endif; ?>
                <div class="weather-info">
                  <div class="weather-city"><?php echo htmlspecialchars($city); ?></div>
                  <div class="weather-desc"><?php echo htmlspecialchars($desc); ?></div>
                </div>
                <div class="weather-temp"><?php echo htmlspecialchars($temp); ?>°C</div>
              </div>
            </aside>
            <?php
            $weather_html = ob_get_clean();
        } else {
            $errMsg = '';
            if (is_array($d) && !empty($d['message'])) $errMsg = htmlspecialchars($d['message']);
            $weather_html = '<aside class="weather-card error"><div class="weather-title">Weather unavailable</div><div class="muted">'.($errMsg ?: 'Unable to fetch weather').'</div></aside>';
        }
    } else {
        $note = $fetch_error ? htmlspecialchars($fetch_error) : 'Unable to fetch weather data.';
        $weather_html = '<aside class="weather-card error"><div class="weather-title">Weather unavailable</div><div class="muted">'. $note .'</div></aside>';
    }
} else {
    $weather_html = '<aside class="weather-card error"><div class="weather-title">Weather unavailable</div><div class="muted">Set OPENWEATHER_API_KEY to enable</div></aside>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    /* ===== GLOBAL LUXURY DARK THEME ===== */
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #0f172a 0%, #111827 40%, #1e293b 100%);
      color: #ffffff;
      min-height: 100vh;
    }

    /* ===== HERO SECTION ===== */
    .hero-section {
      position: relative;
      min-height: 600px;
      display: flex;
      align-items: center;
      overflow: hidden;
    }

    .hero-bg {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
    }

    .hero-bg::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(15, 23, 42, 0.85) 0%, rgba(17, 24, 39, 0.7) 50%, rgba(30, 41, 59, 0.6) 100%);
    }

    .hero-content {
      position: relative;
      z-index: 2;
      max-width: 1200px;
      margin: 0 auto;
      padding: 60px 20px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 60px;
      align-items: center;
    }

   /* Animated Gold Text - Replace existing .hero-text h1 styles with this */
.hero-text h1 {
  font-size: 64px;
  font-weight: 900;
  color: #fff;
  margin: 0 0 20px 0;
  line-height: 1.1;
  position: relative;
  overflow: hidden;
}

.hero-text h1 span {
  color: transparent;
  background: linear-gradient(
    90deg,
    #B8941F 0%,
    #D4AF37 25%,
    #F4D03F 50%,
    #D4AF37 75%,
    #B8941F 100%
  );
  background-size: 200% auto;
  -webkit-background-clip: text;
  background-clip: text;
  display: block;
  animation: goldShine 3s linear infinite;
  text-shadow: 0 0 40px rgba(212, 175, 55, 0.3);
}

@keyframes goldShine {
  0% { background-position: 200% center; }
  100% { background-position: -200% center; }
}

/* Add glow pulse effect */
.hero-text h1::after {
  content: 'Gokarna Forest Hotel';
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
  filter: blur(8px);
  opacity: 0.6;
}

@keyframes glowPulse {
  0%, 100% { opacity: 0.3; }
  50% { opacity: 0.8; }
}

    .hero-text .lead {
      font-size: 20px;
      color: #cbd5e1;
      margin-bottom: 30px;
      line-height: 1.6;
      max-width: 500px;
    }

    .hero-stats {
      display: flex;
      gap: 40px;
      margin-bottom: 40px;
    }

    .hero-stat {
      text-align: center;
    }

    .hero-stat-number {
      font-size: 36px;
      font-weight: 900;
      color: #D4AF37;
      display: block;
    }

    .hero-stat-label {
      font-size: 14px;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .hero-actions {
      display: flex;
      gap: 16px;
    }

    .btn-primary {
      padding: 16px 32px;
      background: linear-gradient(135deg, #D4AF37 0%, #B8941F 100%);
      color: #0f172a;
      border: none;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 800;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      transition: all 0.3s ease;
      box-shadow: 0 10px 30px rgba(212, 175, 55, 0.3);
    }

    .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 40px rgba(212, 175, 55, 0.4);
    }

    .btn-secondary {
      padding: 16px 32px;
      background: rgba(255, 255, 255, 0.1);
      color: #fff;
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      font-size: 16px;
      font-weight: 700;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
    }

    .btn-secondary:hover {
      background: rgba(255, 255, 255, 0.2);
      border-color: #D4AF37;
    }

    /* Hero Side Panel */
    .hero-panel {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(212, 175, 55, 0.2);
      border-radius: 24px;
      padding: 40px;
      text-align: center;
    }

    .hero-panel h3 {
      color: #D4AF37;
      font-size: 24px;
      margin-bottom: 10px;
    }

    .hero-panel p {
      color: #94a3b8;
      margin-bottom: 20px;
    }

    /* Weather Card */
    .weather-card {
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(212, 175, 55, 0.2);
      border-radius: 16px;
      padding: 20px;
      margin-top: 20px;
    }

    .weather-content {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .weather-card img {
      width: 64px;
      height: 64px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 8px;
    }

    .weather-info {
      flex: 1;
      text-align: left;
    }

    .weather-city {
      font-weight: 800;
      font-size: 18px;
      color: #fff;
    }

    .weather-desc {
      font-size: 14px;
      color: #94a3b8;
      text-transform: capitalize;
    }

    .weather-temp {
      font-size: 32px;
      font-weight: 900;
      color: #D4AF37;
    }

    /* ===== FEATURES SECTION - WARM CREAM BACKGROUND ===== */
    .features-section {
      padding: 100px 20px;
      position: relative;
      background: linear-gradient(135deg, #faf8f5 0%, #f5f0e8 50%, #faf8f5 100%);
      color: #1e293b;
    }

    .features-section::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 800px;
      height: 800px;
      background: radial-gradient(circle, rgba(212, 175, 55, 0.08) 0%, transparent 70%);
      pointer-events: none;
    }

    .features-section .section-header h2 {
      color: #1e293b;
    }

    .features-section .section-header h2 span {
      color: #B8941F;
    }

    .features-section .section-header p {
      color: #64748b;
    }

    .section-header {
      text-align: center;
      margin-bottom: 60px;
      position: relative;
    }

    .section-header h2 {
      font-size: 42px;
      font-weight: 800;
      color: #fff;
      margin: 0 0 16px 0;
    }

    .section-header h2 span {
      color: #D4AF37;
    }

    .section-header p {
      color: #94a3b8;
      font-size: 18px;
      max-width: 600px;
      margin: 0 auto;
    }

    .features-container {
      display: flex;
      justify-content: center;
      gap: 30px;
      flex-wrap: wrap;
      max-width: 1200px;
      margin: 0 auto;
      position: relative;
    }

    .feature-box {
      background: rgba(255, 255, 255, 0.8);
      border: 1px solid rgba(212, 175, 55, 0.2);
      border-radius: 20px;
      padding: 40px 30px;
      width: 300px;
      text-align: center;
      transition: all 0.4s ease;
      position: relative;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }

    .feature-box::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, #D4AF37, #B8941F);
      transform: scaleX(0);
      transition: transform 0.4s ease;
    }

    .feature-box:hover {
      transform: translateY(-10px);
      border-color: rgba(212, 175, 55, 0.4);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
      background: rgba(255, 255, 255, 0.95);
    }

    .feature-box:hover::before {
      transform: scaleX(1);
    }

    .feature-icon {
      width: 80px;
      height: 80px;
      background: rgba(212, 175, 55, 0.1);
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 24px;
      font-size: 36px;
      color: #B8941F;
      transition: all 0.3s ease;
    }

    .feature-box:hover .feature-icon {
      background: rgba(212, 175, 55, 0.2);
      transform: scale(1.1);
    }

    .feature-box h3 {
      color: #1e293b;
      font-size: 22px;
      margin-bottom: 12px;
    }

    .feature-box p {
      color: #64748b;
      font-size: 15px;
      line-height: 1.7;
      margin: 0;
    }

    /* ===== ROOMS PREVIEW SECTION - SOFT WHITE BACKGROUND ===== */
    .rooms-preview {
      padding: 100px 20px;
      background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 50%, #ffffff 100%);
      color: #1e293b;
    }

    .rooms-preview .section-header h2 {
      color: #1e293b;
    }

    .rooms-preview .section-header h2 span {
      color: #B8941F;
    }

    .rooms-preview .section-header p {
      color: #64748b;
    }

    .rooms-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 24px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .room-card {
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid rgba(212, 175, 55, 0.15);
      border-radius: 20px;
      overflow: hidden;
      transition: all 0.4s ease;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }

    .room-card:hover {
      transform: translateY(-10px);
      border-color: rgba(212, 175, 55, 0.4);
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
      background: rgba(255, 255, 255, 1);
    }

    .room-image-wrap {
      position: relative;
      height: 200px;
      overflow: hidden;
    }

    .room-image-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.6s ease;
    }

    .room-card:hover .room-image-wrap img {
      transform: scale(1.1);
    }

    .room-price-badge {
      position: absolute;
      bottom: 12px;
      right: 12px;
      background: rgba(15, 23, 42, 0.95);
      padding: 8px 16px;
      border-radius: 10px;
      border: 1px solid rgba(212, 175, 55, 0.3);
    }

    .room-price-badge .currency {
      color: #D4AF37;
      font-size: 12px;
      font-weight: 700;
    }

    .room-price-badge .amount {
      color: #fff;
      font-size: 20px;
      font-weight: 900;
    }

    .room-info {
      padding: 20px;
    }

    .room-info h4 {
      color: #1e293b;
      font-size: 18px;
      margin: 0 0 8px 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .room-info h4 i {
      color: #B8941F;
    }

    .room-type {
      color: #64748b;
      font-size: 14px;
      margin-bottom: 16px;
    }

    .btn-book-small {
      width: 100%;
      padding: 12px;
      background: linear-gradient(135deg, #D4AF37 0%, #B8941F 100%);
      color: #0f172a;
      border: none;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 800;
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.3s ease;
    }

    .btn-book-small:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
    }

    .view-all-rooms {
      text-align: center;
      margin-top: 50px;
    }

    /* ===== GALLERY SECTION - WARM BEIGE BACKGROUND ===== */
    .gallery-section {
      padding: 100px 20px;
      position: relative;
      background: linear-gradient(135deg, #f5f0e8 0%, #faf8f5 50%, #f5f0e8 100%);
      color: #1e293b;
    }

    .gallery-section .section-header h2 {
      color: #1e293b;
    }

    .gallery-section .section-header h2 span {
      color: #B8941F;
    }

    .gallery-section .section-header p {
      color: #64748b;
    }

    .gallery-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .gallery-card {
      background: rgba(255, 255, 255, 0.8);
      border: 1px solid rgba(212, 175, 55, 0.15);
      border-radius: 20px;
      overflow: hidden;
      transition: all 0.4s ease;
      position: relative;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }

    .gallery-card.featured {
      grid-column: span 2;
      grid-row: span 2;
    }

    .gallery-card:hover {
      transform: translateY(-8px);
      border-color: rgba(212, 175, 55, 0.4);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
      background: rgba(255, 255, 255, 0.95);
    }

    .gallery-card img {
      width: 100%;
      height: 100%;
      min-height: 200px;
      object-fit: cover;
      transition: transform 0.6s ease;
    }

    .gallery-card:hover img {
      transform: scale(1.05);
    }

    .gallery-overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      padding: 30px;
      background: linear-gradient(transparent, rgba(15, 23, 42, 0.95));
    }

    .gallery-overlay h3 {
      color: #D4AF37;
      font-size: 24px;
      margin: 0 0 8px 0;
    }

    .gallery-overlay p {
      color: #cbd5e1;
      margin: 0;
      font-size: 15px;
    }

    /* ===== ABOUT SECTION - DARK BACKGROUND RETURNS ===== */
    .about-section {
      padding: 100px 20px;
      background: rgba(0, 0, 0, 0.2);
    }

    .about-content {
      max-width: 800px;
      margin: 0 auto;
      text-align: center;
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(212, 175, 55, 0.1);
      border-radius: 24px;
      padding: 60px;
      position: relative;
      overflow: hidden;
    }

    .about-content::before {
      content: '"';
      position: absolute;
      top: 20px;
      left: 30px;
      font-size: 120px;
      color: rgba(212, 175, 55, 0.1);
      font-family: Georgia, serif;
      line-height: 1;
    }

    .about-content h3 {
      font-size: 32px;
      color: #fff;
      margin-bottom: 20px;
    }

    .about-content p {
      color: #94a3b8;
      font-size: 18px;
      line-height: 1.8;
      margin: 0;
    }

    /* ===== RESPONSIVE ===== */
    @media(max-width: 1024px) {
      .hero-content {
        grid-template-columns: 1fr;
        text-align: center;
      }
      
      .hero-text .lead {
        margin-left: auto;
        margin-right: auto;
      }
      
      .hero-stats {
        justify-content: center;
      }
      
      .hero-actions {
        justify-content: center;
      }
      
      .rooms-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .gallery-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .gallery-card.featured {
        grid-column: span 2;
        grid-row: span 1;
      }
    }

    @media(max-width: 768px) {
      .hero-text h1 {
        font-size: 42px;
      }
      
      .rooms-grid {
        grid-template-columns: 1fr;
        max-width: 400px;
      }
      
      .gallery-grid {
        grid-template-columns: 1fr;
      }
      
      .gallery-card.featured {
        grid-column: span 1;
      }
      
      .features-container {
        flex-direction: column;
        align-items: center;
      }
      
      .feature-box {
        width: 100%;
        max-width: 350px;
      }
    }
  </style>
</head>
<body>

<?php include 'header.php'; ?>

<!-- ===== HERO SECTION ===== -->
<section class="hero-section">
  <div class="hero-bg" style="background-image: url('images/rooms/header3.jpg');"></div>
  
  <div class="hero-content">
    <div class="hero-text">
      <h1>Welcome</h1>
      <p class="lead">A tranquil retreat nestled among lush greenery — peaceful stays, warm hospitality, and unforgettable experiences await you.</p>
      
      <div class="hero-stats">
        <div class="hero-stat">
          <span class="hero-stat-number"><?php echo count($rooms); ?>+</span>
          <div class="hero-stat-label">Luxury Rooms</div>
        </div>
        <div class="hero-stat">
          <span class="hero-stat-number">4.9</span>
          <div class="hero-stat-label">Guest Rating</div>
        </div>
        <div class="hero-stat">
          <span class="hero-stat-number">24/7</span>
          <div class="hero-stat-label">Service</div>
        </div>
      </div>
      
      <div class="hero-actions">
        <a href="rooms.php" class="btn-primary">
          <i class="fas fa-calendar-check"></i> Book Now
        </a>
        <a href="#rooms" class="btn-secondary">
          <i class="fas fa-eye"></i> View Rooms
        </a>
      </div>
    </div>
    
    <div class="hero-panel">
      <h3><i class="fas fa-cloud-sun"></i> Current Weather</h3>
      <p>Plan your perfect getaway</p>
      <?php echo $weather_html; ?>
      
      <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid rgba(212, 175, 55, 0.2);">
        <div style="font-size: 14px; color: #94a3b8; margin-bottom: 8px;">Need Help?</div>
        <div style="font-size: 20px; color: #D4AF37; font-weight: 800;">
          <i class="fas fa-phone"></i> +977-9813535520
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== FEATURES SECTION ===== -->
<section class="features-section">
  <div class="section-header">
    <h2>Why Choose <span>Us</span></h2>
    <p>Experience the perfect blend of luxury, comfort, and nature at our premium forest retreat</p>
  </div>
  
  <div class="features-container">
    <div class="feature-box">
      <div class="feature-icon">
        <i class="fas fa-wifi"></i>
      </div>
      <h3>Free WiFi</h3>
      <p>High-speed internet access available in all rooms and public areas throughout your stay.</p>
    </div>

    <div class="feature-box">
      <div class="feature-icon">
        <i class="fas fa-bed"></i>
      </div>
      <h3>Luxury Bedding</h3>
      <p>Premium quality mattresses and linens designed for maximum comfort and relaxation.</p>
    </div>

    <div class="feature-box">
      <div class="feature-icon">
        <i class="fas fa-tv"></i>
      </div>
      <h3>Smart TV</h3>
      <p>Enjoy premium entertainment with flat-screen smart televisions in every room.</p>
    </div>
  </div>
</section>

<!-- ===== ROOMS PREVIEW SECTION ===== -->
<section class="rooms-preview" id="rooms">
  <div class="section-header">
    <h2>Featured <span>Rooms</span></h2>
    <p>Handpicked accommodations for your perfect stay</p>
  </div>
  
  <div class="rooms-grid">
    <?php
    $exampleImages = [
      'images/rooms/1.jpg',
      'images/rooms/2.jpg',
      'images/rooms/3.jpg',
      'images/rooms/4.jfif',
    ];
    $count = max(1, count($exampleImages));
    
    if (count($rooms) > 0):
      foreach($rooms as $i => $r):
        $img = !empty($r['image']) ? $r['image'] : $exampleImages[$i % $count];
        $title = !empty($r['room_number']) ? 'Room ' . htmlspecialchars($r['room_number']) : htmlspecialchars($r['room_type']);
        $type = htmlspecialchars($r['room_type'] ?? 'Standard');
        $price = htmlspecialchars($r['price'] ?? '0');
        $id = (int)$r['id'];
    ?>
      <div class="room-card">
        <div class="room-image-wrap">
          <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($title); ?>" loading="lazy">
          <div class="room-price-badge">
            <span class="currency">Rs</span>
            <span class="amount"><?php echo $price; ?></span>
          </div>
        </div>
        <div class="room-info">
          <h4><i class="fas fa-door-open"></i> <?php echo $title; ?></h4>
          <div class="room-type"><?php echo $type; ?></div>
          <a href="book_room.php?room=<?php echo $id; ?>" class="btn-book-small">
            <i class="fas fa-calendar-check"></i> Book Now
          </a>
        </div>
      </div>
    <?php endforeach; else: ?>
      <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
        <p style="color: #64748b;">No rooms available at the moment.</p>
      </div>
    <?php endif; ?>
  </div>
  
  <div class="view-all-rooms">
    <a href="rooms.php" class="btn-secondary" style="padding: 16px 40px; background: rgba(30, 41, 59, 0.1); color: #1e293b; border-color: rgba(30, 41, 59, 0.2);">
      <i class="fas fa-th-large"></i> View All Rooms
    </a>
  </div>
</section>

<!-- ===== GALLERY SECTION ===== -->
<section class="gallery-section">
  <div class="section-header">
    <h2>Explore Our <span>Facilities</span></h2>
    <p>World-class amenities designed for your comfort and enjoyment</p>
  </div>
  
  <div class="gallery-grid">
    <div class="gallery-card featured">
      <img src="images/rooms/swimming.jfif" alt="Swimming Pool">
      <div class="gallery-overlay">
        <h3><i class="fas fa-swimming-pool"></i> Swimming Pool</h3>
        <p>Relax and enjoy our outdoor swimming pool surrounded by nature</p>
      </div>
    </div>
    
    <div class="gallery-card">
      <img src="images/rooms/restaurant.jpg" alt="Restaurant">
      <div class="gallery-overlay">
        <h3><i class="fas fa-utensils"></i> Restaurant</h3>
        <p>Delicious meals in our cozy modern restaurant</p>
      </div>
    </div>
    
    <div class="gallery-card">
      <img src="images/rooms/spa.jfif" alt="Spa & Wellness">
      <div class="gallery-overlay">
        <h3><i class="fas fa-spa"></i> Spa & Wellness</h3>
        <p>Rejuvenate with our premium spa services</p>
      </div>
    </div>
    
    <div class="gallery-card">
      <img src="images/rooms/gym.jpg" alt="Gym">
      <div class="gallery-overlay">
        <h3><i class="fas fa-dumbbell"></i> Fitness Center</h3>
        <p>State-of-the-art equipment for your workout</p>
      </div>
    </div>
    
    <div class="gallery-card">
      <img src="images/rooms/library.jpg" alt="Library">
      <div class="gallery-overlay">
        <h3><i class="fas fa-book"></i> Library</h3>
        <p>Quiet reading corners for book lovers</p>
      </div>
    </div>
  </div>
</section>

<!-- ===== ABOUT SECTION ===== -->
<section class="about-section">
  <div class="about-content">
    <h3>About Gokarna Forest Hotel</h3>
    <p>Located near serene forests and scenic beaches, Gokarna Forest Hotel offers a peaceful escape with modern comforts and friendly service. Whether you're seeking adventure or relaxation, our dedicated team ensures every moment of your stay is memorable.</p>
  </div>
</section>

<?php include 'footer.php'; ?>
</body>
</html>