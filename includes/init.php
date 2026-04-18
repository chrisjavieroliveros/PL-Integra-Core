<?php
/**
 * Plugin bootstrap functions.
 *
 * @package Integra_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once INTEGRA_CORE_DIR_PATH . 'includes/class-integra-core-token-registry.php';
require_once INTEGRA_CORE_DIR_PATH . 'includes/class-integra-core-runtime-css.php';
require_once INTEGRA_CORE_DIR_PATH . 'includes/class-integra-core-admin.php';

/**
 * Initializes the plugin.
 *
 * @return void
 */
function integra_core_init() {
	load_plugin_textdomain(
		'integra-core',
		false,
		dirname( INTEGRA_CORE_BASENAME ) . '/languages'
	);

	Integra_Core_Runtime_CSS::boot();
	Integra_Core_Admin::boot();
}
