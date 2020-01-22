<?php
/**
 * Functions to improve compatibility with Litespeed Cache.
 *
 * @package   Charitable/Functions/Compatibility
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.14
 * @version   1.6.14
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Disable cache in Litespeed.
 *
 * @since  1.6.14
 *
 * @return void
 */
function charitable_compat_litespeed_cache_disable_cache() {
	define( 'LSCACHE_NO_CACHE', true );
}

add_action( 'charitable_do_not_cache', 'charitable_compat_litespeed_cache_disable_cache' );
