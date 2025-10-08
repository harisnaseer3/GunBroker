# TajMap Plot Booking - Complete Deployment Guide

## 🎯 Overview
This guide provides everything needed to port the TajMap Plot Booking system from GunBroker to another WordPress site, focusing on the two main components:

1. **User Side**: Available Plots (Interactive Canvas Viewer)
2. **Admin Side**: Advanced Plot Editor

## 📦 What You Get

### Core Functionality
- ✅ Interactive canvas for plot viewing (User side)
- ✅ Advanced plot editor with drawing tools (Admin side)
- ✅ Lead management system
- ✅ Database integration
- ✅ Responsive design
- ✅ AJAX-powered interface

### Files Included
```
tajmap-plot-booking/
├── 📄 Main Files
│   ├── tajmap-plot-booking.php          # Plugin entry point
│   ├── install-tajmap-plot-booking.php  # Automated installer
│   ├── package-for-deployment.php       # Package creator
│   └── README-INSTALLATION.md           # Detailed docs
│
├── 📁 Core Logic
│   └── includes/class-tajmap-pb.php     # Main plugin class
│
├── 🎨 Templates
│   ├── admin-plot-editor.php            # Admin plot editor
│   └── frontend/
│       ├── plot-selection-interactive.php # User plot viewer
│       └── plot-selection-canvas.php     # Alternative viewer
│
├── 🎨 Assets
│   ├── plot-editor.js                   # Admin canvas functionality
│   ├── frontend.css                     # User interface styles
│   ├── admin-enhanced.css               # Admin interface styles
│   └── admin.js                         # Admin functionality
│
└── 📚 Documentation
    ├── README-INSTALLATION.md            # Installation guide
    └── DEPLOYMENT-GUIDE.md              # This file
```

## 🚀 Quick Start (3 Methods)

### Method 1: Automated Installation (Recommended)
1. **Upload Files**: Upload the entire plugin folder to `/wp-content/plugins/`
2. **Run Installer**: Visit `yoursite.com/wp-content/plugins/tajmap-plot-booking/install-tajmap-plot-booking.php`
3. **Follow Steps**: Complete the 4-step installation process
4. **Activate Plugin**: Go to WordPress Admin → Plugins → Activate

### Method 2: Package Deployment
1. **Create Package**: Run `package-for-deployment.php` to create a clean deployment package
2. **Download Package**: Get the files from `deployment-package/` folder
3. **Upload to Target Site**: Upload to new WordPress site
4. **Run Installation**: Use the installation script on the new site

### Method 3: Manual Installation
1. **Upload Plugin**: Upload files maintaining directory structure
2. **Activate Plugin**: Activate in WordPress admin
3. **Create Pages**: Add `[tajmap_plot_canvas]` shortcode to a page
4. **Configure**: Set up plots and base map in admin

## 🛠️ Installation Process

### Step 1: System Requirements
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+
- Modern browser with Canvas support
- 128MB PHP memory limit

### Step 2: Database Setup
The installer automatically creates these tables:
- `wp_tajmap_plots` - Plot data and coordinates
- `wp_tajmap_leads` - Lead management
- `wp_tajmap_users` - User data
- `wp_tajmap_saved_plots` - User saved plots
- `wp_tajmap_lead_history` - Lead tracking

### Step 3: Page Creation
- **Available Plots Page**: Auto-created at `/available-plots`
- **Admin Menu**: Plot Management menu in WordPress admin
- **Shortcodes**: Available for custom page integration

### Step 4: Configuration
- **Base Map**: Upload your site map image
- **Plots**: Create plots using the drawing tools
- **Settings**: Configure company info and preferences

## 🎨 User Interface

### User Side (Available Plots)
- **Interactive Canvas**: Zoom, pan, and explore plots
- **Plot Selection**: Click plots to view details
- **Lead Capture**: Contact forms for interested users
- **Responsive Design**: Works on all devices
- **Pagination**: Handles large numbers of plots

### Admin Side (Plot Editor)
- **Drawing Tools**: Create and edit plot polygons
- **Base Map Management**: Upload and position site maps
- **Plot Management**: Add, edit, delete plots
- **Lead Management**: Track and manage inquiries
- **Analytics**: View reports and statistics

## 🔧 Configuration Options

### Admin Settings
```php
// Company Information
- Company Name
- Development Name
- Contact Email/Phone
- Default Currency
- Measurement Units

// Display Options
- Default Zoom Level
- Map Tile Server
- Email Notifications
- User Registration Requirements

// Advanced Features
- Lead Scoring
- Analytics Tracking
- Data Retention
- Backup Settings
```

### Shortcode Options
```php
[tajmap_plot_canvas]           // Interactive canvas viewer
[tajmap_plot_selection]        // Alternative plot selection
[tajmap_landing_page]          // Landing page template
[tajmap_gallery]             // Gallery view
[tajmap_user_dashboard]        // User dashboard
```

