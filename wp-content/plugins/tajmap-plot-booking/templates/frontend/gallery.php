<?php
if (!defined('ABSPATH')) { exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - <?php echo get_bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body>
    <div class="gallery-page">
        <!-- Header -->
        <header class="page-header">
            <div class="header-content">
                <h1 class="page-title">Development Gallery</h1>
                <p class="page-subtitle">Explore our premium development through stunning visuals</p>
                <button class="back-btn" onclick="window.location.href='/'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15,18 9,12 15,6"></polyline>
                    </svg>
                    Back to Home
                </button>
            </div>
        </header>

        <!-- Gallery Navigation -->
        <nav class="gallery-nav">
            <div class="nav-container">
                <button class="nav-btn active" data-category="all">All</button>
                <button class="nav-btn" data-category="aerial">Aerial Views</button>
                <button class="nav-btn" data-category="amenities">Amenities</button>
                <button class="nav-btn" data-category="infrastructure">Infrastructure</button>
                <button class="nav-btn" data-category="sample-plots">Sample Plots</button>
            </div>
        </nav>

        <!-- Gallery Grid -->
        <main class="gallery-main">
            <div class="gallery-grid" id="gallery-grid">
                <!-- Gallery items will be loaded here -->
            </div>

            <!-- Loading State -->
            <div class="loading-state" id="gallery-loading">
                <div class="loading-spinner"></div>
                <p>Loading gallery...</p>
            </div>

            <!-- Empty State -->
            <div class="empty-state" id="gallery-empty" style="display: none;">
                <div class="empty-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21,15 16,10 5,21"></polyline>
                    </svg>
                </div>
                <h3>No Images Found</h3>
                <p>This category doesn't have any images yet.</p>
            </div>
        </main>

        <!-- Lightbox Modal -->
        <div class="lightbox-overlay" id="lightbox-modal">
            <div class="lightbox-container">
                <button class="lightbox-close" id="lightbox-close">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>

                <button class="lightbox-nav lightbox-prev" id="lightbox-prev">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15,18 9,12 15,6"></polyline>
                    </svg>
                </button>

                <button class="lightbox-nav lightbox-next" id="lightbox-next">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9,18 15,12 9,6"></polyline>
                    </svg>
                </button>

                <div class="lightbox-content">
                    <img id="lightbox-image" src="" alt="">
                    <div class="lightbox-info">
                        <h3 id="lightbox-title"></h3>
                        <p id="lightbox-description"></p>
                        <div class="lightbox-meta">
                            <span id="lightbox-category"></span>
                            <span id="lightbox-date"></span>
                        </div>
                    </div>
                </div>

                <!-- Image Counter -->
                <div class="lightbox-counter">
                    <span id="current-image">1</span>
                    <span>of</span>
                    <span id="total-images">0</span>
                </div>
            </div>
        </div>

        <!-- Development Amenities Section -->
        <section class="amenities-section">
            <div class="container">
                <h2 class="section-title">Project Amenities</h2>
                <div class="amenities-grid">
                    <div class="amenity-item">
                        <div class="amenity-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                <path d="M2 17l10 5 10-5"></path>
                                <path d="M2 12l10 5 10-5"></path>
                            </svg>
                        </div>
                        <h3>Gated Community</h3>
                        <p>24/7 security with controlled access points</p>
                    </div>

                    <div class="amenity-item">
                        <div class="amenity-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </div>
                        <h3>Prime Location</h3>
                        <p>Excellent connectivity to major highways and city center</p>
                    </div>

                    <div class="amenity-item">
                        <div class="amenity-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9,22 9,12 15,12 15,22"></polyline>
                            </svg>
                        </div>
                        <h3>Modern Infrastructure</h3>
                        <p>Underground utilities, wide roads, and street lighting</p>
                    </div>

                    <div class="amenity-item">
                        <div class="amenity-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                                <line x1="9" y1="9" x2="9.01" y2="9"></line>
                                <line x1="15" y1="9" x2="15.01" y2="9"></line>
                            </svg>
                        </div>
                        <h3>Recreational Areas</h3>
                        <p>Parks, playgrounds, and community spaces</p>
                    </div>

                    <div class="amenity-item">
                        <div class="amenity-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path>
                            </svg>
                        </div>
                        <h3>Power Backup</h3>
                        <p>Reliable electricity supply with backup generators</p>
                    </div>

                    <div class="amenity-item">
                        <div class="amenity-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M7 16l-4-4 4-4"></path>
                                <path d="M17 8l4 4-4 4"></path>
                                <path d="M14 2l2 20"></path>
                            </svg>
                        </div>
                        <h3>Water Supply</h3>
                        <p>Continuous water supply with treatment facilities</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Location & Directions -->
        <section class="location-section">
            <div class="container">
                <h2 class="section-title">Location & Connectivity</h2>
                <div class="location-content">
                    <div class="location-info">
                        <h3>How to Reach Us</h3>
                        <div class="location-details">
                            <div class="location-item">
                                <strong>Address:</strong>
                                <p>Premium Development Project<br>Strategic Location, City</p>
                            </div>
                            <div class="location-item">
                                <strong>Nearest Landmark:</strong>
                                <p>5 minutes from Central Business District</p>
                            </div>
                            <div class="location-item">
                                <strong>Transportation:</strong>
                                <p>Well connected by major highways and public transport</p>
                            </div>
                        </div>

                        <div class="directions-btn">
                            <button class="cta-btn primary" onclick="window.open('https://maps.google.com', '_blank')">
                                Get Directions
                            </button>
                        </div>
                    </div>

                    <div class="location-map">
                        <div class="map-placeholder">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <p>Interactive map coming soon</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Download Brochure -->
        <section class="brochure-section">
            <div class="container">
                <div class="brochure-content">
                    <div class="brochure-info">
                        <h2>Download Project Brochure</h2>
                        <p>Get detailed information about our development, including floor plans, specifications, and investment details.</p>
                        <ul class="brochure-features">
                            <li>Complete project overview</li>
                            <li>Detailed plot layouts</li>
                            <li>Infrastructure specifications</li>
                            <li>Investment analysis</li>
                            <li>Legal documentation guide</li>
                        </ul>
                    </div>

                    <div class="brochure-download">
                        <button class="download-btn" onclick="downloadBrochure()">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7,10 12,15 17,10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Download Brochure (PDF)
                        </button>
                        <small>* Download requires registration</small>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
