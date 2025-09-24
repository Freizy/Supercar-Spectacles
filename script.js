// Navigation Hamburger Toggle
const hamburger = document.querySelector(".hamburger");
const navMenu = document.querySelector(".nav-menu");
if (hamburger && navMenu) {
  hamburger.addEventListener("click", () => {
    navMenu.classList.toggle("active");
  });
}

// Debug navigation links
document.addEventListener("DOMContentLoaded", function () {
  const navLinks = document.querySelectorAll(".nav-link");
  navLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      console.log("Navigation link clicked:", this.href);
      // Don't prevent default - let the link work normally
    });
  });

  // Video logo fallback handling
  const videoLogos = document.querySelectorAll(".logo-video");

  videoLogos.forEach((video) => {
    const fallback = video.nextElementSibling;

    // Force video to play
    video.play().catch(function (error) {
      console.log("Video autoplay failed:", error);
    });

    // Handle video loading errors
    video.addEventListener("error", function () {
      console.log("Video failed to load, showing fallback image");
      video.classList.add("error");
      if (fallback && fallback.classList.contains("fallback")) {
        fallback.style.display = "block";
      }
    });

    // Handle video load success
    video.addEventListener("loadeddata", function () {
      console.log("Video loaded successfully");
      video.classList.remove("error");
      if (fallback && fallback.classList.contains("fallback")) {
        fallback.style.display = "none";
      }
    });

    // Ensure video loops properly
    video.addEventListener("ended", function () {
      video.currentTime = 0;
      video.play().catch(function (error) {
        console.log("Video replay failed:", error);
      });
    });
  });
});

// Smooth Scroll to Sections
function scrollToTickets() {
  document.getElementById("tickets").scrollIntoView({ behavior: "smooth" });
}
function scrollToAbout() {
  document.getElementById("about").scrollIntoView({ behavior: "smooth" });
}

// Ticket Purchase Modal Logic - Only initialize if modal exists
const purchaseModal = document.getElementById("purchaseModal");
if (purchaseModal) {
  const closeModalBtns = document.querySelectorAll(".modal .close");
  const ticketForm = document.getElementById("ticketForm");
  const ticketType = document.getElementById("ticketType");
  const quantity = document.getElementById("quantity");
  const totalAmount = document.getElementById("totalAmount");

  const ticketPrices = {
    general: 500,
    vip: 1500,
    premium: 2500,
  };

  function purchaseTicket(type) {
    if (purchaseModal) {
      purchaseModal.classList.add("show");
      if (ticketType) ticketType.value = type;
      updateTotal();
    }
  }

  if (closeModalBtns) {
    closeModalBtns.forEach((btn) => {
      btn.onclick = function () {
        this.closest(".modal").classList.remove("show");
      };
    });
  }

  function updateTotal() {
    if (ticketType && quantity && totalAmount) {
      const price = ticketPrices[ticketType.value] || 0;
      const qty = parseInt(quantity.value) || 1;
      totalAmount.textContent = `₵${price * qty}`;
    }
  }

  if (ticketType) ticketType.onchange = updateTotal;
  if (quantity) quantity.oninput = updateTotal;

  if (ticketForm) {
    ticketForm.onsubmit = function (e) {
      e.preventDefault();
      alert(
        "Thank you for your purchase! You will receive a confirmation email."
      );
      purchaseModal.classList.remove("show");
      ticketForm.reset();
      updateTotal();
    };
  }

  // Make purchaseTicket function globally available
  window.purchaseTicket = purchaseTicket;
}

// Global modal close functionality
window.onclick = function (event) {
  if (event.target.classList.contains("modal")) {
    event.target.classList.remove("show");
  }
};

// Showcase Registration Modal Logic
document.addEventListener("DOMContentLoaded", function () {
  const showcaseModal = document.getElementById("showcaseModal");
  if (showcaseModal) {
    const showcaseForm = document.getElementById("showcaseForm");

    // Function to open showcase modal
    window.openShowcaseModal = function () {
      showcaseModal.classList.add("show");
    };

    // Handle showcase form submission
    /*
    if (showcaseForm) {
      showcaseForm.onsubmit = function (e) {
        e.preventDefault();

        alert(
          "Thank you for registering your supercar! We will contact you soon."
        );
        showcaseForm.submit();
        showcaseModal.classList.remove("show");
        showcaseForm.reset();
      };
    }
      */

    // Close modal functionality
    const closeBtn = showcaseModal.querySelector(".close");
    if (closeBtn) {
      closeBtn.onclick = function () {
        showcaseModal.classList.remove("show");
      };
    }

    // Close modal when clicking outside
    showcaseModal.onclick = function (event) {
      if (event.target === showcaseModal) {
        showcaseModal.classList.remove("show");
      }
    };
  }
});

