<?php
/**
 * Sets up Charitable templates for specific views.
 *
 * @version   1.0.0
 * @package   Charitable/Classes/Charitable_Templates
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'Charitable_Templates' ) ) :

	/**
	 * Charitable_Templates
	 *
	 * @since   1.0.0
	 */
	class Charitable_Templates {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Templates|null
		 */
		private static $instance = null;

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.2.0
		 *
		 * @return  Charitable_Templates
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Set up the class.
		 *
		 * Note that the only way to instantiate an object is with the charitable_start method,
		 * which can only be called during the start phase. In other words, don't try
		 * to instantiate this object.
		 *
		 * @since   1.0.0
		 */
		private function __construct() {
			/* If you want to unhook any of the callbacks attached above, use this hook. */
			do_action( 'charitable_templates_start', $this );
		}

		/***********************************************/
		/* HERE BE DEPRECATED METHODS
		/***********************************************/

		/**
		 * @deprecated 1.5.0
		 */
		public function template_loader( $template ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.5.0',
				'Charitable_Endpoints::template_loader()'
			);

			return charitable()->endpoints()->template_loader( $template );
		}

		/**
		 * @deprecated 1.5.0
		 */
		protected function get_donation_receipt_template( $template ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.5.0',
				'Charitable_Donation_Receipt_Endpoint::get_template()'
			);

			return charitable()->endpoints()->get_endpoint_template( 'donation_receipt', $template );
		}

		/**
		 * @deprecated 1.5.0
		 */
		protected function get_donation_processing_template( $template ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.5.0',
				'Charitable_Donation_Processing_Endpoint::get_template()'
			);

			return charitable()->endpoints()->get_endpoint_template( 'donation_processing', $template );
		}

		/**
		 * @deprecated 1.5.0
		 */
		protected function get_donate_template( $template ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.5.0',
				'Charitable_Campaign_Donation_Endpoint::get_template()'
			);

			return charitable()->endpoints()->get_endpoint_template( 'campaign_donation', $template );
		}

		/**
		 * @deprecated 1.5.0
		 */
		protected function get_widget_template( $template ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.5.0',
				'Charitable_Campaign_Widget_Endpoint::get_template()'
			);

			return charitable()->endpoints()->get_endpoint_template( 'campaign_widget', $template );
		}

		/**
		 * @deprecated 1.5.0
		 */
		protected function get_email_template( $template ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.5.0',
				'Charitable_Email_Preview_Endpoint::get_template()'
			);

			return charitable()->endpoints()->get_endpoint_template( 'email_preview', $template );
		}

		/**
		 * @deprecated 1.5.0
		 */
		protected function get_forgot_password_template( $template ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.5.0',
				'Charitable_Forgot_Password_Endpoint::get_template()'
			);

			return charitable()->endpoints()->get_endpoint_template( 'forgot_password', $template );
		}

		/**
		 * @deprecated 1.5.0
		 */
		protected function get_reset_password_template( $template ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.5.0',
				'Charitable_Reset_Password_Endpoint::get_template()'
			);

		return charitable()->endpoints()->get_endpoint_template( 'reset_password', $template );
		}

		/**
		 * @deprecated 1.3.0
		 */
		public function add_donation_page_body_class( $classes ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.3.0',
				'charitable_add_body_classes()'
			);

			return $classes;
		}

		/**
		 * @deprecated 1.3.0
		 */
		public function add_widget_page_body_class( $classes ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.3.0',
				'charitable_add_body_classes()'
			);

			return $classes;
		}

		/**
		 * @deprecated 1.3.0
		 */
		public function remove_admin_bar_from_widget_template() {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.3.0',
				'charitable_hide_admin_bar()'
			);

			return charitable_hide_admin_bar();
		}

		/**
		 * @deprecated 1.3.0
		 */
		public function donation_receipt_template( $template ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.3.0',
				'Charitable_Templates::template_loader() or Charitable_Templates::get_donation_receipt_template()'
			);

			return $this->get_donation_receipt_template( $template );
		}

		/**
		 * @deprecated 1.3.0
		 */
		public function donation_processing_template( $template ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.3.0',
				'Charitable_Templates::template_loader() or Charitable_Templates::get_donation_processing_template()'
			);

			return $this->get_donation_processing_template( $template );
		}

		/**
		 * @deprecated 1.3.0
		 */
		public function donate_template( $template ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.3.0',
				'Charitable_Templates::template_loader() or Charitable_Templates::get_donate_template()'
			);

			return $this->get_donate_template( $template );
		}

		/**
		 * @deprecated 1.3.0
		 */
		public function widget_template( $template ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.3.0',
				'Charitable_Templates::template_loader() or Charitable_Templates::get_widget_template()'
			);

			return $this->get_widget_template( $template );
		}

		/**
		 * @deprecated 1.3.0
		 */
		public function email_template( $template ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.3.0',
				'Charitable_Templates::template_loader() or Charitable_Templates::get_email_template()'
			);

			return $this->get_email_template( $template );
		}
	}

endif; // End class_exists check
