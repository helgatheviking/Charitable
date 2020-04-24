<?php
/**
 * Charitable User Functions.
 *
 * User related functions.
 *
 * @package     Charitable/Functions/User
 * @author      Eric Daams
 * @copyright   Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 * @version     1.6.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns a Charitable_User object for the given user.
 *
 * This will first attempt to retrieve it from the object cache to prevent duplicate objects.
 *
 * @since  1.0.0
 *
 * @param  int     $user_id The ID of the user to retrieve.
 * @param  boolean $force Optional. Whether to force an update of the local cache from the persistent.
 * @return Charitable_User
 */
function charitable_get_user( $user_id, $force = false ) {
	if ( is_a( $user_id, 'WP_User' ) ) {
		$user_id = $user_id->ID;
	}

	$user = wp_cache_get( $user_id, 'charitable_user', $force );

	if ( ! $user ) {
		$user = new Charitable_User( $user_id );
		wp_cache_set( $user_id, $user, 'charitable_user' );
	}

	return $user;
}

/**
 * Returns a mapping of user keys.
 *
 * This is needed because the key used in forms is not always the
 * same as they key used for storing the database value.
 *
 * @since  1.4.0
 *
 * @return string[]
 */
function charitable_get_user_mapped_keys() {
	/**
	 * Filter the mapping of shorthand keys to meta keys.
	 *
	 * The meta keys are used in the wp_usermeta table.
	 *
	 * @since 1.0.0
	 *
	 * @param array $keys User meta keys.
	 */
	return apply_filters(
		'charitable_donor_mapped_keys',
		array(
			'email'            => 'user_email',
			'company'          => 'donor_company',
			'address'          => 'donor_address',
			'address_2'        => 'donor_address_2',
			'city'             => 'donor_city',
			'state'            => 'donor_state',
			'postcode'         => 'donor_postcode',
			'zip'              => 'donor_postcode',
			'country'          => 'donor_country',
			'phone'            => 'donor_phone',
			'user_description' => 'description',
		)
	);
}

/**
 * Returns a list of the core user keys.
 *
 * Core user keys are any keys that can be passed to wp_update_user or wp_insert_user.
 *
 * @see wp_update_user
 * @see wp_insert_user
 *
 * @since  1.4.0
 *
 * @return string[]
 */
function charitable_get_user_core_keys() {
	return array(
		'ID',
		'user_pass',
		'user_login',
		'user_nicename',
		'user_url',
		'user_email',
		'display_name',
		'nickname',
		'first_name',
		'last_name',
		'rich_editing',
		'date_registered',
		'role',
		'jabber',
		'aim',
		'yim',
	);
}

/**
 * Returns a list of the donor keys.
 *
 * These keys correspond to the columns in the wp_charitable_donors table.
 *
 * @since  1.6.2
 *
 * @return string[]
 */
function charitable_get_donor_keys() {
	return array(
		'donor_id',
		'user_id',
		'email',
		'first_name',
		'last_name',
		'date_joined',
		'date_erased',
		'contact_consent',
	);
}

/**
 * Return the email address when data is erased.
 *
 * @since  1.6.0
 *
 * @param  string $email The email address to check.
 * @return boolean True if the email is valid; false if it is marked as invalid.
 */
function charitable_is_valid_email_address( $email ) {
	$invalid = array();

	if ( function_exists( 'wp_privacy_anonymize_data' ) ) {
		$invalid[] = wp_privacy_anonymize_data( 'email' );
	}

	/**
	 * Filter the list of invalid email addresses used by Charitable.
	 *
	 * @since 1.6.0
	 *
	 * @param string[] $addresses A list of strings that are invalid email addresses.
	 */
	$invalid = apply_filters( 'charitable_invalid_email_addresses', $invalid );

	return strlen( $email ) && ! in_array( $email, $invalid );
}

/**
 * Return whether donors can be added without an email address.
 *
 * @since  1.6.0
 *
 * @return boolean True if donors can be added without an email address. False otherwise.
 */
function charitable_permit_donor_without_email() {
	/**
	 * Filter whether donors can be added without an email address.
	 *
	 * Prior to Charitable 1.6, this was never permitted. As of Charitable 1.6, it's possible
	 * to support manual donations without an email address by using this filter.
	 *
	 * NOTE: By default, the public donation form still requires an email address, so this
	 * primarily affects programattically created donors, or donors created via manual
	 * donations in the admin.
	 *
	 * @see https://github.com/Charitable/Charitable/issues/535
	 *
	 * @since 1.6.0
	 *
	 * @param boolean $permitted Whether donors can be added without an email address.
	 */
	return apply_filters( 'charitable_permit_donor_without_email', false );
}

/**
 * Get a donor ID based on an email address.
 *
 * @since  1.6.2
 *
 * @param  string $email The email address.
 * @return int
 */
function charitable_get_donor_id_by_email( $email ) {
	return charitable_get_table( 'donors' )->get_donor_id_by_email( $email );
}
