<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SUPERCAR SPECTACLE - Home</title>
  <link rel="stylesheet" href="styles.css" />
  <link
    href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap"
    rel="stylesheet" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>

<body>
  <!-- Navigation -->
  <nav class="navbar">
    <div class="nav-container">
      <div class="logo">
        <a href="index.php" class="logo-link">
          <img
            src="logo.png"
            alt="SUPERCAR SPECTACLE Logo"
            class="logo-image" />
          <img
            src="logo text.png"
            alt="SUPERCAR SPECTACLE Logo"
            class="logo-text-image" />
        </a>
      </div>
      <ul class="nav-menu">
        <li class="nav-item">
          <a href="index.php" class="nav-link active">Home</a>
        </li>
        <li class="nav-item">
          <a href="about.html" class="nav-link">About</a>
        </li>
        <li class="nav-item">
          <a href="news.html" class="nav-link">News</a>
        </li>
        <li class="nav-item">
          <a href="gallery.html" class="nav-link">Gallery</a>
        </li>
        <li class="nav-item">
          <button class="nav-link register-btn" onclick="openShowcaseModal()">
            Register Now
          </button>
        </li>
      </ul>
      <div class="hamburger">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-content">
      <div class="hero-image-container">
        <img
          src="selected images/H1.JPG"
          alt="HOME IMAGE"
          class="homeImage active"
          onload="console.log('✅ Image 1 loaded successfully')"
          onerror="console.log('❌ Image 1 failed to load:', this.src)" />
        <img
          src="selected images/H2.jpeg"
          alt="HOME IMAGE"
          class="homeImage"
          onload="console.log('✅ Image 2 loaded successfully')"
          onerror="console.log('❌ Image 2 failed to load:', this.src)" />
        <img
          src="selected images/H4.JPG"
          alt="HOME IMAGE"
          class="homeImage"
          onload="console.log('✅ Image 3 loaded successfully')"
          onerror="console.log('❌ Image 3 failed to load:', this.src)" />
        <div class="hero-overlay">
          <h1 class="hero-title">SUPERCAR SPECTACLE</h1>
          <p class="hero-subtitle">
            Experience the Ultimate Automotive Extravaganza
          </p>
          <div class="hero-buttons">
            <button class="btn btn-primary" onclick="scrollToTickets()">
              Get Tickets
            </button>
            <button
              class="btn btn-secondary"
              onclick="window.location.href='about.html'">
              Learn More
            </button>
          </div>
        </div>
        <!-- Image counter indicator -->
        <div class="image-counter">
          <span class="counter-dot active" data-index="0"></span>
          <span class="counter-dot" data-index="1"></span>
          <span class="counter-dot" data-index="2"></span>
        </div>
      </div>
    </div>
  </section>

  <?php
  // Check for and display any session messages
  session_start();
  if (isset($_SESSION['form_message']) && !empty($_SESSION['form_message'])) {
    $message = htmlspecialchars($_SESSION['form_message']);
    $type = $_SESSION['message_type'] ?? 'info'; // Default to info if not set

    // Determine the style based on message type
    $style = '';
    switch ($type) {
      case 'success':
        $style = 'background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;';
        break;
      case 'error':
        $style = 'background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;';
        break;
      case 'info':
        $style = 'background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;';
        break;
    }

    echo "<div class='message' style='padding: 15px; margin: 20px auto; border-radius: 5px; width: 80%; text-align: center; $style'>$message</div>";

    // Clear the session messages after displaying them
    unset($_SESSION['form_message']);
    unset($_SESSION['message_type']);
  }
  ?>

  <!-- Showcase Registration Section -->
  <section id="showcase" class="showcase-section">
    <div class="container">
      <div class="showcase-header">
        <h2>Have a Supercar to Showcase?</h2>
        <p>Register your car with us to be showcased</p>
        <button class="btn btn-primary" onclick="openShowcaseModal()">
          Register Here
        </button>
      </div>
    </div>
  </section>

  <!-- Ticket Sales Section -->
  <section id="tickets" class="tickets">
    <div class="container">
      <h2>Get Your Tickets</h2>
      <div class="ticket-cards">
        <div class="ticket-card">
          <div class="ticket-header">
            <h3>General Admission</h3>
            <div class="price">₵500</div>
          </div>
          <ul class="ticket-features">
            <li>
              <i class="fas fa-check"></i> Access to all exhibition areas
            </li>
            <li><i class="fas fa-check"></i> Live demonstrations</li>
            <li><i class="fas fa-check"></i> Food & beverage access</li>
            <li><i class="fas fa-check"></i> Event program</li>
          </ul>
          <button class="btn btn-ticket" onclick="purchaseTicket('general')">
            Purchase Ticket
          </button>
        </div>
        <div class="ticket-card featured">
          <div class="ticket-badge">Most Popular</div>
          <div class="ticket-header">
            <h3>VIP Experience</h3>
            <div class="price">₵1,500</div>
          </div>
          <ul class="ticket-features">
            <li>
              <i class="fas fa-check"></i> All General Admission benefits
            </li>
            <li><i class="fas fa-check"></i> Exclusive VIP lounge access</li>
            <li><i class="fas fa-check"></i> Meet & greet with drivers</li>
            <li><i class="fas fa-check"></i> Premium parking</li>
            <li><i class="fas fa-check"></i> Complimentary refreshments</li>
          </ul>
          <button class="btn btn-ticket" onclick="purchaseTicket('vip')">
            Purchase Ticket
          </button>
        </div>
        <div class="ticket-card">
          <div class="ticket-header">
            <h3>Premium Package</h3>
            <div class="price">₵2,500</div>
          </div>
          <ul class="ticket-features">
            <li><i class="fas fa-check"></i> All VIP benefits</li>
            <li><i class="fas fa-check"></i> Ride-along experience</li>
            <li><i class="fas fa-check"></i> Behind-the-scenes tour</li>
            <li><i class="fas fa-check"></i> Exclusive merchandise</li>
            <li><i class="fas fa-check"></i> Priority seating</li>
          </ul>
          <button class="btn btn-ticket" onclick="purchaseTicket('premium')">
            Purchase Ticket
          </button>
        </div>
      </div>
    </div>
  </section>

  <!-- Event Details -->
  <section class="event-details">
    <div class="container">
      <div class="details-grid">
        <div class="detail-item">
          <i class="fas fa-calendar-alt"></i>
          <h3>Date & Time</h3>
          <p>December 15-17, 2025<br />10:00 AM - 8:00 PM</p>
        </div>
        <div class="detail-item">
          <i class="fas fa-map-marker-alt"></i>
          <h3>Location</h3>
          <p>Borteyman Stadium<br />Accra, Ghana</p>
        </div>
        <div class="detail-item">
          <i class="fas fa-car"></i>
          <h3>Featured Cars</h3>
          <p>50+ Supercars<br />Exclusive Debuts</p>
        </div>
        <div class="detail-item">
          <i class="fas fa-users"></i>
          <h3>Expected Attendance</h3>
          <p>1,000+ Visitors<br />International Guests</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Newsletter Subscription -->
  <section class="newsletter">
    <div class="container">
      <div class="newsletter-content">
        <h2>Stay Updated</h2>
        <p>Subscribe to our newsletter for the latest news and exclusive updates about SUPERCAR SPECTACLE</p>
        <form class="newsletter-form" method="post" action="admin/subscribe.php">
          <input type="email" name="email" placeholder="Enter your email address" required>
          <button type="submit" class="btn btn-primary">Subscribe</button>
        </form>
      </div>
    </div>
  </section>


  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-section">
          <div class="footer-logo">
            <a href="index.php" class="logo-link">
              <img
                src="logo.png"
                alt="SUPERCAR SPECTACLE Logo"
                class="logo-image" />
              <img
                src="logo text.png"
                alt="SUPERCAR SPECTACLE Logo"
                class="logo-text-image" />
            </a>
          </div>
          <p>Ghana's premier automotive showcase</p>
        </div>
        <div class="footer-section">
          <h3>Quick Links</h3>
          <ul>
            <li><a href="index.phpl">Home</a></li>
            <li><a href="about.html">About</a></li>
            <li><a href="news.html">News</a></li>
            <li><a href="gallery.html">Gallery</a></li>
          </ul>
        </div>
        <div class="footer-section">
          <h3>Contact</h3>
          <p><i class="fas fa-phone"></i> 0558702163</p>
          <p><i class="fas fa-envelope"></i> supercarspectacle1@gmail.com</p>
          <p><i class="fas fa-map-marker-alt"></i> Accra, Ghana</p>
        </div>
        <div class="footer-section">
          <h3>Follow Us</h3>
          <div class="social-links">
            <a href="#"><i class="fab fa-facebook"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-youtube"></i></a>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        <p>
          &copy; 2025 SUPERCAR SPECTACLE. All rights reserved. | Designed by
        </p>
      </div>
    </div>
  </footer>

  <!-- Purchase Modal -->
  <div id="purchaseModal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2>Purchase Tickets</h2>
      <form id="ticketForm">
        <div class="form-group">
          <label for="ticketType">Ticket Type:</label>
          <select id="ticketType" required>
            <option value="">Select ticket type</option>
            <option value="general">General Admission - ₵500</option>
            <option value="vip">VIP Experience - ₵1,500</option>
            <option value="premium">Premium Package - ₵2,500</option>
          </select>
        </div>
        <div class="form-group">
          <label for="quantity">Quantity:</label>
          <input
            type="number"
            id="quantity"
            min="1"
            max="10"
            value="1"
            required />
        </div>
        <div class="form-group">
          <label for="name">Full Name:</label>
          <input type="text" id="name" required />
        </div>
        <div class="form-group">
          <label for="email">Email:</label>
          <input type="email" id="email" required />
        </div>
        <div class="form-group">
          <label for="phone">Phone:</label>
          <input type="tel" id="phone" required />
        </div>
        <div class="total-section">
          <h3>Total: <span id="totalAmount">₵0</span></h3>
        </div>
        <button type="submit" class="btn btn-primary">
          Complete Purchase
        </button>
      </form>
    </div>
  </div>

  <!-- Showcase Registration Modal -->
  <div id="showcaseModal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2>Register Your Supercar</h2>
      <form id="showcaseForm" method="post" action="./admin/register_showcase.php" name="showcaseForm">
        <div class="form-group">
          <label for="owner_name">Full Name:</label>
          <input type="text" id="owner_name" name="owner_name" required />
        </div>
        <div class="form-group">
          <label for="car_make">Car Make:</label>
          <input
            type="text"
            id="car_make"
            name="car_make"
            placeholder="e.g. Ferrari"
            required />
        </div>
        <div class="form-group">
          <label for="car_model">Car Model:</label>
          <input
            type="text"
            id="car_model"
            name="car_model"
            placeholder="e.g. 488 Pista"
            required />
        </div>

        <div class="form-group">
          <label for="contact_number">Contact Number:</label>
          <input
            type="tel"
            id="contact_number"
            name="contact_number"
            required />
        </div>
        <div class="form-group">
          <label for="plate_number">Plate Number:</label>
          <input
            type="text"
            id="plate_number"
            name="plate_number"
            placeholder="e.g. GT-1234-AB"
            required />
        </div>
        <div class="form-group">
          <label for="description">Brief Description:</label>
          <textarea
            id="description"
            name="description"
            rows="4"
            placeholder="Tell us about your car: specs, mods, unique features"
            required></textarea>
        </div>
        <button type="submit" name="sub" class="btn btn-primary">
          Submit Registration
        </button>
      </form>
    </div>
  </div>
  <script src="script.js"></script>
</body>

</html>