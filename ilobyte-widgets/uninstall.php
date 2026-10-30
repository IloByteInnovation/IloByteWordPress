<?php
/**
 * Remove the plugin's settings and cached service menu when it is deleted.
 *
 * @package IloByteWidgets
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$ilobyte_widgets_last_good = get_option( 'ilobyte_widgets_menu_last_good' );
if ( is_array( $ilobyte_widgets_last_good ) && ! empty( $ilobyte_widgets_last_good['cache_key'] ) ) {
	delete_transient( (string) $ilobyte_widgets_last_good['cache_key'] );
}

delete_option( 'ilobyte_widgets_menu_last_good' );
delete_option( 'ilobyte_widgets_host' );
delete_option( 'ilobyte_widgets_key' );
delete_option( 'ilobyte_widgets_site' );
