<?php
/**
 * Class that models the donation receipt email.
 *
 * @version     1.0.0
 * @package     Charitable/Classes/Charitable_Email_Donation_Receipt
 * @author      Eric Daams
 * @copyright   Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Email_Donation_Receipt' ) ) :

	/**
	 * Donation Receipt Email
	 *
	 * @since   1.0.0
	 */
	class Charitable_Email_Donation_Receipt extends Charitable_Email {

		/** Email ID */
		const ID = 'donation_receipt';

		/**
		 * Array of supported object types (campaigns, donations, donors, etc).
		 *
		 * @since 1.0.0
		 *
		 * @var   string[]
		 */
		protected $object_types = array( 'donation' );

		/**
		 * Instantiate the email class, defining its key values.
		 *
		 * @since 1.0.0
		 *
		 * @param array $objects an array containing a donation object.
		 */
		public function __construct( $objects = array() ) {
			parent::__construct( $objects );

			$this->name = apply_filters( 'charitable_email_donation_receipt_name', __( 'Donor: Donation Receipt', 'charitable' ) );
		}

		/**
		 * Returns the current email's ID.
		 *
		 * @since   1.0.3
		 *
		 * @return  string
		 */
		public static function get_email_id() {
			return self::ID;
		}

		/**
		 * Static method that is fired right after a donation is completed, sending the donation receipt.
		 *
		 * @since   1.0.0
		 *
		 * @param   int $donation_id The donation ID.
		 * @return  boolean
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

			if ( ! $donation->has_valid_email() ) {
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
		 * Resend an email.
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

			$email = new Charitable_Email_Donation_Receipt( array(
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
			$donation   = charitable_get_donation( $object_id );
			$resendable = is_object( $donation )
				&& $donation->has_valid_email()
				&& charitable_is_approved_status( $donation->get_status() );

			/**
			 * Filter whether the email can be resent.
			 *
			 * @since 1.6.0
			 *
			 * @param boolean $resendable Whether the email can be resent.
			 * @param int     $object_id  The donation ID.
			 * @param array   $args       Mixed set of arguments.
			 */
			return apply_filters( 'charitable_can_resend_donation_receipt_email', $resendable, $object_id, $args );
		}

		/**
		 * Return the recipient for the email.
		 *
		 * @since   1.0.0
		 *
		 * @return  string
		 */
		public function get_recipient() {
			if ( ! is_a( $this->donation, 'Charitable_Donation' ) ) {
				return '';
			}

			/**
			 * Deprecated hook. Use charitable_email_donation_receipt_recipient instead.
			 *
			 * @deprecated 1.9.0
			 *
			 * @since 1.0.0
			 * @since 1.6.0 Deprecated
			 *
			 * @param string                            $email_address Recipient email address.
			 * @param Charitable_Email_Donation_Receipt $email         Instance of `Charitable_Email_Donation_Receipt`.
			 */
			$email_address = apply_filters( 'charitable_email_donation_receipt_receipient', $this->donation->get_donor()->get_email(), $this );

			/**
			 * Filter the recipient for the donation receipt email.
			 *
			 * @since 1.6.0
			 *
			 * @param string                            $email_address Recipient email address.
			 * @param Charitable_Email_Donation_Receipt $email         Instance of `Charitable_Email_Donation_Receipt`.
			 */
			return apply_filters( 'charitable_email_donation_receipt_recipient', $email_address, $this );
		}

		/**
		 * Return the default subject line for the email.
		 *
		 * @since   1.0.0
		 *
		 * @return  string
		 */
		protected function get_default_subject() {
			return apply_filters( 'charitable_email_donation_receipt_default_subject', __( 'Thank you for your donation', 'charitable' ), $this );
		}

		/**
		 * Return the default headline for the email.
		 *
		 * @since   1.0.0
		 *
		 * @return  string
		 */
		protected function get_default_headline() {
			return apply_filters( 'charitable_email_donation_receipt_default_headline', __( 'Your Donation Receipt', 'charitable' ), $this );
		}

		/**
		 * Return the default body for the email.
		 *
		 * @since   1.0.0
		 *
		 * @return  string
		 */
		protected function get_default_body() {
			ob_start();
?>
<p><?php _e( 'Dear [charitable_email show=donor_first_name],', 'charitable' ); ?></p>
<p><?php _e( 'Thank you so much for your generous donation.', 'charitable' ); ?></p>
<p><strong><?php _e( 'Your Receipt', 'charitable' ); ?></strong><br />
[charitable_email show=donation_summary]</p>
<p><?php _e( 'With thanks, [charitable_email show=site_name]', 'charitable' ); ?></p>
<?php
			$body = ob_get_clean();

			return apply_filters( 'charitable_email_donation_receipt_default_body', $body, $this );
		}
	}

endif;
