<?php
/**
 * Plugin Name:     Chromatic Aberration Hover
 * Plugin URI:      https://exzent.de
 * Description:     Applies a chromatic aberration hover mask to selectors with configurable colors and behavior.
 * Version:         0.1.0
 * Author:          MitchWP Exzent.deBuilder
 * Author URI:      https://exzent.de
 * License:         GPL-2.0-or-later
 * Text Domain:     chromatic-aberration-hover
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Chromatic_Aberration_Hover' ) ) {

	class Chromatic_Aberration_Hover {

		const OPTION_NAME = 'cah_options';
		const SETTINGS_PAGE = 'chromatic-aberration-hover';
		const VERSION = '0.1.0';

		/**
		 * Hold the current option set.
		 *
		 * @var array
		 */
		private $options = array();

		private $defaults = array(
			'enabled'        => 1,
			'selectors'      => '.site-logo img, .site-logo svg, h1.site-title',
			'mask_radius'    => 300,
			'shadow_size'    => 4,
			'colors_mode'    => 'two',
			'left_color'     => '#ff0000',
			'right_color'    => '#00ffff',
			'red_color'      => '#ff0000',
			'green_color'    => '#00ff00',
			'blue_color'     => '#0000ff',
			'tracking_mode'  => 'per_element',
			'pause_event'    => 'cah-pause',
			'resume_event'   => 'cah-resume',
		);

		public function __construct() {
			$this->options = $this->get_options();

			add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		}

		/**
		 * Register the admin settings page.
		 */
		public function add_settings_page() {
			add_options_page(
				__( 'Chromatic Aberration Hover', 'chromatic-aberration-hover' ),
				__( 'Chromatic Aberration Hover', 'chromatic-aberration-hover' ),
				'manage_options',
				self::SETTINGS_PAGE,
				array( $this, 'render_settings_page' )
			);
		}

		/**
		 * Register plugin options.
		 */
		public function register_settings() {
			register_setting(
				'cah_settings_group',
				self::OPTION_NAME,
				array( $this, 'sanitize_options' )
			);
		}

		/**
		 * Sanitize the saved options.
		 *
		 * @param array $input Raw input.
		 * @return array
		 */
		public function sanitize_options( $input ) {
			$input = is_array( $input ) ? $input : array();
			$clean = wp_parse_args( $input, $this->defaults );

			$clean['enabled']       = ! empty( $input['enabled'] ) ? 1 : 0;
			$clean['selectors']     = isset( $input['selectors'] ) ? sanitize_textarea_field( $input['selectors'] ) : '';
			$clean['mask_radius']   = isset( $input['mask_radius'] ) ? max( 0, absint( $input['mask_radius'] ) ) : $this->defaults['mask_radius'];
			$clean['shadow_size']   = isset( $input['shadow_size'] ) ? max( 0, absint( $input['shadow_size'] ) ) : $this->defaults['shadow_size'];
			$clean['colors_mode']   = in_array( $input['colors_mode'], array( 'two', 'three' ), true ) ? $input['colors_mode'] : $this->defaults['colors_mode'];
			$clean['tracking_mode'] = in_array( $input['tracking_mode'], array( 'per_element', 'global' ), true ) ? $input['tracking_mode'] : $this->defaults['tracking_mode'];

			$clean['pause_event']  = isset( $input['pause_event'] ) ? sanitize_text_field( $input['pause_event'] ) : $this->defaults['pause_event'];
			$clean['resume_event'] = isset( $input['resume_event'] ) ? sanitize_text_field( $input['resume_event'] ) : $this->defaults['resume_event'];
			if ( '' === $clean['pause_event'] ) {
				$clean['pause_event'] = $this->defaults['pause_event'];
			}
			if ( '' === $clean['resume_event'] ) {
				$clean['resume_event'] = $this->defaults['resume_event'];
			}

			$clean['left_color']  = $this->sanitize_hex_color_with_default( $input['left_color'], $this->defaults['left_color'] );
			$clean['right_color'] = $this->sanitize_hex_color_with_default( $input['right_color'], $this->defaults['right_color'] );
			$clean['red_color']   = $this->sanitize_hex_color_with_default( $input['red_color'], $this->defaults['red_color'] );
			$clean['green_color'] = $this->sanitize_hex_color_with_default( $input['green_color'], $this->defaults['green_color'] );
			$clean['blue_color']  = $this->sanitize_hex_color_with_default( $input['blue_color'], $this->defaults['blue_color'] );

			return $clean;
		}

		/**
		 * Helper for hex colors.
		 */
		private function sanitize_hex_color_with_default( $value, $default ) {
			$sanitized = sanitize_hex_color( $value );
			return $sanitized ? $sanitized : $default;
		}

		/**
		 * Render the settings page markup.
		 */
		public function render_settings_page() {
			$options = $this->get_options();
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Chromatic Aberration Hover', 'chromatic-aberration-hover' ); ?></h1>
				<form method="post" action="options.php">
					<?php
					settings_fields( 'cah_settings_group' );
					do_settings_sections( 'cah_settings_group' );
					?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">
								<label for="cah_enabled"><?php esc_html_e( 'Enable effect', 'chromatic-aberration-hover' ); ?></label>
							</th>
							<td>
								<input type="checkbox" id="cah_enabled" name="cah_options[enabled]" value="1" <?php checked( $options['enabled'], 1 ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="cah_selectors"><?php esc_html_e( 'Target selectors', 'chromatic-aberration-hover' ); ?></label>
							</th>
							<td>
								<textarea id="cah_selectors" name="cah_options[selectors]" rows="4" cols="50"><?php echo esc_textarea( $options['selectors'] ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Comma-separated CSS selectors (e.g. .site-logo img, h1.site-title).', 'chromatic-aberration-hover' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="cah_mask_radius"><?php esc_html_e( 'Mask radius (px)', 'chromatic-aberration-hover' ); ?></label>
							</th>
							<td>
								<input type="number" id="cah_mask_radius" name="cah_options[mask_radius]" value="<?php echo esc_attr( $options['mask_radius'] ); ?>" min="0" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="cah_shadow_size"><?php esc_html_e( 'Shadow size (px)', 'chromatic-aberration-hover' ); ?></label>
							</th>
							<td>
								<input type="number" id="cah_shadow_size" name="cah_options[shadow_size]" value="<?php echo esc_attr( $options['shadow_size'] ); ?>" min="0" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label><?php esc_html_e( 'Color mode', 'chromatic-aberration-hover' ); ?></label>
							</th>
							<td>
								<label><input type="radio" name="cah_options[colors_mode]" value="two" <?php checked( $options['colors_mode'], 'two' ); ?> /> <?php esc_html_e( 'Two colors', 'chromatic-aberration-hover' ); ?></label><br />
								<label><input type="radio" name="cah_options[colors_mode]" value="three" <?php checked( $options['colors_mode'], 'three' ); ?> /> <?php esc_html_e( 'Three colors (RGB split)', 'chromatic-aberration-hover' ); ?></label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="cah_left_color"><?php esc_html_e( 'Left color', 'chromatic-aberration-hover' ); ?></label>
							</th>
							<td>
								<input type="color" id="cah_left_color" name="cah_options[left_color]" value="<?php echo esc_attr( $options['left_color'] ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="cah_right_color"><?php esc_html_e( 'Right color', 'chromatic-aberration-hover' ); ?></label>
							</th>
							<td>
								<input type="color" id="cah_right_color" name="cah_options[right_color]" value="<?php echo esc_attr( $options['right_color'] ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="cah_red_color"><?php esc_html_e( 'Red channel color', 'chromatic-aberration-hover' ); ?></label>
							</th>
							<td>
								<input type="color" id="cah_red_color" name="cah_options[red_color]" value="<?php echo esc_attr( $options['red_color'] ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="cah_green_color"><?php esc_html_e( 'Green channel color', 'chromatic-aberration-hover' ); ?></label>
							</th>
							<td>
								<input type="color" id="cah_green_color" name="cah_options[green_color]" value="<?php echo esc_attr( $options['green_color'] ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="cah_blue_color"><?php esc_html_e( 'Blue channel color', 'chromatic-aberration-hover' ); ?></label>
							</th>
							<td>
								<input type="color" id="cah_blue_color" name="cah_options[blue_color]" value="<?php echo esc_attr( $options['blue_color'] ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label><?php esc_html_e( 'Tracking mode', 'chromatic-aberration-hover' ); ?></label>
							</th>
							<td>
								<label><input type="radio" name="cah_options[tracking_mode]" value="per_element" <?php checked( $options['tracking_mode'], 'per_element' ); ?> /> <?php esc_html_e( 'Per-element hover only', 'chromatic-aberration-hover' ); ?></label><br />
								<label><input type="radio" name="cah_options[tracking_mode]" value="global" <?php checked( $options['tracking_mode'], 'global' ); ?> /> <?php esc_html_e( 'Global mouse tracking', 'chromatic-aberration-hover' ); ?></label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="cah_pause_event"><?php esc_html_e( 'Pause event name', 'chromatic-aberration-hover' ); ?></label>
							</th>
							<td>
								<input type="text" id="cah_pause_event" name="cah_options[pause_event]" value="<?php echo esc_attr( $options['pause_event'] ); ?>" />
								<p class="description"><?php esc_html_e( 'Custom event name to pause the effect before another script animates the target.', 'chromatic-aberration-hover' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="cah_resume_event"><?php esc_html_e( 'Resume event name', 'chromatic-aberration-hover' ); ?></label>
							</th>
							<td>
								<input type="text" id="cah_resume_event" name="cah_options[resume_event]" value="<?php echo esc_attr( $options['resume_event'] ); ?>" />
								<p class="description"><?php esc_html_e( 'Event name to restart the effect once the animation is complete.', 'chromatic-aberration-hover' ); ?></p>
							</td>
						</tr>
					</table>
					<?php submit_button(); ?>
				</form>
			</div>
			<?php
		}

		/**
		 * Enqueue frontend assets.
		 */
		public function enqueue_assets() {
			$options = $this->get_options();

			if ( empty( $options['enabled'] ) ) {
				return;
			}

			$dir = plugin_dir_url( __FILE__ );
			wp_enqueue_style( 'cah-styles', $dir . 'assets/aberration.css', array(), self::VERSION );
			wp_enqueue_script( 'cah-scripts', $dir . 'assets/aberration.js', array(), self::VERSION, true );

			wp_localize_script(
				'cah-scripts',
				'cahSettings',
				array(
					'enabled'       => 1,
					'selectors'     => $options['selectors'],
					'maskRadius'    => (int) $options['mask_radius'],
					'shadowSize'    => (int) $options['shadow_size'],
					'colorsMode'    => $options['colors_mode'],
					'leftColor'     => $options['left_color'],
					'rightColor'    => $options['right_color'],
					'redColor'      => $options['red_color'],
					'greenColor'    => $options['green_color'],
					'blueColor'     => $options['blue_color'],
					'trackingMode'  => $options['tracking_mode'],
					'pauseEvent'    => $options['pause_event'],
					'resumeEvent'   => $options['resume_event'],
				)
			);
		}

		/**
		 * Convenience helper for retrieving options with defaults.
		 */
		private function get_options() {
			$saved = get_option( self::OPTION_NAME, array() );
			return wp_parse_args( $saved, $this->defaults );
		}
	}

	new Chromatic_Aberration_Hover();
}
