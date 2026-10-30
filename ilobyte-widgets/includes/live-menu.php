<?php
/**
 * Live service menu: reads the workspace's public service menu (names,
 * prices, durations, categories) from the IloByte Pro API, server-side.
 *
 * IloByte Pro is the single source of truth — WordPress only displays the
 * menu. Requests use the publishable widget key only; no visitor data is sent.
 *
 * Caching:
 * - a transient (10 minutes) keyed by API base + workspace host + slug;
 * - the last good copy in a non-autoloaded option, served when the API
 *   fails (stale beats blank); a failed fetch is retried after 5 minutes so
 *   an outage never costs every page view an 8-second wait;
 * - a static memo, so one request never calls the API more than once.
 *
 * @package IloByteWidgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Option holding the last successfully fetched menu (autoload off). */
const ILOBYTE_WIDGETS_LAST_GOOD_OPTION = 'ilobyte_widgets_menu_last_good';

/**
 * Base URL of the IloByte Pro API, without trailing slash.
 *
 * @return string
 */
function ilobyte_widgets_api_base() {
	/**
	 * Filters the IloByte Pro API base URL used for the live service menu.
	 *
	 * @param string $base Default 'https://api.ilobyte.com/api/v1'.
	 */
	$base = (string) apply_filters( 'ilobyte_widgets_api_base', 'https://api.ilobyte.com/api/v1' );
	return untrailingslashit( esc_url_raw( $base, array( 'http', 'https' ) ) );
}

/**
 * Everything needed to call the menu API, or null when not configured.
 *
 * The workspace slug falls back to the subdomain of a *.ilobyte.com
 * workspace address when the slug setting is empty.
 *
 * @return array{host:string,key:string,site:string,api:string,cache_key:string}|null
 */
function ilobyte_widgets_menu_credentials() {
	$host = strtolower( untrailingslashit( trim( (string) get_option( 'ilobyte_widgets_host', '' ) ) ) );
	$key  = trim( (string) get_option( 'ilobyte_widgets_key', '' ) );
	$site = trim( (string) get_option( 'ilobyte_widgets_site', '' ) );

	if ( '' === $site && '' !== $host ) {
		$hostname = (string) wp_parse_url( $host, PHP_URL_HOST );
		if ( preg_match( '/^([a-z0-9-]+)\.ilobyte\.com$/', $hostname, $m ) && ! in_array( $m[1], array( 'www', 'api', 'app' ), true ) ) {
			$site = $m[1];
		}
	}
	if ( '' === $key || '' === $site ) {
		return null;
	}

	$api = ilobyte_widgets_api_base();
	return array(
		'host'      => $host,
		'key'       => $key,
		'site'      => $site,
		'api'       => $api,
		'cache_key' => 'ilobyte_widgets_menu_' . md5( $api . '|' . $host . '|' . $site ),
	);
}

/**
 * Call GET /commerce/widget/menu once.
 *
 * @param array $creds From ilobyte_widgets_menu_credentials().
 * @return array|WP_Error Normalized menu, or the reason it could not be read.
 */
function ilobyte_widgets_fetch_menu( $creds ) {
	$response = wp_remote_get(
		$creds['api'] . '/commerce/widget/menu',
		array(
			'timeout'             => 8,
			'redirection'         => 2,
			'limit_response_size' => 2 * MB_IN_BYTES,
			'headers'             => array(
				'X-API-Key'   => $creds['key'],
				'X-Tenant-Id' => $creds['site'],
				'Accept'      => 'application/json',
			),
		)
	);
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'ilobyte_widgets_http', $response->get_error_message() );
	}

	$status = (int) wp_remote_retrieve_response_code( $response );
	$body   = json_decode( (string) wp_remote_retrieve_body( $response ), true );

	if ( $status < 200 || $status > 299 ) {
		$detail = '';
		if ( is_array( $body ) && isset( $body['message'] ) ) {
			$detail = is_array( $body['message'] ) ? implode( '; ', array_filter( $body['message'], 'is_string' ) ) : (string) $body['message'];
		}
		$detail = mb_substr( sanitize_text_field( $detail ), 0, 200 );
		return new WP_Error(
			'ilobyte_widgets_status',
			trim(
				sprintf(
					/* translators: 1: HTTP status code, 2: error detail from the API (may be empty). */
					__( 'The IloByte API answered HTTP %1$d. %2$s', 'ilobyte-widgets' ),
					$status,
					$detail
				)
			)
		);
	}

	$menu = ilobyte_widgets_normalize_menu( $body );
	if ( null === $menu ) {
		return new WP_Error( 'ilobyte_widgets_payload', __( 'The IloByte API sent a response this plugin does not understand.', 'ilobyte-widgets' ) );
	}
	return $menu;
}

