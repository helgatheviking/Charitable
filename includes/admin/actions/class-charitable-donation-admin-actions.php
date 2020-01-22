<?php
/**
 * Registers and performs donation actions.
 *
 * @package   Charitable/Classes/Charitable_Donation_Admin_Actions
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donation_Admin_Actions' ) ) :

	/**
	 * Charitable_Donation_Admin_Actions
	 *
	 * @since 1.5.0
	 */
	class Charitable_Donation_Admin_Actions extends Charitable_Admin_Actions {

		/** Email ID */
		const TYPE = 'donation';

		/**
		 * Return the action type.
		 *
		 * This will be used to construct the hook that runs the actual actions, and is
		 * required to differentiate between actions with the same key added by different
		 * implementation classes.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		public function get_type() {
			return self::TYPE;
		}
	}

endif;
