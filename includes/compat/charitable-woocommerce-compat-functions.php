<?php
/**
 * Functions to improve compatibility with WooCommerce.
 *
 * @package   Charitable/Functions/Compatibility
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.37
 * @version   1.6.37
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Charitable handles password resets & email verification in the same
 * way that WooCommerce handles password resets, which results in redirects
 * not working the way they're expected to.
 *
 * To avoid this, Charitable adds a query arg of 'charitable' to the
 * reset/email verification link. If that query arg is set, WooCommerce's
 * handler for this kind of request should not be used, instead falling
 * back to Charitable's.
 *
 * @since  1.6.37
 *
 * @return void
 */
function charitable_compat_woocommerce_prevent_wc_reset_redirect() {
	/* This is a password reset. */
	if ( isset( $_GET['key'] ) && ( isset( $_GET['id'] ) || isset( $_GET['login'] ) ) ) {
		if ( isset( $_GET['charitable'] ) ) {
			remove_action( 'template_redirect', array( 'WC_Form_Handler', 'redirect_reset_password_link' ) );
		}
	}
}

add_action( 'template_redirect', 'charitable_compat_woocommerce_prevent_wc_reset_redirect', 1 );