## 📊 Database Schema

### Plots Table Structure
```sql
CREATE TABLE wp_tajmap_plots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plot_name VARCHAR(191) NOT NULL,
    street VARCHAR(191),
    sector VARCHAR(191),
    block VARCHAR(191),
    coordinates LONGTEXT NOT NULL,        -- JSON polygon data
    status ENUM('available','sold') DEFAULT 'available',
    base_image_id BIGINT UNSIGNED,        -- WordPress media ID
    base_image_transform LONGTEXT,          -- JSON transform data
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME
);
```

### Leads Table Structure
```sql
CREATE TABLE wp_tajmap_leads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plot_id BIGINT UNSIGNED NOT NULL,
    phone VARCHAR(64) NOT NULL,
    email VARCHAR(191) NOT NULL,
    message TEXT,
    status ENUM('new','contacted','interested','closed') DEFAULT 'new',
    source VARCHAR(50) DEFAULT 'website',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

## 🔍 Troubleshooting

### Common Issues & Solutions

#### Canvas Not Loading
- **Check**: Browser console for JavaScript errors
- **Verify**: jQuery is loaded
- **Ensure**: AJAX endpoints are accessible
- **Fix**: Check file permissions and paths

#### Database Errors
- **Check**: Database user permissions
- **Verify**: Table creation completed
- **Ensure**: No plugin conflicts
- **Fix**: Check WordPress debug log

#### Images Not Displaying
- **Check**: WordPress media upload permissions
- **Verify**: File paths and URLs are correct
- **Ensure**: Proper image handling
- **Fix**: Check upload directory permissions

#### AJAX Failures
- **Check**: Nonce verification
- **Verify**: User permissions
- **Ensure**: No JavaScript conflicts
- **Fix**: Check WordPress AJAX configuration

### Debug Mode
Enable WordPress debug mode for detailed error messages:
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## 📈 Performance Optimization

### Server Requirements
- **PHP Memory**: 128MB minimum, 256MB recommended
- **Database**: MySQL 5.6+ with proper indexing
- **Storage**: SSD recommended for better performance
- **CDN**: Consider for image delivery

### Optimization Tips
- **Image Optimization**: Compress base map images
- **Caching**: Use WordPress caching plugins
- **Database**: Regular cleanup of old data
- **Assets**: Minify CSS/JS files

## 🔒 Security Considerations

### Data Protection
- **Nonce Verification**: All AJAX requests protected
- **Input Sanitization**: All user inputs sanitized
- **Permission Checks**: Admin functions protected
- **SQL Injection**: Prepared statements used

### Best Practices
- **Regular Updates**: Keep WordPress and plugins updated
- **Backup Strategy**: Regular database backups
- **User Permissions**: Limit admin access
- **SSL Certificate**: Use HTTPS for data security

## 📞 Support & Maintenance

### Getting Help
1. **Check Logs**: WordPress debug log for errors
2. **Test Environment**: Use staging site for testing
3. **Plugin Conflicts**: Deactivate other plugins temporarily
4. **Theme Issues**: Test with default WordPress theme

### Maintenance Tasks
- **Regular Backups**: Database and file backups
- **Update Checks**: Keep WordPress and plugins updated
- **Performance Monitoring**: Check site speed and database size
- **Security Updates**: Apply security patches promptly

## 🎯 Success Metrics

### After Installation, You Should Have:
- ✅ Working plot editor in admin
- ✅ Interactive plot viewer for users
- ✅ Lead capture and management
- ✅ Responsive design on all devices
- ✅ Database properly configured
- ✅ All AJAX functionality working

### Key URLs to Test:
- **Admin Plot Editor**: `/wp-admin/admin.php?page=tajmap-plot-editor`
- **User Plot Viewer**: `/available-plots`
- **Admin Dashboard**: `/wp-admin/admin.php?page=tajmap-plot-management`
- **Settings Page**: `/wp-admin/admin.php?page=tajmap-plot-settings`

## 📋 Post-Installation Checklist

- [ ] Plugin activated successfully
- [ ] Database tables created
- [ ] Admin menu items visible
- [ ] Plot editor accessible
- [ ] User plot viewer working
- [ ] Base map uploads correctly
- [ ] Plot creation tools functional
- [ ] Lead capture forms working
- [ ] AJAX requests successful
- [ ] Responsive design confirmed
- [ ] All shortcodes working
- [ ] Settings configured
- [ ] Test data created
- [ ] User permissions set
- [ ] Backup strategy in place

## 🎉 You're Ready!

Your TajMap Plot Booking system is now ready for use. The system provides a complete solution for real estate plot management with both user-facing and admin interfaces.

**Next Steps:**
1. Upload your base map image
2. Create your first plots
3. Test the user interface
4. Configure your settings
5. Start managing leads!

For additional support or customization needs, refer to the detailed documentation in `README-INSTALLATION.md`.

