<?php
/**
 * Charitable Donation Functions.
 *
 * @package   Charitable/Functions/Donation
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.34
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the given donation.
 *
 * This will first attempt to retrieve it from the object cache to prevent duplicate objects.
 *
 * @since  1.0.0
 *
 * @param  int     $donation_id The donation ID.
 * @param  boolean $force       Whether to force a non-cached donation object to be retrieved.
 * @return Charitable_Donation|false
 */
function charitable_get_donation( $donation_id, $force = false ) {
	$donation = wp_cache_get( $donation_id, 'charitable_donation', $force );

	if ( ! $donation ) {
		$donation = charitable()->registry()->get( 'donation_factory' )->get_donation( $donation_id );
		wp_cache_set( $donation_id, $donation, 'charitable_donation' );
	}

	return $donation;
}

/**
 * Update the donation cache.
 *
 * @since  1.6.34
 *
 * @param  Charitable_Donation|Charitable_Recurring_Donation $donation The donation object.
 * @return mixed
 */
function charitable_update_cached_donation( Charitable_Abstract_Donation $donation ) {
	wp_cache_set( $donation->ID, $donation, 'charitable_donation' );
}

/**
 * Given a donation ID and a key, return the submitted value.
 *
 * @since  1.5.0
 *
 * @param  Charitable_Abstract_Donation $donation The donation ID.
 * @param  string                       $key      The meta key.
 * @return mixed
 */
function charitable_get_donor_meta_value( Charitable_Abstract_Donation $donation, $key ) {
	return $donation->get_donor()->get_donor_meta( $key );
}

/**
 * Given a donation ID and a key, return the submitted value.
 *
 * @since  1.5.0
 *
 * @param  Charitable_Abstract_Donation $donation The donation ID.
 * @param  string                       $key      The meta key.
 * @return mixed
 */
function charitable_get_donation_meta_value( Charitable_Abstract_Donation $donation, $key ) {
	return get_post_meta( $donation->ID, $key, true );
}

/**
 * Sanitize a value for a donation field, based on the type of field.
 *
 * @since  1.6.7
 *
 * @param  mixed  $value The submitted value.
 * @param  string $key   The meta key.
 * @return mixed
 */
function charitable_get_sanitized_donation_field_value( $value, $key ) {
	$field = charitable()->donation_fields()->get_field( $key );
	$form  = ( false === $field->admin_form ) ? $field->donation_form : $field->admin_form;

	if ( ! is_array( $form ) ) {
		return $value;
	}

	if ( ! array_key_exists( 'options', $form ) ) {
		return $value;
	}

	if ( is_array( $value ) ) {
		$values = array();

		foreach ( $value as $val ) {
			$values[] = array_key_exists( $val, $form['options'] ) ? $form['options'][ $val ] : $val;
		}

		$value = implode( ', ', $values );
	} else {
		$value = array_key_exists( $value, $form['options'] ) ? $form['options'][ $value ] : $value;
	}

	return $value;
}

/**
 * Return the date formatted for a form field value.
 *
 * @since  1.5.3
 *
 * @param  Charitable_Abstract_Donation $donation The donation instance.
 * @return string
 */
function charitable_get_donation_date_for_form_value( Charitable_Abstract_Donation $donation ) {
	return $donation->get_date( 'F j, Y' );
}

/**
 * Returns the donation for the current request.
 *
 * @since  1.0.0
 *
 * @return Charitable_Donation|false
 */
function charitable_get_current_donation() {
	return charitable_get_helper( 'request' )->get_current_donation();
}

/**
 * Create a donation.
 *
 * @since  1.4.0
 *
 * @param  array $args Values for the donation.
 * @return int
 */
function charitable_create_donation( array $args ) {
	$donation_id = Charitable_Donation_Processor::get_instance()->save_donation( $args );

	Charitable_Donation_Processor::destroy();

	return $donation_id;
}

/**
 * Find and return a donation based on the given donation key.
 *
 * @since  1.4.0
 *
 * @param  string $donation_key The donation key to query by.
 * @return int|null
 */
function charitable_get_donation_by_key( $donation_key ) {
	global $wpdb;

	$sql = "SELECT post_id
			FROM $wpdb->postmeta
			WHERE meta_key = 'donation_key'
			AND meta_value = %s";

	return $wpdb->get_var( $wpdb->prepare( $sql, $donation_key ) );
}

/**
 * Find and return a donation using a gateway transaction ID.
 *
 * @since  1.4.7
 *
 * @param  string $transaction_id The transaction id to query by.
 * @return int|null
 */
