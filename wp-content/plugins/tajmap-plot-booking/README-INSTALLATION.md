# TajMap Plot Booking - Installation Guide

## Overview
This package contains the essential components for porting the TajMap Plot Booking system to another WordPress site. It includes:

1. **User Side**: Interactive plot viewing canvas with zoom, pan, and plot selection
2. **Admin Side**: Advanced plot editor with drawing tools and management features

## What's Included

### Core Files
- `tajmap-plot-booking.php` - Main plugin file
- `includes/class-tajmap-pb.php` - Core plugin class
- `install-tajmap-plot-booking.php` - Installation script

### Templates
- `templates/admin-plot-editor.php` - Advanced plot editor (Admin)
- `templates/frontend/plot-selection-interactive.php` - User plot viewer
- `templates/frontend/plot-selection-canvas.php` - Alternative canvas viewer

### Assets
- `assets/plot-editor.js` - Admin canvas functionality
- `assets/frontend.css` - Frontend styling
- `assets/admin-enhanced.css` - Admin styling
- `assets/admin.js` - Admin functionality

## Installation Steps

### Method 1: Automated Installation (Recommended)

1. **Upload Files**
   ```bash
   # Upload the entire plugin folder to your WordPress plugins directory
   /wp-content/plugins/tajmap-plot-booking/
   ```

2. **Run Installation Script**
   - Navigate to: `yoursite.com/wp-content/plugins/tajmap-plot-booking/install-tajmap-plot-booking.php`
   - Follow the 4-step installation process
   - The script will:
     - Check system requirements
     - Create database tables
     - Configure pages and settings
     - Set up admin menus

3. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Find "TajMap Plot Booking" and click "Activate"

### Method 2: Manual Installation

1. **Upload Plugin Files**
   - Upload the plugin folder to `/wp-content/plugins/`
   - Ensure all files maintain their directory structure

2. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Activate "TajMap Plot Booking"

3. **Create Database Tables**
   - The plugin will automatically create required tables on activation
   - Tables created:
     - `wp_tajmap_plots` - Plot data
     - `wp_tajmap_leads` - Lead management
     - `wp_tajmap_users` - User data
     - `wp_tajmap_saved_plots` - User saved plots
     - `wp_tajmap_lead_history` - Lead tracking

4. **Create Pages**
   - Create a new page with the shortcode: `[tajmap_plot_canvas]`
   - Or use the auto-created "Available Plots" page

## Configuration

### Admin Setup
1. **Access Plot Editor**
   - Go to WordPress Admin → Plot Management → Plot Editor
   - Upload your base map image
   - Use drawing tools to create plots

2. **Configure Settings**
   - Go to Plot Management → Settings
   - Set company information
   - Configure email settings
   - Adjust display options

### Frontend Setup
1. **Available Plots Page**
   - Visit `/available-plots` (auto-created)
   - Or create a custom page with `[tajmap_plot_canvas]` shortcode

2. **Shortcode Options**
   ```php
   [tajmap_plot_canvas]           // Interactive canvas viewer
   [tajmap_plot_selection]        // Alternative plot selection
   [tajmap_landing_page]          // Landing page template
   [tajmap_gallery]               // Gallery view
   [tajmap_user_dashboard]        // User dashboard
   ```

## Features

### User Side (Available Plots)
- ✅ Interactive canvas with zoom and pan
- ✅ Plot selection and details
- ✅ Responsive design
- ✅ Pagination for large plot lists
- ✅ Lead capture forms
- ✅ User registration and saved plots

### Admin Side (Plot Editor)
- ✅ Advanced drawing tools
- ✅ Polygon creation and editing
- ✅ Base map management
- ✅ Plot status management
- ✅ Lead management system
- ✅ Analytics and reporting
- ✅ Export functionality

## System Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **Browser**: Modern browser with Canvas support
- **Memory**: 128MB PHP memory limit (recommended)

## Database Schema

### Plots Table (`wp_tajmap_plots`)
```sql
- id (BIGINT, Primary Key)
- plot_name (VARCHAR)
- street, sector, block (VARCHAR)
- coordinates (LONGTEXT, JSON)
- status (ENUM: 'available', 'sold')
- base_image_id (BIGINT)
- base_image_transform (LONGTEXT, JSON)
- created_at, updated_at (DATETIME)
```

### Leads Table (`wp_tajmap_leads`)
```sql
- id (BIGINT, Primary Key)
- plot_id (BIGINT, Foreign Key)
- phone, email (VARCHAR)
- message (TEXT)
- status (ENUM: 'new', 'contacted', 'interested', 'closed')
- source (VARCHAR)
- created_at (DATETIME)
```

## Troubleshooting

### Common Issues

1. **Canvas Not Loading**
   - Check browser console for JavaScript errors
   - Ensure jQuery is loaded
   - Verify AJAX endpoints are accessible

2. **Database Errors**
   - Check database permissions
   - Verify table creation
   - Check for plugin conflicts

3. **Images Not Displaying**
   - Verify image upload permissions
   - Check file paths and URLs
   - Ensure proper WordPress media handling

4. **AJAX Failures**
   - Check nonce verification
   - Verify user permissions
   - Check for plugin conflicts

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## File Structure
```
tajmap-plot-booking/
├── tajmap-plot-booking.php          # Main plugin file
├── install-tajmap-plot-booking.php  # Installation script
├── includes/
│   └── class-tajmap-pb.php          # Core plugin class
├── templates/
│   ├── admin-plot-editor.php         # Admin plot editor
│   └── frontend/
│       ├── plot-selection-interactive.php  # User plot viewer
│       └── plot-selection-canvas.php      # Alternative viewer
├── assets/
│   ├── plot-editor.js               # Admin canvas functionality
│   ├── frontend.css                 # Frontend styles
│   ├── admin-enhanced.css           # Admin styles
│   └── admin.js                     # Admin functionality
└── README-INSTALLATION.md           # This file
```

## Support

For technical support or questions:
1. Check the WordPress debug log
2. Verify all requirements are met
3. Test with default WordPress theme
4. Check for plugin conflicts

## Version History
- **v2.0.3** - Current version with canvas improvements
- **v2.0.2** - Added pagination and UI improvements
- **v2.0.1** - Fixed zoom and pan functionality
- **v2.0.0** - Major rewrite with canvas-based editor

## License
GPL v2 or later - Same as WordPress

