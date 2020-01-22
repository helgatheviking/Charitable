<?php
/**
 * Functions to improve compatibility with WP Fastest Cache.
 *
 * @package     Charitable/Functions/Compatibility
 * @author      Eric Daams
 * @copyright   Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4.18
 * @version     1.4.18
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clear the campaign page cache after a donation is received.
 *
 * @since   1.4.18
 *
 * @param   int $campaign_id The campaign ID.
 * @return  void
 */
function charitable_compat_wp_fastest_cache_clear_campaign_cache( $campaign_id ) {
	global $wp_fastest_cache;

	if ( ! is_a( $wp_fastest_cache, 'WpFastestCache' ) ) {
		return;
	}

	$wp_fastest_cache->singleDeleteCache( false, $campaign_id );
}

add_action( 'charitable_flush_campaign_cache', 'charitable_compat_wp_fastest_cache_clear_campaign_cache' );
