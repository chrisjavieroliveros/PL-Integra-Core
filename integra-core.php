<?php
/**
 * Plugin Name:       Integra Core
 * Plugin URI:        https://example.com/integra-core
 * Description:       Shared design token styles for the Integra ecosystem.
 * Version:           0.1.2
 * Author:            Integra
 * Author URI:        https://example.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       integra-core
 * Domain Path:       /languages
 * Requires PHP:      7.4
 * Requires at least: 6.2
 *
 * @package Integra_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'INTEGRA_CORE_VERSION', '0.1.2' );
define( 'INTEGRA_CORE_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'INTEGRA_CORE_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'INTEGRA_CORE_BASENAME', plugin_basename( __FILE__ ) );

require_once INTEGRA_CORE_DIR_PATH . 'includes/activate.php';
require_once INTEGRA_CORE_DIR_PATH . 'includes/deactivate.php';
require_once INTEGRA_CORE_DIR_PATH . 'includes/init.php';

register_activation_hook( __FILE__, 'integra_core_activate' );
register_deactivation_hook( __FILE__, 'integra_core_deactivate' );

add_action( 'plugins_loaded', 'integra_core_init' );
