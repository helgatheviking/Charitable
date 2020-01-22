<?php
/**
 * Sets up the Donations dashboard widget.
 *
 * @package     Charitable/Classes/Charitable_Donations_Dashboard_Widget
 * @version     1.2.0
 * @author      Eric Daams
 * @copyright   Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donations_Dashboard_Widget' ) ) :

	/**
	 * Charitable_Donations_Dashboard_Widget
	 *
	 * @since   1.2.0
	 */
	class Charitable_Donations_Dashboard_Widget {

		/**
		 * The widget ID.
		 */
		const ID = 'charitable_dashboard_donations';

		/**
		 * Create class object.
		 *
		 * @since   1.2.0
		 */
		public static function register() {
			if ( ! current_user_can( 'view_charitable_sensitive_data' ) ) {
				return;
			}

			wp_add_dashboard_widget( self::ID, __( 'Charitable Donation Statistics', 'charitable' ), array( 'Charitable_Donations_Dashboard_Widget', 'display' ) );
		}

		/**
		 * Print the widget contents.
		 *
		 * @since   1.2.0
		 *
		 * @return  void
		 */
		public static function display() {
	?>
			<p class="hide-if-no-js">
				<img src="<?php echo charitable()->get_path( 'assets', false ) ?>/images/charitable-loading.gif" width="60" height="60" alt="<?php esc_attr_e( 'Loading&hellip;', 'charitable' ) ?>" />
			</p>
	<?php
		}

		/**
		 * Return the content to display inside the widget.
		 *
		 * @since   1.2.0
		 *
		 * @return  void
		 */
		public static function get_content() {
			charitable_admin_view( 'dashboard-widgets/donations-widget' );
			die();
		}
	}

endif;
