<?php
/**
 * Runtime CSS generator.
 *
 * @package Integra_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Integra_Core_Runtime_CSS {
	/**
	 * Runtime stylesheet relative path.
	 */
	const STYLESHEET_RELATIVE_PATH = 'assets/css/integra-core.css';

	/**
	 * @var bool
	 */
	private static $enqueued = false;

	/**
	 * Boots the runtime CSS hooks.
	 *
	 * @return void
	 */
	public static function boot() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'enqueue_block_assets', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * Enqueues the generated stylesheet once per request.
	 *
	 * @return void
	 */
	public static function enqueue() {
		if ( self::$enqueued ) {
			return;
		}

		$path = self::file_path();
		$url  = self::file_url();

		if ( ! file_exists( $path ) ) {
			self::write_values( Integra_Core_Token_Registry::defaults() );
		}

		$version = file_exists( $path ) ? (string) filemtime( $path ) : INTEGRA_CORE_VERSION;

		wp_enqueue_style( 'integra-core', $url, array(), $version );

		self::$enqueued = true;
	}

	/**
	 * Returns the absolute stylesheet path.
	 *
	 * @return string
	 */
	public static function file_path() {
		return INTEGRA_CORE_DIR_PATH . self::STYLESHEET_RELATIVE_PATH;
	}

	/**
	 * Returns the stylesheet URL.
	 *
	 * @return string
	 */
	public static function file_url() {
		return INTEGRA_CORE_DIR_URL . self::STYLESHEET_RELATIVE_PATH;
	}

	/**
	 * Checks whether the stylesheet can be written.
	 *
	 * @return bool
	 */
	public static function is_writable() {
		$path = self::file_path();

		if ( file_exists( $path ) ) {
			return is_writable( $path );
		}

		return is_writable( dirname( $path ) );
	}

	/**
	 * Writes a full token value set to the runtime stylesheet.
	 *
	 * @param array<string, string> $values Token values.
	 * @return bool
	 */
	public static function write_values( $values ) {
		if ( ! self::is_writable() ) {
			return false;
		}

		return false !== file_put_contents( self::file_path(), self::render_css( $values ) );
	}

	/**
	 * Renders grouped CSS variables.
	 *
	 * @param array<string, string> $values Runtime values.
	 * @return string
	 */
	public static function render_css( $values ) {
		$lines = array();

		foreach ( Integra_Core_Token_Registry::sections() as $section ) {
			$lines[] = '/* ' . $section['comment'] . ' */';
			$lines[] = ':root {';

			foreach ( $section['tokens'] as $key => $default ) {
				$lines[] = sprintf( '  %s: %s;', $key, $values[ $key ] ?? $default );
			}

			$lines[] = '}';
			$lines[] = '';
		}

		return implode( "\n", $lines );
	}
}
