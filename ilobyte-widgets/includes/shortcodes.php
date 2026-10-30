<?php
/**
 * Live-data shortcodes: [ilobyte_price], [ilobyte_duration], [ilobyte_menu].
 *
 * Prices and durations are read from IloByte Pro (see live-menu.php); nothing
 * here is editable from WordPress. When data is missing, visitors see nothing
 * and administrators get a hint.
 *
 * @package IloByteWidgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared lookup for the inline shortcodes.
 *
 * @param array  $atts Shortcode attributes.
 * @param string $tag  Shortcode tag (for the shortcode_atts filter).
 * @param array  $defaults Extra defaults.
 * @return array{0: array|null, 1: string, 2: array, 3: string} [ service, hint, atts, currency ]
 */
function ilobyte_widgets_lookup_for_shortcode( $atts, $tag, $defaults = array() ) {
	$atts = shortcode_atts( array_merge( array( 'service' => '' ), $defaults ), $atts, $tag );
	$code = ilobyte_widgets_sanitize_service_code( $atts['service'] );
	if ( '' === $code ) {
		/* translators: %s: shortcode name. */
		return array( null, ilobyte_widgets_admin_hint( sprintf( __( '[%s] needs a service="ITEM-CODE" attribute.', 'ilobyte-widgets' ), $tag ) ), $atts, '' );
	}

	$state = ilobyte_widgets_get_menu_state();
	if ( empty( $state['menu'] ) ) {
		/* translators: %s: reason the live prices could not be loaded. */
		return array( null, ilobyte_widgets_admin_hint( sprintf( __( 'live prices are unavailable: %s', 'ilobyte-widgets' ), $state['error'] ) ), $atts, '' );
	}

	$service = ilobyte_widgets_find_service( $state['menu'], $code );
	if ( null === $service ) {
		/* translators: %s: item code. */
		return array( null, ilobyte_widgets_admin_hint( sprintf( __( 'no bookable service with code "%s" in your IloByte Pro workspace.', 'ilobyte-widgets' ), $code ) ), $atts, '' );
	}
	return array( $service, '', $atts, $state['menu']['currency'] );
}

/**
 * [ilobyte_price service="ITM-0002"] → ₦30,000
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function ilobyte_widgets_price_shortcode( $atts ) {
	list( $service, $hint, , $currency ) = ilobyte_widgets_lookup_for_shortcode( $atts, 'ilobyte_price' );
	if ( null === $service ) {
		return $hint;
	}
	$price = ilobyte_widgets_format_price( $service['price'], $currency );
	if ( '' === $price ) {
		/* translators: %s: item code. */
		return ilobyte_widgets_admin_hint( sprintf( __( 'service "%s" has no price in IloByte Pro.', 'ilobyte-widgets' ), $service['code'] ) );
	}
	return esc_html( $price );
}
add_shortcode( 'ilobyte_price', 'ilobyte_widgets_price_shortcode' );

/**
 * [ilobyte_duration service="ITM-0002"] → 60 Minutes; format="short" → 60 min
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function ilobyte_widgets_duration_shortcode( $atts ) {
	list( $service, $hint, $atts ) = ilobyte_widgets_lookup_for_shortcode( $atts, 'ilobyte_duration', array( 'format' => 'long' ) );
	if ( null === $service ) {
		return $hint;
	}
	$format   = ( 'short' === strtolower( trim( (string) $atts['format'] ) ) ) ? 'short' : 'long';
	$duration = ilobyte_widgets_format_duration( $service['durationMinutes'], $format );
	if ( '' === $duration ) {
		/* translators: %s: item code. */
		return ilobyte_widgets_admin_hint( sprintf( __( 'service "%s" has no duration in IloByte Pro.', 'ilobyte-widgets' ), $service['code'] ) );
	}
	return esc_html( $duration );
}
add_shortcode( 'ilobyte_duration', 'ilobyte_widgets_duration_shortcode' );

/**
 * Enqueue the menu stylesheet (also registers it when needed).
 */
function ilobyte_widgets_enqueue_menu_style() {
	wp_enqueue_style(
		'ilobyte-widgets-menu',
		plugins_url( 'assets/menu.css', ILOBYTE_WIDGETS_FILE ),
		array(),
		ILOBYTE_WIDGETS_VERSION
	);
}

