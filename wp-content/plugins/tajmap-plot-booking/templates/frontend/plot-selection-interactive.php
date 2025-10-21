<?php
if (!defined('ABSPATH')) { exit; }
// Background image is now handled by the global base map in canvas, not as CSS background
?>
<div class="tajmap-interactive-plot-selection">
    <!-- Header -->
    <div class="plot-header">
        <h1>Available Plots</h1>
        <p>Interactive Plot Selection - Click and drag to explore, zoom to see details</p>
    </div>

    <!-- Main Content -->
    <div class="plot-main">
        <!-- Interactive Map Container -->
        <div class="map-container">
            <div class="map-header">
                <div class="map-controls">
                    <button id="zoom-in" class="control-btn" title="Zoom In">+</button>
                    <button id="zoom-out" class="control-btn" title="Zoom Out">-</button>
                    <button id="fit-view" class="control-btn" title="Fit to View">⌂</button>
                    <button id="reset-view" class="control-btn" title="Reset View">↻</button>
                </div>
                <div class="zoom-level">
                    <span id="zoom-percentage">100%</span>
                </div>
            </div>
            
            <div id="interactive-map" class="interactive-map">
                <div class="loading-overlay" id="loading-overlay">
                    <div class="loading-spinner"></div>
                    <p>Loading plots...</p>
                </div>
                <canvas id="plot-canvas" width="800" height="600"></canvas>
            </div>
        </div>

        <!-- Hover Popup - Moved outside container for proper positioning -->
        <div id="plot-hover-popup" class="plot-hover-popup" style="display: none;">
            <div class="popup-content">
                <div class="popup-header">
                    <h4 id="popup-plot-name">Plot Name</h4>
                    <span id="popup-plot-status" class="status-badge">Available</span>
                </div>
                <div class="popup-body">
                    <div class="popup-details">
                        <div class="detail-row">
                            <span class="label">Sector:</span>
                            <span id="popup-plot-sector">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Type:</span>
                            <span id="popup-plot-type">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Category:</span>
                            <span id="popup-plot-category">-</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Street:</span>
                            <span id="popup-plot-street">-</span>
                        </div>
                        <div class="detail-row" id="popup-description-row" style="display: none;">
                            <span class="label">Description:</span>
                            <span id="popup-plot-description">-</span>
                        </div>
                    </div>
                    <div class="popup-actions">
                        <button id="popup-contact-btn" class="contact-btn" onclick="openContactForm()">
                            📝 Inquire Plot
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Plot Details Panel -->
        <div class="plot-details-panel">
            <div class="panel-header">
                <h3>Plot Details</h3>
            </div>
            <div class="panel-content">
                <div id="plot-info" class="plot-info">
                    <div class="placeholder">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27,6.96 12,12.01 20.73,6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                        <p>Click on a plot to view details</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Plot List -->
    <div class="plot-list-section">
        <h3>All Plots (<span id="plot-count">0</span>)</h3>
        <div id="plot-list" class="plot-list">
            <!-- Plots will be loaded here -->
        </div>
    </div>
    
    <!-- Contact Form Modal -->
    <div id="contact-modal" class="contact-modal" style="display: none;">
        <div class="modal-overlay" onclick="closeContactForm()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Contact Admin</h3>
                <button class="close-btn" onclick="closeContactForm()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="contact-form">
                    <div class="form-group">
                        <label for="contact-name">Name *</label>
                        <input type="text" id="contact-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="contact-email">Email (Optional)</label>
                        <input type="email" id="contact-email" name="email">
                    </div>
                    <div class="form-group">
                        <label for="contact-phone">Contact Number *</label>
                        <input type="tel" id="contact-phone" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label for="contact-message">Message *</label>
                        <textarea id="contact-message" name="message" rows="4" required placeholder="Tell us about your interest in this plot..."></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeContactForm()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.tajmap-interactive-plot-selection {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
    position: relative;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    background: #f8fafc;
}

.tajmap-interactive-plot-selection::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(2px);
    border-radius: 16px;
    z-index: 0;
    pointer-events: none;
}

.tajmap-interactive-plot-selection > * {
    position: relative;
    z-index: 1;
}

