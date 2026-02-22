<?php
// about.php
include'header.php';
include 'breadcrumb.php';
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About Us - Gokarna Forest Hotel</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #f9fafb;
    color: #111827;
    line-height: 1.6;
}

/* HERO SECTION */
.hero {
    height: 60vh;
    background: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)),
                url('https://images.unsplash.com/photo-1566073771259-6a8506099945') center/cover no-repeat;
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
    color: #fff;
}

.hero h1 {
    font-size: 48px;
    font-weight: 700;
}

.hero p {
    font-size: 18px;
    margin-top: 10px;
    opacity: 0.9;
}

/* CONTAINER */
.container {
    max-width: 1100px;
    margin: 60px auto;
    padding: 0 20px;
}

/* SECTION TITLE */
.section-title {
    text-align: center;
    margin-bottom: 40px;
}

.section-title h2 {
    font-size: 32px;
    color: #111827;
}

.section-title p {
    color: #6b7280;
    margin-top: 10px;
}

/* ABOUT TEXT */
.about-text {
    background: #ffffff;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    margin-bottom: 60px;
    text-align: center;
}

/* FEATURES SECTION */
.features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
}

.feature-card {
    background: #ffffff;
    padding: 30px 20px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 10px 25px rgba(0,0,0,0.07);
    transition: 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.12);
}

.feature-card h3 {
    margin-bottom: 12px;
    color: #3b82f6;
}

.feature-card p {
    color: #6b7280;
    font-size: 14px;
}

/* FOOTER */
.footer {
    background: #111827;
    color: #fff;
    text-align: center;
    padding: 20px;
    margin-top: 60px;
}

/* RESPONSIVE */
@media(max-width:768px) {
    .hero h1 {
        font-size: 32px;
    }
}
</style>

</head>
<body>

<!-- HERO -->
<section class="hero">
    <div>
        <h1>About Gokarna Forest Hotel</h1>
        <p>Experience Luxury in the Heart of Nature</p>
    </div>
</section>

<!-- ABOUT CONTENT -->
<div class="container">

    <div class="section-title">
        <h2>Welcome to Our Hotel</h2>
        <p>Your comfort is our priority</p>
    </div>

    <div class="about-text">
        <p>
            Gokarna Forest Hotel offers a perfect blend of luxury and nature.
            Surrounded by peaceful greenery and modern facilities, our hotel
            provides the best experience for relaxation, business stays,
            and family vacations.
        </p>
        <br>
        <p>
            We are committed to delivering exceptional hospitality,
            comfortable rooms, delicious dining, and unforgettable memories.
        </p>
    </div>

    <div class="section-title">
        <h2>Why Choose Us?</h2>
    </div>

    <div class="features">
        <div class="feature-card">
            <h3>Luxury Rooms</h3>
            <p>Spacious and modern rooms designed for ultimate comfort.</p>
        </div>

        <div class="feature-card">
            <h3>Peaceful Location</h3>
            <p>Enjoy nature and serenity away from city noise.</p>
        </div>

        <div class="feature-card">
            <h3>24/7 Service</h3>
            <p>Our staff is available anytime to assist you.</p>
        </div>

        <div class="feature-card">
            <h3>Best Pricing</h3>
            <p>Affordable luxury with top-tier facilities.</p>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>
</body>
</html>