function charitable_get_donation_by_transaction_id( $transaction_id ) {
	global $wpdb;

	$sql = "SELECT post_id
			FROM $wpdb->postmeta
			WHERE meta_key = '_gateway_transaction_id'
			AND meta_value = %s";

	return $wpdb->get_var( $wpdb->prepare( $sql, $transaction_id ) );
}

/**
 * Return the IPN url for this gateway.
 *
 * IPNs in Charitable are structured in this way: charitable-listener=gateway
 *
 * @since  1.4.0
 *
 * @param  string $gateway The gateway to get the ipn URL for.
 * @return string
 */
function charitable_get_ipn_url( $gateway ) {
	return charitable_get_permalink( 'webhook_listener', array( 'gateway' => $gateway ) );
}

/**
 * Checks for calls to our IPN.
 *
 * This method is called on the init hook.
 *
 * IPNs in Charitable are structured in this way: charitable-listener=gateway
 *
 * @deprecated 1.9.0
 *
 * @since  1.4.0
 * @since  1.6.14 Deprecated. This is now handled by the webhook listener endpoint.
 *
 * @return boolean True if this is a call to our IPN. False otherwise.
 */
function charitable_ipn_listener() {
	charitable_get_deprecated()->deprecated_function(
		__FUNCTION__,
		'1.6.14',
		"charitable()->endpoints()->get_endpoint( 'webhook_listener' )->process_incoming_webhook()"
	);

	return charitable()->endpoints()->get_endpoint( 'webhook_listener' )->process_incoming_webhook();
}

/**
 * Checks if this is happening right after a donation.
 *
 * This method is called on the init hook.
 *
 * @since  1.4.0
 *
 * @return boolean Whether this is after a donation.
 */
function charitable_is_after_donation() {
	if ( is_admin() ) {
		return false;
	}

	$processor = get_transient( 'charitable_donation_' . charitable_get_session()->get_session_id() );

	if ( ! $processor ) {
		return false;
	}

	/**
	 * Do something on a user's first page load after a donation has been made.
	 *
	 * @since 1.3.6
	 *
	 * @param Charitable_Donation_Processor $processor The instance of `Charitable_Donation_Processor`.
	 */
	do_action( 'charitable_after_donation', $processor );

	foreach ( $processor->get_campaign_donations_data() as $campaign_donation ) {
		charitable_get_session()->remove_donation( $campaign_donation['campaign_id'] );
	}

	delete_transient( 'charitable_donation_' . charitable_get_session()->get_session_id() );

	return true;

}

/**
 * Returns whether the donation status is valid.
 *
 * @since  1.4.0
 *
 * @param  string $status The status to check.
 * @return boolean
 */
function charitable_is_valid_donation_status( $status ) {
	return array_key_exists( $status, charitable_get_valid_donation_statuses() );
}

/**
 * Returns the donation statuses that signify a donation was complete.
 *
 * By default, this is just 'charitable-completed'. However, 'charitable-preapproval'
 * is also counted.
 *
 * @since  1.4.0
 *
 * @return string[]
 */
function charitable_get_approval_statuses() {
	/**
	 * Filter the list of donation statuses that we consider "approved".
	 *
	 * All statuses must already be listed as valid donation statuses.
	 *
	 * @see   charitable_get_valid_donation_statuses
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $statuses List of statuses.
	 */
	$statuses = apply_filters( 'charitable_approval_donation_statuses', array( 'charitable-completed' ) );

	return array_filter( $statuses, 'charitable_is_valid_donation_status' );
}

/**
 * Returns whether the passed status is an confirmed status.
 *
 * @since  1.4.0
 *
 * @param  string|false $status The status to check.
 * @return boolean
 */
function charitable_is_approved_status( $status ) {
	return false !== $status && in_array( $status, charitable_get_approval_statuses() );
}

/**
 * Return array of valid donations statuses.
 *
 * @since  1.4.0
 *
 * @return array
 */
function charitable_get_valid_donation_statuses() {
	/**
	 * Filter the list of possible donation statuses.
	 *
	 * @since 1.0.0
	 *
	 * @param array $statuses The list of status as a key=>value array.
	 */
	return apply_filters(
		'charitable_donation_statuses',
		array(
			'charitable-completed' => __( 'Paid', 'charitable' ),
			'charitable-pending'   => __( 'Pending', 'charitable' ),
			'charitable-failed'    => __( 'Failed', 'charitable' ),
			'charitable-cancelled' => __( 'Cancelled', 'charitable' ),
			'charitable-refunded'  => __( 'Refunded', 'charitable' ),
		)
	);
}

/**
 * Cancel a donation.
 *
 * @since  1.4.0
 *
 * @global WP_Query $wp_query
 * @return boolean True if the donation was cancelled. False otherwise.
 */
