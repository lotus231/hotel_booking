<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
include 'config.php';

// Fetch rooms
$res = $conn->query("SELECT * FROM rooms ORDER BY id DESC");
$rooms = [];
if ($res && $res->num_rows > 0) {
    while($r = $res->fetch_assoc()) $rooms[] = $r;
}

// Function to check room availability
function isRoomAvailable($conn, $room_id, $check_in = null, $check_out = null) {
    // Default: check for today
    if (!$check_in) $check_in = date('Y-m-d');
    if (!$check_out) $check_out = date('Y-m-d', strtotime('+1 day'));
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as booking_count 
        FROM bookings 
        WHERE room_id = ? 
        AND status = 'confirmed'
        AND (
            (check_in <= ? AND check_out > ?) OR
            (check_in < ? AND check_out >= ?) OR
            (check_in >= ? AND check_out <= ?)
        )
    ");
    $stmt->bind_param("issssss", $room_id, $check_out, $check_in, $check_out, $check_in, $check_in, $check_out);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['booking_count'] == 0; // true if available
}

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Rooms - Gokarna Forest Resort</title>
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

    /* ===== COMPACT HERO SECTION ===== */
    .rooms-hero {
      position: relative;
      min-height: 350px;
      display: flex;
      align-items: center;
      overflow: hidden;
      padding: 30px 0;
    }

    .rooms-hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(17, 24, 39, 0.8) 50%, rgba(30, 41, 59, 0.7) 100%);
      z-index: 1;
    }

    .rooms-hero-bg {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-size: cover;
      background-position: center;
      background-image: url('images/rooms/header3.jpg');
    }

    .rooms-hero-content {
      position: relative;
      z-index: 2;
      max-width: 1200px;
      margin: 0 auto;
      padding: 30px 20px;
      width: 100%;
      text-align: center;
    }

    /* Animated Gold Text */
    .rooms-hero h1 {
      font-size: 36px;
      font-weight: 700;
      color: #fff;
      margin: 0 0 12px 0;
      line-height: 1.2;
    }

    .rooms-hero h1 span {
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
      display: inline-block;
      animation: goldShine 3s linear infinite;
    }

    @keyframes goldShine {
      0% { background-position: 200% center; }
      100% { background-position: -200% center; }
    }

    .rooms-hero p {
      font-size: 15px;
      color: #cbd5e1;
      max-width: 500px;
      margin: 0 auto 24px;
    }

    .hero-stats {
      display: flex;
      justify-content: center;
      gap: 24px;
      margin-bottom: 24px;
      flex-wrap: wrap;
    }

    .hero-stat {
      text-align: center;
      padding: 12px 24px;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(212, 175, 55, 0.2);
      border-radius: 12px;
    }

    .hero-stat-number {
      font-size: 24px;
      font-weight: 800;
      color: #D4AF37;
      display: block;
    }

    .hero-stat-label {
      font-size: 11px;
      color: #94a3b8;
      text-transform: uppercase;
    }

    /* ===== FILTER SECTION - WARM CREAM ===== */
    .filter-section {
      padding: 40px 20px;
      background: linear-gradient(135deg, #faf8f5 0%, #f5f0e8 50%, #faf8f5 100%);
      color: #1e293b;
    }

    .filter-container {
      max-width: 1200px;
      margin: 0 auto;
    }

    .date-filter {
      display: flex;
      gap: 16px;
      align-items: center;
      margin-bottom: 24px;
      flex-wrap: wrap;
    }

    .date-input-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .date-input-group label {
      font-size: 12px;
      color: #64748b;
      font-weight: 600;
      text-transform: uppercase;
    }

    .date-input-group input {
      padding: 10px 14px;
      border: 1px solid rgba(212, 175, 55, 0.3);
      border-radius: 10px;
      font-size: 14px;
      background: #fff;
      color: #1e293b;
    }

    .btn-check {
      padding: 10px 20px;
      background: linear-gradient(135deg, #D4AF37, #B8941F);
      color: #0f172a;
      border: none;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      margin-top: 18px;
    }

    .filter-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      padding: 20px 24px;
      background: rgba(255, 255, 255, 0.8);
      border: 1px solid rgba(212, 175, 55, 0.2);
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }

    .filter-tabs {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .filter-tab {
      padding: 10px 20px;
      background: transparent;
      border: 1px solid rgba(212, 175, 55, 0.3);
      border-radius: 25px;
      color: #64748b;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
    }

    .filter-tab:hover,
    .filter-tab.active {
      background: linear-gradient(135deg, #D4AF37, #B8941F);
      color: #0f172a;
      border-color: transparent;
    }

    .results-count {
      color: #64748b;
      font-size: 14px;
    }

    .results-count span {
      color: #B8941F;
      font-weight: 700;
    }

    /* ===== ROOMS GRID SECTION - CLEAN WHITE ===== */
    .rooms-section {
      padding: 60px 20px;
      background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 50%, #ffffff 100%);
      color: #1e293b;
    }

    .rooms-container {
      max-width: 1200px;
      margin: 0 auto;
    }

    .rooms-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 24px;
    }

    /* Room Card */
    .room-card {
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid rgba(212, 175, 55, 0.15);
      border-radius: 20px;
      overflow: hidden;
      transition: all 0.4s ease;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
      display: flex;
      flex-direction: column;
      position: relative;
    }

    .room-card:hover {
      transform: translateY(-12px);
      border-color: rgba(212, 175, 55, 0.4);
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    }

    /* Availability Badge */
    .availability-badge {
      position: absolute;
      top: 12px;
      right: 12px;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      z-index: 10;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .available {
      background: linear-gradient(135deg, #22c55e, #16a34a);
      color: #fff;
    }

    .booked {
      background: linear-gradient(135deg, #ef4444, #dc2626);
      color: #fff;
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

    .room-badges {
      position: absolute;
      top: 12px;
      left: 12px;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
    }

    .badge-featured {
      background: linear-gradient(135deg, #D4AF37, #B8941F);
      color: #0f172a;
    }

    .badge-type {
      background: rgba(15, 23, 42, 0.9);
      color: #D4AF37;
      border: 1px solid rgba(212, 175, 55, 0.3);
    }

    .room-price-badge {
      position: absolute;
      bottom: 12px;
      right: 12px;
      background: rgba(15, 23, 42, 0.95);
      padding: 8px 16px;
      border-radius: 12px;
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

    .room-price-badge .period {
      color: #94a3b8;
      font-size: 11px;
    }

    .room-info {
      padding: 20px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .room-header {
      margin-bottom: 16px;
    }

    .room-title {
      font-size: 18px;
      font-weight: 800;
      color: #1e293b;
      margin: 0 0 6px 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .room-title i {
      color: #B8941F;
    }

    .room-meta {
      color: #64748b;
      font-size: 13px;
    }

    .room-features {
      display: flex;
      gap: 12px;
      margin-bottom: 16px;
      padding-bottom: 16px;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .feature {
      display: flex;
      align-items: center;
      gap: 4px;
      color: #64748b;
      font-size: 12px;
    }

    .feature i {
      color: #B8941F;
    }

    .room-actions {
      margin-top: auto;
      display: flex;
      gap: 8px;
    }

    .btn-small {
      flex: 1;
      padding: 10px 16px;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 700;
      text-decoration: none;
      text-align: center;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .btn-view {
      background: rgba(30, 41, 59, 0.05);
      color: #64748b;
      border: 1px solid rgba(30, 41, 59, 0.1);
    }

    .btn-view:hover {
      background: rgba(30, 41, 59, 0.1);
      color: #1e293b;
    }

    .btn-book {
      background: linear-gradient(135deg, #D4AF37 0%, #B8941F 100%);
      color: #0f172a;
      box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
    }

    .btn-book:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
    }

    .btn-book:disabled {
      background: #94a3b8;
      cursor: not-allowed;
      box-shadow: none;
    }

    /* Success Message */
    .message {
      padding: 16px 24px;
      border-radius: 12px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 600;
      animation: slideDown 0.4s ease;
      background: rgba(34, 197, 94, 0.1);
      border: 1px solid rgba(34, 197, 94, 0.3);
      color: #16a34a;
    }

    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 80px 20px;
      grid-column: 1 / -1;
    }

    .empty-state i {
      font-size: 64px;
      color: rgba(212, 175, 55, 0.3);
      margin-bottom: 20px;
    }

    .empty-state h3 {
      color: #1e293b;
      font-size: 24px;
      margin-bottom: 8px;
    }

    .empty-state p {
      color: #64748b;
    }

    /* ===== RESPONSIVE ===== */
    @media(max-width: 1200px) {
      .rooms-grid { grid-template-columns: repeat(3, 1fr); }
    }

    @media(max-width: 900px) {
      .rooms-grid { grid-template-columns: repeat(2, 1fr); }
      .date-filter { flex-direction: column; align-items: stretch; }
      .btn-check { margin-top: 0; }
    }

    @media(max-width: 560px) {
      .rooms-grid { grid-template-columns: 1fr; }
      .filter-bar { flex-direction: column; gap: 16px; }
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>

<!-- ===== HERO SECTION ===== -->
<section class="rooms-hero">
  <div class="rooms-hero-bg"></div>
  <div class="rooms-hero-content">
    <h1><span>Our Luxury Rooms</span></h1>
    <p>Experience comfort and elegance in our carefully curated selection of premium accommodations</p>
    
    <div class="hero-stats">
      <div class="hero-stat">
        <span class="hero-stat-number"><?php echo count($rooms); ?></span>
        <div class="hero-stat-label">Rooms</div>
      </div>
      <div class="hero-stat">
        <span class="hero-stat-number">24/7</span>
        <div class="hero-stat-label">Service</div>
      </div>
      <div class="hero-stat">
        <span class="hero-stat-number">4.9</span>
        <div class="hero-stat-label">Rating</div>
      </div>
    </div>
  </div>
</section>

<!-- ===== FILTER SECTION WITH DATE PICKER ===== -->
<section class="filter-section">
  <div class="filter-container">
    
    <!-- Date Filter Form -->
    <form method="GET" class="date-filter">
      <div class="date-input-group">
        <label>Check In</label>
        <input type="date" name="check_in" value="<?php echo $_GET['check_in'] ?? date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>">
      </div>
      <div class="date-input-group">
        <label>Check Out</label>
        <input type="date" name="check_out" value="<?php echo $_GET['check_out'] ?? date('Y-m-d', strtotime('+1 day')); ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
      </div>
      <button type="submit" class="btn-check">
        <i class="fas fa-search"></i> Check Availability
      </button>
    </form>

    <div class="filter-bar">
      <div class="filter-tabs">
        <a href="#" class="filter-tab active">All Rooms</a>
        <a href="#" class="filter-tab">Standard</a>
        <a href="#" class="filter-tab">Deluxe</a>
        <a href="#" class="filter-tab">Suite</a>
      </div>
      <div class="results-count">Showing <span><?php echo count($rooms); ?></span> rooms</div>
    </div>
  </div>
</section>

<!-- ===== ROOMS GRID SECTION ===== -->
<section class="rooms-section">
  <div class="rooms-container">
    
    <?php if(!empty($_SESSION['message'])): ?>
      <div class="message">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
      </div>
    <?php endif; ?>

    <div class="rooms-grid">
      <?php
      $exampleImages = [
        'images/rooms/1.jpg',
        'images/rooms/2.jpg',
        'images/rooms/3.jpg',
        'images/rooms/4.jfif',
      ];

      $count = max(1, count($exampleImages));
      
      // Get dates from URL or default
      $check_in = $_GET['check_in'] ?? date('Y-m-d');
      $check_out = $_GET['check_out'] ?? date('Y-m-d', strtotime('+1 day'));
      
      if (count($rooms) === 0): ?>
        
        <div class="empty-state">
          <i class="fas fa-hotel"></i>
          <h3>No Rooms Available</h3>
          <p>Please check back later for new accommodations</p>
        </div>
        
      <?php else: 
        foreach($rooms as $i => $r):
          $img = !empty($r['image']) ? $r['image'] : $exampleImages[$i % $count];
          $title = !empty($r['room_number']) ? 'Room ' . htmlspecialchars($r['room_number']) : htmlspecialchars($r['room_type']);
          $type = htmlspecialchars($r['room_type'] ?? 'Standard');
          $price = htmlspecialchars($r['price'] ?? '0');
          $id = (int)$r['id'];
          $isFeatured = $i < 2;
          
          // Check availability
          $isAvailable = isRoomAvailable($conn, $id, $check_in, $check_out);
      ?>

        <article class="room-card">
          
          <!-- Availability Badge -->
          <div class="availability-badge <?php echo $isAvailable ? 'available' : 'booked'; ?>">
            <i class="fas fa-<?php echo $isAvailable ? 'check-circle' : 'times-circle'; ?>"></i>
            <?php echo $isAvailable ? 'Available' : 'Booked'; ?>
          </div>

          <div class="room-image-wrap">
            <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($title); ?>" loading="lazy">
            
            <div class="room-badges">
              <?php if($isFeatured): ?>
                <span class="badge badge-featured"><i class="fas fa-star"></i> Featured</span>
              <?php endif; ?>
              <span class="badge badge-type"><?php echo $type; ?></span>
            </div>
            
            <div class="room-price-badge">
              <span class="currency">Rs</span>
              <span class="amount"><?php echo $price; ?></span>
              <span class="period">/night</span>
            </div>
          </div>
          
          <div class="room-info">
            <div class="room-header">
              <h3 class="room-title">
                <i class="fas fa-door-open"></i>
                <?php echo $title; ?>
              </h3>
              <div class="room-meta">
                <i class="fas fa-map-marker-alt"></i> Gokarna Forest
              </div>
            </div>
            
            <div class="room-features">
              <div class="feature">
                <i class="fas fa-wifi"></i> Free WiFi
              </div>
              <div class="feature">
                <i class="fas fa-bed"></i> King Bed
              </div>
              <div class="feature">
                <i class="fas fa-bath"></i> Bath
              </div>
            </div>
            
            <div class="room-actions">
              <a href="room_details.php?id=<?php echo $id; ?>" class="btn-small btn-view">
                <i class="fas fa-eye"></i> View
              </a>
              <?php if($isAvailable): ?>
                <a class="btn-small btn-book" href="book_room.php?room=<?php echo $id; ?>&check_in=<?php echo $check_in; ?>&check_out=<?php echo $check_out; ?>">
                  <i class="fas fa-calendar-check"></i> Book
                </a>
              <?php else: ?>
                <button class="btn-small btn-book" disabled>
                  <i class="fas fa-ban"></i> Booked
                </button>
              <?php endif; ?>
            </div>
          </div>
        </article>

      <?php endforeach; endif; ?>
    </div>
  </div>
</section>

<?php include 'footer.php'; ?>
</body>
</html>