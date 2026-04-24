<?php
/**
 * Admin page controller for token editing.
 *
 * @package Integra_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Integra_Core_Admin {
	const MENU_SLUG = 'integra-core';

	/**
	 * Boots admin hooks.
	 *
	 * @return void
	 */
	public static function boot() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_post_integra_core_save_tokens', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_integra_core_update_core', array( __CLASS__, 'handle_update_core' ) );
		add_action( 'admin_post_integra_core_reset_tokens', array( __CLASS__, 'handle_reset' ) );
	}

	/**
	 * Registers the admin page.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'Integra Config', 'integra-core' ),
			__( 'Integra Config', 'integra-core' ),
			'manage_options',
			self::MENU_SLUG,
			array( __CLASS__, 'render_page' ),
			'dashicons-admin-customizer',
			57
		);
	}

	/**
	 * Renders the admin page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'integra-core' ) );
		}

		$tabs      = Integra_Core_Token_Registry::tabs();
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'abstracts';

		if ( ! isset( $tabs[ $active_tab ] ) ) {
			$active_tab = 'abstracts';
		}

		$sections       = Integra_Core_Token_Registry::sections_for_tab( $active_tab );
		$section_slugs  = array_keys( $sections );
		$active_section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';
		$values         = Integra_Core_Token_Registry::values();
		$registry       = Integra_Core_Token_Registry::flat_registry();

		if ( empty( $active_section ) || ! isset( $sections[ $active_section ] ) ) {
			$active_section = isset( $section_slugs[0] ) ? $section_slugs[0] : '';
		}

		$current_section = $active_section ? $sections[ $active_section ] : null;
		$last_section_slug = ! empty( $section_slugs ) ? end( $section_slugs ) : '';
		$configs_file      = basename( Integra_Core_Runtime_CSS::file_path() );
		$core_file         = basename( Integra_Core_Runtime_CSS::global_file_path() );
		$configs_version   = Integra_Core_Runtime_CSS::configs_version();
		$core_version      = Integra_Core_Runtime_CSS::global_version();

		?>
		<div class="wrap">
			<div style="display:flex;justify-content:space-between;align-items:start;gap:16px;">
				<div>
					<h1><?php esc_html_e( 'Integra Config', 'integra-core' ); ?></h1>
					<p><?php esc_html_e( 'Edit token values by category and save them directly into the runtime Integra Config stylesheet.', 'integra-core' ); ?></p>
					<p style="margin:8px 0 0;color:#50575e;">
						<?php echo esc_html( $configs_file . ' v' . $configs_version . ' | ' . $core_file . ' v' . $core_version ); ?>
					</p>
				</div>
				<?php if ( $current_section ) : ?>
					<div style="display:flex;gap:8px;align-items:center;margin-top:8px;">
						<button type="submit" form="integra-core-token-form" class="button button-primary">
							<?php esc_html_e( 'Save Tokens', 'integra-core' ); ?>
						</button>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="integra_core_update_core">
							<input type="hidden" name="tab" value="<?php echo esc_attr( $active_tab ); ?>">
							<input type="hidden" name="section" value="<?php echo esc_attr( $active_section ); ?>">
							<?php wp_nonce_field( 'integra_core_update_core' ); ?>
							<?php submit_button( __( 'Update Core', 'integra-core' ), 'secondary', 'submit', false ); ?>
						</form>
					</div>
				<?php endif; ?>
			</div>

			<?php self::render_notice(); ?>

			<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Token categories', 'integra-core' ); ?>">
				<?php foreach ( $tabs as $tab => $label ) : ?>
					<?php
					$url   = admin_url( 'admin.php?page=' . self::MENU_SLUG . '&tab=' . $tab );
					$class = 'nav-tab' . ( $tab === $active_tab ? ' nav-tab-active' : '' );
					?>
					<a class="<?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( $url ); ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<?php if ( ! empty( $sections ) ) : ?>
				<ul class="subsubsub" style="float:none;margin:14px 0 18px;">
					<?php foreach ( $sections as $section_slug => $section ) : ?>
						<?php
						$url = add_query_arg(
							array(
								'page'    => self::MENU_SLUG,
								'tab'     => $active_tab,
								'section' => $section_slug,
							),
							admin_url( 'admin.php' )
						);
						?>
						<li>
							<a href="<?php echo esc_url( $url ); ?>" class="<?php echo $section_slug === $active_section ? 'current' : ''; ?>">
								<?php echo esc_html( $section['title'] ); ?>
							</a><?php echo $last_section_slug !== $section_slug ? ' | ' : ''; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( $current_section ) : ?>
				<form id="integra-core-token-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 20px;">
					<input type="hidden" name="action" value="integra_core_save_tokens">
					<input type="hidden" name="tab" value="<?php echo esc_attr( $active_tab ); ?>">
					<input type="hidden" name="section" value="<?php echo esc_attr( $active_section ); ?>">
					<?php wp_nonce_field( 'integra_core_save_tokens' ); ?>

					<div class="postbox" style="margin-bottom: 20px; max-width: 1200px;">
						<div class="inside">
							<h2><?php echo esc_html( $current_section['title'] ); ?></h2>
							<p><code><?php echo esc_html( $current_section['comment'] ); ?></code></p>
							<?php self::render_section_tokens( $current_section, $registry, $values ); ?>
						</div>
					</div>
				</form>
			<?php endif; ?>

			<div style="display: flex; gap: 12px; align-items: center;">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="integra_core_reset_tokens">
					<input type="hidden" name="scope" value="section">
					<input type="hidden" name="tab" value="<?php echo esc_attr( $active_tab ); ?>">
					<input type="hidden" name="section" value="<?php echo esc_attr( $active_section ); ?>">
					<?php wp_nonce_field( 'integra_core_reset_tokens' ); ?>
					<?php submit_button( __( 'Reset This Group', 'integra-core' ), 'secondary', 'submit', false ); ?>
				</form>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="integra_core_reset_tokens">
					<input type="hidden" name="scope" value="all">
					<input type="hidden" name="tab" value="<?php echo esc_attr( $active_tab ); ?>">
					<input type="hidden" name="section" value="<?php echo esc_attr( $active_section ); ?>">
					<?php wp_nonce_field( 'integra_core_reset_tokens' ); ?>
					<?php submit_button( __( 'Reset All Tokens', 'integra-core' ), 'delete', 'submit', false ); ?>
				</form>
			</div>
		</div>
		<?php
		self::render_admin_assets();
	}

	/**
	 * Handles token saves for a tab.
	 *
	 * @return void
	 */
	public static function handle_save() {
		self::assert_permissions( 'integra_core_save_tokens' );

		$tab     = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'abstracts';
		$section = isset( $_POST['section'] ) ? sanitize_key( wp_unslash( $_POST['section'] ) ) : '';
		$posted  = isset( $_POST['tokens'] ) && is_array( $_POST['tokens'] ) ? $_POST['tokens'] : array();
		$values  = array_replace(
			Integra_Core_Token_Registry::defaults(),
			Integra_Core_Token_Registry::build_overrides_for_section( $section, $posted )
		);

		if ( ! Integra_Core_Runtime_CSS::write_values( $values ) ) {
			wp_safe_redirect( self::admin_url( $tab, $section, 'save_failed' ) );
			exit;
		}

		delete_option( Integra_Core_Token_Registry::OPTION_NAME );

		wp_safe_redirect( self::admin_url( $tab, $section, 'saved' ) );
		exit;
	}

	/**
	 * Handles global core version bumps.
	 *
	 * @return void
	 */
	public static function handle_update_core() {
		self::assert_permissions( 'integra_core_update_core' );

		$tab     = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'abstracts';
		$section = isset( $_POST['section'] ) ? sanitize_key( wp_unslash( $_POST['section'] ) ) : '';

		if ( ! Integra_Core_Runtime_CSS::bump_global_version() ) {
			wp_safe_redirect( self::admin_url( $tab, $section, 'core_update_failed' ) );
			exit;
		}

		wp_safe_redirect( self::admin_url( $tab, $section, 'core_updated' ) );
		exit;
	}

	/**
	 * Handles resets.
	 *
	 * @return void
	 */
	public static function handle_reset() {
		self::assert_permissions( 'integra_core_reset_tokens' );

		$tab     = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'abstracts';
		$section = isset( $_POST['section'] ) ? sanitize_key( wp_unslash( $_POST['section'] ) ) : '';
		$scope   = isset( $_POST['scope'] ) ? sanitize_key( wp_unslash( $_POST['scope'] ) ) : 'section';
		$values  = Integra_Core_Token_Registry::defaults();

		if ( 'all' !== $scope ) {
			$values = array_replace(
				$values,
				Integra_Core_Token_Registry::build_overrides_for_section( $section, array(), true )
			);
		}

		if ( ! Integra_Core_Runtime_CSS::write_values( $values ) ) {
			wp_safe_redirect( self::admin_url( $tab, $section, 'reset_failed' ) );
			exit;
		}

		delete_option( Integra_Core_Token_Registry::OPTION_NAME );

		wp_safe_redirect( self::admin_url( $tab, $section, 'reset' ) );
		exit;
	}

	/**
	 * Renders an input control for a token.
	 *
	 * @param string $key     Token key.
	 * @param string $value   Current value.
	 * @param string $control Control type.
	 * @return void
	 */
	private static function render_input( $key, $value, $control, $default ) {
		$name = 'tokens[' . $key . ']';

		if ( 'color' === $control ) {
			?>
			<div class="integra-core-color-control" data-token-key="<?php echo esc_attr( $key ); ?>">
				<div class="integra-core-color-row">
					<input type="color" class="integra-core-color-picker" value="#000000" />
					<input
						class="regular-text integra-core-color-text"
						type="text"
						name="<?php echo esc_attr( $name ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
						data-default="<?php echo esc_attr( $default ); ?>"
						placeholder="#rrggbb or #rrggbbaa"
						spellcheck="false"
					/>
					<button type="button" class="button button-secondary integra-core-alpha-toggle">
						<?php esc_html_e( 'Opacity', 'integra-core' ); ?>
					</button>
				</div>
				<div class="integra-core-alpha-popover" hidden>
					<div class="integra-core-alpha-row">
						<span><?php esc_html_e( 'Opacity', 'integra-core' ); ?></span>
					<input type="range" class="integra-core-color-alpha" min="0" max="100" step="1" value="100" />
					<span class="integra-core-alpha-value">100%</span>
					</div>
				</div>
			</div>
			<?php
			return;
		}

		printf(
			'<input class="regular-text" type="%1$s" step="any" name="%2$s" value="%3$s" />',
			esc_attr( 'number' === $control ? 'number' : 'text' ),
			esc_attr( $name ),
			esc_attr( $value )
		);
	}

	/**
	 * Renders token inputs for a section.
	 *
	 * @param array $section  Section config.
	 * @param array $registry Flat token registry.
	 * @param array $values   Current token values.
	 * @return void
	 */
	private static function render_section_tokens( $section, $registry, $values ) {
		if ( 'Colors' === $section['title'] ) {
			self::render_color_section_tokens( $section, $registry, $values );
			return;
		}

		if ( 'Button' === $section['title'] ) {
			self::render_appearance_section_tokens(
				$section,
				$registry,
				$values,
				'button',
				__( 'Global Button Properties', 'integra-core' )
			);
			return;
		}

		if ( 'Pill' === $section['title'] ) {
			self::render_appearance_section_tokens(
				$section,
				$registry,
				$values,
				'pill',
				__( 'Global Pill Properties', 'integra-core' )
			);
			return;
		}

		self::render_token_table( $section['tokens'], $registry, $values );
	}

	/**
	 * Renders color tokens with family blocks.
	 *
	 * @param array $section  Section config.
	 * @param array $registry Flat token registry.
	 * @param array $values   Current token values.
	 * @return void
	 */
	private static function render_color_section_tokens( $section, $registry, $values ) {
		$grouped = self::split_color_token_groups( $section['tokens'] );

		if ( ! empty( $grouped['base'] ) ) {
			echo '<h3 class="integra-core-token-subheading">' . esc_html__( 'Base Colors', 'integra-core' ) . '</h3>';
			self::render_token_table( $grouped['base'], $registry, $values );
		}

		if ( empty( $grouped['families'] ) ) {
			return;
		}

		echo '<div class="integra-core-token-groups">';

		foreach ( $grouped['families'] as $family => $tokens ) {
			?>
			<section class="integra-core-token-group">
				<div class="integra-core-token-group__header">
					<h3><?php echo esc_html( self::format_token_group_label( $family ) ); ?></h3>
					<p><code><?php echo esc_html( '--in-color-' . $family . '-*' ); ?></code></p>
				</div>
				<?php self::render_token_table( $tokens, $registry, $values ); ?>
			</section>
			<?php
		}

		echo '</div>';
	}

	/**
	 * Splits color tokens into standalone base colors and per-family groups.
	 *
	 * @param array $tokens Color tokens in display order.
	 * @return array<string, array>
	 */
	private static function split_color_token_groups( $tokens ) {
		$families = array();
		$grouped  = array(
			'base'     => array(),
			'families' => array(),
		);

		foreach ( $tokens as $key => $default ) {
			if ( ! preg_match( '/^--in-color-([A-Za-z0-9]+)(?:-[A-Za-z0-9]+)?$/', $key, $matches ) ) {
				$grouped['base'][ $key ] = $default;
				continue;
			}

			$family = $matches[1];

			if ( ! isset( $families[ $family ] ) ) {
				$families[ $family ] = array();
			}

			$families[ $family ][ $key ] = $default;
		}

		foreach ( $families as $family => $family_tokens ) {
			if ( 1 === count( $family_tokens ) ) {
				$grouped['base'] += $family_tokens;
				continue;
			}

			$grouped['families'][ $family ] = $family_tokens;
		}

		return $grouped;
	}

	/**
	 * Formats a token group slug for display.
	 *
	 * @param string $group Token group slug.
	 * @return string
	 */
	private static function format_token_group_label( $group ) {
		return ucwords( str_replace( '-', ' ', $group ) );
	}

	/**
	 * Renders component tokens with grouped appearance blocks.
	 *
	 * @param array  $section        Section config.
	 * @param array  $registry       Flat token registry.
	 * @param array  $values         Current token values.
	 * @param string $component_slug Component token slug.
	 * @param string $globals_label  Global tokens heading.
	 * @return void
	 */
	private static function render_appearance_section_tokens( $section, $registry, $values, $component_slug, $globals_label ) {
		$grouped = self::split_appearance_token_groups( $section['tokens'], $component_slug );

		if ( ! empty( $grouped['globals'] ) ) {
			echo '<h3 class="integra-core-token-subheading">' . esc_html( $globals_label ) . '</h3>';
			self::render_token_table( $grouped['globals'], $registry, $values );
		}

		if ( empty( $grouped['variants'] ) ) {
			return;
		}

		echo '<div class="integra-core-token-groups">';

		foreach ( $grouped['variants'] as $variant => $tokens ) {
			?>
			<section class="integra-core-token-group">
				<div class="integra-core-token-group__header">
					<h3><?php echo esc_html( self::format_token_group_label( $variant ) ); ?></h3>
					<p><code><?php echo esc_html( '--in-' . $component_slug . '-' . $variant . '-*' ); ?></code></p>
				</div>
				<?php self::render_token_table( $tokens, $registry, $values ); ?>
			</section>
			<?php
		}

		echo '</div>';
	}

	/**
	 * Splits component tokens into global and per-variant appearance groups.
	 *
	 * @param array  $tokens         Component tokens in display order.
	 * @param string $component_slug Component token slug.
	 * @return array<string, array>
	 */
	private static function split_appearance_token_groups( $tokens, $component_slug ) {
		$grouped = array(
			'globals'  => array(),
			'variants' => array(),
		);
		$component_slug = preg_quote( $component_slug, '/' );

		foreach ( $tokens as $key => $default ) {
			if (
				preg_match(
					'/^--in-' . $component_slug . '-([a-z0-9]+)-(bg|fg|border|hover-bg|hover-fg|hover-border|outline-hover-border)$/',
					$key,
					$matches
				)
			) {
				$variant = $matches[1];

				if ( ! isset( $grouped['variants'][ $variant ] ) ) {
					$grouped['variants'][ $variant ] = array();
				}

				$grouped['variants'][ $variant ][ $key ] = $default;
				continue;
			}

			$grouped['globals'][ $key ] = $default;
		}

		return $grouped;
	}

	/**
	 * Renders a token table.
	 *
	 * @param array $tokens   Tokens to render.
	 * @param array $registry Flat token registry.
	 * @param array $values   Current token values.
	 * @return void
	 */
	private static function render_token_table( $tokens, $registry, $values ) {
		?>
		<table class="widefat striped integra-core-token-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Token', 'integra-core' ); ?></th>
					<th><?php esc_html_e( 'Label', 'integra-core' ); ?></th>
					<th><?php esc_html_e( 'Value', 'integra-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $tokens as $key => $default ) : ?>
					<?php $meta = $registry[ $key ]; ?>
					<tr>
						<td><code><?php echo esc_html( $key ); ?></code></td>
						<td><?php echo esc_html( $meta['label'] ); ?></td>
						<td><?php self::render_input( $key, $values[ $key ] ?? $default, $meta['control'], $default ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Displays admin notices based on query args.
	 *
	 * @return void
	 */
	private static function render_notice() {
		$status = isset( $_GET['integra_core_status'] ) ? sanitize_key( wp_unslash( $_GET['integra_core_status'] ) ) : '';

		if ( 'saved' === $status ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Token values saved.', 'integra-core' ) . '</p></div>';
		}

		if ( 'reset' === $status ) {
			echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Token values reset to defaults.', 'integra-core' ) . '</p></div>';
		}

		if ( 'core_updated' === $status ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Integra Core stylesheet version updated.', 'integra-core' ) . '</p></div>';
		}

		if ( 'save_failed' === $status || 'reset_failed' === $status ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Integra Core could not write assets/css/integra-configs.css. Check plugin file permissions and try again.', 'integra-core' ) . '</p></div>';
		}

		if ( 'core_update_failed' === $status ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Integra Core could not update assets/css/integra-core.min.css. Check plugin file permissions and try again.', 'integra-core' ) . '</p></div>';
		}
	}

	/**
	 * Verifies capabilities and nonce for admin actions.
	 *
	 * @param string $nonce_action Nonce action.
	 * @return void
	 */
	private static function assert_permissions( $nonce_action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'integra-core' ) );
		}

		check_admin_referer( $nonce_action );
	}

	/**
	 * Builds a redirect URL for the admin page.
	 *
	 * @param string $tab    Tab slug.
	 * @param string $status Status slug.
	 * @return string
	 */
	private static function admin_url( $tab, $section, $status ) {
		return add_query_arg(
			array(
				'page'                => self::MENU_SLUG,
				'tab'                 => $tab,
				'section'             => $section,
				'integra_core_status' => $status,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Renders inline styles and JS for the admin UI.
	 *
	 * @return void
	 */
	private static function render_admin_assets() {
		?>
		<style>
			.integra-core-token-subheading {
				margin: 0 0 12px;
			}

			.integra-core-token-groups {
				display: grid;
				gap: 16px;
				margin-top: 20px;
			}

			.integra-core-token-group {
				border: 1px solid #dcdcde;
				border-radius: 10px;
				background: #fff;
				padding: 16px;
			}

			.integra-core-token-group__header {
				margin-bottom: 12px;
			}

			.integra-core-token-group__header h3 {
				margin: 0 0 4px;
			}

			.integra-core-token-group__header p {
				margin: 0;
				color: #50575e;
			}

			.integra-core-token-table {
				margin: 0;
			}

			.integra-core-color-control {
				position: relative;
				max-width: 460px;
			}

			.integra-core-color-row,
			.integra-core-alpha-row {
				display: flex;
				align-items: center;
				gap: 8px;
			}

			.integra-core-color-picker {
				inline-size: 42px;
				min-inline-size: 42px;
				block-size: 32px;
				padding: 0;
				border-radius: 6px;
			}

			.integra-core-color-text {
				flex: 1 1 auto;
				min-width: 0;
			}

			.integra-core-alpha-toggle {
				white-space: nowrap;
			}

			.integra-core-alpha-popover {
				position: absolute;
				top: calc(100% + 6px);
				right: 0;
				z-index: 20;
				min-width: 220px;
				padding: 10px 12px;
				background: #fff;
				border: 1px solid #c3c4c7;
				border-radius: 8px;
				box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
			}

			.integra-core-color-alpha {
				flex: 1 1 auto;
				min-width: 0;
			}

			.integra-core-alpha-value {
				min-width: 44px;
				text-align: right;
				color: #50575e;
			}
		</style>
		<script>
			(function() {
				function clamp(num, min, max) {
					return Math.min(max, Math.max(min, num));
				}

				function componentToHex(value) {
					return value.toString(16).padStart(2, '0');
				}

				function parseColor(value) {
					const raw = (value || '').trim().toLowerCase();

					if (!raw) {
						return null;
					}

					const hexMatch = raw.match(/^#([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i);
					if (hexMatch) {
						let hex = hexMatch[1];
						if (hex.length === 3 || hex.length === 4) {
							hex = hex.split('').map((char) => char + char).join('');
						}
						const alpha = hex.length === 8 ? parseInt(hex.slice(6, 8), 16) / 255 : 1;
						return {
							r: parseInt(hex.slice(0, 2), 16),
							g: parseInt(hex.slice(2, 4), 16),
							b: parseInt(hex.slice(4, 6), 16),
							a: alpha
						};
					}

					const rgbMatch = raw.match(/^rgba?\(([^)]+)\)$/);
					if (!rgbMatch) {
						return null;
					}

					const parts = rgbMatch[1].split(',').map((part) => part.trim());
					if (parts.length < 3 || parts.length > 4) {
						return null;
					}

					const r = Number(parts[0]);
					const g = Number(parts[1]);
					const b = Number(parts[2]);
					const a = parts.length === 4 ? Number(parts[3]) : 1;

					if ([r, g, b, a].some((num) => Number.isNaN(num))) {
						return null;
					}

					return {
						r: clamp(Math.round(r), 0, 255),
						g: clamp(Math.round(g), 0, 255),
						b: clamp(Math.round(b), 0, 255),
						a: clamp(a, 0, 1)
					};
				}

				function toHex(color) {
					const hex = '#' + componentToHex(color.r) + componentToHex(color.g) + componentToHex(color.b);
					if (color.a < 0.999) {
						return hex + componentToHex(Math.round(color.a * 255));
					}
					return hex;
				}

				function updateControl(control, color) {
					if (!control || !color) {
						return;
					}

					const picker = control.querySelector('.integra-core-color-picker');
					const alpha = control.querySelector('.integra-core-color-alpha');
					const alphaValue = control.querySelector('.integra-core-alpha-value');
					const text = control.querySelector('.integra-core-color-text');
					const nextValue = toHex(color);

					picker.value = '#' + componentToHex(color.r) + componentToHex(color.g) + componentToHex(color.b);
					alpha.value = String(Math.round(color.a * 100));
					alphaValue.textContent = alpha.value + '%';
					text.value = nextValue;
				}

				function initControl(control) {
					const picker = control.querySelector('.integra-core-color-picker');
					const alpha = control.querySelector('.integra-core-color-alpha');
					const toggle = control.querySelector('.integra-core-alpha-toggle');
					const popover = control.querySelector('.integra-core-alpha-popover');
					const text = control.querySelector('.integra-core-color-text');
					const fallback = parseColor(text.dataset.default) || { r: 0, g: 0, b: 0, a: 1 };
					let current = parseColor(text.value) || fallback;

					updateControl(control, current);

					toggle.addEventListener('click', function(event) {
						event.preventDefault();
						const isOpen = !popover.hasAttribute('hidden');
						document.querySelectorAll('.integra-core-alpha-popover').forEach(function(item) {
							item.setAttribute('hidden', 'hidden');
						});
						if (!isOpen) {
							popover.removeAttribute('hidden');
						}
					});

					picker.addEventListener('input', function() {
						current = parseColor(text.value) || current || fallback;
						const pickerColor = parseColor(picker.value) || fallback;
						current = { r: pickerColor.r, g: pickerColor.g, b: pickerColor.b, a: current.a };
						updateControl(control, current);
					});

					alpha.addEventListener('input', function() {
						current = parseColor(text.value) || current || fallback;
						current = { r: current.r, g: current.g, b: current.b, a: clamp(Number(alpha.value) / 100, 0, 1) };
						updateControl(control, current);
					});

					text.addEventListener('input', function() {
						const parsed = parseColor(text.value);
						if (parsed) {
							current = parsed;
							updateControl(control, current);
						}
					});

					text.addEventListener('blur', function() {
						const parsed = parseColor(text.value) || fallback;
						current = parsed;
						updateControl(control, current);
					});
				}

				document.addEventListener('DOMContentLoaded', function() {
					document.querySelectorAll('.integra-core-color-control').forEach(initControl);
					document.addEventListener('click', function(event) {
						if (!event.target.closest('.integra-core-color-control')) {
							document.querySelectorAll('.integra-core-alpha-popover').forEach(function(item) {
								item.setAttribute('hidden', 'hidden');
							});
						}
					});
				});
			}());
		</script>
		<?php
	}
}
