<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'badgeos');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'Asahi@123');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'Q6j(> :fF98WJk23d`Ds7 xB*){c`TMrK;-;%:ytDTl<ag9(m?+MGd>ysi-s[DCr');
define('SECURE_AUTH_KEY',  'lbQ2:RJ}dI)${:L{WUD[<lcsuR@|GJ&asig#Q~T|YF#9|GTfhtgs57$Z)=CURbFA');
define('LOGGED_IN_KEY',    'dCKRlU$_F]&|fWgk.PsL_VX4zA]7D XF5]/i!hu{^Kzp`V _GMTy:rCuc++2T1$+');
define('NONCE_KEY',        '2kyRC)O]T}o_ YTt1!:`5lJ$pL-52u(#5*Ye/h-)bsIhYs*6v,k4u X~h`(m{r0!');
define('AUTH_SALT',        '?_5Fx;^5ylR{_nu-IEmI|-6B .ja1dM+saDhj|2ncS{>p@0IWP8G80C3p*-h=wy[');
define('SECURE_AUTH_SALT', 'n78x*-/C6LN- )$:tHyb:dBJhqp!6(Ie_ ^3Sc_B#uC[9+B7 %!gM88yhdB^6#z7');
define('LOGGED_IN_SALT',   '7fb<k6jlFK8nz.5M*5<=VZnEYO/1)lG|O{Bp.;1|UVEX3HdSS-%N5h8;OW2=5Q~`');
define('NONCE_SALT',       'WrU=1IqHE5&sJU(o?Z5TQVREEQ$ozxUdqy:k!>#|r=.B||[.2EQ@x2)?;}9cg|wu');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);
define('FS_METHOD', 'direct');

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
    define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
