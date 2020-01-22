<?php
/**
 * Functions to improve compatibility with WP Rocket.
 *
 * @package     Charitable/Functions/Compatibility
 * @version     1.4.18
 * @author      Eric Daams
 * @copyright   Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
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
function charitable_compat_wp_rocket_clear_campaign_cache( $campaign_id ) {
	if ( ! function_exists( 'rocket_clean_post' ) ) {
		return;
	}

	rocket_clean_post( $campaign_id );
}

add_action( 'charitable_flush_campaign_cache', 'charitable_compat_wp_rocket_clear_campaign_cache' );