// News Page: Category Tabs - Only initialize if elements exist
const categoryTabs = document.querySelectorAll(".category-tab");
const newsItems = document.querySelectorAll(".news-item");
if (categoryTabs.length && newsItems.length) {
  categoryTabs.forEach((tab) => {
    tab.onclick = function () {
      categoryTabs.forEach((t) => t.classList.remove("active"));
      this.classList.add("active");
      const cat = this.getAttribute("data-category");
      newsItems.forEach((item) => {
        if (cat === "all" || item.getAttribute("data-category") === cat) {
          item.style.display = "";
        } else {
          item.style.display = "none";
        }
      });
    };
  });
}

// News Page: Read More Button
window.readMore = function (btn) {
  alert("Full article coming soon!");
};

// Newsletter Subscription
window.subscribeNewsletter = function (e) {
  e.preventDefault();
  alert("Thank you for subscribing!");
  e.target.reset();
};

// Car Sales: Filtering
window.filterCars = function () {
  const search = document.getElementById("searchCars").value.toLowerCase();
  const brand = document.getElementById("brandFilter").value;
  const price = document.getElementById("priceFilter").value;
  const year = document.getElementById("yearFilter").value;
  const cars = document.querySelectorAll(".car-card");
  cars.forEach((car) => {
    let show = true;
    if (
      search &&
      !car.querySelector("h3").textContent.toLowerCase().includes(search)
    )
      show = false;
    if (brand && car.getAttribute("data-brand") !== brand) show = false;
    if (year && car.getAttribute("data-year") !== year) show = false;
    if (price) {
      const carPrice = parseInt(car.getAttribute("data-price"));
      if (price === "0-500000" && carPrice > 500000) show = false;
      if (
        price === "500000-1000000" &&
        (carPrice < 500000 || carPrice > 1000000)
      )
        show = false;
      if (
        price === "1000000-2000000" &&
        (carPrice < 1000000 || carPrice > 2000000)
      )
        show = false;
      if (price === "2000000+" && carPrice < 2000000) show = false;
    }
    car.style.display = show ? "" : "none";
  });
};

// Car Sales: Car Details Modal (Demo Only) - Only initialize if modals exist
const carModal = document.getElementById("carModal");
const inquiryModal = document.getElementById("inquiryModal");

// Set up close buttons for all modals
const allCloseBtns = document.querySelectorAll(".close");
if (allCloseBtns) {
  allCloseBtns.forEach((btn) => {
    btn.onclick = function () {
      this.closest(".modal").classList.remove("show");
    };
  });
}

