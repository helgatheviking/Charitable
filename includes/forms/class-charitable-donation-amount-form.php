<?php
/**
 * Donation amount form model class.
 *
 * @package   Charitable/Classes/Charitable_Donation_Amount_Form
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donation_Amount_Form' ) ) :

	/**
	 * Charitable_Donation_Amount_Form
	 *
	 * @since  1.0.0
	 */
	class Charitable_Donation_Amount_Form extends Charitable_Donation_Form implements Charitable_Donation_Form_Interface {

		/**
		 * @var     Charitable_Campaign
		 */
		protected $campaign;

		/**
		 * @var     array
		 */
		protected $form_fields;

		/**
		 * @var     string
		 */
		protected $nonce_action = 'charitable_donation_amount';

		/**
		 * @var     string
		 */
		protected $nonce_name = '_charitable_donation_amount_nonce';

		/**
		 * Action to be executed upon form submission.
		 *
		 * @var     string
		 */
		protected $form_action = 'make_donation_streamlined';

		/**
		 * Return the donation form fields.
		 *
		 * @since  1.0.0
		 *
		 * @return array[]
		 */
		public function get_fields() {
			return $this->get_donation_fields();
		}

		/**
		 * Validate the form submission.
		 *
		 * @since  1.4.4
		 *
		 * @return boolean
		 */
		public function validate_submission() {
			/* If we have already validated the submission, return the value. */
			if ( $this->validated ) {
				return $this->valid;
			}

			$this->validated = true;

			$this->valid = $this->validate_security_check()
				&& $this->check_required_fields( $this->get_merged_fields() )
				&& $this->validate_amount();

			$this->valid = apply_filters( 'charitable_validate_donation_amount_form_submission', $this->valid, $this );

			return $this->valid;
		}

		/**
		 * Return the donation values.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_donation_values() {
			$submitted = $this->get_submitted_values();

			$values = array(
				'campaign_id' => $submitted['campaign_id'],
				'amount'      => self::get_donation_amount( $submitted ),
			);

			return apply_filters( 'charitable_donation_amount_form_submission_values', $values, $submitted, $this );
		}

		/**
		 * Redirect to payment form after submission.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $campaign_id The campaign we are donating to.
		 * @param  int $amount      The donation amount.
		 * @return void
		 */
		public function redirect_after_submission( $campaign_id, $amount ) {
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				return;
			}

			$redirect_url = charitable_get_permalink( 'campaign_donation_page', array( 'campaign_id' => $campaign_id ) );

			if ( 'same_page' == charitable_get_option( 'donation_form_display', 'separate_page' ) ) {
				$redirect_url .= '#charitable-donation-form';
			}

			$redirect_url = apply_filters( 'charitable_donation_amount_form_redirect', $redirect_url, $campaign_id, $amount );

			wp_redirect( esc_url_raw( $redirect_url ) );

			die();
		}

		/**
		 * Render the donation form.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function render() {
			/* Load the script if it hasn't been loaded yet. */
			if ( ! wp_script_is( 'charitable-script', 'enqueued' ) ) {

				if ( ! class_exists( 'Charitable_Public' ) ) {
					require_once( charitable()->get_path( 'public' ) . 'class-charitable-public.php' );
				}

				Charitable_Public::get_instance()->enqueue_donation_form_scripts();
			}

			charitable_template(
				'donation-form/form-donation.php',
				array(
					'campaign' => $this->get_campaign(),
					'form'     => $this,
					'form_id'  => 'charitable-donation-amount-form',
				)
			);
		}
	}

endif;
