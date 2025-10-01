# TajMap Plot Booking - User Module Instructions

## Overview
The user module allows visitors to view all plots that have been added by the admin, including both sold and available plots. Users can filter, search, and express interest in plots.

## How to Use

### Method 1: Using Shortcode (Recommended)
Add this shortcode to any WordPress page or post:
```
[tajmap_plot_selection]
```

### Method 2: Direct Page Access
Create a new WordPress page and add the shortcode `[tajmap_plot_selection]` to the content.

### Method 3: Custom Page Template
Use the custom page template `templates/frontend/user-plots-page.php` for a standalone implementation.

## Features Available to Users

### 1. Plot Viewing
- **Map View**: Interactive map showing all plots with visual indicators
- **List View**: Tabular list of all plots with details
- **Plot Details**: Click on any plot to see detailed information

### 2. Filtering & Search
- **Filter by Sector**: Select specific sectors
- **Filter by Block**: Select specific blocks  
- **Filter by Status**: Show only Available or Sold plots
- **Search**: Search by plot name
- **Apply/Clear Filters**: Easy filter management

### 3. Plot Information Display
- Plot Name
- Sector, Block, Street
- Status (Available/Sold)
- Price (if set by admin)
- Area (if set by admin)
- Visual plot boundaries on map

### 4. User Actions
- **Express Interest**: Fill out inquiry form for available plots
- **View Details**: Click plots to see full information
- **Save Plots**: (If user is logged in) Save plots for later viewing

## Admin Requirements

### 1. Plot Data
Ensure plots are added through the admin panel with:
- Plot name
- Coordinates (polygon points)
- Status (available/sold)
- Sector, Block, Street (optional)
- Price, Area (optional)

### 2. Base Map Image
Upload a base map image in the admin plot editor for better visualization.

## Technical Details

### Files Involved
- `templates/frontend/plot-selection.php` - Main user interface
- `assets/frontend.css` - User interface styling
- `assets/frontend.js` - Interactive functionality
- `includes/class-tajmap-pb.php` - AJAX handlers

### AJAX Endpoints
- `tajmap_pb_get_plots` - Retrieves all plots for display
- `tajmap_pb_save_lead` - Saves user inquiries
- `tajmap_pb_get_plot_details` - Gets detailed plot information

### Security
- All AJAX requests use WordPress nonces
- User data is sanitized and validated
- No admin privileges required for viewing

## Customization

### Styling
Modify `assets/frontend.css` to customize the appearance.

### Functionality
Modify `assets/frontend.js` to add custom features.

### Layout
Modify `templates/frontend/plot-selection.php` to change the structure.

## Troubleshooting

### Plots Not Showing
1. Check if plots exist in the admin panel
2. Verify AJAX nonce is correct
3. Check browser console for JavaScript errors

### Styling Issues
1. Ensure `frontend.css` is enqueued
2. Check for CSS conflicts with theme
3. Verify file paths are correct

### AJAX Errors
1. Check WordPress AJAX URL is correct
2. Verify nonce verification
3. Check user permissions

## Support
For technical support, check the plugin documentation or contact the development team.

