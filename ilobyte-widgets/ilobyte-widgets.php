<?php
/**
 * Plugin Name:       IloByte Widgets — Bookings & Store
 * Plugin URI:        https://github.com/IloByteInnovation/IloByteWordPress
 * Description:       Embed your IloByte Pro appointment booking and mini store on any page with the [ilobyte_booking] and [ilobyte_shop] shortcodes. Requires an IloByte Pro workspace.
 * Version:           1.0.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            IloByte Innovation
 * Author URI:        https://www.ilobyte.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ilobyte-widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ILOBYTE_WIDGETS_VERSION', '1.0.1' );

/**
 * Settings: the workspace address (https://yourbusiness.ilobyte.com), the
 * publishable widget key (pk_...), and the workspace slug. All three come from
 * IloByte Pro under Settings → Apps & Devices → Website widgets.
 */
function ilobyte_widgets_register_settings() {
	register_setting( 'ilobyte_widgets', 'ilobyte_widgets_host', array(
		'type'              => 'string',
		'sanitize_callback' => 'esc_url_raw',
		'default'           => '',
	) );
	register_setting( 'ilobyte_widgets', 'ilobyte_widgets_key', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '',
	) );
	register_setting( 'ilobyte_widgets', 'ilobyte_widgets_site', array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '',
	) );
}
add_action( 'admin_init', 'ilobyte_widgets_register_settings' );

function ilobyte_widgets_settings_menu() {
	add_options_page(
		__( 'IloByte Widgets', 'ilobyte-widgets' ),
		__( 'IloByte Widgets', 'ilobyte-widgets' ),
		'manage_options',
		'ilobyte-widgets',
		'ilobyte_widgets_settings_page'
	);
}
add_action( 'admin_menu', 'ilobyte_widgets_settings_menu' );

function ilobyte_widgets_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'IloByte Widgets', 'ilobyte-widgets' ); ?></h1>
		<p>
			<?php esc_html_e( 'Find these values in your IloByte Pro workspace under Settings → Apps & Devices → Website widgets.', 'ilobyte-widgets' ); ?>
		</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'ilobyte_widgets' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ilobyte_widgets_host"><?php esc_html_e( 'Workspace address', 'ilobyte-widgets' ); ?></label></th>
					<td>
						<input name="ilobyte_widgets_host" id="ilobyte_widgets_host" type="url" class="regular-text"
							placeholder="https://yourbusiness.ilobyte.com"
							value="<?php echo esc_attr( get_option( 'ilobyte_widgets_host' ) ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ilobyte_widgets_key"><?php esc_html_e( 'Widget key (pk_…)', 'ilobyte-widgets' ); ?></label></th>
					<td>
						<input name="ilobyte_widgets_key" id="ilobyte_widgets_key" type="text" class="regular-text"
							placeholder="pk_live_…"
							value="<?php echo esc_attr( get_option( 'ilobyte_widgets_key' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'A publishable key — safe to appear in your pages.', 'ilobyte-widgets' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ilobyte_widgets_site"><?php esc_html_e( 'Workspace slug', 'ilobyte-widgets' ); ?></label></th>
					<td>
						<input name="ilobyte_widgets_site" id="ilobyte_widgets_site" type="text" class="regular-text"
							placeholder="yourbusiness"
							value="<?php echo esc_attr( get_option( 'ilobyte_widgets_site' ) ); ?>" />
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<h2><?php esc_html_e( 'Usage', 'ilobyte-widgets' ); ?></h2>
		<p><code>[ilobyte_booking]</code> — <?php esc_html_e( 'appointment booking calendar', 'ilobyte-widgets' ); ?><br />
		<code>[ilobyte_shop]</code> — <?php esc_html_e( 'mini store with checkout', 'ilobyte-widgets' ); ?></p>
	</div>
	<?php
}

/**
 * Render one widget iframe. The iframe is served by the business's own IloByte
 * workspace; this plugin ships no remote code — only the bundled resize
 * listener below.
 */
function ilobyte_widgets_render( $widget, $title ) {
	$host = get_option( 'ilobyte_widgets_host' );
	$key  = get_option( 'ilobyte_widgets_key' );
	$site = get_option( 'ilobyte_widgets_site' );
	if ( ! $host || ! $key ) {
		if ( current_user_can( 'manage_options' ) ) {
			return '<p><em>' . esc_html__( 'IloByte widget: set your workspace address and widget key under Settings → IloByte Widgets.', 'ilobyte-widgets' ) . '</em></p>';
		}
		return '';
	}
	wp_enqueue_script(
		'ilobyte-widgets-resize',
		plugins_url( 'assets/resize.js', __FILE__ ),
		array(),
		ILOBYTE_WIDGETS_VERSION,
		true
	);
	$src = untrailingslashit( esc_url( $host ) ) . '/embed/' . rawurlencode( $widget )
		. '?k=' . rawurlencode( $key )
		. ( $site ? '&site=' . rawurlencode( $site ) : '' );
	return sprintf(
		'<iframe class="ilobyte-widget-frame" src="%s" style="width:100%%;border:0;display:block;min-height:320px;" loading="lazy" allow="payment" title="%s"></iframe>',
		esc_url( $src ),
		esc_attr( $title )
	);
}

add_shortcode( 'ilobyte_booking', function () {
	return ilobyte_widgets_render( 'booking', __( 'Appointment booking', 'ilobyte-widgets' ) );
} );
add_shortcode( 'ilobyte_shop', function () {
	return ilobyte_widgets_render( 'shop', __( 'Online store', 'ilobyte-widgets' ) );
} );
