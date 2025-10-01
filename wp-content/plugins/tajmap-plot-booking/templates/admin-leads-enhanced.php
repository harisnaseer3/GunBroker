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

$leads = $wpdb->get_results($wpdb->prepare(
    'SELECT l.*, p.plot_name, p.street, p.sector, p.block FROM ' . TAJMAP_PB_TABLE_LEADS . ' l
     LEFT JOIN ' . TAJMAP_PB_TABLE_PLOTS . ' p ON p.id = l.plot_id
     ' . $where_clause . ' ORDER BY l.created_at DESC',
    $params
), ARRAY_A);

// Get lead statistics
$total_leads = count($leads);
$new_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . TAJMAP_PB_TABLE_LEADS . " WHERE status = %s " . $where_clause, array_merge(['new'], $params)));
$contacted_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . TAJMAP_PB_TABLE_LEADS . " WHERE status = %s " . $where_clause, array_merge(['contacted'], $params)));
$interested_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . TAJMAP_PB_TABLE_LEADS . " WHERE status = %s " . $where_clause, array_merge(['interested'], $params)));
$closed_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . TAJMAP_PB_TABLE_LEADS . " WHERE status = %s " . $where_clause, array_merge(['closed'], $params)));

$export_url = wp_nonce_url(admin_url('admin-post.php?action=tajmap_pb_export_leads'), 'tajmap_pb_export');
?>
<div class="wrap tajmap-leads-management">
    <div class="leads-header">
        <h1>Leads Management</h1>
        <div class="leads-actions">
            <button class="btn primary" onclick="exportLeads()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7,10 12,15 17,10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Export CSV
            </button>
            <button class="btn secondary" onclick="refreshLeads()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23,4 23,10 17,10"></polyline>
                    <polyline points="1,20 1,14 7,14"></polyline>
                    <path d="M20.49,9A9,9,0,0,0,5.64,5.64L1,10m22,4l-4.64,4.36A9,9,0,0,1,3.51,15"></path>
                </svg>
                Refresh
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="leads-stats">
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($total_leads); ?></div>
            <div class="stat-label">Total Leads</div>
        </div>
        <div class="stat-card new">
            <div class="stat-value"><?php echo number_format($new_count); ?></div>
            <div class="stat-label">New</div>
        </div>
        <div class="stat-card contacted">
            <div class="stat-value"><?php echo number_format($contacted_count); ?></div>
            <div class="stat-label">Contacted</div>
        </div>
        <div class="stat-card interested">
            <div class="stat-value"><?php echo number_format($interested_count); ?></div>
            <div class="stat-label">Interested</div>
        </div>
        <div class="stat-card closed">
            <div class="stat-value"><?php echo number_format($closed_count); ?></div>
            <div class="stat-label">Closed</div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="leads-filters">
        <div class="filter-row">
            <div class="search-box">
                <input type="text" id="leads-search" placeholder="Search by email, phone, or plot name..." value="<?php echo esc_attr($search_filter); ?>">
                <button id="search-btn" class="search-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </button>
            </div>

            <div class="filter-controls">
                <select id="status-filter" class="filter-select">
                    <option value="">All Status</option>
                    <option value="new" <?php selected($status_filter, 'new'); ?>>New</option>
                    <option value="contacted" <?php selected($status_filter, 'contacted'); ?>>Contacted</option>
                    <option value="interested" <?php selected($status_filter, 'interested'); ?>>Interested</option>
                    <option value="closed" <?php selected($status_filter, 'closed'); ?>>Closed</option>
                </select>

                <select id="sort-by" class="filter-select">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                    <option value="status">By Status</option>
                    <option value="plot">By Plot</option>
                </select>

                <button id="clear-filters" class="btn secondary small">Clear</button>
            </div>
        </div>
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
                    <?php foreach ($leads as $lead): ?>
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
                                        <span class="has-message">Has message</span>
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
                    <?php foreach ($leads as $lead): ?>
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
                    <?php foreach ($leads as $lead): ?>
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
                    <?php foreach ($leads as $lead): ?>
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

    <!-- Lead Details Modal -->
    <div class="modal-overlay" id="lead-modal">
        <div class="modal-container large">
            <div class="modal-header">
                <h3>Lead Details</h3>
                <button class="modal-close" id="lead-modal-close">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="modal-body" id="lead-modal-body">
                <div class="loading-state">
                    <div class="loading-spinner"></div>
                    <p>Loading lead details...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
