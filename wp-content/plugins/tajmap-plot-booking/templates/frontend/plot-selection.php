<?php
if (!defined('ABSPATH')) { exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plot Selection - <?php echo get_bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body>
    <div class="plot-selection-page">
        <!-- Header -->
        <header class="page-header">
            <div class="header-content">
                <h1 class="page-title">Interactive Plot Selection</h1>
                <p class="page-subtitle">Click on any plot to view details and express your interest</p>
                <button class="back-btn" onclick="window.location.href='/'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15,18 9,12 15,6"></polyline>
                    </svg>
                    Back to Home
                </button>
            </div>
        </header>

        <div class="plot-selection-main">
            <!-- Filter Sidebar -->
            <aside class="filter-sidebar" id="filter-sidebar">
                <div class="filter-header">
                    <h3>Filter Plots</h3>
                    <button class="filter-toggle" id="filter-toggle">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9,18 15,12 9,6"></polyline>
                        </svg>
                    </button>
                </div>

                <div class="filter-content">
                    <div class="filter-group">
                        <label for="filter-sector">Sector</label>
                        <select id="filter-sector" class="filter-select">
                            <option value="">All Sectors</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="filter-block">Block</label>
                        <select id="filter-block" class="filter-select">
                            <option value="">All Blocks</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="filter-status">Status</label>
                        <select id="filter-status" class="filter-select">
                            <option value="">All Status</option>
                            <option value="available">Available</option>
                            <option value="sold">Sold</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Plot Search</label>
                        <input type="text" id="filter-search" placeholder="Search by plot name..." class="filter-input">
                    </div>

                    <div class="filter-actions">
                        <button id="apply-filters" class="filter-btn primary">Apply Filters</button>
                        <button id="clear-filters" class="filter-btn secondary">Clear All</button>
                    </div>

                    <!-- Results Summary -->
                    <div class="results-summary">
                        <p id="results-count">Showing all plots</p>
                    </div>
                </div>
            </aside>

            <!-- Map Container -->
            <main class="map-container">
                <div class="map-toolbar">
                    <div class="toolbar-left">
                        <button id="zoom-in" class="toolbar-btn" title="Zoom In">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                                <line x1="11" y1="8" x2="11" y2="14"></line>
                                <line x1="8" y1="11" x2="14" y2="11"></line>
                            </svg>
                        </button>
                        <button id="zoom-out" class="toolbar-btn" title="Zoom Out">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                                <line x1="8" y1="11" x2="14" y2="11"></line>
                            </svg>
                        </button>
                        <button id="fit-view" class="toolbar-btn" title="Fit to View">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9,22 9,12 15,12 15,22"></polyline>
                            </svg>
                        </button>
                    </div>

                    <div class="toolbar-right">
                        <div class="view-mode">
                            <button id="view-map" class="mode-btn active">Map View</button>
                            <button id="view-list" class="mode-btn">List View</button>
                        </div>
                    </div>
                </div>

                <!-- Interactive Map -->
                <div class="interactive-map" id="interactive-map">
                    <!-- SVG Map will be loaded here -->
                </div>

                <!-- List View -->
                <div class="list-view" id="list-view" style="display: none;">
                    <div class="list-container" id="plots-list">
                        <!-- Plots list will be loaded here -->
                    </div>
                </div>

                <!-- Loading Overlay -->
                <div class="loading-overlay" id="loading-overlay">
                    <div class="loading-spinner"></div>
                    <p>Loading plots...</p>
                </div>
            </main>

            <!-- Plot Details Panel -->
            <aside class="plot-details-panel" id="plot-details-panel">
                <div class="panel-header">
                    <h3 id="panel-title">Select a Plot</h3>
                    <button class="panel-close" id="panel-close">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <div class="panel-content" id="panel-content">
                    <div class="plot-placeholder">
                        <p>Click on any plot on the map to view its details here</p>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Plot Inquiry Modal -->
        <div class="modal-overlay" id="inquiry-modal">
            <div class="modal-container">
                <div class="modal-header">
                    <h3>Express Interest in Plot</h3>
                    <button class="modal-close" id="modal-close">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <div class="modal-body">
                    <!-- Progress Indicator -->
                    <div class="progress-indicator">
                        <div class="progress-step active" data-step="1">
                            <span class="step-number">1</span>
                            <span class="step-label">Plot Details</span>
                        </div>
                        <div class="progress-step" data-step="2">
                            <span class="step-number">2</span>
                            <span class="step-label">Your Information</span>
                        </div>
                        <div class="progress-step" data-step="3">
                            <span class="step-number">3</span>
                            <span class="step-label">Confirmation</span>
                        </div>
                    </div>

                    <!-- Step 1: Plot Summary -->
                    <div class="modal-step active" id="step-1">
                        <div class="plot-summary" id="plot-summary">
                            <!-- Plot details will be populated here -->
                        </div>
                        <button class="step-btn next-btn" onclick="nextStep()">Continue</button>
                    </div>

                    <!-- Step 2: User Information Form -->
                    <div class="modal-step" id="step-2">
                        <form id="inquiry-form" class="inquiry-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first-name">First Name *</label>
                                    <input type="text" id="first-name" name="first_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="last-name">Last Name *</label>
                                    <input type="text" id="last-name" name="last_name" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" required>
                            </div>

                            <div class="form-group">
                                <label for="phone">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" required>
                            </div>

                            <div class="form-group">
                                <label for="message">Message (Optional)</label>
                                <textarea id="message" name="message" rows="4" placeholder="Tell us about your requirements or any questions you have..."></textarea>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="step-btn prev-btn" onclick="prevStep()">Back</button>
                                <button type="submit" class="step-btn next-btn primary">Submit Inquiry</button>
                            </div>
                        </form>
                    </div>

                    <!-- Step 3: Confirmation -->
                    <div class="modal-step" id="step-3">
                        <div class="confirmation-content">
                            <div class="success-icon">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22,4 12,14.01 9,11.01"></polyline>
                                </svg>
                            </div>
                            <h3>Thank You!</h3>
                            <p>Your inquiry has been submitted successfully. Our team will contact you within 24 hours with more information about this plot.</p>
                            <div class="confirmation-details" id="confirmation-details">
                                <!-- Confirmation details will be shown here -->
                            </div>
                            <button class="step-btn primary" onclick="closeModal()">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
