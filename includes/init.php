<?php
/**
 * Plugin bootstrap functions.
 *
 * @package Integra_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

	add_action( 'wp_enqueue_scripts', 'integra_core_enqueue_styles' );
	add_action( 'enqueue_block_assets', 'integra_core_enqueue_styles' );
}

/**
 * Enqueues the shared token stylesheet.
 *
 * @return void
 */
function integra_core_enqueue_styles() {
	wp_enqueue_style(
		'integra-core',
		INTEGRA_CORE_DIR_URL . 'assets/css/integra-core.css',
		array(),
		INTEGRA_CORE_VERSION
	);
}
