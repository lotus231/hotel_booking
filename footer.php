<style>
.site-footer{
  background: linear-gradient(135deg, #0f172a, #111827, #1e293b);
  color: #ffffff;
  padding: 60px 20px 30px;
  margin-top: 80px;
  border-top: 1px solid rgba(212,175,55,0.3);
}

.footer-container{
  max-width: 1200px;
  margin: auto;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 40px;
}

.footer-title{
  font-family: Georgia, serif;
  font-size: 20px;
  color: #D4AF37;
  margin-bottom: 15px;
}

.footer-text{
  color: #cbd5e1;
  line-height: 1.6;
  font-size: 14px;
}

.footer-links a{
  display: block;
  color: #cbd5e1;
  text-decoration: none;
  margin-bottom: 8px;
  transition: 0.3s;
}

.footer-links a:hover{
  color: #D4AF37;
  padding-left: 5px;
}

.footer-contact p{
  margin: 6px 0;
  font-size: 14px;
  color: #cbd5e1;
}

.footer-social{
  margin-top: 15px;
}

.footer-social a{
  display: inline-block;
  margin-right: 10px;
  font-size: 18px;
  color: #D4AF37;
  transition: 0.3s;
}

.footer-social a:hover{
  color: #ffffff;
  transform: translateY(-3px);
}

/* Bottom Line */
.footer-bottom{
  text-align: center;
  margin-top: 40px;
  padding-top: 20px;
  border-top: 1px solid rgba(212,175,55,0.2);
  font-size: 13px;
  color: #94a3b8;
}

/* Responsive */
@media(max-width:768px){
  .footer-container{
    text-align: center;
  }
  .footer-social{
    margin-top: 10px;
  }
}
</style>

<footer class="site-footer">
  <div class="footer-container">

    <!-- Hotel Info -->
    <div>
      <h3 class="footer-title">Gokarna Forest Hotel</h3>
      <p class="footer-text">
        Experience luxury and nature combined. 
        A peaceful 5-star retreat offering world-class service,
        elegant rooms, and unforgettable hospitality.
      </p>
    </div>

    <!-- Quick Links -->
    <div>
      <h3 class="footer-title">Quick Links</h3>
      <div class="footer-links">
        <a href="index.php">Home</a>
        <a href="rooms.php">Rooms</a>
        <a href="about.php">About Us</a>
        <a href="contact.php">Contact</a>
        <a href="booking.php">Book Now</a>
      </div>
    </div>

    <!-- Contact Info -->
    <div>
      <h3 class="footer-title">Contact Us</h3>
      <div class="footer-contact">
        <p>📍 Kageshwori Mahohara 06, Kathmandu, Nepal</p>
        <p>📞 01-4451212</p>
        <p>✉ info@gokarnaforesthotel.com</p>
      </div>

      <div class="footer-social">
        <a href="#">🌐</a>
        <a href="#">📘</a>
        <a href="#">📸</a>
        <a href="#">🐦</a>
      </div>
    </div>

  </div>

  <div class="footer-bottom">
    © <?php echo date("Y"); ?> Gokarna Forest Hotel. All Rights Reserved.
  </div>
</footer>
