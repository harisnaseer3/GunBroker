# TajMap Canvas Plot Viewer

## Overview

The TajMap Canvas Plot Viewer is a new user-side interface that provides an interactive canvas-based plot viewing experience. It applies the same canvas logic from the admin plot editor but with view-only controls, allowing users to explore plots without editing capabilities.

## Features

### View-Only Controls
- **Zoom In/Out**: Mouse wheel or zoom controls
- **Pan**: Click and drag to move around the canvas
- **Fit to View**: Automatically fits all plots to the canvas
- **Grid Toggle**: Show/hide grid overlay
- **Plot Selection**: Click on plots to view details

### Interactive Elements
- **Plot Highlighting**: Selected plots are highlighted with blue dashed border
- **Plot Details Panel**: Shows plot information when selected
- **Plot List**: Sidebar with all available plots
- **Filtering**: Filter plots by sector, block, and status

### Canvas Features
- **Global Base Map**: Background image support (if configured in admin)
- **Responsive Design**: Adapts to different screen sizes
- **Smooth Interactions**: Optimized for user experience

## Usage

### Shortcode
Use the `[tajmap_plot_canvas]` shortcode on any page or post:

```
[tajmap_plot_canvas]
```

### Direct Page Access
The canvas viewer is automatically used for the `/plots` page route.

### Programmatic Usage
```php
// In a template file
echo do_shortcode('[tajmap_plot_canvas]');
```

## Technical Details

### Files Created/Modified
- `templates/frontend/plot-selection-canvas.php` - Main canvas template
- `includes/class-tajmap-pb.php` - Added shortcode support
- Updated `render_plot_selection_page()` to use canvas template

### Dependencies
- jQuery (WordPress default)
- WordPress AJAX functionality
- Canvas API (browser native)

### Browser Support
- Modern browsers with Canvas API support
- Mobile responsive design
- Touch support for mobile devices

## Configuration

### Admin Settings
The canvas viewer uses the same global base map settings as the admin editor:
- Base map image can be set in Plot Management > Settings
- Transform data is automatically applied
- No additional configuration required

### Customization
The canvas viewer can be customized by modifying:
- CSS styles in the template
- JavaScript functionality in the template
- Plot rendering logic

## Differences from Admin Editor

### Removed Features (View-Only)
- Drawing tools (polygon, rectangle, etc.)
- Edit tools (vertex editing, move, rotate)
- Save/delete functionality
- Plot creation tools
- Transform handles for base map

### Retained Features
- Zoom and pan controls
- Grid display
- Plot rendering and highlighting
- Base map support
- Plot selection and details

## Troubleshooting

### Common Issues
1. **Plots not loading**: Check AJAX configuration and database connection
2. **Canvas not responsive**: Ensure proper CSS is loaded
3. **Base map not showing**: Verify base map is set in admin settings

### Debug Information
- Check browser console for JavaScript errors
- Verify `TajMapFrontend` object is available
- Check AJAX responses in Network tab

## Future Enhancements

Potential improvements for the canvas viewer:
- Additional view controls (reset view, specific zoom levels)
- Plot search and filtering
- Export functionality
- Print support
- Mobile-specific optimizations