function charitable_cancel_donation() {
	global $wp_query;

	if ( ! charitable_is_page( 'donation_cancel_page' ) ) {
		return false;
	}

	if ( ! isset( $wp_query->query_vars['donation_id'] ) ) {
		return false;
	}

	$donation = charitable_get_donation( $wp_query->query_vars['donation_id'] );

	if ( ! $donation ) {
		return false;
	}

	/* Donations can only be cancelled if they are currently pending. */
	if ( 'charitable-pending' != $donation->get_status() ) {
		return false;
	}

	if ( ! $donation->is_from_current_user() ) {
		return false;
	}

	$donation->update_status( 'charitable-cancelled' );

	return true;
}

/**
 * Load the donation form script.
 *
 * @since  1.4.0
 *
 * @return void
 */
function charitable_load_donation_form_script() {
	wp_enqueue_script( 'charitable-donation-form' );
}

/**
 * Add a message to a donation's log.
 *
 * @since  1.0.0
 *
 * @param  int    $donation_id The donation ID.
 * @param  string $message     The message to add to the log.
 * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
 */
function charitable_update_donation_log( $donation_id, $message ) {
	return charitable_get_donation( $donation_id )->log()->add( $message );
}

/**
 * Get a donation's log.
 *
 * @since  1.0.0
 *
 * @param  int $donation_id The donation ID.
 * @return array
 */
function charitable_get_donation_log( $donation_id ) {
	return charitable_get_donation( $donation_id )->log()->get_meta_log();
}

/**
 * Get the gateway used for the donation.
 *
 * @since  1.0.0
 *
 * @param  int $donation_id The donation ID.
 * @return string
 */
function charitable_get_donation_gateway( $donation_id ) {
	return get_post_meta( $donation_id, 'donation_gateway', true );
}

/**
 * Sanitize meta values before they are persisted to the database.
 *
 * @since  1.0.0
 *
 * @param  mixed  $value The value to sanitize.
 * @param  string $key   The key of the meta field to be sanitized.
 * @return mixed
 */
function charitable_sanitize_donation_meta( $value, $key ) {
	if ( 'donation_gateway' == $key ) {
		if ( empty( $value ) || ! $value ) {
			$value = 'manual';
		}
	}

	/**
	 * Deprecated hook for filtering donation meta.
	 *
	 * @since 1.0.0
	 * @since 1.5.0 Deprecated.
	 *
	 * @param mixed $value The value of the donation meta field.
	 */
	$value = apply_filters( 'charitable_sanitize_donation_meta-' . $key, $value );

	/**
	 * Filter donation meta.
	 *
	 * This hook takes the form of charitable_sanitize_donation_meta_{key}.
	 * For example, to filter the `donation_gateway` meta, the hook would be:
	 *
	 * charitable_sanitize_donation_meta_doantion_gateway
	 *
	 * @since 1.5.0
	 *
	 * @param mixed $value The value of the donation meta field.
	 */
	return apply_filters( 'charitable_sanitize_donation_meta_' . $key, $value );
}

/**
 * Flush the donations cache for every campaign receiving a donation.
 *
 * @since  1.0.0
 *
 * @param  int $donation_id The donation ID.
 * @return void
 */
function charitable_flush_campaigns_donation_cache( $donation_id ) {
	$campaign_donations = charitable_get_table( 'campaign_donations' )->get_donation_records( $donation_id );

	foreach ( $campaign_donations as $campaign_donation ) {
		Charitable_Campaign::flush_donations_cache( $campaign_donation->campaign_id );
	}

	wp_cache_delete( $donation_id, 'charitable_donation' );

	/**
	 * Do something when the donation cache is flushed.
	 *
	 * @since 1.4.18
	 *
	 * @param int $donation_id The donation ID.
	 */
	do_action( 'charitable_flush_donation_cache', $donation_id );
}

/**
 * Return the minimum donation amount.
 *
 * @since  1.5.0
 *
 * @return float
 */
function charitable_get_minimum_donation_amount() {
	/**
	 * Filter the minimum donation amount.
	 *
	 * @since 1.5.0
	 *
	 * @param float $minimum The minimum amount.
	 */
	return apply_filters( 'charitable_minimum_donation_amount', 0 );
}

/**
 * Check whether the current user has access to a particular donation.
 *
 * @since  1.5.14
 *
 * @param  int $donation_id The donation ID.
 * @return boolean
 */
function charitable_user_can_access_donation( $donation_id ) {
	$donation = charitable_get_donation( $donation_id );

	return $donation && $donation->is_from_current_user();
}
