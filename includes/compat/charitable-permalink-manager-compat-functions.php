<?php
/**
 * Functions to improve compatibility with Permalink Manager.
 *
 * @package   Charitable/Functions/Compatibility
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.41
 * @version   1.6.41
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * When the canonical redirect option is enabled in Permalink Manager
 * (it's on by default), the user is redirected from endpoints in Charitable
 * to the source page.
 *
 * @since  1.6.41
 *
 * @return void
 */
function charitable_compat_permalink_manager_block_canonical_redirects() {
	$helper    = charitable()->endpoints();
	$endpoints = [
		'forgot_password',
		'reset_password',
	];

	if ( in_array( $helper->get_current_endpoint(), $endpoints ) || $helper->get_endpoint( 'profile' )->is_descendent_page() ) {
		set_query_var( 'do_not_redirect', 1 );
	}
}

add_action( 'template_redirect', 'charitable_compat_permalink_manager_block_canonical_redirects', 0 );
