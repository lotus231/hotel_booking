<?php
$css_file = "style.css";
echo "<link rel='stylesheet' href='" . $css_file . "'>";
session_start();
include 'config.php';

// get room id
if (!isset($_GET['room'])) {
    header('Location: rooms.php');
    exit();
}
$room_id = intval($_GET['room']);

// get room
$room_q = $conn->query("SELECT * FROM rooms WHERE id='$room_id' LIMIT 1");
if (!$room_q || $room_q->num_rows == 0) {
    header('Location: rooms.php');
    exit();
}
$room = $room_q->fetch_assoc();

// handle booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $ci = $_POST['check_in'];
    $co = $_POST['check_out'];
    $guests = intval($_POST['guests']);

    // ============================================
    // VALIDATION START
    // ============================================
    
    // 1. Check empty fields
    if (empty($full_name)) {
        $error = "Please enter your full name.";
    } elseif (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (empty($phone)) {
        $error = "Please enter your phone number.";
    } elseif (empty($ci)) {
        $error = "Please select check-in date.";
    } elseif (empty($co)) {
        $error = "Please select check-out date.";
    }
    
    // 2. Validate full name (only letters, spaces, minimum 3 characters)
    elseif (!preg_match('/^[a-zA-Z\s]{3,50}$/', $full_name)) {
        $error = "Name must contain only letters and spaces (3-50 characters).";
    }
    
    // 3. Validate email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address (e.g., user@example.com).";
    }
    
    // 4. Validate phone number (Nepal format: starts with 98 or 97, 10 digits)
    elseif (!preg_match('/^(98|97)[0-9]{8}$/', $phone)) {
        $error = "Please enter a valid 10-digit Nepali mobile number (starting with 98 or 97).";
    }
    
    // 5. Validate check-in date (not in past)
    elseif (strtotime($ci) < strtotime(date('Y-m-d'))) {
        $error = "Check-in date cannot be in the past.";
    }
    
    // 6. Validate check-out date (after check-in)
    elseif (strtotime($ci) >= strtotime($co)) {
        $error = "Check-out date must be after check-in date.";
    }
    
    // 7. Validate maximum stay (30 days)
    elseif ((strtotime($co) - strtotime($ci)) / (60 * 60 * 24) > 30) {
        $error = "Maximum booking duration is 30 days.";
    }
    
    // 8. Validate guests (1-10)
    elseif ($guests < 1 || $guests > 10) {
        $error = "Number of guests must be between 1 and 10.";
    }
    
    // ============================================
    // VALIDATION PASSED - PROCESS BOOKING
    // ============================================
    else {

        $ci_esc = $conn->real_escape_string($ci);
        $co_esc = $conn->real_escape_string($co);

        // real-time availability check
        $check = $conn->query("
            SELECT * FROM bookings 
            WHERE room_id='$room_id'
            AND status IN ('pending','approved')
            AND NOT (check_out <= '$ci_esc' OR check_in >= '$co_esc')
        ");

        if ($check && $check->num_rows > 0) {
            $error = "Sorry! This room is already booked for your selected dates. Please choose different dates.";
        } else {

            $stmt = $conn->prepare("INSERT INTO bookings 
                (room_id, full_name, email, phone, check_in, check_out, guests, booking_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')");

            $stmt->bind_param("isssssi", $room_id, $full_name, $email, $phone, $ci, $co, $guests);

            if ($stmt->execute()) {
                $success = "Booking request sent successfully! We will contact you soon for confirmation.";
                // Clear form data after success
                $full_name = $email = $phone = $ci = $co = '';
                $guests = 1;
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
    }
}

include 'header.php';
?>

<style>
/* Luxury Booking Page Styles */
.booking-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #0f172a 0%, #111827 40%, #1e293b 100%);
    padding: 60px 20px;
    display: flex;
    justify-content: center;
    align-items: flex-start;
}

.booking-wrapper {
    width: 100%;
    max-width: 800px;
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 0;
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    border: 1px solid rgba(212, 175, 55, 0.15);
    overflow: hidden;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6);
}

/* Room Preview Side */
.room-preview {
    background: linear-gradient(180deg, rgba(212, 175, 55, 0.1) 0%, rgba(15, 23, 42, 0.8) 100%);
    padding: 40px 30px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
}

.room-preview::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(212, 175, 55, 0.1) 0%, transparent 70%);
    animation: pulse 4s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

.room-image-container {
    position: relative;
    z-index: 1;
    margin-bottom: 20px;
}

.room-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 16px;
    border: 2px solid rgba(212, 175, 55, 0.3);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
}

