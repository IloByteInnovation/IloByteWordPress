<?php
/**
 * Plugin Name:       IloByte Widgets — Bookings & Store
 * Plugin URI:        https://github.com/IloByteInnovation/IloByteWordPress
 * Description:       Embed your IloByte Pro appointment booking, mini store and live service menu (prices and durations kept in sync with your workspace) on any page with shortcodes. Requires an IloByte Pro workspace.
 * Version:           1.1.0
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

define( 'ILOBYTE_WIDGETS_VERSION', '1.1.0' );
define( 'ILOBYTE_WIDGETS_FILE', __FILE__ );

require_once plugin_dir_path( __FILE__ ) . 'includes/format.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/live-menu.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/shortcodes.php';

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
	$notice_key = 'ilobyte_widgets_notice_' . get_current_user_id();
	$notice     = get_transient( $notice_key );
	if ( false !== $notice ) {
		delete_transient( $notice_key );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'IloByte Widgets', 'ilobyte-widgets' ); ?></h1>
		<?php if ( is_array( $notice ) && isset( $notice['type'], $notice['message'] ) ) : ?>
			<div class="notice notice-<?php echo esc_attr( 'success' === $notice['type'] ? 'success' : 'error' ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
		<?php endif; ?>
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
		<?php ilobyte_widgets_live_prices_box(); ?>
		<h2><?php esc_html_e( 'Usage', 'ilobyte-widgets' ); ?></h2>
		<p><code>[ilobyte_booking]</code> — <?php esc_html_e( 'appointment booking calendar', 'ilobyte-widgets' ); ?><br />
		<code>[ilobyte_booking service="ITM-0002"]</code> — <?php esc_html_e( 'booking calendar opened on one service (a ?service=ITM-0002 link to the page does the same)', 'ilobyte-widgets' ); ?><br />
		<code>[ilobyte_shop]</code> — <?php esc_html_e( 'mini store with checkout', 'ilobyte-widgets' ); ?><br />
		<code>[ilobyte_price service="ITM-0002"]</code> — <?php esc_html_e( 'live price of a service, e.g. ₦30,000', 'ilobyte-widgets' ); ?><br />
		<code>[ilobyte_duration service="ITM-0002"]</code> — <?php esc_html_e( 'live duration, e.g. 60 Minutes (add format="short" for 60 min)', 'ilobyte-widgets' ); ?><br />
		<code>[ilobyte_menu book_url="/book-a-session/"]</code> — <?php esc_html_e( 'service menu grouped by category, with a Book link per service (optional: category="Massages", images="yes")', 'ilobyte-widgets' ); ?></p>
	</div>
	<?php
}

/**
 * Settings page box: when the live prices were last synced, and Refresh now.
 * Shows cached state only — viewing the settings page never calls the API.
 */
function ilobyte_widgets_live_prices_box() {
	$last   = ilobyte_widgets_last_good_menu();
	$cached = ilobyte_widgets_cached_menu_state();
	?>
	<h2><?php esc_html_e( 'Live prices', 'ilobyte-widgets' ); ?></h2>
	<p>
		<?php esc_html_e( 'Service names, prices and durations are managed in IloByte Pro — change them there, not in WordPress. This website picks up changes within 10 minutes, or straight away when you press Refresh now.', 'ilobyte-widgets' ); ?>
	</p>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Last successful sync', 'ilobyte-widgets' ); ?></th>
			<td>
				<?php
				if ( $last && ! empty( $last['fetched_at'] ) ) {
					$fetched_at = (int) $last['fetched_at'];
					echo esc_html(
						sprintf(
							/* translators: 1: date and time, 2: human-readable time difference, e.g. "5 mins". */
							__( '%1$s (%2$s ago)', 'ilobyte-widgets' ),
							wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $fetched_at ),
							human_time_diff( $fetched_at )
						)
					);
				} else {
					esc_html_e( 'Never', 'ilobyte-widgets' );
				}
				?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Services', 'ilobyte-widgets' ); ?></th>
			<td><?php echo esc_html( number_format_i18n( $last ? count( $last['menu']['services'] ) : 0 ) ); ?></td>
		</tr>
		<?php if ( $cached && ! empty( $cached['error'] ) ) : ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Last attempt', 'ilobyte-widgets' ); ?></th>
				<td>
					<?php
					if ( $last ) {
						/* translators: %s: error message. */
						$failure = sprintf( __( 'Failed: %s — visitors see the last good copy.', 'ilobyte-widgets' ), $cached['error'] );
					} else {
						/* translators: %s: error message. */
						$failure = sprintf( __( 'Failed: %s — prices and the menu are hidden from visitors until a sync succeeds.', 'ilobyte-widgets' ), $cached['error'] );
					}
					echo esc_html( $failure );
					?>
				</td>
			</tr>
		<?php endif; ?>
	</table>
	<?php if ( null === ilobyte_widgets_menu_credentials() ) : ?>
		<p class="description"><?php esc_html_e( 'Save your widget key and workspace slug above to turn on live prices.', 'ilobyte-widgets' ); ?></p>
	<?php else : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ilobyte_widgets_refresh" />
			<?php wp_nonce_field( 'ilobyte_widgets_refresh' ); ?>
			<?php submit_button( __( 'Refresh now', 'ilobyte-widgets' ), 'secondary', 'ilobyte_widgets_refresh_submit', false ); ?>
		</form>
	<?php endif; ?>
	<?php
}

