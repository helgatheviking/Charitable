<?php
/**
 * Charitable Deprecated Functions.
 *
 * @package   Charitable/Functions/Deprecated
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.1
 * @version   1.6.29
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @deprecated 1.0.1
 */
function charitable_user_dashboard() {
	charitable_get_deprecated()->deprecated_function(
		__FUNCTION__,
		'1.0.1',
		'charitable_get_user_dashboard'
	);

	return charitable_get_user_dashboard();
}

/**
 * @deprecated 1.4.0
 */
function charitable_user_can_access_receipt( Charitable_Donation $donation ) {
	charitable_get_deprecated()->deprecated_function(
		__FUNCTION__,
		'1.4.0',
		'Charitable_Donation::is_from_current_user()'
	);

	return $donation->is_from_current_user();
}

/**
 * @deprecated 1.5.0
 */
if ( ! function_exists( 'charitable_template_campaign_content' ) ) :

	function charitable_template_campaign_content( $content ) {
		charitable_get_deprecated()->deprecated_function(
			__FUNCTION__,
			'1.5.0',
			'Charitable_Endpoints::get_content()'
		);

		return charitable()->endpoints()->get_content( $content, 'campaign' );
	}

endif;

/**
 * @deprecated 1.5.0
 */
if ( ! function_exists( 'charitable_template_donation_form_content' ) ) :

	function charitable_template_donation_form_content( $content ) {
		charitable_get_deprecated()->deprecated_function(
			__FUNCTION__,
			'1.5.0',
			'Charitable_Endpoints::get_content()'
		);

		return charitable()->endpoints()->get_content( $content, 'campaign_donation' );
	}

endif;

/**
 * @deprecated 1.5.0
 */
if ( ! function_exists( 'charitable_template_donation_receipt_content' ) ) :

	function charitable_template_donation_receipt_content( $content ) {
		charitable_get_deprecated()->deprecated_function(
			__FUNCTION__,
			'1.5.0',
			'Charitable_Endpoints::get_content()'
		);

		return charitable()->endpoints()->get_content( $content, 'donation_receipt' );
	}

endif;

/**
 * @deprecated 1.5.0
 */
if ( ! function_exists( 'charitable_template_donation_processing_content' ) ) :

	function charitable_template_donation_processing_content( $content ) {
		charitable_get_deprecated()->deprecated_function(
			__FUNCTION__,
			'1.5.0',
			'Charitable_Endpoints::get_content()'
		);
		return charitable()->endpoints()->get_content( $content, 'donation_processing' );

	}

endif;

/**
 * @deprecated 1.5.0
 */
if ( ! function_exists( 'charitable_template_forgot_password_content' ) ) :

	function charitable_template_forgot_password_content( $content ) {
		charitable_get_deprecated()->deprecated_function(
			__FUNCTION__,
			'1.5.0',
			'Charitable_Endpoints::get_content()'
		);

		return charitable()->endpoints()->get_content( $content, 'forgot_password' );
	}

endif;

/**
 * @deprecated 1.5.0
 */
if ( ! function_exists( 'charitable_template_reset_password_content' ) ) :

	function charitable_template_reset_password_content( $content ) {
		charitable_get_deprecated()->deprecated_function(
			__FUNCTION__,
			'1.5.0',
			'Charitable_Endpoints::get_content()'
		);

		return charitable()->endpoints()->get_content( $content, 'reset_password' );
	}

endif;

/**
 * @deprecated 1.5.0
 */
if ( ! function_exists( 'charitable_add_body_classes' ) ) :

	function charitable_add_body_classes( $classes ) {
		charitable_get_deprecated()->deprecated_function(
			__FUNCTION__,
			'1.5.0',
			'Charitable_Endpoints::add_body_classes()'
		);

		return charitable()->endpoints()->add_body_classes( $classes );
	}

endif;

/**
 * Yoast attempts to executes shortcodes from the admin, so we
 * need to make sure these will work properly.
 *
 * @deprecated 2.0.0
 *
 * @since  1.5.4
 * @since  1.6.10 Deprecated.
 *
 * @return void
 */
function charitable_wpseo_compat_load_template_files() {
	charitable_get_deprecated()->deprecated_function(
		__FUNCTION__,
		'1.6.10',
		'charitable()->load_template_files()'
	);

	charitable()->load_template_files();
}
