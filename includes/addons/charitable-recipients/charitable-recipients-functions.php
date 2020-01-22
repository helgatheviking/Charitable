<?php

/**
 * Charitable Recipients Functions.
 *
 * @package   Charitable/Functions/Recipients
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers a recipient type.
 *
 * @since  1.0.0
 *
 * @param  string $recipient_type The ID of the recipient type we're registering.
 * @param  array  $args           Set of arguments defining that recipient type.
 * @return void
 */
function charitable_register_recipient_type( $recipient_type, $args = array() ) {
	Charitable_Recipient_Types::get_instance()->register( $recipient_type, $args );
}

/**
 * Returns the registered recipient types.
 *
 * @since  1.0.0
 *
 * @return array
 */
function charitable_get_recipient_types() {
	return Charitable_Recipient_Types::get_instance()->get_types();
}

/**
 * Returns a given recipient type, or false if the recipient type is not registered.
 *
 * @since  1.0.0
 *
 * @param  string $recipient_type The recipient type we want to retrieve.
 * @return array|false
 */
function charitable_get_recipient_type( $recipient_type ) {
	$recipient_types = charitable_get_recipient_types();

	if ( ! array_key_exists( $recipient_type, $recipient_types ) ) {
		return false;
	}

	return $recipient_types[ $recipient_type ];
}