/**
 * Validate and clean the API payload into the shape the shortcodes use.
 *
 * Accepts the bare object or the API's `{ "data": { ... } }` envelope.
 *
 * @param mixed $body Decoded JSON.
 * @return array|null { currency, categories: [{id,name}], services: [...] }, or null if unusable.
 */
function ilobyte_widgets_normalize_menu( $body ) {
	for ( $i = 0; $i < 2 && is_array( $body ) && ! isset( $body['services'] ) && isset( $body['data'] ) && is_array( $body['data'] ); $i++ ) {
		$body = $body['data'];
	}
	if ( ! is_array( $body ) || ! isset( $body['services'] ) || ! is_array( $body['services'] ) ) {
		return null;
	}

	$currency = isset( $body['currency'] ) && is_string( $body['currency'] )
		? strtoupper( (string) preg_replace( '/[^A-Za-z]/', '', $body['currency'] ) )
		: '';
	if ( 3 !== strlen( $currency ) ) {
		$currency = 'NGN';
	}

	$categories = array();
	if ( isset( $body['categories'] ) && is_array( $body['categories'] ) ) {
		foreach ( array_slice( $body['categories'], 0, 200 ) as $cat ) {
			if ( ! is_array( $cat ) || ! isset( $cat['id'], $cat['name'] ) || ! is_scalar( $cat['id'] ) || ! is_scalar( $cat['name'] ) ) {
				continue;
			}
			$id   = sanitize_text_field( (string) $cat['id'] );
			$name = sanitize_text_field( (string) $cat['name'] );
			if ( '' !== $id && '' !== $name ) {
				$categories[] = array(
					'id'   => $id,
					'name' => $name,
				);
			}
		}
	}

	$services = array();
	foreach ( array_slice( $body['services'], 0, 1000 ) as $svc ) {
		if ( ! is_array( $svc ) ) {
			continue;
		}
		$code = ilobyte_widgets_sanitize_service_code( isset( $svc['code'] ) ? $svc['code'] : '' );
		$name = isset( $svc['name'] ) && is_scalar( $svc['name'] ) ? sanitize_text_field( (string) $svc['name'] ) : '';
		if ( '' === $code || '' === $name ) {
			continue;
		}
		$duration = isset( $svc['durationMinutes'] ) && is_numeric( $svc['durationMinutes'] ) ? (int) round( (float) $svc['durationMinutes'] ) : null;
		$mode     = isset( $svc['depositMode'] ) && is_string( $svc['depositMode'] ) ? strtoupper( $svc['depositMode'] ) : '';

		$services[] = array(
			'itemId'          => isset( $svc['itemId'] ) && is_scalar( $svc['itemId'] ) ? sanitize_text_field( (string) $svc['itemId'] ) : '',
			'code'            => $code,
			'name'            => $name,
			'description'     => isset( $svc['description'] ) && is_scalar( $svc['description'] ) ? sanitize_text_field( (string) $svc['description'] ) : '',
			'imageUrl'        => isset( $svc['imageUrl'] ) && is_string( $svc['imageUrl'] ) ? esc_url_raw( $svc['imageUrl'], array( 'http', 'https' ) ) : '',
			'categoryId'      => isset( $svc['categoryId'] ) && is_scalar( $svc['categoryId'] ) ? sanitize_text_field( (string) $svc['categoryId'] ) : '',
			'durationMinutes' => ( null !== $duration && $duration > 0 ) ? $duration : null,
			'price'           => isset( $svc['price'] ) && is_numeric( $svc['price'] ) ? (float) $svc['price'] : null,
			'depositMode'     => in_array( $mode, array( 'NONE', 'PERCENT', 'FIXED', 'FULL' ), true ) ? $mode : null,
			'depositValue'    => isset( $svc['depositValue'] ) && is_numeric( $svc['depositValue'] ) ? (float) $svc['depositValue'] : null,
		);
	}

	return array(
		'currency'   => $currency,
		'categories' => $categories,
		'services'   => $services,
	);
}

/**
 * The live menu for this request.
 *
 * @param bool $force_refresh Skip the transient and call the API (Refresh now).
 * @return array {
 *     @type array|null $menu       Normalized menu, or null when none is available.
 *     @type int        $fetched_at Unix time of the successful fetch behind $menu (0 if none).
 *     @type bool       $stale      True when the last API call failed ($menu, if any, is the last good copy).
 *     @type string     $error      Why the last call failed ('' when fresh).
 *     @type bool       $configured False when the key or workspace slug is missing.
 * }
 */
