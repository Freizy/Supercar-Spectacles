<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Supercar Spectacle</title>
  <link rel="stylesheet" href="styles.css" />
  <link
    href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap"
    rel="stylesheet" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Countdown segment styling */
    .countdown-segment {
      background: linear-gradient(135deg,
          #000000 0%,
          #1a1a1a 50%,
          #2d2d2d 100%);
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(255, 0, 0, 0.4);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .countdown-segment:hover {
      transform: translateY(-4px) scale(1.03);
      box-shadow: 0 0 20px rgba(255, 0, 0, 0.7), 0 0 40px rgba(255, 0, 0, 0.5);
    }
  </style>
</head>

<body>
  <!-- Navigation -->
  <nav id="hero-navbar" class="navbar">
    <div class="nav-container">
      <div id="nav-logo" class="logo">
        <img
          src="logo.png"
          alt="SUPERCAR SPECTACLE Logo"
          class="logo-image" />
        <img
          src="logo text.png"
          alt="SUPERCAR SPECTACLE Logo"
          class="logo-text-image" />
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

          <div
            id="countdown-display"
            class="grid grid-cols-2 sm:grid-cols-4 gap-4 md:gap-8 mb-12 text-center">
            <!-- Days -->
            <div
              class="countdown-segment p-4 md:p-6 lg:p-8 border-2 border-red-600 rounded-xl shadow-lg bg-black/40 backdrop-blur-sm hover:shadow-red-500/50 transition">
              <span
                id="days"
                class="block text-4xl sm:text-5xl lg:text-7xl font-mono font-bold text-red-500 drop-shadow-[0_0_15px_#ef4444]">00</span>
              <span
                class="block mt-2 text-sm sm:text-lg uppercase tracking-wider text-red-400 drop-shadow-[0_0_8px_#ef4444]">Days</span>
            </div>

            <!-- Hours -->
            <div
              class="countdown-segment p-4 md:p-6 lg:p-8 border-2 border-red-600 rounded-xl shadow-lg bg-black/40 backdrop-blur-sm hover:shadow-red-500/50 transition">
              <span
                id="hours"
                class="block text-4xl sm:text-5xl lg:text-7xl font-mono font-bold text-red-500 drop-shadow-[0_0_15px_#ef4444]">00</span>
              <span
                class="block mt-2 text-sm sm:text-lg uppercase tracking-wider text-red-400 drop-shadow-[0_0_8px_#ef4444]">Hours</span>
            </div>

            <!-- Minutes -->
            <div
              class="countdown-segment p-4 md:p-6 lg:p-8 border-2 border-red-600 rounded-xl shadow-lg bg-black/40 backdrop-blur-sm hover:shadow-red-500/50 transition">
              <span
                id="minutes"
                class="block text-4xl sm:text-5xl lg:text-7xl font-mono font-bold text-red-500 drop-shadow-[0_0_15px_#ef4444]">00</span>
              <span
                class="block mt-2 text-sm sm:text-lg uppercase tracking-wider text-red-400 drop-shadow-[0_0_8px_#ef4444]">Minutes</span>
            </div>

            <!-- Seconds -->
            <div
              class="countdown-segment p-4 md:p-6 lg:p-8 border-2 border-red-600 rounded-xl shadow-lg bg-black/40 backdrop-blur-sm hover:shadow-red-500/50 transition">
              <span
                id="seconds"
                class="block text-4xl sm:text-5xl lg:text-7xl font-mono font-bold text-red-500 drop-shadow-[0_0_15px_#ef4444]">00</span>
              <span
                class="block mt-2 text-sm sm:text-lg uppercase tracking-wider text-red-400 drop-shadow-[0_0_8px_#ef4444]">Seconds</span>
            </div>
            <!-- Image counter indicator -->
            <div class="image-counter">
              <span class="counter-dot active" data-index="0"></span>
              <span class="counter-dot" data-index="1"></span>
              <span class="counter-dot" data-index="2"></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <script src="script.js"></script>
  <script>
    // Use the event date defined in your PHP constants (2025-12-15)
    const EVENT_DATE = "2025-10-10T00:00:00"; // Setting to 10:00 AM start time
    const COUNTDOWN_TARGET = new Date(EVENT_DATE).getTime();

    const countdownElements = {
      days: document.getElementById("days"),
      hours: document.getElementById("hours"),
      minutes: document.getElementById("minutes"),
      seconds: document.getElementById("seconds"),
    };

    // Helper function to ensure numbers are always two digits
    const formatTime = (time) => String(time).padStart(2, "0");

    function updateCountdown() {
      const now = new Date().getTime();
      const distance = COUNTDOWN_TARGET - now;

      // Check if the event has passed
      if (distance < 0) {
        // Display message when event starts
        document.getElementById("countdown-display").innerHTML =
          '<h2 class="text-4xl font-bold text-amber-500">EVENT IS LIVE!</h2>';
        clearInterval(interval);
        return;
      }

      // Calculate time components
      const d = Math.floor(distance / (1000 * 60 * 60 * 24));
      const h = Math.floor(
        (distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
      );
      const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const s = Math.floor((distance % (1000 * 60)) / 1000);

      // Update the display
      countdownElements.days.textContent = formatTime(d);
      countdownElements.hours.textContent = formatTime(h);
      countdownElements.minutes.textContent = formatTime(m);
      countdownElements.seconds.textContent = formatTime(s);
    }

    // Run the countdown function immediately to avoid a 1-second delay
    updateCountdown();

    // Update the countdown every second
    const interval = setInterval(updateCountdown, 1000);
  </script>
</body>

</html>