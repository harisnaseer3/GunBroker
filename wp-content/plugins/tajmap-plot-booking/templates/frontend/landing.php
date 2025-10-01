<?php
if (!defined('ABSPATH')) { exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo get_bloginfo('name'); ?> - Premium Plot Booking</title>
    <?php wp_head(); ?>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-overlay">
            <div class="hero-content">
                <h1 class="hero-title">Discover Your Perfect Plot</h1>
                <p class="hero-subtitle">Premium residential plots in prime locations with interactive mapping and seamless booking experience</p>

                <!-- Search Bar -->
                <div class="search-container">
                    <div class="search-inputs">
                        <input type="text" id="search-sector" placeholder="Sector" class="search-input">
                        <input type="text" id="search-block" placeholder="Block" class="search-input">
                        <select id="search-status" class="search-input">
                            <option value="">All Status</option>
                            <option value="available">Available</option>
                            <option value="sold">Sold</option>
                        </select>
                        <button id="search-btn" class="search-btn">Search Plots</button>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number" id="total-plots">0</span>
                        <span class="stat-label">Total Plots</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" id="available-plots">0</span>
                        <span class="stat-label">Available</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" id="recent-leads">0</span>
                        <span class="stat-label">Recent Inquiries</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Plots Carousel -->
    <section class="featured-section">
        <div class="container">
            <h2 class="section-title">Featured Plots</h2>
            <div class="featured-carousel" id="featured-carousel">
                <div class="carousel-track" id="carousel-track">
                    <!-- Featured plots will be loaded here -->
                </div>
                <button class="carousel-btn carousel-btn-prev" id="carousel-prev">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15,18 9,12 15,6"></polyline>
                    </svg>
                </button>
                <button class="carousel-btn carousel-btn-next" id="carousel-next">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9,18 15,12 9,6"></polyline>
                    </svg>
                </button>
            </div>
        </div>
    </section>

    <!-- Interactive Map Preview -->
    <section class="map-preview-section">
        <div class="container">
            <h2 class="section-title">Explore Our Development</h2>
            <p class="section-subtitle">Click on any plot to view details and book your interest</p>

            <div class="map-preview-container">
                <div class="map-preview" id="map-preview">
                    <!-- Interactive map will be loaded here -->
                </div>
                <div class="map-legend">
                    <div class="legend-item">
                        <div class="legend-color available"></div>
                        <span>Available</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color sold"></div>
                        <span>Sold</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color reserved"></div>
                        <span>Reserved</span>
                    </div>
                </div>
            </div>

            <div class="cta-section">
                <button class="cta-btn primary" onclick="window.location.href='/plots'">View All Plots</button>
                <button class="cta-btn secondary" onclick="window.location.href='/gallery'">View Gallery</button>
            </div>
        </div>
    </section>

    <!-- Development Highlights -->
    <section class="highlights-section">
        <div class="container">
            <div class="highlights-grid">
                <div class="highlight-item">
                    <div class="highlight-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </div>
                    <h3>Prime Location</h3>
                    <p>Strategically located with excellent connectivity and infrastructure</p>
                </div>

                <div class="highlight-item">
                    <div class="highlight-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                            <path d="M2 17l10 5 10-5"></path>
                            <path d="M2 12l10 5 10-5"></path>
                        </svg>
                    </div>
                    <h3>Modern Infrastructure</h3>
                    <p>State-of-the-art amenities and world-class facilities</p>
                </div>

                <div class="highlight-item">
                    <div class="highlight-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9,22 9,12 15,12 15,22"></polyline>
                        </svg>
                    </div>
                    <h3>Secure Investment</h3>
                    <p>High appreciation potential with legal clarity and documentation</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section-final">
        <div class="container">
            <h2>Ready to Find Your Perfect Plot?</h2>
            <p>Join thousands of satisfied customers who found their dream property with us</p>
            <div class="cta-buttons">
                <button class="cta-btn primary large" onclick="window.location.href='/plots'">Start Exploring</button>
                <button class="cta-btn outline large" onclick="window.location.href='/contact'">Contact Us</button>
            </div>
        </div>
    </section>

    <?php wp_footer(); ?>
</body>
</html>
