<?php
/**
 * Plugin activation callback.
 *
 * @package Integra_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once INTEGRA_CORE_DIR_PATH . 'includes/class-integra-core-token-registry.php';
require_once INTEGRA_CORE_DIR_PATH . 'includes/class-integra-core-runtime-css.php';

/**
 * Runs on plugin activation.
 *
 * @return void
 */
function integra_core_activate() {
	$values = Integra_Core_Token_Registry::defaults();
	$stored = get_option( Integra_Core_Token_Registry::OPTION_NAME, array() );

	if ( is_array( $stored ) ) {
		$values = array_replace( $values, $stored );
	}

	Integra_Core_Runtime_CSS::write_values( $values );
	delete_option( Integra_Core_Token_Registry::OPTION_NAME );

	update_option( 'integra_core_version', INTEGRA_CORE_VERSION, false );
}
