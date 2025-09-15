<!-- filepath: c:\xampp\htdocs\delivery\includes\footer.php -->
<footer class="site-footer">
  <div class="footer-container">
    <div class="footer-left">
      <h3>ShipManager</h3>
      <p>Delivering smiles, one package at a time.</p>
    </div>
    <div class="footer-center">
      <h4>Quick Links</h4>
      <ul>
        <li><a href="./neworder.php">New Order</a></li>
        <li><a href="./orders.php">Orders</a></li>


        <li><a href="./track.php">Tracking</a></li>
      </ul>
    </div>
    <div class="footer-right">
      <h4>Contact Us</h4>
      <p>Email: support@shipmanager.com</p>
      <p>Phone: +977 9812345678</p>
      <p>Address: Birtamode, Jhapa, Nepal</p>
    </div>
  </div>
  <div class="footer-bottom">
    &copy; <?php echo date("Y"); ?> ShipManager. All rights reserved.
  </div>
</footer>

<style>
.site-footer {
  background: linear-gradient(135deg, #004aad, #0077b6);
  color: #fff;
  padding: 40px 20px 20px;
  font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
  margin-top: 50px;
  border-top-left-radius: 20px;
  border-top-right-radius: 20px;
}

.footer-container {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 30px;
  max-width: 1200px;
  margin: 0 auto;
}

.footer-left h3 {
  font-size: 1.8rem;
  margin-bottom: 10px;
  background: linear-gradient(135deg, #fff, #f0f0f0);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.footer-left p {
  font-size: 0.95rem;
  line-height: 1.6;
}

.footer-center h4,
.footer-right h4 {
  font-size: 1.2rem;
  margin-bottom: 12px;
  font-weight: bold;
}

.footer-center ul {
  list-style: none;
  padding: 0;
}

.footer-center ul li {
  margin-bottom: 8px;
}

.footer-center ul li a {
  color: #fff;
  text-decoration: none;
  transition: color 0.3s;
}

.footer-center ul li a:hover {
  color: #ffd700;
}

.footer-right p {
  margin: 6px 0;
  font-size: 0.95rem;
}

.footer-bottom {
  text-align: center;
  margin-top: 30px;
  font-size: 0.9rem;
  border-top: 1px solid rgba(255,255,255,0.3);
  padding-top: 15px;
}

@media(max-width: 768px) {
  .footer-container {
    flex-direction: column;
    text-align: center;
    gap: 20px;
  }
  .footer-center ul li {
    display: inline-block;
    margin: 0 10px;
  }
}
</style>
