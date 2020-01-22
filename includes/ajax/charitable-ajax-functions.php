<?php
/**
 * Charitable AJAX Functions.
 *
 * Functions used with ajax hooks.
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

if ( ! function_exists( 'charitable_ajax_get_donation_form' ) ) :

	/**
	 * Returns the donation form content for a particular campaign, through AJAX.
	 *
	 * @since   1.2.3
	 *
	 * @return  void
	 */
	function charitable_ajax_get_donation_form() {
		if ( ! isset( $_POST['campaign_id'] ) ) {
			wp_send_json_error();
		}

		/* Load the template files. */
		require_once( charitable()->get_path( 'includes' ) . 'public/charitable-template-functions.php' );
		require_once( charitable()->get_path( 'includes' ) . 'public/charitable-template-hooks.php' );

		$campaign = new Charitable_Campaign( $_POST['campaign_id'] );

		ob_start();

		$campaign->get_donation_form()->render();

		/**
		 * Strip any shortcodes that haven't been rendered yet.
		 *
		 * @see https://github.com/Charitable/Charitable/issues/708
		 */
		$output = preg_replace( '~(?:\[/?)[^/\]]+/?\]~s', '', ob_get_clean() );

		wp_send_json_success( $output );

		die();
	}

endif;

if ( ! function_exists( 'charitable_plupload_image_upload' ) ) :

	/**
	 * Upload an image via plupload.
	 *
	 * @return void
	 */
	function charitable_plupload_image_upload() {
		$post_id  = (int) filter_input( INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT );
		$field_id = (string) filter_input( INPUT_POST, 'field_id' );

		check_ajax_referer( 'charitable-upload-images-' . $field_id );

		$file = $_FILES['async-upload'];
		$file_attr = wp_handle_upload( $file, array( 'test_form' => false ) );

		if ( isset( $file_attr['error'] ) ) {
			wp_send_json_error( $file_attr );
		}

		$attachment = array(
			'guid'              => $file_attr['url'],
			'post_mime_type'    => $file_attr['type'],
			'post_title'        => preg_replace( '/\.[^.]+$/', '', basename( $file['name'] ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		/**
		 * Insert the file as an attachment.
		 */
		$attachment_id = wp_insert_attachment( $attachment, $file_attr['file'], $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error();
		}

		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $file_attr['file'] ) );

		$size = (string) filter_input( INPUT_POST, 'size' );
		$max_uploads = (int) filter_input( INPUT_POST, 'max_uploads', FILTER_SANITIZE_NUMBER_INT );

		if ( ! $size ) {
			$size = 'thumbnail';
		}

		ob_start();

		charitable_template( 'form-fields/picture-preview.php', array(
			'image' => $attachment_id,
			'field' => array(
			'key' => $field_id,
			'size' => $size,
			'max_uploads' => $max_uploads,
			),
		) );

		wp_send_json_success( ob_get_clean() );
	}

endif;

/**
 * Get donor data, given a donor ID.
 *
 * @since  1.6.28
 *
 * @return void
 */
function charitable_ajax_get_donor_data() {
	$donor_id = (int) filter_input( INPUT_POST, 'donor_id', FILTER_SANITIZE_NUMBER_INT );

	if ( ! check_ajax_referer( 'donor-select', 'nonce' ) ) {
		wp_send_json_error( 'nonce check failed', '403' );
	}

	$fields = array_key_exists( 'fields', $_POST ) ? $_POST['fields'] : [];
	$donor  = new Charitable_Donor( $donor_id );
	$data   = [];

	foreach ( $fields as $field ) {
		$data[ $field ] = $donor->get_donor_meta( $field );
	}

	wp_send_json_success( $data );
}

/**
 * Receives an AJAX request to load session content and returns
 * the output to be loaded.
 *
 * @since  1.5.0
 *
 * @return void
 */
function charitable_ajax_get_session_content() {
	if ( ! array_key_exists( 'templates', $_POST ) ) {
		wp_send_json_error( __( 'Missing templates in request.', 'charitable' ) );
	}

	$output = array();

	foreach ( $_POST['templates'] as $i => $template_args ) {
		if ( empty( $template_args ) || ! array_key_exists( 'template', $template_args ) ) {
			continue;
		}

		/**
		 * Get the output for the session content item.
		 *
		 * @since 1.5.0
		 *
		 * @param false|string $content The content to return, or a false in case of failure.
		 * @param array        $args    Mixed set of arguments.
		 */
		$output[ $i ] = apply_filters( 'charitable_session_content_' . $template_args['template'], false, $template_args );
	}

	wp_send_json_success( $output );
}

/**
 * Return the donation receipt.
 *
 * @since  1.5.0
 *
 * @param  string|false $content Content to return, or false in case of failure.
 * @param  array        $args    Mixed array of args.
 * @return string|false
 */
function charitable_ajax_get_session_donation_receipt( $content, $args ) {
	if ( ! array_key_exists( 'donation_id', $args ) ) {
		return $content;
	}

	$donation = charitable_get_donation( $args['donation_id'] );

	if ( ! $donation ) {
		return $content;
	}

	return charitable_template_donation_receipt_output( '', $donation );
}

/**
 * Return the donation form's amount field.
 *
 * @since  1.5.0
 *
 * @param  string|false $content Content to return, or false in case of failure.
 * @param  array        $args    Mixed array of args.
 * @return string|false
 */
function charitable_ajax_get_session_donation_form_amount_field( $content, $args ) {
	if ( ! array_key_exists( 'campaign_id', $args ) ) {
		return $content;
	}

	if ( ! array_key_exists( 'form_id', $args ) ) {
		return $content;
	}

	ob_start();

	charitable_template(
		'donation-form/donation-amount-list.php',
		array(
			'campaign' => charitable_get_campaign( $args['campaign_id'] ),
			'form_id'  => $args['form_id'],
		)
	);

	return ob_get_clean();
}

/**
 * Return the donation form's amount field.
 *
 * @since  1.5.0
 *
 * @param  string|false $content Content to return, or false in case of failure.
 * @param  array        $args    Mixed array of args.
 * @return string|false
 */
function charitable_ajax_get_session_donation_form_current_amount_text( $content, $args ) {
	if ( ! array_key_exists( 'campaign_id', $args ) ) {
		return $content;
	}

	if ( ! array_key_exists( 'form_id', $args ) ) {
		return $content;
	}

	$amount = charitable_get_campaign( $args['campaign_id'] )->get_donation_amount_in_session();

	return charitable_template_donation_form_current_amount_text( $amount, $args['form_id'], $args['campaign_id'] );
}

/**
 * Return the error messages.
 *
 * @since  1.5.0
 *
 * @param  string|false $content Content to return, or false in case of failure.
 * @return string|false
 */
function charitable_ajax_get_session_errors( $content ) {
	$errors = charitable_get_notices()->get_errors();

	if ( empty( $errors ) ) {
		return $content;
	}

	ob_start();

	charitable_template(
		'form-fields/errors.php',
		array(
			'errors' => $errors,
		)
	);

	return ob_get_clean();
}

/**
 * Return the notices
 *
 * @since  1.5.0
 *
 * @param  string|false $content Content to return, or false in case of failure.
 * @return string|false
 */
function charitable_ajax_get_session_notices( $content ) {
	$notices = charitable_get_notices()->get_notices();

	if ( empty( $notices ) ) {
		return $content;
	}

	ob_start();

	charitable_template(
		'form-fields/notices.php',
		array(
			'notices' => $notices,
		)
	);

	return ob_get_clean();
}
