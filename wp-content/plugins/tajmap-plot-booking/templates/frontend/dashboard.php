<?php
if (!defined('ABSPATH')) { exit; }

$current_user = wp_get_current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo get_bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body>
    <div class="dashboard-page">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-content">
                <h1 class="page-title">My Dashboard</h1>
                <p class="page-subtitle">Welcome back, <?php echo esc_html($current_user->display_name); ?>!</p>
                <div class="user-actions">
                    <button class="btn secondary" onclick="window.location.href='/'">Back to Home</button>
                    <button class="btn outline" onclick="window.location.href='/plots'">Browse Plots</button>
                </div>
            </div>
        </header>

        <div class="dashboard-main">
            <!-- Sidebar Navigation -->
            <aside class="dashboard-sidebar">
                <nav class="sidebar-nav">
                    <button class="nav-item active" data-section="overview">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                        Overview
                    </button>
                    <button class="nav-item" data-section="saved-plots">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                        </svg>
                        Saved Plots
                    </button>
                    <button class="nav-item" data-section="inquiries">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        My Inquiries
                    </button>
                    <button class="nav-item" data-section="profile">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Profile Settings
                    </button>
                    <button class="nav-item" data-section="notifications">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        Notifications
                    </button>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="dashboard-content">
                <!-- Overview Section -->
                <section class="dashboard-section active" id="overview-section">
                    <div class="section-header">
                        <h2>Dashboard Overview</h2>
                        <p>Here's what's happening with your plot interests</p>
                    </div>

                    <div class="overview-grid">
                        <!-- Stats Cards -->
                        <div class="stat-card">
                            <div class="stat-icon">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                                </svg>
                            </div>
                            <div class="stat-info">
                                <h3 id="saved-count">0</h3>
                                <p>Saved Plots</p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                            </div>
                            <div class="stat-info">
                                <h3 id="inquiries-count">0</h3>
                                <p>Total Inquiries</p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                                </svg>
                            </div>
                            <div class="stat-info">
                                <h3 id="pending-count">0</h3>
                                <p>Pending Responses</p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12,6 12,12 16,14"></polyline>
                                </svg>
                            </div>
                            <div class="stat-info">
                                <h3 id="last-activity">Never</h3>
                                <p>Last Activity</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="activity-section">
                        <h3>Recent Activity</h3>
                        <div class="activity-list" id="recent-activity">
                            <div class="activity-empty">
                                <p>No recent activity</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Saved Plots Section -->
                <section class="dashboard-section" id="saved-plots-section">
                    <div class="section-header">
                        <h2>Saved Plots</h2>
                        <p>Plots you've bookmarked for future reference</p>
                    </div>

                    <div class="saved-plots-grid" id="saved-plots-grid">
                        <div class="loading-state">
                            <div class="loading-spinner"></div>
                            <p>Loading saved plots...</p>
                        </div>
                    </div>
                </section>

                <!-- Inquiries Section -->
                <section class="dashboard-section" id="inquiries-section">
                    <div class="section-header">
                        <h2>My Inquiries</h2>
                        <p>Track the status of your plot inquiries</p>
                    </div>

                    <div class="inquiries-container" id="inquiries-container">
                        <div class="loading-state">
                            <div class="loading-spinner"></div>
                            <p>Loading inquiries...</p>
                        </div>
                    </div>
                </section>

                <!-- Profile Section -->
                <section class="dashboard-section" id="profile-section">
                    <div class="section-header">
                        <h2>Profile Settings</h2>
                        <p>Manage your personal information and preferences</p>
                    </div>

                    <form id="profile-form" class="profile-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="profile-first-name">First Name</label>
                                <input type="text" id="profile-first-name" name="first_name" value="<?php echo esc_attr($current_user->first_name); ?>">
                            </div>
                            <div class="form-group">
                                <label for="profile-last-name">Last Name</label>
                                <input type="text" id="profile-last-name" name="last_name" value="<?php echo esc_attr($current_user->last_name); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="profile-email">Email Address</label>
                            <input type="email" id="profile-email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" readonly>
                            <small>Your email cannot be changed here. Contact support if needed.</small>
                        </div>

                        <div class="form-group">
                            <label for="profile-phone">Phone Number</label>
                            <input type="tel" id="profile-phone" name="phone" value="">
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn primary">Save Changes</button>
                        </div>
                    </form>
                </section>

                <!-- Notifications Section -->
                <section class="dashboard-section" id="notifications-section">
                    <div class="section-header">
                        <h2>Notification Preferences</h2>
                        <p>Choose how you want to be notified about your inquiries</p>
                    </div>

                    <div class="notifications-form">
                        <div class="notification-item">
                            <div class="notification-info">
                                <h4>Email Notifications</h4>
                                <p>Receive updates about your inquiries via email</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="email-notifications" checked>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div class="notification-item">
                            <div class="notification-info">
                                <h4>SMS Notifications</h4>
                                <p>Get SMS updates for urgent inquiries</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="sms-notifications">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div class="notification-item">
                            <div class="notification-info">
                                <h4>Marketing Updates</h4>
                                <p>Receive information about new plots and developments</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="marketing-updates">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn primary">Save Preferences</button>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