(function($) {
    // Enhanced leads management functionality
    $(document).ready(function() {
        initializeLeadsManagement();
    });

    function initializeLeadsManagement() {
        // View toggle functionality
        $('.view-btn').on('click', function() {
            const view = $(this).data('view');
            toggleView(view);
        });

        // Status filter functionality
        $('#status-filter, #sort-by').on('change', function() {
            applyFilters();
        });

        // Search functionality
        $('#leads-search').on('input', debounce(applyFilters, 300));

        // Status change handlers
        $(document).on('change', '.lead-status-select', function() {
            const leadId = $(this).data('id');
            const newStatus = $(this).val();
            updateLeadStatus(leadId, newStatus);
        });

        // Lead card actions
        $(document).on('click', '.lead-card .btn', function(e) {
            e.stopPropagation();
            const action = $(this).text().toLowerCase();
            const leadId = $(this).closest('.lead-card').data('id');

            switch(action) {
                case 'view':
                    viewLead(leadId);
                    break;
                case 'contact':
                    contactLead(leadId);
                    break;
                case 'interested':
                    markInterested(leadId);
                    break;
                case 'close deal':
                    markClosed(leadId);
                    break;
            }
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

    function applyFilters() {
        const status = $('#status-filter').val();
        const search = $('#leads-search').val();
        const sortBy = $('#sort-by').val();

        // Update URL parameters
        const url = new URL(window.location);
        if (status) url.searchParams.set('status', status);
        if (search) url.searchParams.set('search', search);
        if (sortBy !== 'newest') url.searchParams.set('sort', sortBy);

        window.location.href = url.toString();
    }

    function updateLeadStatus(leadId, status) {
        $.post(TajMapPB.ajaxUrl, {
            action: 'tajmap_pb_set_lead_status',
            nonce: TajMapPB.nonce,
            id: leadId,
            status: status
        }, function(response) {
            if (response.success) {
                // Update UI accordingly
                $(`.lead-card[data-id="${leadId}"], tr[data-id="${leadId}"]`).fadeOut(300, function() {
                    // Move to appropriate column or update status
                    location.reload();
                });
            } else {
                alert('Failed to update lead status');
            }
        });
    }

    function viewLead(leadId) {
        // Load lead details in modal
        $.post(TajMapPB.ajaxUrl, {
            action: 'tajmap_pb_get_lead_details',
            nonce: TajMapPB.nonce,
            lead_id: leadId
        }, function(response) {
            if (response.success) {
                showLeadModal(response.data.lead, response.data.history);
            } else {
                alert('Failed to load lead details');
            }
        });
    }

    function showLeadModal(lead, history) {
        const modal = $('#lead-modal');
        const body = $('#lead-modal-body');

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

                        <div class="add-note">
                            <h5>Add Note</h5>
                            <textarea id="lead-note" placeholder="Add a note about this lead..."></textarea>
                            <button class="btn primary small" onclick="addLeadNote(${lead.id})">Add Note</button>
                        </div>
                    </div>
                </div>
            </div>
        `);

        modal.show();
    }

    function addLeadNote(leadId) {
        const note = $('#lead-note').val().trim();
        if (!note) return;

        $.post(TajMapPB.ajaxUrl, {
            action: 'tajmap_pb_add_lead_note',
            nonce: TajMapPB.nonce,
            lead_id: leadId,
            note: note
        }, function(response) {
            if (response.success) {
                $('#lead-note').val('');
                // Refresh modal content
                viewLead(leadId);
            } else {
                alert('Failed to add note');
            }
        });
    }

    function contactLead(leadId) {
        updateLeadStatus(leadId, 'contacted');
    }

    function markInterested(leadId) {
        updateLeadStatus(leadId, 'interested');
    }

    function markClosed(leadId) {
        updateLeadStatus(leadId, 'closed');
    }

    function exportLeads() {
        window.location.href = '<?php echo $export_url; ?>';
    }

    function refreshLeads() {
        window.location.reload();
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

})(jQuery);
</script>
