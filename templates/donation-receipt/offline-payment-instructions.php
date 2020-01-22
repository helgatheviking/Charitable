<?php
/**
 * Displays the offline payment instructions
 *
 * Override this template by copying it to yourtheme/charitable/donation-receipt/offline-payment-instructions.php
 *
 * @author  Studio 164a
 * @package Charitable/Templates/Donation Receipt
 * @since   1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* @var Charitable_Donation */
$donation = $view_args['donation'];

echo wpautop( $donation->get_gateway_object()->get_value( 'instructions' ) );