.plot-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 30px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    color: white;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
    animation: fadeInDown 0.6s ease-out;
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.plot-header h1 {
    font-size: 2.5rem;
    color: white;
    margin-bottom: 10px;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.plot-header p {
    color: rgba(255,255,255,0.9);
    font-size: 1.1rem;
    font-weight: 400;
}

.plot-main {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.map-container {
    background: white;
    border: none;
    border-radius: 16px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    animation: fadeInUp 0.7s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.map-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-bottom: none;
}

.map-controls {
    display: flex;
    gap: 10px;
}

.control-btn {
    width: 44px;
    height: 44px;
    border: none;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    border-radius: 8px;
    cursor: pointer;
    font-size: 20px;
    font-weight: bold;
    color: white;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.control-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 6px 20px rgba(0,0,0,0.25);
}

.control-btn:active {
    transform: translateY(0) scale(0.98);
}

.zoom-level {
    color: white;
    font-size: 15px;
    font-weight: 600;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    padding: 8px 16px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.interactive-map {
    position: relative;
    width: 100%;
    max-width: 1200px; /* match admin canvas container width */
    height: 600px;
    background: #ffffff; /* clean canvas background */
    overflow: hidden;
    margin: 0 auto; /* center the canvas */
}

#plot-canvas {
    width: 100%;
    height: 100%;
    cursor: default; /* Normal cursor by default - changes to pointer over plots */
    display: block;
}

.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.95) 0%, rgba(118, 75, 162, 0.95) 100%);
    backdrop-filter: blur(10px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 5px solid rgba(255,255,255,0.3);
    border-top: 5px solid white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-overlay p {
    color: white;
    font-size: 18px;
    margin: 0;
    font-weight: 500;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.plot-details-panel {
    display: none;
}

/* Hide common theme sidebars/widgets on this page */
body .sidebar,
body #secondary,
body .widget-area,
body .right-sidebar,
body .site-sidebar {
    display: none !important;
}

/* Expand content area if theme uses a grid with sidebar */
body .content-area,
body .site-main,
body .site-content,
body .container,
body .wrap {
    max-width: 100% !important;
    width: 100% !important;
}

.panel-header {
    background: #f8fafc;
    padding: 15px 20px;
    border-bottom: 1px solid #e5e7eb;
}

.panel-header h3 {
    margin: 0;
    color: #1f2937;
    font-size: 1.2rem;
}

.panel-content {
    padding: 20px;
}

.plot-info {
    color: #6b7280;
}

.placeholder {
    text-align: center;
    color: #9ca3af;
}

.placeholder svg {
    margin-bottom: 15px;
}

.plot-list-section {
    margin-top: 40px;
    animation: fadeInUp 0.9s ease-out;
}

.plot-list-section h3 {
    color: #1f2937;
    margin-bottom: 20px;
    font-size: 1.8rem;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.plot-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.plot-item {
    background: white;
    border: none;
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    position: relative;
    overflow: hidden;
}

.plot-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.plot-item:hover::before {
    transform: scaleX(1);
}

.plot-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(102, 126, 234, 0.25);
}

.plot-item.selected {
    border: 2px solid #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
}

.plot-item.selected::before {
    transform: scaleX(1);
}

.plot-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
    font-size: 1.1rem;
}

.plot-status {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.plot-status.available {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.plot-status.sold {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.plot-status.reserved {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.plot-details {
    font-size: 0.9rem;
    color: #6b7280;
    line-height: 1.4;
}

.btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    margin-top: 15px;
    width: 100%;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.btn:active {
    transform: translateY(0);
}

.btn-primary {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-primary:hover {
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
}

/* Hover Popup Styles */
.plot-hover-popup {
    position: fixed;
    background: white;
    border: none;
    border-radius: 12px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
    z-index: 10000;
    pointer-events: auto;
    max-width: 300px;
    opacity: 0;
    transform: translateY(-10px) scale(0.95);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: default;
    overflow: hidden;
}

.plot-hover-popup::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.plot-hover-popup.show {
    opacity: 1;
    transform: translateY(0) scale(1);
}

.popup-content {
    padding: 0;
}

.popup-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 4px;
}

.popup-header h4 {
    margin: 0;
    font-size: 17px;
    font-weight: 700;
}

.status-badge {
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(10px);
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.sold {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.status-badge.available {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.status-badge.reserved {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.popup-body {
    padding: 20px;
}

.popup-details {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    font-size: 14px;
}

.detail-row .label {
    font-weight: 700;
    color: #667eea;
    min-width: 80px;
}

.detail-row span:last-child {
    color: #1f2937;
    text-align: right;
    flex: 1;
    margin-left: 8px;
    font-weight: 500;
}

/* Contact Button in Popup */
.popup-actions {
    padding: 16px 20px;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
}

.contact-btn {
    width: 100%;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    justify-content: center;
    gap: 6px;
    pointer-events: auto; /* Ensure button is clickable */
}

.contact-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
}

.contact-btn:active {
    transform: translateY(0);
}

/* Contact Modal Styles */
.contact-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
}

.modal-content {
    position: relative;
    background: white;
    border-radius: 16px;
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.25);
    max-width: 550px;
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    animation: modalSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-30px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px 28px;
    border-bottom: none;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.modal-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: white;
}

.close-btn {
    background: rgba(255,255,255,0.2);
    border: none;
    font-size: 28px;
    color: white;
    cursor: pointer;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: rotate(90deg);
}

.modal-body {
    padding: 28px;
    max-height: calc(90vh - 100px);
    overflow-y: auto;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #1f2937;
    font-size: 15px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s ease;
    font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

.form-actions {
    display: flex;
    gap: 16px;
    justify-content: flex-end;
    margin-top: 28px;
    padding-top: 24px;
    border-top: 1px solid #e5e7eb;
}

.btn-secondary {
    background: #6b7280;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-secondary:hover {
    background: #4b5563;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
}

.btn-primary {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-primary:disabled {
    background: #9ca3af;
    cursor: not-allowed;
}

/* Pagination */
.pagination {
    display: flex;
    gap: 8px;
    justify-content: center;
    align-items: center;
    margin-top: 16px;
}
.page-btn {
    background: #ffffff;
    color: #374151;
    border: 1px solid #e5e7eb;
    border-radius: 9999px;
    padding: 6px 12px;
    font-size: 13px;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    transition: all 0.15s ease;
}
.page-btn:hover { background: #f9fafb; border-color: #d1d5db; transform: translateY(-1px); }
.page-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
.page-btn.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.page-dots { color: #9ca3af; font-size: 13px; padding: 0 4px; }

@media (max-width: 768px) {
    .plot-main {
        grid-template-columns: 1fr;
    }
    
    .plot-details-panel {
        order: -1;
    }
    
    .interactive-map {
        height: 400px;
    }
}
</style>

