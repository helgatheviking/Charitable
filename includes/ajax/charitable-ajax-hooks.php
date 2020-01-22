<?php
/**
 * Charitable AJAX Hooks.
 *
 * Action/filter hooks used for Charitable AJAX setup.
 *
 * @package   Charitable/Functions/AJAX
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.2.3
 * @version   1.6.28
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve a campaign's donation form via AJAX.
 *
 * @see charitable_ajax_get_donation_form
 */
add_action( 'wp_ajax_get_donation_form', 'charitable_ajax_get_donation_form' );
add_action( 'wp_ajax_nopriv_get_donation_form', 'charitable_ajax_get_donation_form' );

/**
 * Upload an image through pupload uploader.
 *
 * @see charitable_plupload_image_upload
 */
add_action( 'wp_ajax_charitable_plupload_image_upload', 'charitable_plupload_image_upload' );
add_action( 'wp_ajax_nopriv_charitable_plupload_image_upload', 'charitable_plupload_image_upload' );

/**
 * Retrieve the details for a particular donor.
 *
 * @see charitable_ajax_get_donor_data
 */
add_action( 'wp_ajax_charitable_get_donor_data', 'charitable_ajax_get_donor_data' );

/**
 * Get session content.
 *
 * @see charitable_ajax_get_session_content()
 */
add_action( 'wp_ajax_charitable_get_session_content', 'charitable_ajax_get_session_content' );
add_action( 'wp_ajax_nopriv_charitable_get_session_content', 'charitable_ajax_get_session_content' );

/**
 * Return the content for particular templates.
 *
 * @see charitable_ajax_get_session_donation_receipt
 * @see charitable_ajax_get_session_donation_form_amount_field
 * @see charitable_ajax_get_session_donation_form_current_amount_text
 * @see charitable_ajax_get_session_errors
 * @see charitable_ajax_get_session_notices
 */
add_filter( 'charitable_session_content_donation_receipt', 'charitable_ajax_get_session_donation_receipt', 10, 2 );
add_filter( 'charitable_session_content_donation_form_amount_field', 'charitable_ajax_get_session_donation_form_amount_field', 10, 2 );
add_filter( 'charitable_session_content_donation_form_current_amount_text', 'charitable_ajax_get_session_donation_form_current_amount_text', 10, 2 );
add_filter( 'charitable_session_content_errors', 'charitable_ajax_get_session_errors' );
add_filter( 'charitable_session_content_notices', 'charitable_ajax_get_session_notices' );
