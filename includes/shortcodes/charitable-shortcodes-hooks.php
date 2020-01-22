<?php
/**
 * Charitable Shortcodes Hooks.
 *
 * Action/filter hooks used for Charitable shortcodes
 *
 * @package     Charitable/Functions/Shortcodes
 * @version     1.2.0
 * @author      Eric Daams
 * @copyright   Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register shortcodes.
 *
 * @see Charitable_Campaigns_Shortcode::display()
 * @see Charitable_Donors_Shortcode::display()
 * @see Charitable_My_Donations_Shortcode::display()
 * @see Charitable_Donation_Form_Shortcode::display()
 * @see Charitable_Donation_Receipt_Shortcode::display()
 * @see Charitable_Login_Shortcode::display()
 * @see Charitable_Logout_Shortcode::display()
 * @see Charitable_Registration_Shortcode::display()
 * @see Charitable_Profile_Shortcode::display()
 * @see Charitable_Email_Shortcode::display()
 */
add_shortcode( 'campaigns', array( 'Charitable_Campaigns_Shortcode', 'display' ) );
add_shortcode( 'charitable_donors', array( 'Charitable_Donors_Shortcode', 'display' ) );
add_shortcode( 'charitable_donation_form', array( 'Charitable_Donation_Form_Shortcode', 'display' ) );
add_shortcode( 'donation_receipt', array( 'Charitable_Donation_Receipt_Shortcode', 'display' ) );
add_shortcode( 'charitable_my_donations', array( 'Charitable_My_Donations_Shortcode', 'display' ) );
add_shortcode( 'charitable_login', array( 'Charitable_Login_Shortcode', 'display' ) );
add_shortcode( 'charitable_logout', array( 'Charitable_Logout_Shortcode', 'display' ) );
add_shortcode( 'charitable_registration', array( 'Charitable_Registration_Shortcode', 'display' ) );
add_shortcode( 'charitable_profile', array( 'Charitable_Profile_Shortcode', 'display' ) );
add_shortcode( 'charitable_stat', array( 'Charitable_Stat_Shortcode', 'display' ) );
add_shortcode( 'charitable_email', array( 'Charitable_Email_Shortcode', 'display' ) );

/**
 * Fingerprint the login form with our charitable=true hidden field.
 *
 * @see Charitable_Login_Shortcode::add_hidden_field_to_login_form()
 */
add_filter( 'login_form_bottom', array( 'Charitable_Login_Shortcode', 'add_hidden_field_to_login_form' ), 10, 2 );

/**
 * Set the current email before sending or previewing an email.
 *
 * @see Charitable_Email_Shortcode::init()
 * @see Charitable_Email_Shortcode::init_preview()
 */
add_action( 'charitable_before_send_email', array( 'Charitable_Email_Shortcode', 'init' ) );
add_action( 'charitable_before_preview_email', array( 'Charitable_Email_Shortcode', 'init_preview' ) );

/**
 * Flush the `Charitable_Email_Shortcode` instance after an email is sent or previewed.
 *
 * @see Charitable_Email_Shortcode::flush()
 */
add_action( 'charitable_after_send_email', array( 'Charitable_Email_Shortcode', 'flush' ) );
add_action( 'charitable_after_preview_email', array( 'Charitable_Email_Shortcode', 'flush' ) );
