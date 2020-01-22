<?php
/**
 * Charitable User Management Hooks
 *
 * @package   Charitable/User Management/User Management
 * @author    Rafe Colton
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.4.0
 * @version   1.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fire off the password reset request.
 *
 * @see Charitable_Forgot_Password_Form::retrieve_password()
 */
add_action( 'charitable_retrieve_password', array( 'Charitable_Forgot_Password_Form', 'retrieve_password' ) );

/**
 * Reset a user's password.
 *
 * @see Charitable_Reset_Password_Form::reset_password()
 */
add_action( 'charitable_reset_password', array( 'Charitable_Reset_Password_Form', 'reset_password' ) );

/**
 * Save a profile.
 *
 * @see Charitable_Profile_Form::update_profile()
 */
add_action( 'charitable_update_profile', array( 'Charitable_Profile_Form', 'update_profile' ) );

/**
 * Save a user after registration.
 *
 * @see Charitable_Registration_Form::save_registration()
 */
add_action( 'charitable_save_registration', array( 'Charitable_Registration_Form', 'save_registration' ) );

/**
 * Display any notices before the login, profile and donation history pages.
 *
 * @see charitable_template_notices
 */
add_action( 'charitable_login_form_before', 'charitable_template_notices', 10, 0 );
add_action( 'charitable_my_donations_before', 'charitable_template_notices', 10, 0 );

/**
 * Add support for deprecated `charitable_user_profile_after_fields` hook.
 *
 * @see Charitable_Profile_Form::add_deprecated_charitable_user_profile_after_fields_hook()
 */
add_action( 'charitable_form_after_fields', array( 'Charitable_Profile_Form', 'add_deprecated_charitable_user_profile_after_fields_hook' ) );

/**
 * Redirect the user to the password reset page with the query string removed.
 *
 * @see Charitable_User_Management::maybe_redirect_to_password_reset()
 */
add_action( 'template_redirect', array( Charitable_User_Management::get_instance(), 'maybe_redirect_to_password_reset' ) );

/**
 * Hides the WP Admin bar if the current user is not allowed to view it.
 *
 * @see Charitable_User_Management::remove_admin_bar()
 */
add_action( 'after_setup_theme', array( Charitable_User_Management::get_instance(), 'maybe_remove_admin_bar' ) );

/**
 * Redirects the user away from /wp-admin if they are not authorized to access it.
 *
 * @see Charitable_User_Management::maybe_redirect_away_from_admin()
 */
add_action( 'admin_init', array( Charitable_User_Management::get_instance(), 'maybe_redirect_away_from_admin' ) );

/**
 * If desired, all access to wp-login.php can be redirected to the Charitable login page.
 *
 * This is switched off by default. To enable this option, you need to set a Charitable
 * login page and also return true for the filter:
 *
 * add_filter( 'charitable_disable_wp_login', '__return_true' );
 *
 * @see Charitable_User_Management::redirect_to_charitable_login()
 */
add_action( 'login_form_login', array( Charitable_User_Management::get_instance(), 'maybe_redirect_to_charitable_login' ) );

/**
 * If hiding all access to wp-login.php using the charitable_disable_wp_login
 * filter, capture login error messages and display them on the Charitable
 * login page
 *
 * @see Charitable_User_Management::maybe_redirect_at_authenticate()
 */
add_filter( 'authenticate', array( Charitable_User_Management::get_instance(), 'maybe_redirect_at_authenticate' ), 101, 2 );

/**
 * If hiding all access to wp-login.php using the charitable_disable_wp_login
 * filter, redirect user to custom forgot password page if they try to directly
 * access /wp-login.php?action=lostpassword
 *
 * @see Charitable_User_Management::maybe_redirect_to_custom_lostpassword()
 */
add_action( 'login_form_lostpassword', array( Charitable_User_Management::get_instance(), 'maybe_redirect_to_custom_lostpassword' ) );

/**
 * If hiding all access to wp-login.php using the charitable_disable_wp_login
 * filter, redirect user to custom reset password page if they try to directly
 * access /wp-login.php?action=rp or /wp-login.php?action=resetpass
 *
 * @see Charitable_User_Management::maybe_redirect_to_custom_password_reset_page()
 */
add_action( 'login_form_rp', array( Charitable_User_Management::get_instance(), 'maybe_redirect_to_custom_password_reset_page' ) );
add_action( 'login_form_resetpass', array( Charitable_User_Management::get_instance(), 'maybe_redirect_to_custom_password_reset_page' ) );

/**
 * Send a user verification email.
 *
 * @see Charitable_User_Management::send_verification_email()
 */
add_action( 'charitable_verify_email', array( Charitable_User_Management::get_instance(), 'send_verification_email' ) );
