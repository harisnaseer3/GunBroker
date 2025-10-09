# Taj Residencia Landing Page Usage

## Overview
A beautiful landing page has been created for the Taj Residencia project with the map image from [emap.pk](https://emap.pk/taj-residencia-islamabad-map).

## Features
- **Responsive Design**: Works on all devices
- **Interactive Map**: Hover effects and smooth transitions
- **Modern UI**: Gradient backgrounds and floating animations
- **Loading States**: Smooth transitions with loading spinners
- **Feature Cards**: Highlighting project benefits

## Usage

### Method 1: Shortcode (Recommended)
Add this shortcode to any WordPress page or post:
```
[tajmap_landing_page]
```

### Method 2: Direct URL
Visit: `your-site.com/plot-selection/`

### Method 3: Programmatic Usage
```php
echo do_shortcode('[tajmap_landing_page]');
```

## Customization

### Project Settings
The landing page uses settings from the plugin configuration:
- **Project Name**: Set in plugin settings
- **Project Description**: Set in plugin settings
- **Map Image**: Automatically loads from emap.pk

### Styling
The landing page includes:
- Gradient backgrounds
- Floating animations
- Hover effects
- Responsive grid layout
- Modern typography

## Navigation Flow
1. **Landing Page** → User sees the beautiful map and project info
2. **Click Map/Button** → Redirects to interactive plot selection
3. **Plot Selection** → Full interactive canvas with plot browsing

## Technical Details
- **Template**: `templates/frontend/landing.php`
- **Shortcode**: `tajmap_landing_page`
- **Route**: `/plot-selection/`
- **Assets**: Self-contained CSS and JavaScript
- **Map Source**: https://emap.pk/taj-residencia-islamabad-map

## Browser Support
- Chrome, Firefox, Safari, Edge
- Mobile responsive
- Touch-friendly interactions
- Fallback for older browsers

## Performance
- Optimized images
- Minimal external dependencies
- Fast loading times
- Smooth animations