if (carModal || inquiryModal) {
  const carTitle = document.getElementById("carTitle");
  const carPrice = document.getElementById("carPrice");
  const carPower = document.getElementById("carPower");
  const carAcceleration = document.getElementById("carAcceleration");
  const carTopSpeed = document.getElementById("carTopSpeed");
  const carYear = document.getElementById("carYear");
  const carColor = document.getElementById("carColor");
  const carTransmission = document.getElementById("carTransmission");
  const carDescription = document.getElementById("carDescription");
  const inquiryCar = document.getElementById("inquiryCar");

  const carData = [
    {
      title: "Ferrari SF90 Stradale",
      price: "$850,000",
      power: "986 HP",
      acceleration: "2.5s",
      topSpeed: "211 mph",
      year: "2023",
      color: "Red",
      transmission: "Automatic",
      description:
        "A plug-in hybrid supercar with blistering performance and Italian flair.",
    },
    {
      title: "Lamborghini Huracán STO",
      price: "$420,000",
      power: "631 HP",
      acceleration: "3.0s",
      topSpeed: "193 mph",
      year: "2024",
      color: "Orange",
      transmission: "Automatic",
      description:
        "Track-focused V10 supercar with aggressive aerodynamics and raw power.",
    },
    {
      title: "McLaren 765LT",
      price: "$1,200,000",
      power: "755 HP",
      acceleration: "2.7s",
      topSpeed: "205 mph",
      year: "2023",
      color: "Blue",
      transmission: "Automatic",
      description:
        "Lightweight, extreme, and exhilarating. The ultimate McLaren LT.",
    },
    {
      title: "Porsche 911 GT3 RS",
      price: "$280,000",
      power: "518 HP",
      acceleration: "3.2s",
      topSpeed: "184 mph",
      year: "2024",
      color: "White",
      transmission: "Automatic",
      description:
        "Precision-engineered for the track, yet road-legal. The ultimate 911.",
    },
    {
      title: "Bugatti Chiron Super Sport",
      price: "$3,500,000",
      power: "1578 HP",
      acceleration: "2.4s",
      topSpeed: "273 mph",
      year: "2022",
      color: "Black",
      transmission: "Automatic",
      description:
        "The pinnacle of hypercar engineering. Unmatched speed and luxury.",
    },
    {
      title: "Aston Martin DBS",
      price: "$650,000",
      power: "715 HP",
      acceleration: "3.4s",
      topSpeed: "211 mph",
      year: "2023",
      color: "Green",
      transmission: "Automatic",
      description:
        "British elegance meets brute force. A grand tourer like no other.",
    },
  ];

  window.viewCarDetails = function (idx) {
    if (!carModal) return;
    const car = carData[idx - 1];
    if (carTitle) carTitle.textContent = car.title;
    if (carPrice) carPrice.textContent = car.price;
    if (carPower) carPower.textContent = car.power;
    if (carAcceleration) carAcceleration.textContent = car.acceleration;
    if (carTopSpeed) carTopSpeed.textContent = car.topSpeed;
    if (carYear) carYear.textContent = car.year;
    if (carColor) carColor.textContent = car.color;
    if (carTransmission) carTransmission.textContent = car.transmission;
    if (carDescription) carDescription.textContent = car.description;
    carModal.classList.add("show");
    if (inquiryCar) inquiryCar.value = car.title;
  };

  window.inquireCar = function (idx) {
    if (!inquiryModal) return;
    const car = carData[idx - 1];
    if (inquiryCar) inquiryCar.value = car.title;
    inquiryModal.classList.add("show");
  };

  window.inquireCarModal = function () {
    if (!inquiryModal) return;
    if (inquiryCar && carTitle) inquiryCar.value = carTitle.textContent;
    inquiryModal.classList.add("show");
  };

  window.scheduleTestDrive = function () {
    alert("Test drive scheduling coming soon!");
  };

  // Inquiry Form Submission
  const inquiryForm = document.getElementById("inquiryForm");
  if (inquiryForm) {
    inquiryForm.onsubmit = function (e) {
      e.preventDefault();
      alert("Thank you for your inquiry! Our team will contact you soon.");
      inquiryModal.classList.remove("show");
      inquiryForm.reset();
    };
  }
}

// Add to Favorites (Demo Only)
window.addToFavorites = function (idx) {
  alert("Added to favorites! (Demo only)");
};

// Load More News (Demo Only)
window.loadMoreNews = function () {
  alert("More news coming soon!");
};

// Change Main Car Image (Demo Only)
window.changeMainImage = function (src) {
  const carMainImage = document.getElementById("carMainImage");
  if (carMainImage) carMainImage.src = src;
};

// Hero Image Shuffling Functionality
document.addEventListener("DOMContentLoaded", function () {
  console.log("DOM loaded, starting image shuffle...");

  const heroImages = document.querySelectorAll(".homeImage");
  const counterDots = document.querySelectorAll(".counter-dot");

  console.log("Found images:", heroImages.length);
  console.log("Found dots:", counterDots.length);

  if (heroImages.length === 0) {
    console.log("No images found!");
    return;
  }

  let currentIndex = 0;

  // Function to show image by index
  function showImage(imageIndex) {
    console.log("Showing image:", imageIndex);

    // Remove active class from all images (this will trigger fade out)
    heroImages.forEach((img, i) => {
      img.classList.remove("active");
    });

    // Wait for fade out, then show new image
    setTimeout(() => {
      // Show selected image
      heroImages[imageIndex].classList.add("active");

      // Update dots
      counterDots.forEach((dot, i) => {
        dot.classList.toggle("active", i === imageIndex);
      });

      currentIndex = imageIndex;
    }, 1250); // Half of the transition time for smooth crossfade
  }

  // Function to go to next image
  function nextImage() {
    const nextIndex = (currentIndex + 1) % heroImages.length;
    showImage(nextIndex);
  }

  // Initialize - show first image
  showImage(0);

  // Add click events to dots
  counterDots.forEach((dot, dotIndex) => {
    dot.addEventListener("click", () => {
      console.log("Dot clicked:", dotIndex);
      showImage(dotIndex);
    });
  });

  // Start automatic shuffling
  console.log("Starting automatic shuffle every 8 seconds...");
  setInterval(nextImage, 8000);

  // Test shuffle after 5 seconds
  setTimeout(() => {
    console.log("Testing first shuffle...");
    nextImage();
  }, 5000);
});
