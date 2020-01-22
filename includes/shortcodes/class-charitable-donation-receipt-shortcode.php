<?php
/**
 * Donation Receipt shortcode class.
 *
 * @package   Charitable/Shortcodes/Donation Receipt
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.2.0
 * @version   1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donation_Receipt_Shortcode' ) ) :

	/**
	 * Charitable_Donation_Receipt_Shortcode class.
	 *
	 * @since   1.2.0
	 */
	class Charitable_Donation_Receipt_Shortcode {

		/**
		 * The callback method for the campaigns shortcode.
		 *
		 * This receives the user-defined attributes and passes the logic off to the class.
		 *
		 * @since   1.2.0
		 *
		 * @param   array   $atts   User-defined shortcode attributes.
		 * @return  string
		 */
		public static function display( $atts ) {
			return apply_filters( 'charitable_donation_receipt_shortcode', charitable_template_donation_receipt_output( '' ) );
		}
	}

endif;
