<?php
/**
 * Pure formatting and sanitizing helpers: service codes, prices, durations.
 *
 * @package IloByteWidgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize an IloByte Pro item code (e.g. "ITM-0002").
 *
 * Keeps letters, digits, dash, underscore and dot. Anything longer than 64
 * characters is not a real code, so it is rejected rather than truncated.
 *
 * @param mixed $code Raw code from a shortcode attribute, URL or API payload.
 * @return string The clean code, or '' when nothing usable remains.
 */
function ilobyte_widgets_sanitize_service_code( $code ) {
	if ( ! is_scalar( $code ) ) {
		return '';
	}
	$code = preg_replace( '/[^A-Za-z0-9._-]/', '', (string) $code );
	if ( ! is_string( $code ) || '' === $code || strlen( $code ) > 64 ) {
		return '';
	}
	return $code;
}

/**
 * The service code requested in the current page URL (?service=ITM-0002).
 *
 * Lets a price list's "Book" buttons link to /book-a-session/?service=CODE so
 * the embedded calendar opens on that service.
 *
 * @return string Sanitized code, or ''.
 */
function ilobyte_widgets_requested_service() {
	// Read-only display hint: it only preselects a service in the embedded
	// calendar and changes nothing on this site, so there is no form or nonce.
	if ( ! isset( $_GET['service'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return '';
	}
	$raw = sanitize_text_field( wp_unslash( $_GET['service'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return ilobyte_widgets_sanitize_service_code( $raw );
}

/**
 * Currency symbols placed directly before the amount.
 *
 * Currencies not listed here are shown with their ISO code ("XOF 5,000").
 *
 * @return array<string,string> ISO 4217 code => symbol.
 */
function ilobyte_widgets_currency_symbols() {
	$symbols = array(
		'NGN' => "\u{20A6}",          // ₦
		'USD' => '$',
		'GBP' => "\u{00A3}",          // £
		'EUR' => "\u{20AC}",          // €
		'GHS' => "GH\u{20B5}",        // GH₵
		'KES' => "KSh\u{00A0}",
		'ZAR' => 'R',
		'INR' => "\u{20B9}",          // ₹
		'JPY' => "\u{00A5}",          // ¥
	);

	/**
	 * Filters the currency symbols used by [ilobyte_price] and [ilobyte_menu].
	 *
	 * @param array<string,string> $symbols ISO 4217 code => prefix symbol.
	 */
	return (array) apply_filters( 'ilobyte_widgets_currency_symbols', $symbols );
}

/**
 * Format a price for display: ₦30,000 — no decimals when the amount is whole.
 *
 * @param mixed  $amount   Number or numeric string (the API may send decimals as strings).
 * @param string $currency ISO 4217 code; NGN when missing.
 * @return string Plain text (not escaped), or '' when there is no price.
 */
function ilobyte_widgets_format_price( $amount, $currency = 'NGN' ) {
	if ( null === $amount || '' === $amount || ! is_numeric( $amount ) ) {
		return '';
	}
	$amount   = (float) $amount;
	$is_whole = abs( $amount - round( $amount ) ) < 0.005;
	$number   = $is_whole ? number_format_i18n( round( $amount ), 0 ) : number_format_i18n( $amount, 2 );

	$currency = strtoupper( (string) preg_replace( '/[^A-Za-z]/', '', (string) $currency ) );
	if ( 3 !== strlen( $currency ) ) {
		$currency = 'NGN';
	}
	$symbols   = ilobyte_widgets_currency_symbols();
	$formatted = isset( $symbols[ $currency ] )
		? $symbols[ $currency ] . $number
		: $currency . "\u{00A0}" . $number;

	/**
	 * Filters a formatted price.
	 *
	 * @param string $formatted Formatted price, e.g. "₦30,000".
	 * @param float  $amount    The numeric amount.
	 * @param string $currency  ISO 4217 code.
	 */
	return (string) apply_filters( 'ilobyte_widgets_formatted_price', $formatted, $amount, $currency );
}

/**
 * Format a duration in minutes.
 *
 * Long (default): 60 → "60 Minutes", 90 → "1 Hour 30 Minutes", 120 → "2 Hours".
 * Short:          60 → "60 min",     90 → "1 hr 30 min",       120 → "2 hrs".
 *
 * @param mixed  $minutes Whole minutes.
 * @param string $format  'long' or 'short'.
 * @return string Plain text (not escaped), or '' when there is no duration.
 */
function ilobyte_widgets_format_duration( $minutes, $format = 'long' ) {
	if ( null === $minutes || ! is_numeric( $minutes ) ) {
		return '';
	}
	$minutes = (int) round( (float) $minutes );
	if ( $minutes <= 0 ) {
		return '';
	}
	$short = ( 'short' === $format );

	if ( $minutes <= 60 ) {
		return ilobyte_widgets_minutes_label( $minutes, $short );
	}

	$hours = intdiv( $minutes, 60 );
	$rest  = $minutes % 60;
	if ( $short ) {
		/* translators: %s: number of hours, abbreviated unit. */
		$label = sprintf( _n( '%s hr', '%s hrs', $hours, 'ilobyte-widgets' ), number_format_i18n( $hours ) );
	} else {
		/* translators: %s: number of hours. */
		$label = sprintf( _n( '%s Hour', '%s Hours', $hours, 'ilobyte-widgets' ), number_format_i18n( $hours ) );
	}
	if ( $rest > 0 ) {
		$label .= ' ' . ilobyte_widgets_minutes_label( $rest, $short );
	}
	return $label;
}

/**
 * "30 Minutes" / "30 min".
 *
 * @param int  $minutes Whole minutes.
 * @param bool $short   Abbreviated unit.
 * @return string
 */
function ilobyte_widgets_minutes_label( $minutes, $short ) {
	if ( $short ) {
		/* translators: %s: number of minutes, abbreviated unit. */
		return sprintf( _n( '%s min', '%s min', $minutes, 'ilobyte-widgets' ), number_format_i18n( $minutes ) );
	}
	/* translators: %s: number of minutes. */
	return sprintf( _n( '%s Minute', '%s Minutes', $minutes, 'ilobyte-widgets' ), number_format_i18n( $minutes ) );
}
