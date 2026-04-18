<?php
/**
 * Token registry and persistence helpers.
 *
 * @package Integra_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Integra_Core_Token_Registry {
	const OPTION_NAME = 'integra_core_tokens';

	/**
	 * @var array<string, mixed>|null
	 */
	private static $sections = null;

	/**
	 * Returns tab definitions.
	 *
	 * @return array<string, string>
	 */
	public static function tabs() {
		return array(
			'abstracts'  => 'Abstracts',
			'base'       => 'Base',
			'layout'     => 'Layout',
			'components' => 'Components',
		);
	}

	/**
	 * Returns section definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function sections() {
		if ( null === self::$sections ) {
			self::$sections = require INTEGRA_CORE_DIR_PATH . 'includes/token-definitions.php';
		}

		return self::$sections;
	}

	/**
	 * Returns sections for a specific tab.
	 *
	 * @param string $tab Tab slug.
	 * @return array<string, array<string, mixed>>
	 */
	public static function sections_for_tab( $tab ) {
		$sections = array();

		foreach ( self::sections() as $slug => $section ) {
			if ( $tab === $section['tab'] ) {
				$sections[ $slug ] = $section;
			}
		}

		return $sections;
	}

	/**
	 * Returns a single section definition.
	 *
	 * @param string $section_slug Section slug.
	 * @return array<string, mixed>|null
	 */
	public static function section( $section_slug ) {
		$sections = self::sections();

		return isset( $sections[ $section_slug ] ) ? $sections[ $section_slug ] : null;
	}

	/**
	 * Returns a flattened registry keyed by token.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function flat_registry() {
		$registry = array();

		foreach ( self::sections() as $section_slug => $section ) {
			foreach ( $section['tokens'] as $key => $default ) {
				$registry[ $key ] = array(
					'key'           => $key,
					'label'         => self::build_label( $key ),
					'tab'           => $section['tab'],
					'section'       => $section_slug,
					'section_title' => $section['title'],
					'comment'       => $section['comment'],
					'control'       => self::infer_control( $key, $default ),
					'default'       => $default,
				);
			}
		}

		return $registry;
	}

	/**
	 * Returns default values keyed by token.
	 *
	 * @return array<string, string>
	 */
	public static function defaults() {
		$defaults = array();

		foreach ( self::sections() as $section ) {
			foreach ( $section['tokens'] as $key => $value ) {
				$defaults[ $key ] = $value;
			}
		}

		return $defaults;
	}

	/**
	 * Returns saved overrides.
	 *
	 * @return array<string, string>
	 */
	public static function overrides() {
		$stored   = self::read_stylesheet_values();
		$defaults = self::defaults();
		$valid    = array();

		if ( ! is_array( $stored ) ) {
			return array();
		}

		foreach ( $stored as $key => $value ) {
			if ( isset( $defaults[ $key ] ) && is_string( $value ) ) {
				$valid[ $key ] = $value;
			}
		}

		return $valid;
	}

	/**
	 * Returns merged runtime values.
	 *
	 * @return array<string, string>
	 */
	public static function values() {
		return array_replace( self::defaults(), self::overrides() );
	}

	/**
	 * Reads token values from the generated stylesheet.
	 *
	 * @return array<string, string>
	 */
	private static function read_stylesheet_values() {
		$path = Integra_Core_Runtime_CSS::file_path();

		if ( ! file_exists( $path ) || ! is_readable( $path ) ) {
			return array();
		}

		$content = file_get_contents( $path );

		if ( false === $content ) {
			return array();
		}

		preg_match_all( '/(--[a-z0-9-]+)\s*:\s*([^;]+);/i', $content, $matches, PREG_SET_ORDER );

		$values = array();

		foreach ( $matches as $match ) {
			$values[ trim( $match[1] ) ] = trim( $match[2] );
		}

		return $values;
	}

	/**
	 * Sanitizes a token value according to its control type.
	 *
	 * @param string $key   Token key.
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function sanitize_value( $key, $value ) {
		$registry = self::flat_registry();
		$default  = self::defaults();

		if ( ! isset( $registry[ $key ] ) ) {
			return '';
		}

		$value = trim( wp_unslash( (string) $value ) );

		if ( '' === $value ) {
			return $default[ $key ];
		}

		switch ( $registry[ $key ]['control'] ) {
			case 'color':
				$normalized = self::normalize_color_value( $value );
				return $normalized ? $normalized : $default[ $key ];
			case 'number':
				return is_numeric( $value ) ? (string) ( 0 + $value ) : $default[ $key ];
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Returns token keys for a tab.
	 *
	 * @param string $tab Tab slug.
	 * @return string[]
	 */
	public static function keys_for_tab( $tab ) {
		$keys = array();

		foreach ( self::sections_for_tab( $tab ) as $section ) {
			$keys = array_merge( $keys, array_keys( $section['tokens'] ) );
		}

		return $keys;
	}

	/**
	 * Returns token keys for a section.
	 *
	 * @param string $section_slug Section slug.
	 * @return string[]
	 */
	public static function keys_for_section( $section_slug ) {
		$section = self::section( $section_slug );

		if ( ! $section ) {
			return array();
		}

		return array_keys( $section['tokens'] );
	}

	/**
	 * Creates saved overrides from posted values.
	 *
	 * @param string              $tab         Tab slug.
	 * @param array<string,mixed> $posted      Posted token values.
	 * @param bool                $reset_scope Whether to reset instead of save.
	 * @return array<string, string>
	 */
	public static function build_overrides_for_tab( $tab, $posted, $reset_scope = false ) {
		$overrides = self::overrides();
		$defaults  = self::defaults();

		foreach ( self::keys_for_tab( $tab ) as $key ) {
			if ( $reset_scope ) {
				unset( $overrides[ $key ] );
				continue;
			}

			$value = isset( $posted[ $key ] ) ? self::sanitize_value( $key, (string) $posted[ $key ] ) : $defaults[ $key ];

			if ( $value === $defaults[ $key ] ) {
				unset( $overrides[ $key ] );
			} else {
				$overrides[ $key ] = $value;
			}
		}

		return $overrides;
	}

	/**
	 * Creates saved overrides from posted values for a single section.
	 *
	 * @param string              $section_slug Section slug.
	 * @param array<string,mixed> $posted       Posted token values.
	 * @param bool                $reset_scope  Whether to reset instead of save.
	 * @return array<string, string>
	 */
	public static function build_overrides_for_section( $section_slug, $posted, $reset_scope = false ) {
		$overrides = self::overrides();
		$defaults  = self::defaults();

		foreach ( self::keys_for_section( $section_slug ) as $key ) {
			if ( $reset_scope ) {
				unset( $overrides[ $key ] );
				continue;
			}

			$value = isset( $posted[ $key ] ) ? self::sanitize_value( $key, (string) $posted[ $key ] ) : $defaults[ $key ];

			if ( $value === $defaults[ $key ] ) {
				unset( $overrides[ $key ] );
			} else {
				$overrides[ $key ] = $value;
			}
		}

		return $overrides;
	}

	/**
	 * Creates a human readable token label.
	 *
	 * @param string $key Token key.
	 * @return string
	 */
	private static function build_label( $key ) {
		$label = preg_replace( '/^--in-/', '', $key );
		$parts = preg_split( '/-/', $label );

		if ( ! is_array( $parts ) ) {
			return $key;
		}

		$parts = array_map(
			static function ( $part ) {
				return preg_match( '/^\d+$/', $part ) ? $part : ucfirst( $part );
			},
			$parts
		);

		return implode( ' ', $parts );
	}

	/**
	 * Infers an admin control type from the token default.
	 *
	 * @param string $key     Token key.
	 * @param string $default Default value.
	 * @return string
	 */
	private static function infer_control( $key, $default ) {
		if ( 0 === strpos( $key, '--in-color-' ) && preg_match( '/^#(?:[0-9a-fA-F]{3}){1,2}$/', $default ) ) {
			return 'color';
		}

		if ( preg_match( '/^-?\d+(?:\.\d+)?$/', $default ) ) {
			return 'number';
		}

		return 'text';
	}

	/**
	 * Normalizes a color string into hex or hex8.
	 *
	 * @param string $value Raw color string.
	 * @return string
	 */
	private static function normalize_color_value( $value ) {
		$value = trim( strtolower( $value ) );

		if ( preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/', $value, $matches ) ) {
			$hex = $matches[1];

			if ( 3 === strlen( $hex ) || 4 === strlen( $hex ) ) {
				$hex = implode(
					'',
					array_map(
						static function ( $char ) {
							return $char . $char;
						},
						str_split( $hex )
					)
				);
			}

			return '#' . $hex;
		}

		if ( preg_match( '/^rgba?\(([^)]+)\)$/', $value, $matches ) ) {
			$parts = array_map( 'trim', explode( ',', $matches[1] ) );

			if ( count( $parts ) < 3 || count( $parts ) > 4 ) {
				return '';
			}

			$r = self::normalize_color_channel( $parts[0] );
			$g = self::normalize_color_channel( $parts[1] );
			$b = self::normalize_color_channel( $parts[2] );

			if ( null === $r || null === $g || null === $b ) {
				return '';
			}

			if ( 4 === count( $parts ) ) {
				$alpha = self::normalize_alpha_channel( $parts[3] );

				if ( null === $alpha ) {
					return '';
				}

				if ( 1 >= $alpha ) {
					return sprintf( '#%02x%02x%02x%02x', $r, $g, $b, (int) round( $alpha * 255 ) );
				}
			}

			return sprintf( '#%02x%02x%02x', $r, $g, $b );
		}

		return '';
	}

	/**
	 * Normalizes a single RGB channel.
	 *
	 * @param string $value Raw channel.
	 * @return int|null
	 */
	private static function normalize_color_channel( $value ) {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		$channel = (int) round( (float) $value );

		if ( $channel < 0 || $channel > 255 ) {
			return null;
		}

		return $channel;
	}

	/**
	 * Normalizes a single alpha channel.
	 *
	 * @param string $value Raw alpha.
	 * @return float|null
	 */
	private static function normalize_alpha_channel( $value ) {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		$alpha = (float) $value;

		if ( $alpha < 0 || $alpha > 1 ) {
			return null;
		}

		return $alpha;
	}
}
