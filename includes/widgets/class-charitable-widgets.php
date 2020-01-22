<?php
/**
 * Charitable widgets class.
 *
 * Registers custom widgets for Charitable.
 *
 * @version		1.0.0
 * @package		Charitable/Classes/Charitable_Widgets
 * @author 		Eric Daams
 * @copyright 	Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Widgets' ) ) :

	/**
	 * Charitable_Widgets
	 *
	 * @final
	 * @since   1.0.0
	 */
	final class Charitable_Widgets {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Widgets|null
		 */
		private static $instance = null;

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.2.0
		 *
		 * @return  Charitable_Widgets
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Set up the class. This can only be loaded with the get_instance() method.
		 *
		 * @since   1.0.0
		 */
		private function __construct() {
			add_action( 'widgets_init', array( $this, 'register_widgets' ) );
		}

		/**
		 * Register widgets.
		 *
		 * @see 	widgets_init hook
		 *
		 * @since   1.0.0
		 *
		 * @return 	void
		 */
		public function register_widgets() {
			register_widget( 'Charitable_Campaign_Terms_Widget' );
			register_widget( 'Charitable_Campaigns_Widget' );
			register_widget( 'Charitable_Donors_Widget' );
			register_widget( 'Charitable_Donate_Widget' );
			register_widget( 'Charitable_Donation_Stats_Widget' );
		}
	}

endif;
