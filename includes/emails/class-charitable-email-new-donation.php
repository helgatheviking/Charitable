<?php
/**
 * Class that models the new donation email.
 *
 * @version     1.0.0
 * @package     Charitable/Classes/Charitable_Email_New_Donation
 * @author      Eric Daams
 * @copyright   Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Email_New_Donation' ) ) :

	/**
	 * New Donation Email
	 *
	 * @since   1.0.0
	 */
	class Charitable_Email_New_Donation extends Charitable_Email {

		/** Email ID */
		const ID = 'new_donation';

		/**
		 * Whether the email allows you to define the email recipients.
		 *
		 * @since 1.1.0
		 *
		 * @var   boolean
		 */
		protected $has_recipient_field = true;

		/**
		 * Object types supported by this email.
		 *
		 * @since 1.0.0
		 *
		 * @var   string[] Array of supported object types (campaigns, donations, donors, etc).
		 */
		protected $object_types = array( 'donation' );

		/**
		 * Instantiate the email class, defining its key values.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed[] $objects Array containing a Charitable_Donation object.
		 */
		public function __construct( $objects = array() ) {
			parent::__construct( $objects );

			/**
			 * Customize the name of the donation notification.
			 *
			 * @since 1.0.0
			 *
			 * @param string $name
			 */
			$this->name = apply_filters( 'charitable_email_new_donation_name', __( 'Admin: New Donation Notification', 'charitable' ) );
		}

		/**
		 * Returns the current email's ID.
		 *
		 * @since  1.0.3
		 *
		 * @return string
		 */
		public static function get_email_id() {
			return self::ID;
		}

		/**
		 * Static method that triggers the sending of this email. Sends the receipt with the donation is marked complete.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $donation_id The ID of the donation that we're sending an email about.
		 * @return boolean
		 */
		public static function send_with_donation_id( $donation_id ) {
			if ( ! charitable_get_helper( 'emails' )->is_enabled_email( self::get_email_id() ) ) {
				return false;
			}

			if ( ! charitable_is_approved_status( get_post_status( $donation_id ) ) ) {
				return false;
			}

			$donation = charitable_get_donation( $donation_id );

			if ( ! is_object( $donation ) || 0 == count( $donation->get_campaign_donations() ) ) {
				return false;
			}

			if ( ! apply_filters( 'charitable_send_' . self::get_email_id(), true, $donation ) ) {
				return false;
			}

			$email = new self(
				array(
					'donation' => $donation,
				)
			);

			/**
			 * Don't resend the email.
			 */
			if ( $email->is_sent_already( $donation_id ) ) {
				return false;
			}

			$sent = $email->send();

			/**
			 * Log that the email was sent.
			 */
			if ( apply_filters( 'charitable_log_email_send', true, self::get_email_id(), $email ) ) {
				$email->log( $donation_id, $sent );
			}

			return $sent;
		}

		/**
		 * Resend the email.
		 *
		 * @since  1.5.0
		 *
		 * @param  int   $object_id An object ID.
		 * @param  array $args      Mixed set of arguments.
		 * @return boolean
		 */
		public static function resend( $object_id, $args = array() ) {
			$donation = charitable_get_donation( $object_id );

			if ( ! is_object( $donation ) || 0 == count( $donation->get_campaign_donations() ) ) {
				return false;
			}

			$email = new Charitable_Email_New_Donation( array(
				'donation' => $donation,
			) );

			$success = $email->send();

			/**
			 * Log that the email was sent.
			 */
			if ( apply_filters( 'charitable_log_email_send', true, self::get_email_id(), $email ) ) {
				$email->log( $object_id, $success );
			}

			return $success;
		}

		/**
		 * Checks whether an email can be resent.
		 *
		 * @since  1.5.0
		 *
		 * @param  int   $object_id An object ID.
		 * @param  array $args      Mixed set of arguments.
		 * @return boolean
		 */
		public static function can_be_resent( $object_id, $args = array() ) {
			$resendable = charitable_is_approved_status( get_post_status( $object_id ) );

			/**
			 * Filter whether the email can be resent.
			 *
			 * @since 1.6.0
			 *
			 * @param boolean $resendable Whether the email can be resent.
			 * @param int     $object_id  The donation ID.
			 * @param array   $args       Mixed set of arguments.
			 */
			return apply_filters( 'charitable_can_resend_new_donation_email', $resendable, $object_id, $args );
		}

		/**
		 * Return the default recipient for the email.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		protected function get_default_recipient() {
			return get_option( 'admin_email' );
		}

		/**
		 * Return the default subject line for the email.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		protected function get_default_subject() {
			return __( 'You have received a new donation', 'charitable' );
		}

		/**
		 * Return the default headline for the email.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		protected function get_default_headline() {
			/**
			 * Filter the default email headline.
			 *
			 * @since 1.0.0
			 *
			 * @param string                        $headline Default headline.
			 * @param Charitable_Email_New_Donation $email    The email object.
			 */
			return apply_filters( 'charitable_email_donation_receipt_default_headline', __( 'New Donation', 'charitable' ), $this );
		}

		/**
		 * Return the default body for the email.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		protected function get_default_body() {
			ob_start();
?>
<p><?php _e( '[charitable_email show=donor] has just made a donation!', 'charitable' ) ?></p>
<p><strong>Summary</strong><br />
[charitable_email show=donation_summary]</p>
<?php
			/**
			 * Filter the default body content.
			 *
			 * @since 1.0.0
			 *
			 * @param string                        $body  Default email body content.
			 * @param Charitable_Email_New_Donation $email The email object.
			 */
			return apply_filters( 'charitable_email_new_donation_default_body', ob_get_clean(), $this );
		}
	}

endif;
