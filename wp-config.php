<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'gunbroker' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'Ooge9._X@=%i E5BV,H*M8SopJ^[gu5L`7iZZ:8<-J!@d2z`{g`1f6cVPd}c|<6;' );
define( 'SECURE_AUTH_KEY',  '_V;HV8Gi]Jn+)H^HaY<4U=K$B+!X$/r%0:k|MCTRkug#iyvu|Qt;RT;q(][A*>Cm' );
define( 'LOGGED_IN_KEY',    '3zHOGAggU_x.KY[=?Ux}{Me9&V4[Z6Rk_,K&8#W5I~].(rl>}1(#W,.oKg(hUi1X' );
define( 'NONCE_KEY',        'M0rUgh.PyZbkQE{kF/y (Q?:.w_nAU2A?=%RL /Sy`;<((^IE|3|a?5]T$=:jmvp' );
define( 'AUTH_SALT',        '&H:oUPt -^[[M%fK]R@2H+#=I*^HabUwFca<e>U{/IcS~={wTQF52o]KXc!0cY$r' );
define( 'SECURE_AUTH_SALT', '9|,lJ_@of]?b;u8;3x6CdHT(d78<M/4exyO$2G1qZ89?kG4Pg=JXC)F8:Im v4AM' );
define( 'LOGGED_IN_SALT',   'C^qCeP^!5h=Rn<#+#G]iU:XB|m.e;f2_+]vxdv90L@<-5Cw@d=x!# /sZ`LxANKd' );
define( 'NONCE_SALT',       'h[i1MMs!ZK/W0 !!O^3e(=z8`Rs0r:)SD9e!aG=<5<H.@tLIFC`;.1-;/N3[avAT' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
