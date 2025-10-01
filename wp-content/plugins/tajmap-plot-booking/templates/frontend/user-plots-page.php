<?php
/**
 * User Plots View Page Template
 * 
 * This template displays all plots (sold and available) for users to view
 * Usage: [tajmap_plot_selection]
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get the shortcode content
$shortcode_content = do_shortcode('[tajmap_plot_selection]');

// If we're in a WordPress context, use the shortcode directly
if (function_exists('wp_head')) {
    echo $shortcode_content;
} else {
    // Standalone mode - include the full HTML structure
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Available Plots - <?php echo get_bloginfo('name'); ?></title>
        <link rel="stylesheet" href="<?php echo TAJMAP_PB_URL; ?>assets/frontend.css?ver=<?php echo TAJMAP_PB_VERSION; ?>">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    </head>
    <body>
        <?php echo $shortcode_content; ?>
        <script src="<?php echo TAJMAP_PB_URL; ?>assets/frontend.js?ver=<?php echo TAJMAP_PB_VERSION; ?>"></script>
    </body>
    </html>
    <?php
}