function ilobyte_widgets_get_menu_state( $force_refresh = false ) {
	static $memo = array();

	$creds = ilobyte_widgets_menu_credentials();
	if ( null === $creds ) {
		return array(
			'menu'       => null,
			'fetched_at' => 0,
			'stale'      => false,
			'error'      => __( 'Add your widget key and workspace slug under Settings → IloByte Widgets.', 'ilobyte-widgets' ),
			'configured' => false,
		);
	}
	$cache_key = $creds['cache_key'];

	// At most one API call per request, even when Refresh now is pressed.
	if ( isset( $memo[ $cache_key ] ) && ( ! $force_refresh || $memo[ $cache_key ]['called_api'] ) ) {
		return $memo[ $cache_key ];
	}

	if ( ! $force_refresh ) {
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) && array_key_exists( 'menu', $cached ) ) {
			$cached['configured'] = true;
			$cached['called_api'] = false;
			$memo[ $cache_key ]   = $cached;
			return $cached;
		}
	}

	$result = ilobyte_widgets_fetch_menu( $creds );
	if ( is_wp_error( $result ) ) {
		$last  = get_option( ILOBYTE_WIDGETS_LAST_GOOD_OPTION );
		$valid = is_array( $last ) && isset( $last['cache_key'], $last['menu'] ) && $last['cache_key'] === $cache_key && is_array( $last['menu'] );
		$state = array(
			'menu'       => $valid ? $last['menu'] : null,
			'fetched_at' => $valid ? (int) $last['fetched_at'] : 0,
			'stale'      => true,
			'error'      => $result->get_error_message(),
		);
		set_transient( $cache_key, $state, 5 * MINUTE_IN_SECONDS );
	} else {
		$state = array(
			'menu'       => $result,
			'fetched_at' => time(),
			'stale'      => false,
			'error'      => '',
		);
		set_transient( $cache_key, $state, 10 * MINUTE_IN_SECONDS );
		update_option(
			ILOBYTE_WIDGETS_LAST_GOOD_OPTION,
			array(
				'cache_key'  => $cache_key,
				'fetched_at' => $state['fetched_at'],
				'menu'       => $result,
			),
			false
		);
	}

	$state['configured'] = true;
	$state['called_api'] = true;
	$memo[ $cache_key ]  = $state;
	return $state;
}

/**
 * Throw away the cached menu and fetch it again (the Refresh now button).
 *
 * @return array The new state, as ilobyte_widgets_get_menu_state().
 */
function ilobyte_widgets_refresh_menu() {
	$creds = ilobyte_widgets_menu_credentials();
	if ( null !== $creds ) {
		delete_transient( $creds['cache_key'] );
	}
	return ilobyte_widgets_get_menu_state( true );
}

/**
 * The last good copy for the current workspace, without calling the API.
 *
 * @return array|null { fetched_at, menu } or null.
 */
function ilobyte_widgets_last_good_menu() {
	$creds = ilobyte_widgets_menu_credentials();
	$last  = get_option( ILOBYTE_WIDGETS_LAST_GOOD_OPTION );
	if ( null === $creds || ! is_array( $last ) || ! isset( $last['cache_key'], $last['menu'] ) || $last['cache_key'] !== $creds['cache_key'] ) {
		return null;
	}
	return $last;
}

/**
 * The cached state for the current workspace, without calling the API.
 *
 * @return array|null
 */
function ilobyte_widgets_cached_menu_state() {
	$creds = ilobyte_widgets_menu_credentials();
	if ( null === $creds ) {
		return null;
	}
	$cached = get_transient( $creds['cache_key'] );
	return is_array( $cached ) ? $cached : null;
}

/**
 * Find a service by its item code (case-insensitive).
 *
 * @param array  $menu Normalized menu.
 * @param string $code Sanitized item code.
 * @return array|null
 */
function ilobyte_widgets_find_service( $menu, $code ) {
	if ( '' === $code || ! is_array( $menu ) || empty( $menu['services'] ) ) {
		return null;
	}
	foreach ( $menu['services'] as $svc ) {
		if ( 0 === strcasecmp( $svc['code'], $code ) ) {
			return $svc;
		}
	}
	return null;
}

/**
 * An HTML comment visible only to administrators (for inline shortcodes,
 * where a visible notice would break the surrounding markup).
 *
 * @param string $message Plain-text hint.
 * @return string
 */
function ilobyte_widgets_admin_hint( $message ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return '';
	}
	return '<!-- ' . esc_html( 'IloByte Widgets: ' . $message ) . ' -->';
}

/**
 * A short visible notice shown only to administrators.
 *
 * @param string $message Plain-text notice.
 * @return string
 */
function ilobyte_widgets_admin_notice( $message ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return '';
	}
	return '<p class="ilobyte-menu-notice"><em>' . esc_html( $message ) . '</em></p>';
}
