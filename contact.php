<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
include "config.php";
//include 'breadcrumb.php';
$pageTitle = 'Contact Us - Gokarna Forest Hotel';

// Handle form submission
$success_msg = $error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? 'General Inquiry');
    $message = trim($_POST['message'] ?? '');
    $priority = $_POST['priority'] ?? 'normal';

    // Validation
    if (!$name || !$email || !$message) {
        $error_msg = "Please fill in all required fields.";
    } elseif (strlen($name) < 2 || strlen($name) > 100) {
        $error_msg = "Name must be between 2 and 100 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } elseif ($phone && !preg_match('/^[0-9\s\-\+\(\)]{7,20}$/', $phone)) {
        $error_msg = "Please enter a valid phone number.";
    } elseif (strlen($message) < 10 || strlen($message) > 2000) {
        $error_msg = "Message must be between 10 and 2000 characters.";
    } else {
        // Sanitize inputs
        $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
        $subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // ============================================
        // OPTION 1: SAVE TO DATABASE (Recommended)
        // ============================================
        // Create this table first:
        /*
        CREATE TABLE contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            subject VARCHAR(200),
            message TEXT NOT NULL,
            priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
            status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );
        */
        
        $stmt = $conn->prepare("INSERT INTO contact_messages 
            (name, email, phone, subject, message, priority, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("ssssssss", $name, $email, $phone, $subject, $message, $priority, $ip_address, $user_agent);
            $db_saved = $stmt->execute();
            $stmt->close();
        }

        // ============================================
        // OPTION 2: SEND EMAIL (Choose one method)
        // ============================================
        
        // Method A: Basic PHP mail()
        $to = "info@gokarnaforesthotel.com";
        $email_subject = "New Contact Form Submission: $subject";
        $email_body = "
            <html>
            <head><title>New Contact Message</title></head>
            <body>
                <h2>Contact Form Submission</h2>
                <p><strong>Name:</strong> $name</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Phone:</strong> $phone</p>
                <p><strong>Subject:</strong> $subject</p>
                <p><strong>Priority:</strong> $priority</p>
                <p><strong>Message:</strong></p>
                <p>$message</p>
                <hr>
                <p><small>IP: $ip_address | Time: " . date('Y-m-d H:i:s') . "</small></p>
            </body>
            </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: $email" . "\r\n";
        $headers .= "Reply-To: $email" . "\r\n";
        
        // Uncomment to enable email sending:
        // $email_sent = mail($to, $email_subject, $email_body, $headers);

        // ============================================
        // OPTION 3: SAVE TO FILE (Backup)
        // ============================================
        $log_entry = date('Y-m-d H:i:s') . " | Name: $name | Email: $email | Phone: $phone | Subject: $subject | Message: " . substr($message, 0, 100) . "...\n";
        file_put_contents('contact_messages.log', $log_entry, FILE_APPEND | LOCK_EX);

        // Success message
        if (isset($db_saved) && $db_saved) {
            $success_msg = "Thank you $name! Your message has been received. We'll get back to you within 24 hours.";
            
            // Clear form
            $name = $email = $phone = $subject = $message = '';
            
            // Optional: Send auto-reply to user
            // autoReplyEmail($email, $name);
        } else {
            $error_msg = "Sorry, there was an error saving your message. Please try again or email us directly.";
        }
    }
}

