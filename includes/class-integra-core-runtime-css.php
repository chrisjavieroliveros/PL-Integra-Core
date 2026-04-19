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
	 * Token stylesheet relative path.
	 */
	const CONFIGS_STYLESHEET_RELATIVE_PATH = 'assets/css/integra-configs.css';

	/**
	 * Global stylesheet relative path.
	 */
	const GLOBAL_STYLESHEET_RELATIVE_PATH = 'assets/css/integra-core.min.css';

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

		$configs_path = self::file_path();
		$configs_url  = self::file_url();
		$core_path    = self::global_file_path();
		$core_url     = self::global_file_url();

		if ( ! file_exists( $configs_path ) ) {
			self::write_values( Integra_Core_Token_Registry::defaults() );
		}

		$configs_version = self::asset_version( $configs_path );
		$core_version    = self::asset_version( $core_path );

		wp_enqueue_style( 'integra-configs', $configs_url, array(), $configs_version );
		wp_enqueue_style( 'integra-core', $core_url, array( 'integra-configs' ), $core_version );

		self::$enqueued = true;
	}

	/**
	 * Returns the absolute stylesheet path.
	 *
	 * @return string
	 */
	public static function file_path() {
		return INTEGRA_CORE_DIR_PATH . self::CONFIGS_STYLESHEET_RELATIVE_PATH;
	}

	/**
	 * Returns the stylesheet URL.
	 *
	 * @return string
	 */
	public static function file_url() {
		return INTEGRA_CORE_DIR_URL . self::CONFIGS_STYLESHEET_RELATIVE_PATH;
	}

	/**
	 * Returns the absolute global stylesheet path.
	 *
	 * @return string
	 */
	public static function global_file_path() {
		return INTEGRA_CORE_DIR_PATH . self::GLOBAL_STYLESHEET_RELATIVE_PATH;
	}

	/**
	 * Returns the global stylesheet URL.
	 *
	 * @return string
	 */
	public static function global_file_url() {
		return INTEGRA_CORE_DIR_URL . self::GLOBAL_STYLESHEET_RELATIVE_PATH;
	}

	/**
	 * Returns the readable version for the configs stylesheet.
	 *
	 * @return string
	 */
	public static function configs_version() {
		return self::asset_version( self::file_path() );
	}

	/**
	 * Returns the readable version for the global stylesheet.
	 *
	 * @return string
	 */
	public static function global_version() {
		return self::asset_version( self::global_file_path() );
	}

	/**
	 * Builds a readable asset version from a file modification time.
	 *
	 * @param string $path Absolute asset path.
	 * @return string
	 */
	private static function asset_version( $path ) {
		if ( ! file_exists( $path ) ) {
			return INTEGRA_CORE_VERSION;
		}

		return gmdate( 'Y.m.d.His', (int) filemtime( $path ) );
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
	 * Checks whether the global stylesheet can be written.
	 *
	 * @return bool
	 */
	public static function global_is_writable() {
		$path = self::global_file_path();

		if ( file_exists( $path ) ) {
			return is_writable( $path );
		}

		return is_writable( dirname( $path ) );
	}

	/**
	 * Bumps the global stylesheet version by updating its modified time.
	 *
	 * @return bool
	 */
	public static function bump_global_version() {
		$path = self::global_file_path();

		if ( ! self::global_is_writable() || ! file_exists( $path ) ) {
			return false;
		}

		return touch( $path );
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