/**
 * Load the menu stylesheet in <head> when the current post uses [ilobyte_menu]
 * (avoids a flash of unstyled content). Page builders that store content
 * elsewhere still get the stylesheet, enqueued late by the shortcode itself.
 */
function ilobyte_widgets_maybe_enqueue_menu_style() {
	if ( ! is_singular() ) {
		return;
	}
	$post = get_post();
	if ( $post && has_shortcode( (string) $post->post_content, 'ilobyte_menu' ) ) {
		ilobyte_widgets_enqueue_menu_style();
	}
}
add_action( 'wp_enqueue_scripts', 'ilobyte_widgets_maybe_enqueue_menu_style' );

/**
 * Is a shortcode attribute a "yes"?
 *
 * @param mixed $value Attribute value.
 * @return bool
 */
function ilobyte_widgets_attr_is_yes( $value ) {
	return in_array( strtolower( trim( (string) $value ) ), array( 'yes', 'true', '1', 'on' ), true );
}

/**
 * Shortcode attribute text: decode the entities the block/classic editor adds
 * ("Hair &amp; Nails" → "Hair & Nails") and trim.
 *
 * @param mixed $value Attribute value.
 * @return string
 */
function ilobyte_widgets_attr_text( $value ) {
	return trim( html_entity_decode( sanitize_text_field( (string) $value ), ENT_QUOTES, 'UTF-8' ) );
}

/**
 * [ilobyte_menu] — the live service menu, grouped by category.
 *
 * Attributes:
 * - category   Only these categories (name, comma-separated, case-insensitive).
 * - book_url   Adds a "Book" link per row to book_url?service=CODE.
 * - book_label Text of that link (default "Book").
 * - images     "yes" shows a thumbnail per service.
 * - headings   "no" hides the category headings.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function ilobyte_widgets_menu_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'category'   => '',
			'book_url'   => '',
			'book_label' => '',
			'images'     => 'no',
			'headings'   => 'yes',
		),
		$atts,
		'ilobyte_menu'
	);

	$state = ilobyte_widgets_get_menu_state();
	$menu  = $state['menu'];
	if ( empty( $menu ) ) {
		return ilobyte_widgets_admin_notice(
			/* translators: %s: reason the live menu could not be loaded. */
			sprintf( __( 'IloByte menu is not shown to visitors: %s', 'ilobyte-widgets' ), $state['error'] )
		);
	}

	// Group services by category, in the order IloByte Pro sends them.
	$groups = array();
	foreach ( $menu['categories'] as $cat ) {
		$groups[ $cat['id'] ] = array(
			'name'     => $cat['name'],
			'services' => array(),
		);
	}
	$other = array(
		'name'     => __( 'Other services', 'ilobyte-widgets' ),
		'services' => array(),
	);
	foreach ( $menu['services'] as $svc ) {
		if ( '' !== $svc['categoryId'] && isset( $groups[ $svc['categoryId'] ] ) ) {
			$groups[ $svc['categoryId'] ]['services'][] = $svc;
		} else {
			$other['services'][] = $svc;
		}
	}

	$wanted = array_filter( array_map( 'trim', explode( ',', ilobyte_widgets_attr_text( $atts['category'] ) ) ), 'strlen' );
	if ( $wanted ) {
		$groups = array_filter(
			$groups,
			function ( $group ) use ( $wanted ) {
				foreach ( $wanted as $name ) {
					if ( 0 === strcasecmp( $group['name'], $name ) ) {
						return true;
					}
				}
				return false;
			}
		);
	} elseif ( $other['services'] ) {
		$groups['__other'] = $other;
	}

	$groups = array_filter(
		$groups,
		function ( $group ) {
			return ! empty( $group['services'] );
		}
	);
	if ( ! $groups ) {
		if ( $wanted ) {
			/* translators: %s: category names from the shortcode. */
			$message = sprintf( __( 'IloByte menu: no services in category "%s".', 'ilobyte-widgets' ), implode( ', ', $wanted ) );
		} else {
			$message = __( 'IloByte menu: your workspace has no bookable services yet.', 'ilobyte-widgets' );
		}
		return ilobyte_widgets_admin_notice( $message );
	}

	ilobyte_widgets_enqueue_menu_style();

	// Relative ("/book-a-session/") or absolute; an unsafe scheme yields '' (no Book links).
	$book_url   = esc_url_raw( trim( html_entity_decode( (string) $atts['book_url'], ENT_QUOTES, 'UTF-8' ) ) );
	$book_label = ilobyte_widgets_attr_text( $atts['book_label'] );
	if ( '' === $book_label ) {
		$book_label = __( 'Book', 'ilobyte-widgets' );
	}
	$images = ilobyte_widgets_attr_is_yes( $atts['images'] );
	// A lone "Other services" group gets no heading: it is just "the menu".
	$headings = 'no' !== strtolower( trim( (string) $atts['headings'] ) )
		&& ! ( 1 === count( $groups ) && isset( $groups['__other'] ) );

	$html = '<div class="ilobyte-menu' . ( $images ? ' ilobyte-menu--images' : '' ) . '">';
	foreach ( $groups as $group ) {
		$html .= '<div class="ilobyte-menu__category">';
		if ( $headings ) {
			$html .= '<h3 class="ilobyte-menu__title">' . esc_html( $group['name'] ) . '</h3>';
		}
		$html .= '<ul class="ilobyte-menu__list">';
		foreach ( $group['services'] as $svc ) {
			$html .= ilobyte_widgets_menu_row( $svc, $menu['currency'], $book_url, $book_label, $images );
		}
		$html .= '</ul></div>';
	}
	$html .= '</div>';
	return $html;
}
add_shortcode( 'ilobyte_menu', 'ilobyte_widgets_menu_shortcode' );