.room-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: linear-gradient(135deg, #D4AF37, #B8941F);
    color: #0f172a;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.room-details {
    position: relative;
    z-index: 1;
    color: #fff;
}

.room-number {
    font-size: 32px;
    font-weight: 800;
    color: #D4AF37;
    margin: 0 0 8px 0;
    text-shadow: 0 2px 10px rgba(212, 175, 55, 0.3);
}

.room-type {
    font-size: 16px;
    color: #cbd5e1;
    margin-bottom: 20px;
    font-weight: 500;
}

.price-tag {
    display: flex;
    align-items: baseline;
    gap: 4px;
    margin-bottom: 24px;
}

.price-currency {
    font-size: 24px;
    color: #D4AF37;
    font-weight: 700;
}

.price-amount {
    font-size: 48px;
    color: #fff;
    font-weight: 900;
    line-height: 1;
}

.price-period {
    font-size: 14px;
    color: #94a3b8;
    margin-left: 4px;
}

.features-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.features-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
    color: #e2e8f0;
    font-size: 14px;
}

.features-list li::before {
    content: '✓';
    width: 22px;
    height: 22px;
    background: rgba(212, 175, 55, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #D4AF37;
    font-size: 12px;
    font-weight: 700;
}

/* Form Side */
.booking-form-container {
    padding: 40px;
    background: rgba(255, 255, 255, 0.02);
}

.form-header {
    margin-bottom: 30px;
}

.form-header h2 {
    color: #D4AF37;
    font-size: 28px;
    margin: 0 0 8px 0;
    font-weight: 800;
}

.form-header p {
    color: #94a3b8;
    font-size: 14px;
    margin: 0;
}

/* Alert Messages */
.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.alert::before {
    font-size: 18px;
}

.alert.error {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #fca5a5;
}

.alert.error::before { content: '⚠'; }

.alert.success {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #86efac;
}

.alert.success::before { content: '✓'; }

/* Form Styles */
.booking-form {
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

.form-group input,
.form-group select {
    width: 100%;
    padding: 14px 16px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(212, 175, 55, 0.2);
    border-radius: 12px;
    color: #fff;
    font-size: 15px;
    font-family: inherit;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #D4AF37;
    background: rgba(255, 255, 255, 0.08);
    box-shadow: 0 0 20px rgba(212, 175, 55, 0.1);
}

.form-group input::placeholder {
    color: #64748b;
}

/* Date Inputs Styling */
.date-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

/* Guests Input */
.guests-input {
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(212, 175, 55, 0.2);
    border-radius: 12px;
    overflow: hidden;
}

.guests-input button {
    width: 44px;
    height: 48px;
    background: transparent;
    border: none;
    color: #D4AF37;
    font-size: 20px;
    cursor: pointer;
    transition: all 0.2s;
}

.guests-input button:hover {
    background: rgba(212, 175, 55, 0.1);
}

.guests-input input {
    flex: 1;
    text-align: center;
    border: none !important;
    background: transparent !important;
    font-weight: 700;
    font-size: 16px;
}

/* Submit Button */
.btn-submit {
    width: 100%;
    padding: 18px;
    background: linear-gradient(135deg, #D4AF37 0%, #B8941F 100%);
    color: #0f172a;
    border: none;
    border-radius: 14px;
    font-size: 16px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 10px;
    position: relative;
    overflow: hidden;
}

.btn-submit::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(212, 175, 55, 0.3);
}

.btn-submit:hover::before {
    left: 100%;
}

.btn-submit:active {
    transform: translateY(0);
}

/* Back Link */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #94a3b8;
    text-decoration: none;
    font-size: 14px;
    margin-top: 24px;
    transition: color 0.3s;
}

.back-link:hover {
    color: #D4AF37;
}

/* Responsive */
@media (max-width: 768px) {
    .booking-wrapper {
        grid-template-columns: 1fr;
        max-width: 500px;
    }
    
    .room-preview {
        padding: 30px;
    }
    
    .booking-form-container {
        padding: 30px;
    }
    
    .date-group {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .booking-page {
        padding: 20px 15px;
    }
    
    .room-number {
        font-size: 24px;
    }
    
    .price-amount {
        font-size: 36px;
    }
}
</style>

<div class="booking-page">
    <div class="booking-wrapper">
        
        <!-- Room Preview Side -->
        <div class="room-preview">
            <div>
                <div class="room-image-container">
                    <?php 
                    $roomImage = !empty($room['image']) ? $room['image'] : 'images/rooms/1.jpg';
                    ?>
                    <img src="<?php echo htmlspecialchars($roomImage); ?>" alt="Room <?php echo htmlspecialchars($room['room_number']); ?>" class="room-image">
                    <span class="room-badge">Available</span>
                </div>
                
                <div class="room-details">
                    <h1 class="room-number">Room <?php echo htmlspecialchars($room['room_number']); ?></h1>
                    <p class="room-type"><?php echo htmlspecialchars($room['room_type']); ?></p>
                    
                    <div class="price-tag">
                        <span class="price-currency">Rs</span>
                        <span class="price-amount"><?php echo htmlspecialchars($room['price']); ?></span>
                        <span class="price-period">/night</span>
                    </div>
                    
                    <ul class="features-list">
                        <li>Free WiFi</li>
                        <li>Comfortable Bedding</li>
                        <li>Room Service</li>
                        <li>City View</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Booking Form Side -->
        <div class="booking-form-container">
            <div class="form-header">
                <h2>Book Your Stay</h2>
                <p>Complete the form below to reserve your room</p>
            </div>

            <?php if(isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(isset($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" class="booking-form" id="bookingForm">
                
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required 
                           value="<?php echo isset($_POST['full_name']) && !isset($success) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                           pattern="[a-zA-Z\s]{3,50}" title="Only letters and spaces allowed (3-50 characters)">
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" placeholder="your@email.com" required
                           value="<?php echo isset($_POST['email']) && !isset($success) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" placeholder="98XXXXXXXX" required
                           value="<?php echo isset($_POST['phone']) && !isset($success) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                           pattern="(98|97)[0-9]{8}" title="10-digit Nepali mobile number starting with 98 or 97"
                           maxlength="10">
                </div>

                <div class="date-group">
                    <div class="form-group">
                        <label for="check_in">Check-in Date *</label>
                        <input type="date" id="check_in" name="check_in" required
                               value="<?php echo isset($_POST['check_in']) && !isset($success) ? htmlspecialchars($_POST['check_in']) : ''; ?>"
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="check_out">Check-out Date *</label>
                        <input type="date" id="check_out" name="check_out" required
                               value="<?php echo isset($_POST['check_out']) && !isset($success) ? htmlspecialchars($_POST['check_out']) : ''; ?>"
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="guests">Number of Guests *</label>
                    <div class="guests-input">
                        <button type="button" onclick="adjustGuests(-1)">−</button>
                        <input type="number" id="guests" name="guests" min="1" max="10" 
                               value="<?php echo isset($_POST['guests']) && !isset($success) ? htmlspecialchars($_POST['guests']) : '1'; ?>" readonly>
                        <button type="button" onclick="adjustGuests(1)">+</button>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Confirm Booking</button>
                
                <a href="rooms.php" class="back-link">← Back to all rooms</a>
            </form>
        </div>
        
    </div>
</div>

<script>
// Guest counter functionality
function adjustGuests(change) {
    const input = document.getElementById('guests');
    let value = parseInt(input.value) + change;
    if (value >= 1 && value <= 10) {
        input.value = value;
    }
}

// Date validation - ensure check-out is after check-in
document.getElementById('check_in').addEventListener('change', function() {
    const checkIn = new Date(this.value);
    const checkOutInput = document.getElementById('check_out');
    const minCheckOut = new Date(checkIn);
    minCheckOut.setDate(minCheckOut.getDate() + 1);
    checkOutInput.min = minCheckOut.toISOString().split('T')[0];
    
    if (new Date(checkOutInput.value) <= checkIn) {
        checkOutInput.value = minCheckOut.toISOString().split('T')[0];
    }
});

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>

<?php include 'footer.php'; ?>