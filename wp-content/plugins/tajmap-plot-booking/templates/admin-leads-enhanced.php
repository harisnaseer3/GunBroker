<?php
if (!defined('ABSPATH')) { exit; }

if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}

global $wpdb;

// Get leads with enhanced filtering
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$search_filter = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

$where = [];
$params = [];

if ($status_filter && $status_filter !== 'all') {
    $where[] = 'l.status = %s';
    $params[] = $status_filter;
}

if ($search_filter) {
    $where[] = '(l.email LIKE %s OR l.phone LIKE %s OR p.plot_name LIKE %s)';
    $params[] = '%' . $wpdb->esc_like($search_filter) . '%';
    $params[] = '%' . $wpdb->esc_like($search_filter) . '%';
    $params[] = '%' . $wpdb->esc_like($search_filter) . '%';
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get all leads for Kanban board (not filtered)
$all_leads = $wpdb->get_results(
    'SELECT l.*, p.plot_name, p.street, p.sector, p.block, u.display_name as admin_name 
     FROM ' . TAJMAP_PB_TABLE_LEADS . ' l
     LEFT JOIN ' . TAJMAP_PB_TABLE_PLOTS . ' p ON p.id = l.plot_id
     LEFT JOIN ' . $wpdb->users . ' u ON u.ID = l.admin_user_id
     ORDER BY l.created_at DESC',
    ARRAY_A
);

// Get filtered leads for list view
$leads = $wpdb->get_results($wpdb->prepare(
    'SELECT l.*, p.plot_name, p.street, p.sector, p.block, u.display_name as admin_name 
     FROM ' . TAJMAP_PB_TABLE_LEADS . ' l
     LEFT JOIN ' . TAJMAP_PB_TABLE_PLOTS . ' p ON p.id = l.plot_id
     LEFT JOIN ' . $wpdb->users . ' u ON u.ID = l.admin_user_id
     ' . $where_clause . ' ORDER BY l.created_at DESC',
    $params
), ARRAY_A);

// Get lead statistics (always show all leads, not filtered)
$total_leads = $wpdb->get_var("SELECT COUNT(*) FROM " . TAJMAP_PB_TABLE_LEADS);
$new_count = $wpdb->get_var("SELECT COUNT(*) FROM " . TAJMAP_PB_TABLE_LEADS . " WHERE status = 'new'");
$contacted_count = $wpdb->get_var("SELECT COUNT(*) FROM " . TAJMAP_PB_TABLE_LEADS . " WHERE status = 'contacted'");
$interested_count = $wpdb->get_var("SELECT COUNT(*) FROM " . TAJMAP_PB_TABLE_LEADS . " WHERE status = 'interested'");
$closed_count = $wpdb->get_var("SELECT COUNT(*) FROM " . TAJMAP_PB_TABLE_LEADS . " WHERE status = 'closed'");

$export_url = wp_nonce_url(admin_url('admin-post.php?action=tajmap_pb_export_leads'), 'tajmap_pb_export');
?>
<div class="wrap tajmap-leads-management">
    <div class="leads-header">
        <h1>Leads Management</h1>
        <div class="leads-actions">
            <button class="button" onclick="exportLeads()">Export Leads</button>
            <button class="button" onclick="refreshLeads()">Refresh</button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="leads-stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_leads; ?></div>
            <div class="stat-label">Total Leads</div>
        </div>
        <div class="stat-card new">
            <div class="stat-number"><?php echo $new_count; ?></div>
            <div class="stat-label">New</div>
        </div>
        <div class="stat-card contacted">
            <div class="stat-number"><?php echo $contacted_count; ?></div>
            <div class="stat-label">Contacted</div>
        </div>
        <div class="stat-card interested">
            <div class="stat-number"><?php echo $interested_count; ?></div>
            <div class="stat-label">Interested</div>
        </div>
        <div class="stat-card closed">
            <div class="stat-number"><?php echo $closed_count; ?></div>
            <div class="stat-label">Closed</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="leads-filters">
        <form method="get" class="filter-form">
            <input type="hidden" name="page" value="tajmap-plot-leads">
            <select name="status" onchange="this.form.submit()">
                <option value="all" <?php selected($status_filter, 'all'); ?>>All Status</option>
                <option value="new" <?php selected($status_filter, 'new'); ?>>New</option>
                <option value="contacted" <?php selected($status_filter, 'contacted'); ?>>Contacted</option>
                <option value="interested" <?php selected($status_filter, 'interested'); ?>>Interested</option>
                <option value="closed" <?php selected($status_filter, 'closed'); ?>>Closed</option>
            </select>
            <input type="text" name="search" placeholder="Search leads..." value="<?php echo esc_attr($search_filter); ?>">
            <button type="submit" class="button">Search</button>
        </form>
    </div>

    <!-- View Toggle -->
    <div class="view-toggle">
        <button class="view-btn active" data-view="kanban">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            Kanban Board
        </button>
        <button class="view-btn" data-view="list">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="8" y1="6" x2="21" y2="6"></line>
                <line x1="8" y1="12" x2="21" y2="12"></line>
                <line x1="8" y1="18" x2="21" y2="18"></line>
                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                <line x1="3" y1="18" x2="3.01" y2="18"></line>
            </svg>
            List View
        </button>
    </div>

    <!-- Kanban Board View -->
    <div class="leads-kanban" id="kanban-view">
        <div class="kanban-columns">
            <!-- New Column -->
            <div class="kanban-column" data-status="new">
                <div class="column-header">
                    <h3>New</h3>
                    <span class="column-count"><?php echo $new_count; ?></span>
                </div>
                <div class="column-content" id="new-leads">
                    <?php foreach ($all_leads as $lead): ?>
                        <?php if ($lead['status'] === 'new'): ?>
                            <div class="lead-card" data-id="<?php echo $lead['id']; ?>">
                                <div class="lead-header">
                                    <h4><?php echo esc_html($lead['plot_name'] ?: 'Unknown Plot'); ?></h4>
                                    <div class="lead-status">
                                        <span class="status-badge new">New</span>
                                    </div>
                                </div>
                                <div class="lead-contact">
                                    <div class="contact-item">
                                        <strong><?php echo esc_html($lead['email']); ?></strong>
                                    </div>
                                    <div class="contact-item">
                                        <?php echo esc_html($lead['phone']); ?>
                                    </div>
                                </div>
                                <div class="lead-meta">
                                    <span class="lead-date"><?php echo human_time_diff(strtotime($lead['created_at']), current_time('timestamp')); ?> ago</span>
                                    <?php if ($lead['message']): ?>
                                        <span class="has-message" onclick="toggleMessage(<?php echo $lead['id']; ?>)" style="cursor: pointer;" title="Click to view message">
                                            💬 Has message
                                        </span>
                                        <div class="lead-message" id="message-<?php echo $lead['id']; ?>" style="display: none;">
                                            <?php echo esc_html($lead['message']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($lead['admin_name'])): ?>
                                        <span class="admin-name">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="12" cy="7" r="4"></circle>
                                            </svg>
                                            <?php echo esc_html($lead['admin_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="lead-actions">
                                    <button class="btn small primary" onclick="viewLead(<?php echo $lead['id']; ?>)">View</button>
                                    <button class="btn small secondary" onclick="contactLead(<?php echo $lead['id']; ?>)">Contact</button>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Contacted Column -->
            <div class="kanban-column" data-status="contacted">
                <div class="column-header">
                    <h3>Contacted</h3>
                    <span class="column-count"><?php echo $contacted_count; ?></span>
                </div>
                <div class="column-content" id="contacted-leads">
                    <?php foreach ($all_leads as $lead): ?>
                        <?php if ($lead['status'] === 'contacted'): ?>
                            <div class="lead-card contacted" data-id="<?php echo $lead['id']; ?>">
                                <div class="lead-header">
                                    <h4><?php echo esc_html($lead['plot_name'] ?: 'Unknown Plot'); ?></h4>
                                    <div class="lead-status">
                                        <span class="status-badge contacted">Contacted</span>
                                    </div>
                                </div>
                                <div class="lead-contact">
                                    <div class="contact-item">
                                        <strong><?php echo esc_html($lead['email']); ?></strong>
                                    </div>
                                    <div class="contact-item">
                                        <?php echo esc_html($lead['phone']); ?>
                                    </div>
                                </div>
                                <div class="lead-meta">
                                    <span class="lead-date"><?php echo human_time_diff(strtotime($lead['created_at']), current_time('timestamp')); ?> ago</span>
                                    <?php if ($lead['message']): ?>
                                        <span class="has-message" onclick="toggleMessage(<?php echo $lead['id']; ?>)" style="cursor: pointer;" title="Click to view message">
                                            💬 Has message
                                        </span>
                                        <div class="lead-message" id="message-<?php echo $lead['id']; ?>" style="display: none;">
                                            <?php echo esc_html($lead['message']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($lead['admin_name'])): ?>
                                        <span class="admin-name">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="12" cy="7" r="4"></circle>
                                            </svg>
                                            <?php echo esc_html($lead['admin_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="lead-actions">
                                    <button class="btn small primary" onclick="viewLead(<?php echo $lead['id']; ?>)">View</button>
                                    <button class="btn small success" onclick="markInterested(<?php echo $lead['id']; ?>)">Interested</button>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Interested Column -->
            <div class="kanban-column" data-status="interested">
                <div class="column-header">
                    <h3>Interested</h3>
                    <span class="column-count"><?php echo $interested_count; ?></span>
                </div>
                <div class="column-content" id="interested-leads">
                    <?php foreach ($all_leads as $lead): ?>
                        <?php if ($lead['status'] === 'interested'): ?>
                            <div class="lead-card interested" data-id="<?php echo $lead['id']; ?>">
                                <div class="lead-header">
                                    <h4><?php echo esc_html($lead['plot_name'] ?: 'Unknown Plot'); ?></h4>
                                    <div class="lead-status">
                                        <span class="status-badge interested">Interested</span>
                                    </div>
                                </div>
                                <div class="lead-contact">
                                    <div class="contact-item">
                                        <strong><?php echo esc_html($lead['email']); ?></strong>
                                    </div>
                                    <div class="contact-item">
                                        <?php echo esc_html($lead['phone']); ?>
                                    </div>
                                </div>
                                <div class="lead-meta">
                                    <span class="lead-date"><?php echo human_time_diff(strtotime($lead['created_at']), current_time('timestamp')); ?> ago</span>
                                    <?php if ($lead['message']): ?>
                                        <span class="has-message" onclick="toggleMessage(<?php echo $lead['id']; ?>)" style="cursor: pointer;" title="Click to view message">
                                            💬 Has message
                                        </span>
                                        <div class="lead-message" id="message-<?php echo $lead['id']; ?>" style="display: none;">
                                            <?php echo esc_html($lead['message']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($lead['admin_name'])): ?>
                                        <span class="admin-name">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="12" cy="7" r="4"></circle>
                                            </svg>
                                            <?php echo esc_html($lead['admin_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="lead-actions">
                                    <button class="btn small primary" onclick="viewLead(<?php echo $lead['id']; ?>)">View</button>
                                    <button class="btn small success" onclick="markClosed(<?php echo $lead['id']; ?>)">Close Deal</button>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Closed Column -->
            <div class="kanban-column" data-status="closed">
                <div class="column-header">
                    <h3>Closed</h3>
                    <span class="column-count"><?php echo $closed_count; ?></span>
                </div>
                <div class="column-content" id="closed-leads">
                    <?php foreach ($all_leads as $lead): ?>
                        <?php if ($lead['status'] === 'closed'): ?>
                            <div class="lead-card closed" data-id="<?php echo $lead['id']; ?>">
                                <div class="lead-header">
                                    <h4><?php echo esc_html($lead['plot_name'] ?: 'Unknown Plot'); ?></h4>
                                    <div class="lead-status">
                                        <span class="status-badge closed">Closed</span>
                                    </div>
                                </div>
                                <div class="lead-contact">
                                    <div class="contact-item">
                                        <strong><?php echo esc_html($lead['email']); ?></strong>
                                    </div>
                                    <div class="contact-item">
                                        <?php echo esc_html($lead['phone']); ?>
                                    </div>
                                </div>
                                <div class="lead-meta">
                                    <span class="lead-date"><?php echo human_time_diff(strtotime($lead['created_at']), current_time('timestamp')); ?> ago</span>
                                    <?php if ($lead['message']): ?>
                                        <span class="has-message" onclick="toggleMessage(<?php echo $lead['id']; ?>)" style="cursor: pointer;" title="Click to view message">
                                            💬 Has message
                                        </span>
                                        <div class="lead-message" id="message-<?php echo $lead['id']; ?>" style="display: none;">
                                            <?php echo esc_html($lead['message']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($lead['admin_name'])): ?>
                                        <span class="admin-name">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="12" cy="7" r="4"></circle>
                                            </svg>
                                            <?php echo esc_html($lead['admin_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="lead-actions">
                                    <button class="btn small primary" onclick="viewLead(<?php echo $lead['id']; ?>)">View</button>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- List View (Initially Hidden) -->
    <div class="leads-list" id="list-view" style="display: none;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Lead Info</th>
                    <th>Plot</th>
                    <th>Contact</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Source</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="leads-list-body">
                <?php if ($leads): ?>
                    <?php foreach ($leads as $lead): ?>
                        <tr data-id="<?php echo $lead['id']; ?>">
                            <td>
                                <div class="lead-info">
                                    <strong>ID: <?php echo $lead['id']; ?></strong>
                                </div>
                            </td>
                            <td>
                                <?php if ($lead['plot_name']): ?>
                                    <div class="plot-info">
                                        <strong><?php echo esc_html($lead['plot_name']); ?></strong>
                                        <?php if ($lead['sector'] || $lead['block']): ?>
                                            <div class="plot-details">
                                                <?php echo esc_html(trim(($lead['sector'] ?: '') . ' ' . ($lead['block'] ?: ''))); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="no-plot">No plot assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="contact-info">
                                    <div><strong><?php echo esc_html($lead['email']); ?></strong></div>
                                    <div><?php echo esc_html($lead['phone']); ?></div>
                                </div>
                            </td>
                            <td>
                                <?php if ($lead['message']): ?>
                                    <div class="message-preview" title="<?php echo esc_attr($lead['message']); ?>">
                                        <?php echo esc_html(substr($lead['message'], 0, 50) . (strlen($lead['message']) > 50 ? '...' : '')); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="no-message">No message</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <select class="lead-status-select" data-id="<?php echo $lead['id']; ?>" data-current-status="<?php echo esc_attr($lead['status']); ?>">
                                    <option value="new" <?php selected($lead['status'], 'new'); ?>>New</option>
                                    <option value="contacted" <?php selected($lead['status'], 'contacted'); ?>>Contacted</option>
                                    <option value="interested" <?php selected($lead['status'], 'interested'); ?>>Interested</option>
                                    <option value="closed" <?php selected($lead['status'], 'closed'); ?>>Closed</option>
                                </select>
                            </td>
                            <td>
                                <span class="lead-source"><?php echo ucfirst(esc_html($lead['source'])); ?></span>
                            </td>
                            <td>
                                <div class="lead-date">
                                    <div><?php echo date('M j, Y', strtotime($lead['created_at'])); ?></div>
                                    <div class="time-ago"><?php echo human_time_diff(strtotime($lead['created_at']), current_time('timestamp')); ?> ago</div>
                                </div>
                            </td>
                            <td>
                                <div class="lead-actions">
                                    <button class="btn small primary" onclick="viewLead(<?php echo $lead['id']; ?>)">View</button>
                                    <button class="btn small secondary" onclick="editLead(<?php echo $lead['id']; ?>)">Edit</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">
                            <div class="no-leads">
                                <p>No leads found matching your criteria.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Lead Details Modal -->
<div id="lead-modal" class="lead-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Lead Details</h3>
            <button id="lead-modal-close" class="close-btn">&times;</button>
        </div>
        <div id="lead-modal-body" class="modal-body">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<script type="text/javascript">
// Global functions for leads management
function viewLead(leadId) {
    if (typeof TajMapPB === 'undefined') {
        alert('Configuration error: TajMapPB not loaded');
        return;
    }
    
    // Load lead details in modal
    jQuery.post(TajMapPB.ajaxUrl, {
        action: 'tajmap_pb_get_lead_details',
        nonce: TajMapPB.nonce,
        lead_id: leadId
    }, function(response) {
        if (response.success) {
            showLeadModal(response.data.lead, response.data.history);
        } else {
            alert('Failed to load lead details: ' + (response.data || 'Unknown error'));
        }
    }).fail(function(xhr, status, error) {
        alert('AJAX error: ' + error);
    });
}

function showLeadModal(lead, history) {
    const modal = jQuery('#lead-modal');
    const body = jQuery('#lead-modal-body');
    
    // Show modal
    modal.show();

    body.html(`
        <div class="lead-details">
            <div class="details-grid">
                <div class="detail-section">
                    <h4>Lead Information</h4>
                    <div class="detail-item">
                        <label>Status:</label>
                        <select class="lead-status-select" data-id="${lead.id}">
                            <option value="new" ${lead.status === 'new' ? 'selected' : ''}>New</option>
                            <option value="contacted" ${lead.status === 'contacted' ? 'selected' : ''}>Contacted</option>
                            <option value="interested" ${lead.status === 'interested' ? 'selected' : ''}>Interested</option>
                            <option value="closed" ${lead.status === 'closed' ? 'selected' : ''}>Closed</option>
                        </select>
                    </div>
                    <div class="detail-item">
                        <label>Plot:</label>
                        <span>${lead.plot_name || 'Not specified'}</span>
                    </div>
                    <div class="detail-item">
                        <label>Email:</label>
                        <span>${lead.email}</span>
                    </div>
                    <div class="detail-item">
                        <label>Phone:</label>
                        <span>${lead.phone}</span>
                    </div>
                </div>

                <div class="detail-section">
                    <h4>Message</h4>
                    <div class="message-content">
                        ${lead.message || 'No message provided'}
                    </div>
                </div>

                <div class="detail-section">
                    <h4>Activity History</h4>
                    <div class="history-timeline">
                        ${history.map(h => `
                            <div class="history-item">
                                <div class="history-action">${h.action.replace('_', ' ')}</div>
                                <div class="history-details">${h.details || ''}</div>
                                <div class="history-time">${new Date(h.created_at).toLocaleString()}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        </div>
    `);

    // Bind status change handler
    jQuery('.lead-status-select').on('change', function() {
        const leadId = jQuery(this).data('id');
        const newStatus = jQuery(this).val();
        updateLeadStatus(leadId, newStatus);
    });
}

function updateLeadStatus(leadId, status) {
    // Show loading overlay
    showStatusLoadingOverlay();
    
    jQuery.post(TajMapPB.ajaxUrl, {
        action: 'tajmap_pb_set_lead_status',
        nonce: TajMapPB.nonce,
        id: leadId,
        status: status
    }, function(response) {
        if (response.success) {
            // Update UI accordingly
            location.reload();
        } else {
            hideStatusLoadingOverlay();
            alert('Failed to update lead status: ' + (response.data || 'Unknown error'));
        }
    }).fail(function(xhr, status, error) {
        hideStatusLoadingOverlay();
        alert('AJAX error: ' + error);
    });
}

function showStatusLoadingOverlay() {
    // Create loading overlay if it doesn't exist
    if (jQuery('#status-loading-overlay').length === 0) {
        jQuery('body').append(`
            <div id="status-loading-overlay" class="status-loading-overlay">
                <div class="loading-content">
                    <div class="loading-spinner"></div>
                    <div class="loading-text">Updating lead status...</div>
                    <div class="loading-subtext">Please wait while we process your request</div>
                    <div class="loading-progress">
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                        <div class="progress-text">Processing...</div>
                    </div>
                </div>
            </div>
        `);
    }
    jQuery('#status-loading-overlay').css('display', 'flex').hide().fadeIn(300);
    
    // Start progress animation
    startProgressAnimation();
}

function startProgressAnimation() {
    const progressFill = jQuery('.progress-fill');
    const progressText = jQuery('.progress-text');
    
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 90) progress = 90;
        
        progressFill.css('width', progress + '%');
        
        if (progress < 30) {
            progressText.text('Connecting to server...');
        } else if (progress < 60) {
            progressText.text('Updating database...');
        } else if (progress < 90) {
            progressText.text('Finalizing changes...');
        }
        
        if (progress >= 90) {
            clearInterval(interval);
        }
    }, 200);
}

function hideStatusLoadingOverlay() {
    jQuery('#status-loading-overlay').fadeOut(300, function() {
        jQuery(this).remove();
    });
}

function contactLead(leadId) {
    setButtonLoadingState(leadId, 'Contact');
    updateLeadStatus(leadId, 'contacted');
}

function toggleMessage(leadId) {
    const messageDiv = document.getElementById('message-' + leadId);
    if (messageDiv) {
        if (messageDiv.style.display === 'none') {
            messageDiv.style.display = 'block';
        } else {
            messageDiv.style.display = 'none';
        }
    }
}

function markInterested(leadId) {
    setButtonLoadingState(leadId, 'Interested');
    updateLeadStatus(leadId, 'interested');
}

function markClosed(leadId) {
    setButtonLoadingState(leadId, 'Close Deal');
    updateLeadStatus(leadId, 'closed');
}

function setButtonLoadingState(leadId, buttonText) {
    // Find the button and set loading state
    const button = jQuery(`button[onclick*="${leadId}"]`).filter(function() {
        return jQuery(this).text().trim() === buttonText;
    });
    
    if (button.length) {
        button.prop('disabled', true)
              .addClass('loading')
              .html('<span class="btn-spinner"></span> Processing...');
    }
    
    // Add loading animation to the lead card
    const leadCard = jQuery(`.lead-card[data-id="${leadId}"]`);
    if (leadCard.length) {
        leadCard.addClass('status-updating');
    }
}

function exportLeads() {
    window.location.href = '<?php echo $export_url; ?>';
}

function refreshLeads() {
    window.location.reload();
}

jQuery(document).ready(function($) {
    // Enhanced leads management functionality
    initializeLeadsManagement();

    function initializeLeadsManagement() {
        // View toggle functionality
        $('.view-btn').on('click', function() {
            const view = $(this).data('view');
            toggleView(view);
        });

        // Status change handlers
        $(document).on('change', '.lead-status-select', function() {
            const leadId = $(this).data('id');
            const newStatus = $(this).val();
            updateLeadStatus(leadId, newStatus);
        });
    }

    function toggleView(view) {
        $('.view-btn').removeClass('active');
        $(`.view-btn[data-view="${view}"]`).addClass('active');

        if (view === 'kanban') {
            $('#list-view').hide();
            $('#kanban-view').show();
        } else {
            $('#kanban-view').hide();
            $('#list-view').show();
        }
    }

    // Modal event handlers
    $(document).ready(function() {
        // Close modal when clicking close button
        $('#lead-modal-close').on('click', function() {
            $('#lead-modal').hide();
        });
        
        // Close modal when clicking overlay
        $('#lead-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).hide();
            }
        });
        
        // Close modal with Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#lead-modal').is(':visible')) {
                $('#lead-modal').hide();
            }
        });
    });
});
</script>

<style>
.leads-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.leads-actions {
    display: flex;
    gap: 10px;
}

.leads-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #333;
}

.stat-label {
    color: #666;
    margin-top: 5px;
}

.stat-card.new .stat-number { color: #0073aa; }
.stat-card.contacted .stat-number { color: #00a32a; }
.stat-card.interested .stat-number { color: #dba617; }
.stat-card.closed .stat-number { color: #d63638; }

.leads-filters {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.filter-form {
    display: flex;
    gap: 15px;
    align-items: center;
}

.filter-form select,
.filter-form input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.view-toggle {
    margin-bottom: 20px;
}

.view-btn {
    padding: 8px 16px;
    border: 1px solid #ddd;
    background: #fff;
    cursor: pointer;
}

.view-btn.active {
    background: #0073aa;
    color: #fff;
}

.leads-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.lead-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 20px;
    border-left: 4px solid #0073aa;
}

.lead-card.contacted { border-left-color: #00a32a; }
.lead-card.interested { border-left-color: #dba617; }
.lead-card.closed { border-left-color: #d63638; }

.lead-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.lead-header h4 {
    margin: 0;
    color: #333;
}

.lead-contact {
    margin-bottom: 15px;
}

.contact-item {
    margin-bottom: 5px;
}

.lead-actions {
    display: flex;
    gap: 10px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.new { background: #e3f2fd; color: #1976d2; }
.status-badge.contacted { background: #e8f5e8; color: #2e7d32; }
.status-badge.interested { background: #fff3e0; color: #f57c00; }
.status-badge.closed { background: #ffebee; color: #c62828; }

.lead-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: #fff;
    border-radius: 8px;
    max-width: 800px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #ddd;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
}

.modal-body {
    padding: 20px;
}

.lead-details {
    display: grid;
    gap: 20px;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.detail-section h4 {
    margin: 0 0 15px 0;
    color: #333;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 5px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.detail-item label {
    font-weight: 500;
    color: #555;
}

.detail-item span {
    color: #333;
}

.message-content {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    border-left: 4px solid #0073aa;
}

.history-timeline {
    max-height: 200px;
    overflow-y: auto;
}

.history-item {
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.history-action {
    font-weight: 500;
    color: #0073aa;
}

.history-details {
    color: #666;
    margin: 5px 0;
}

.history-time {
    font-size: 12px;
    color: #999;
}

.no-leads {
    text-align: center;
    padding: 40px;
    color: #666;
}

/* Kanban Board Styles */
.leads-kanban {
    margin-top: 20px;
    overflow-x: hidden; /* Prevent horizontal scroll on kanban container */
}

.kanban-columns {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    align-items: start; /* Align columns to top */
    width: 100%; /* Ensure it doesn't exceed container width */
    max-width: 100%; /* Prevent overflow */
}

.kanban-column {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    border: 1px solid #e9ecef;
    min-width: 0; /* Prevent column from exceeding grid cell width */
    overflow: hidden; /* Prevent content overflow */
}

.column-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #dee2e6;
}

.column-header h3 {
    margin: 0;
    color: #495057;
    font-size: 16px;
}

.column-count {
    background: #6c757d;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}

.column-content {
    height: 600px; /* Fixed height - roughly fits 4 leads */
    max-height: 600px; /* Maximum height */
    overflow-y: auto !important; /* FORCE vertical scrollbar when content exceeds height */
    overflow-x: hidden; /* Prevent horizontal scrollbar */
    padding-right: 5px; /* Space for scrollbar */
}

/* Custom scrollbar for column content */
.column-content::-webkit-scrollbar {
    width: 8px; /* Wider for better visibility */
}

.column-content::-webkit-scrollbar-track {
    background: #e5e7eb;
    border-radius: 4px;
}

.column-content::-webkit-scrollbar-thumb {
    background: #9ca3af; /* Darker for better visibility */
    border-radius: 4px;
}

.column-content::-webkit-scrollbar-thumb:hover {
    background: #6b7280; /* Even darker on hover */
}

.lead-card {
    background: #fff;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid #0073aa;
    transition: all 0.3s ease;
    word-wrap: break-word; /* Wrap long words */
    overflow-wrap: break-word; /* Break long words if needed */
    max-width: 100%; /* Don't exceed column width */
}

.lead-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.lead-card.contacted { border-left-color: #00a32a; }
.lead-card.interested { border-left-color: #dba617; }
.lead-card.closed { border-left-color: #d63638; }

.lead-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.lead-header h4 {
    margin: 0;
    color: #333;
    font-size: 14px;
    line-height: 1.3;
}

.lead-contact {
    margin-bottom: 10px;
}

.contact-item {
    margin-bottom: 3px;
    font-size: 12px;
    color: #666;
}

.lead-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    font-size: 11px;
    color: #999;
}

.has-message {
    background: #e3f2fd;
    color: #1976d2;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
    transition: all 0.2s ease;
}

.has-message:hover {
    background: #1976d2;
    color: white;
    transform: scale(1.05);
}

.lead-message {
    margin-top: 8px;
    padding: 10px;
    background: #f8f9fa;
    border-left: 3px solid #1976d2;
    border-radius: 4px;
    font-size: 12px;
    line-height: 1.5;
    color: #333;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
        margin-top: 0;
    }
    to {
        opacity: 1;
        max-height: 200px;
        margin-top: 8px;
    }
}

.admin-name {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #f3e8ff;
    color: #7c3aed;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    margin-left: 8px;
}

.admin-name svg {
    width: 12px;
    height: 12px;
    stroke: currentColor;
}

.lead-actions {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.btn {
    padding: 4px 8px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 11px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn.small {
    padding: 3px 6px;
    font-size: 10px;
}

.btn.primary {
    background: #0073aa;
    color: #fff;
}

.btn.secondary {
    background: #f0f0f0;
    color: #333;
}

.btn.success {
    background: #00a32a;
    color: #fff;
}

.btn:hover {
    opacity: 0.8;
    transform: translateY(-1px);
}

/* List View Styles */
.leads-list {
    margin-top: 20px;
}

.plot-info strong {
    color: #333;
    font-size: 14px;
}

.plot-details {
    color: #666;
    font-size: 12px;
    margin-top: 2px;
}

.contact-info {
    font-size: 13px;
}

.message-preview {
    max-width: 200px;
    font-size: 12px;
    color: #666;
}

.lead-date {
    font-size: 12px;
}

.time-ago {
    color: #999;
    font-size: 11px;
}

.lead-source {
    font-size: 12px;
    color: #666;
    text-transform: capitalize;
}

/* Status Loading Overlay */
.status-loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 99999;
    display: none;
    justify-content: center;
    align-items: center;
    flex-direction: column;
}

.loading-content {
    background: #fff;
    border-radius: 12px;
    padding: 40px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    max-width: 400px;
    width: 90%;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #0073aa;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-text {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.loading-subtext {
    font-size: 14px;
    color: #666;
    line-height: 1.4;
}

.loading-progress {
    margin-top: 20px;
}

.progress-bar {
    width: 100%;
    height: 6px;
    background: #f0f0f0;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 10px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0073aa, #00a32a);
    border-radius: 3px;
    width: 0%;
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 12px;
    color: #666;
    text-align: center;
}

/* Button Loading State */
.btn.loading {
    opacity: 0.7;
    cursor: not-allowed;
    position: relative;
}

.btn-spinner {
    display: inline-block;
    width: 12px;
    height: 12px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-right: 8px;
}

/* Lead Card Loading Animation */
.lead-card.status-updating {
    position: relative;
    overflow: hidden;
}

.lead-card.status-updating::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(0, 115, 170, 0.1), transparent);
    animation: shimmer 1.5s infinite;
    z-index: 1;
}

@keyframes shimmer {
    0% { left: -100%; }
    100% { left: 100%; }
}
</style>