/**
 * Refresh now: clear the cached menu and fetch it again.
 */
function ilobyte_widgets_handle_refresh() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'ilobyte-widgets' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'ilobyte_widgets_refresh' );

	$state = ilobyte_widgets_refresh_menu();
	if ( ! $state['configured'] ) {
		$notice = array(
			'type'    => 'error',
			'message' => $state['error'],
		);
	} elseif ( ! $state['stale'] ) {
		$notice = array(
			'type'    => 'success',
			'message' => sprintf(
				/* translators: %s: number of services. */
				_n( 'Live prices refreshed: %s service.', 'Live prices refreshed: %s services.', count( $state['menu']['services'] ), 'ilobyte-widgets' ),
				number_format_i18n( count( $state['menu']['services'] ) )
			),
		);
	} else {
		$notice = array(
			'type'    => 'error',
			/* translators: %s: error message. */
			'message' => sprintf( __( 'Refresh failed: %s', 'ilobyte-widgets' ), $state['error'] ),
		);
	}
	set_transient( 'ilobyte_widgets_notice_' . get_current_user_id(), $notice, MINUTE_IN_SECONDS );

	wp_safe_redirect( admin_url( 'options-general.php?page=ilobyte-widgets' ) );
	exit;
}
add_action( 'admin_post_ilobyte_widgets_refresh', 'ilobyte_widgets_handle_refresh' );

/**
 * Render one widget iframe. The iframe is served by the business's own IloByte
 * workspace; this plugin ships no remote code — only the bundled resize
 * listener below.
 *
 * @param string $widget  'booking' or 'shop'.
 * @param string $title   Accessible iframe title.
 * @param string $service Optional item code the booking calendar opens on.
 * @return string
 */
function ilobyte_widgets_render( $widget, $title, $service = '' ) {
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
		. ( $site ? '&site=' . rawurlencode( $site ) : '' )
		. ( '' !== $service ? '&service=' . rawurlencode( $service ) : '' );
	return sprintf(
		'<iframe class="ilobyte-widget-frame" src="%s" style="width:100%%;border:0;display:block;min-height:320px;" loading="lazy" allow="payment" title="%s"></iframe>',
		esc_url( $src ),
		esc_attr( $title )
	);
}

/**
 * [ilobyte_booking] — optional service="ITM-0002" opens the calendar on that
 * service; without it, a ?service=ITM-0002 in the page URL does the same.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function ilobyte_widgets_booking_shortcode( $atts ) {
	$atts    = shortcode_atts( array( 'service' => '' ), $atts, 'ilobyte_booking' );
	$service = ilobyte_widgets_sanitize_service_code( $atts['service'] );
	if ( '' === $service ) {
		$service = ilobyte_widgets_requested_service();
	}
	return ilobyte_widgets_render( 'booking', __( 'Appointment booking', 'ilobyte-widgets' ), $service );
}
add_shortcode( 'ilobyte_booking', 'ilobyte_widgets_booking_shortcode' );
add_shortcode( 'ilobyte_shop', function () {
	return ilobyte_widgets_render( 'shop', __( 'Online store', 'ilobyte-widgets' ) );
} );
