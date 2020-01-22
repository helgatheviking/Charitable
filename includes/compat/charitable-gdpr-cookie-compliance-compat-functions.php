<?php
/**
 * Functions to improve compatibility with GDPR Cookie Compliance.
 *
 * @package     Charitable/Functions/Compatibility
 * @author      Eric Daams
 * @copyright   Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6.27
 * @version     1.6.27
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Disable sessions if GDPR Cookie Compliance is activated
 * and user has chosen to block strictly necessary cookies.
 *
 * @since  1.6.27
 *
 * @param  boolean $disable Whether to disable cookies.
 * @return boolean
 */
function charitable_gdpr_cookie_compliance_disable_session( $disable ) {
	return $disable || ! gdpr_cookie_is_accepted( 'strict' );
}

add_filter( 'charitable_disable_cookie', 'charitable_gdpr_cookie_compliance_disable_session' );

/**
 * By default the GDPR Cookie Compliance plugin injects the
 * scripts without page reload, this should be disabled if
 * the PHP functions are used.
 */
add_action( 'gdpr_force_reload', '__return_true' );