/**
 * One <li> of the menu: name · duration · price, a one-line description,
 * and an optional Book link.
 *
 * @param array  $svc        Normalized service.
 * @param string $currency   ISO 4217 code.
 * @param string $book_url   Booking page URL, or ''.
 * @param string $book_label Link text.
 * @param bool   $images     Show a thumbnail.
 * @return string
 */
function ilobyte_widgets_menu_row( $svc, $currency, $book_url, $book_label, $images ) {
	$duration = ilobyte_widgets_format_duration( $svc['durationMinutes'], 'short' );
	$price    = ilobyte_widgets_format_price( $svc['price'], $currency );

	$html = '<li class="ilobyte-menu__item">';

	if ( $images ) {
		if ( '' !== $svc['imageUrl'] ) {
			$html .= sprintf(
				'<img class="ilobyte-menu__image" src="%s" alt="%s" width="64" height="64" loading="lazy" decoding="async" />',
				esc_url( $svc['imageUrl'] ),
				esc_attr( $svc['name'] )
			);
		} else {
			$html .= '<span class="ilobyte-menu__image ilobyte-menu__image--empty" aria-hidden="true"></span>';
		}
	}

	$html .= '<div class="ilobyte-menu__body"><p class="ilobyte-menu__line">';
	$html .= '<span class="ilobyte-menu__name">' . esc_html( $svc['name'] ) . '</span>';
	if ( '' !== $duration || '' !== $price ) {
		$html .= '<span class="ilobyte-menu__meta">';
		if ( '' !== $duration ) {
			$html .= '<span class="ilobyte-menu__duration">' . esc_html( $duration ) . '</span>';
		}
		if ( '' !== $duration && '' !== $price ) {
			$html .= '<span class="ilobyte-menu__sep" aria-hidden="true"> · </span>';
		}
		if ( '' !== $price ) {
			$html .= '<span class="ilobyte-menu__price">' . esc_html( $price ) . '</span>';
		}
		$html .= '</span>';
	}
	$html .= '</p>';
	if ( '' !== $svc['description'] ) {
		$html .= '<p class="ilobyte-menu__description">' . esc_html( $svc['description'] ) . '</p>';
	}
	$html .= '</div>';

	if ( '' !== $book_url ) {
		$html .= sprintf(
			'<a class="ilobyte-menu__book" href="%s">%s<span class="ilobyte-menu__sr"> %s</span></a>',
			esc_url( add_query_arg( 'service', rawurlencode( $svc['code'] ), $book_url ) ),
			esc_html( $book_label ),
			esc_html( $svc['name'] )
		);
	}

	return $html . '</li>';
}