// Function to send auto-reply (optional)
function autoReplyEmail($to_email, $to_name) {
    $subject = "Thank you for contacting Gokarna Forest Hotel";
    $message = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #D4AF37;'>Thank you for reaching out!</h2>
                <p>Dear $to_name,</p>
                <p>We have received your message and appreciate your interest in Gokarna Forest Hotel.</p>
                <p>Our team will review your inquiry and get back to you within 24 hours.</p>
                <p>For urgent matters, please call us directly at <strong>01-4451212</strong>.</p>
                <br>
                <p>Best regards,</p>
                <p><strong>Gokarna Forest Hotel Team</strong><br>
                Gokarna Forest Resort, Kathmandu, Nepal<br>
                Phone: 01-4451212<br>
                Email: info@gokarnaforesthotel.com</p>
            </div>
        </body>
        </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: info@gokarnaforesthotel.com" . "\r\n";
    
    mail($to_email, $subject, $message, $headers);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($pageTitle); ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    * { box-sizing: border-box; }
    
    body {
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #0f172a 0%, #111827 40%, #1e293b 100%);
        color: #ffffff;
        min-height: 100vh;
    }

    /* Hero Section */
    .contact-hero {
        position: relative;
        padding: 80px 20px;
        text-align: center;
        overflow: hidden;
    }

    .contact-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, rgba(212, 175, 55, 0.15) 0%, transparent 70%);
        pointer-events: none;
    }

    .contact-hero h1 {
        font-size: 48px;
        font-weight: 800;
        color: #fff;
        margin: 0 0 16px 0;
        position: relative;
    }

    .contact-hero h1 span {
        color: #D4AF37;
    }

    .contact-hero p {
        font-size: 18px;
        color: #94a3b8;
        max-width: 600px;
        margin: 0 auto;
        position: relative;
    }

    /* Quick Contact Cards */
    .quick-contact {
        display: flex;
        justify-content: center;
        gap: 30px;
        flex-wrap: wrap;
        padding: 0 20px 60px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .quick-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(212, 175, 55, 0.1);
        border-radius: 16px;
        padding: 30px;
        width: 280px;
        text-align: center;
        transition: all 0.4s ease;
    }

    .quick-card:hover {
        transform: translateY(-8px);
        border-color: rgba(212, 175, 55, 0.3);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }

    .quick-icon {
        width: 70px;
        height: 70px;
        background: rgba(212, 175, 55, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 28px;
        color: #D4AF37;
        transition: all 0.3s ease;
    }

    .quick-card:hover .quick-icon {
        background: rgba(212, 175, 55, 0.2);
        transform: scale(1.1);
    }

    .quick-card h3 {
        color: #fff;
        font-size: 20px;
        margin-bottom: 10px;
    }

    .quick-card p, .quick-card a {
        color: #94a3b8;
        font-size: 15px;
        text-decoration: none;
        transition: color 0.3s;
    }

    .quick-card a:hover {
        color: #D4AF37;
    }

    /* Main Container */
    .contact-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px 80px;
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        gap: 40px;
    }

    /* Info Panel */
    .info-panel {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(212, 175, 55, 0.1);
        border-radius: 20px;
        padding: 40px;
        height: fit-content;
    }

    .info-panel h2 {
        color: #D4AF37;
        font-size: 28px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .info-item {
        display: flex;
        gap: 16px;
        margin-bottom: 24px;
        padding-bottom: 24px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .info-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .info-icon {
        width: 50px;
        height: 50px;
        background: rgba(212, 175, 55, 0.1);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #D4AF37;
        font-size: 20px;
        flex-shrink: 0;
    }

    .info-content h4 {
        color: #fff;
        font-size: 16px;
        margin: 0 0 6px 0;
    }

    .info-content p, .info-content a {
        color: #94a3b8;
        font-size: 14px;
        margin: 0;
        text-decoration: none;
        line-height: 1.6;
    }

    .info-content a:hover {
        color: #D4AF37;
    }

    /* Social Links */
    .social-links {
        display: flex;
        gap: 12px;
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .social-links a {
        width: 45px;
        height: 45px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #D4AF37;
        font-size: 18px;
        transition: all 0.3s ease;
    }

    .social-links a:hover {
        background: #D4AF37;
        color: #0f172a;
        transform: translateY(-3px);
    }

    /* Form Panel */
    .form-panel {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(212, 175, 55, 0.1);
        border-radius: 20px;
        padding: 40px;
    }

    .form-panel h2 {
        color: #D4AF37;
        font-size: 28px;
        margin-bottom: 10px;
    }

    .form-panel > p {
        color: #94a3b8;
        margin-bottom: 30px;
    }

    /* Alert Messages */
    .alert {
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
        animation: slideIn 0.4s ease;
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
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

    /* Form Styles */
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
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

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 14px 16px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 12px;
        color: #fff;
        font-size: 15px;
        font-family: inherit;
        transition: all 0.3s ease;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #D4AF37;
        background: rgba(255, 255, 255, 0.08);
        box-shadow: 0 0 20px rgba(212, 175, 55, 0.1);
    }

    .form-group input::placeholder,
    .form-group textarea::placeholder {
        color: #64748b;
    }

    .form-group textarea {
        resize: vertical;
        min-height: 120px;
    }

    .required {
        color: #ef4444;
    }

    /* Character Counter */
    .char-counter {
        text-align: right;
        font-size: 12px;
        color: #64748b;
        margin-top: 6px;
    }

    .char-counter.warning {
        color: #f59e0b;
    }

    /* Submit Button */
    .btn-submit {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #D4AF37 0%, #B8941F 100%);
        color: #0f172a;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(212, 175, 55, 0.3);
    }

    .btn-submit:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    /* Map Section */
    .map-section {
        padding: 0 20px 80px;
    }

    .map-container {
        max-width: 1200px;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(212, 175, 55, 0.1);
        border-radius: 20px;
        overflow: hidden;
        padding: 10px;
    }

    .map-header {
        padding: 20px;
        text-align: center;
    }

    .map-header h2 {
        color: #D4AF37;
        margin: 0 0 8px 0;
    }

    .map-header p {
        color: #94a3b8;
        margin: 0;
    }

    .map-frame {
        border-radius: 12px;
        overflow: hidden;
    }

    iframe {
        width: 100%;
        height: 400px;
        border: 0;
        display: block;
    }

    /* FAQ Section */
    .faq-section {
        max-width: 800px;
        margin: 0 auto;
        padding: 0 20px 80px;
    }

    .faq-section h2 {
        text-align: center;
        color: #D4AF37;
        font-size: 32px;
        margin-bottom: 40px;
    }

    .faq-item {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(212, 175, 55, 0.1);
        border-radius: 12px;
        margin-bottom: 16px;
        overflow: hidden;
    }

    .faq-question {
        padding: 20px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background 0.3s;
    }

    .faq-question:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .faq-question h4 {
        margin: 0;
        color: #fff;
        font-size: 16px;
    }

    .faq-question i {
        color: #D4AF37;
        transition: transform 0.3s;
    }

    .faq-answer {
        padding: 0 20px;
        max-height: 0;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .faq-answer p {
        color: #94a3b8;
        margin: 0;
        padding-bottom: 20px;
        line-height: 1.7;
    }

    .faq-item.active .faq-question {
        background: rgba(212, 175, 55, 0.1);
    }

    .faq-item.active .faq-question i {
        transform: rotate(180deg);
    }

    .faq-item.active .faq-answer {
        max-height: 200px;
    }

    /* Responsive */
    @media(max-width: 900px) {
        .contact-container {
            grid-template-columns: 1fr;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .quick-card {
            width: 100%;
            max-width: 350px;
        }
    }

    @media(max-width: 600px) {
        .contact-hero h1 {
            font-size: 36px;
        }
        
        .info-panel, .form-panel {
            padding: 24px;
        }
    }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<!-- Hero Section -->
<section class="contact-hero">
    <h1>Get in <span>Touch</span></h1>
    <p>We'd love to hear from you. Reach out for reservations, inquiries, or just to say hello!</p>
</section>

<!-- Quick Contact Cards -->
<section class="quick-contact">
    <div class="quick-card">
        <div class="quick-icon"><i class="fas fa-phone-alt"></i></div>
        <h3>Call Us</h3>
        <p><a href="tel:+977014451212">01-4451212</a></p>
        <p style="font-size: 13px;">Mon-Sun, 8AM - 10PM</p>
    </div>
    
    <div class="quick-card">
        <div class="quick-icon"><i class="fas fa-envelope"></i></div>
        <h3>Email Us</h3>
        <p><a href="mailto:info@gokarnaforesthotel.com">info@gokarnaforesthotel.com</a></p>
        <p style="font-size: 13px;">Response within 24hrs</p>
    </div>
    
    <div class="quick-card">
        <div class="quick-icon"><i class="fas fa-map-marker-alt"></i></div>
        <h3>Visit Us</h3>
        <p>Gokarna Forest Resort</p>
        <p style="font-size: 13px;">Kageshwori 06, Kathmandu</p>
    </div>
</section>

<!-- Main Contact Section -->
<section class="contact-container">
    <!-- Info Panel -->
    <div class="info-panel">
        <h2><i class="fas fa-info-circle"></i> Contact Info</h2>
        
        <div class="info-item">
            <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
            <div class="info-content">
                <h4>Address</h4>
                <p>Gokarna Forest Resort<br>Kageshwori 06, Kathmandu<br>Nepal 44600</p>
            </div>
        </div>
        
        <div class="info-item">
            <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
            <div class="info-content">
                <h4>Phone</h4>
                <p><a href="tel:+977014451212">01-4451212</a><br>
                <a href="tel:+9779851012345">+977 98510 12345</a></p>
            </div>
        </div>
        
        <div class="info-item">
            <div class="info-icon"><i class="fas fa-envelope"></i></div>
            <div class="info-content">
                <h4>Email</h4>
                <p><a href="mailto:info@gokarnaforesthotel.com">info@gokarnaforesthotel.com</a><br>
                <a href="mailto:reservations@gokarnaforesthotel.com">reservations@gokarnaforesthotel.com</a></p>
            </div>
        </div>
        
        <div class="info-item">
            <div class="info-icon"><i class="fas fa-clock"></i></div>
            <div class="info-content">
                <h4>Working Hours</h4>
                <p>Front Desk: 24/7<br>Restaurant: 6AM - 11PM<br>Spa: 9AM - 9PM</p>
            </div>
        </div>
        
        <div class="social-links">
            <a href="https://facebook.com" target="_blank" title="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="https://instagram.com" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
            <a href="https://twitter.com" target="_blank" title="Twitter"><i class="fab fa-twitter"></i></a>
            <a href="https://wa.me/9779851012345" target="_blank" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
        </div>
    </div>

    <!-- Form Panel -->
    <div class="form-panel">
        <h2>Send us a Message</h2>
        <p>Fill out the form below and we'll get back to you shortly.</p>

        <?php if($success_msg): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>
        
        <?php if($error_msg): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" id="contactForm">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name <span class="required">*</span></label>
                    <input type="text" name="name" placeholder="John Doe" required 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                           minlength="2" maxlength="100">
                </div>
                
                <div class="form-group">
                    <label>Email Address <span class="required">*</span></label>
                    <input type="email" name="email" placeholder="john@example.com" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" placeholder="+977 98XXXXXXXX"
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Subject <span class="required">*</span></label>
                    <select name="subject" required>
                        <option value="General Inquiry" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'General Inquiry') ? 'selected' : ''; ?>>General Inquiry</option>
                        <option value="Room Reservation" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Room Reservation') ? 'selected' : ''; ?>>Room Reservation</option>
                        <option value="Event Booking" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Event Booking') ? 'selected' : ''; ?>>Event Booking</option>
                        <option value="Feedback" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Feedback') ? 'selected' : ''; ?>>Feedback</option>
                        <option value="Complaint" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Complaint') ? 'selected' : ''; ?>>Complaint</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Priority</label>
                <select name="priority">
                    <option value="low">Low - General question</option>
                    <option value="normal" selected>Normal - Need response soon</option>
                    <option value="high">High - Urgent matter</option>
                    <option value="urgent">Urgent - Immediate attention needed</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Message <span class="required">*</span></label>
                <textarea name="message" placeholder="Tell us how we can help you..." required minlength="10" maxlength="2000" id="messageBox"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                <div class="char-counter" id="charCounter">0 / 2000 characters</div>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Send Message
            </button>
        </form>
    </div>
</section>

<!-- Map Section -->
<section class="map-section">
    <div class="map-container">
        <div class="map-header">
            <h2><i class="fas fa-map-marked-alt"></i> Find Us</h2>
            <p>Located in the serene Gokarna Forest area, just 10km from Kathmandu city center</p>
        </div>
        <div class="map-frame">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3532.374896284704!2d85.31700427453567!3d27.717245232726436!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39eb1911f3b3c0df%3A0x9a15c2f1d0f0f7b3!2sKathmandu%2C%20Nepal!5e0!3m2!1sen!2s!4v1690000000000!5m2!1sen!2s"
                allowfullscreen="" 
                loading="lazy">
            </iframe>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section">
    <h2>Frequently Asked Questions</h2>
    
    <div class="faq-item">
        <div class="faq-question" onclick="this.parentElement.classList.toggle('active')">
            <h4>What are the check-in and check-out times?</h4>
            <i class="fas fa-chevron-down"></i>
        </div>
        <div class="faq-answer">
            <p>Check-in time is 2:00 PM and check-out time is 12:00 PM. Early check-in and late check-out can be arranged upon request, subject to availability.</p>
        </div>
    </div>
    
    <div class="faq-item">
        <div class="faq-question" onclick="this.parentElement.classList.toggle('active')">
            <h4>Do you offer airport pickup services?</h4>
            <i class="fas fa-chevron-down"></i>
        </div>
        <div class="faq-answer">
            <p>Yes, we provide airport pickup and drop-off services at an additional charge. Please contact us at least 24 hours in advance to arrange this service.</p>
        </div>
    </div>
    
    <div class="faq-item">
        <div class="faq-question" onclick="this.parentElement.classList.toggle('active')">
            <h4>Is breakfast included in the room rate?</h4>
            <i class="fas fa-chevron-down"></i>
        </div>
        <div class="faq-answer">
            <p>Yes, complimentary breakfast is included with all room bookings. We serve a variety of continental and local Nepali breakfast options.</p>
        </div>
    </div>
    
    <div class="faq-item">
        <div class="faq-question" onclick="this.parentElement.classList.toggle('active')">
            <h4>Do you have WiFi available?</h4>
            <i class="fas fa-chevron-down"></i>
        </div>
        <div class="faq-answer">
            <p>Yes, complimentary high-speed WiFi is available throughout the hotel including all rooms, restaurant, and common areas.</p>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>

<script>
// Character counter
const messageBox = document.getElementById('messageBox');
const charCounter = document.getElementById('charCounter');

messageBox.addEventListener('input', function() {
    const length = this.value.length;
    charCounter.textContent = length + ' / 2000 characters';
    
    if (length > 1800) {
        charCounter.classList.add('warning');
    } else {
        charCounter.classList.remove('warning');
    }
});

// Initialize counter
if (messageBox.value) {
    charCounter.textContent = messageBox.value.length + ' / 2000 characters';
}

// Form validation
document.getElementById('contactForm').addEventListener('submit', function(e) {
    const btn = this.querySelector('.btn-submit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
});
</script>

</body>
</html>