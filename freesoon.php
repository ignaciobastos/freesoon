<?php
/**
 * Plugin Name: FreeSoon — Coming Soon & Maintenance
 * Description: Create a coming soon or maintenance page with a live visual editor. Free, without ads or locked features.
 * Version: 1.8.3
 * Author: Ignacio Bastos
 * Author URI: https://ignaciobastos.com/
 * Text Domain: freesoon
 * Domain Path: /languages
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'OBRA_VERSION', '1.8.3' );
define( 'OBRA_FILE', __FILE__ );
define( 'OBRA_DIR', plugin_dir_path( __FILE__ ) );
define( 'OBRA_URL', plugin_dir_url( __FILE__ ) );

require_once OBRA_DIR . 'includes/i18n.php';
require_once OBRA_DIR . 'includes/launch.php';
require_once OBRA_DIR . 'includes/legal.php';
require_once OBRA_DIR . 'includes/fonts.php';
require_once OBRA_DIR . 'includes/content-languages.php';
require_once OBRA_DIR . 'includes/class-obra-plugin.php';
require_once OBRA_DIR . 'includes/privacy.php';

register_activation_hook( __FILE__, array( 'Obra_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Obra_Plugin', 'deactivate' ) );
Obra_Plugin::instance();

