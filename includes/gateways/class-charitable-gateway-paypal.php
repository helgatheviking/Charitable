<?php
/**
 * Paypal Payment Gateway class.
 *
 * @package   Charitable/Classes/Charitable_Gateway_Paypal
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Gateway_Paypal' ) ) :

	/**
	 * Paypal Payment Gateway
	 *
	 * @since  1.0.0
	 */
	class Charitable_Gateway_Paypal extends Charitable_Gateway {

		/**
		 * Gateway ID.
		 */
		const ID = 'paypal';

		/**
		 * Instantiate the gateway class, defining its key values.
		 *
		 * @since  1.0.0
		 */
		public function __construct() {
			$this->name = apply_filters( 'charitable_gateway_paypal_name', __( 'PayPal', 'charitable' ) );

			$this->defaults = array(
				'label' => __( 'PayPal', 'charitable' ),
			);

			$this->supports = array(
				'recurring',
				'recurring_cancellation',
				'refunds',
				'1.3.0',
			);
		}

		/**
		 * Register gateway settings.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $settings List of settings. Empty by default.
		 * @return array
		 */
		public function gateway_settings( $settings ) {
			return array_merge( $settings, array(
				'paypal_email'             => array(
					'type'     => 'email',
					'title'    => __( 'PayPal Email Address', 'charitable' ),
					'priority' => 6,
					'help'     => __( 'Enter the email address for the PayPal account that should receive donations.', 'charitable' ),
				),
				'sandbox_paypal_email'     => array(
					'type'     => 'email',
					'title'    => __( 'Sandbox PayPal Email Address', 'charitable' ),
					'priority' => 7,
					'help'     => __( 'Enter the email address for the Sandbox PayPal account that should receive test donations.', 'charitable' ),
				),
				'transaction_mode'         => array(
					'type'     => 'radio',
					'title'    => __( 'PayPal Transaction Type', 'charitable' ),
					'priority' => 8,
					'options'  => array(
						'donations' => __( 'Donations', 'charitable' ),
						'standard'  => __( 'Standard Transaction', 'charitable' ),
					),
					'default'  => 'donations',
					'help'     => sprintf( '%s<br /><a href="%s" target="_blank">%s</a>',
						__( 'PayPal offers discounted fees to registered non-profit organizations. You must create a PayPal Business account to apply.', 'charitable' ),
						'https://www.paypal.com/us/webapps/mpp/donations',
						__( 'Find out more.', 'charitable' )
					),
				),
				'disable_ipn_verification' => array(
					'type'     => 'checkbox',
					'title'    => __( 'Disable IPN Verification', 'charitable' ),
					'priority' => 10,
					'default'  => 0,
					'help'     => __( 'If you are having problems with donations not getting marked as Paid, disabling IPN verification might fix the problem. However, it is important to be aware that this is a <strong>less secure</strong> method for verifying donations.', 'charitable' ),
				),
				'api'                      => array(
					'type'     => 'heading',
					'title'    => __( 'API Settings', 'charitable' ),
					'priority' => 20,
				),
				'api_description'          => array(
					'type'     => 'content',
					'content'  => '<div class="charitable-settings-notice">'
						. '<p>' . __( 'API credentials are necessary to process PayPal refunds and subscription cancellations from inside WordPress. To find your credentials:', 'charitable' ) . '</p>'
						. '<ul>'
						. '<li>' . sprintf(
							/* translators: %s: link */
							__( '<strong>Live</strong>: Go to %s.', 'charitable' ),
							'<a href="https://www.paypal.com/businessprofile/mytools/apiaccess/firstparty/signature" target="_blank">https://www.paypal.com/businessprofile/mytools/apiaccess/firstparty/signature</a>'
						) . '</li>'
						. '<li>' . sprintf(
							/* translators: %s: link */
							__( '<strong>Sandbox</strong>: Go to %s, click on <em>Profile</em> for the Seller account and open the <em>API keys</em> tab.', 'charitable' ),
							'<a href="https://developer.paypal.com/developer/accounts/" target="_blank">https://developer.paypal.com/developer/accounts/</a>'
						) . '</li>'
						. '</ul>'
						. '</div>',
					'priority' => 21,
				),
				'api_username'             => array(
					'title'    => __( 'Live API Username', 'charitable' ),
					'type'     => 'text',
					'priority' => 30,
					'default'  => '',
				),
				'api_password'             => array(
					'title'    => __( 'Live API Password', 'charitable' ),
					'type'     => 'password',
					'priority' => 40,
					'default'  => '',
				),
				'api_signature'            => array(
					'title'    => __( 'Live API Signature', 'charitable' ),
					'type'     => 'password',
					'priority' => 50,
					'default'  => '',
				),
				'sandbox_api_username'     => array(
					'title'       => __( 'Sandbox API Username', 'charitable' ),
					'type'        => 'text',
					'description' => __( 'Create sandbox accounts and obtain API credentials from within your <a href="http://developer.paypal.com">PayPal developer account</a> or read more about it in the <a href="https://developer.paypal.com/docs/classic/api/apiCredentials/#creating-an-api-signature" target="_blank">documentation</a>.', 'charitable' ),
					'priority'    => 60,
					'default'     => '',
				),
				'sandbox_api_password'     => array(
					'title'    => __( 'Sandbox API Password', 'charitable' ),
					'type'     => 'password',
					'priority' => 70,
					'default'  => '',
				),
				'sandbox_api_signature'    => array(
					'title'    => __( 'Sandbox API Signature', 'charitable' ),
					'type'     => 'password',
					'priority' => 80,
					'default'  => '',
				),
			) );
		}

		/**
		 * Retrieve PayPal API credentials.
		 *
		 * @since  1.6.0
		 *
		 * @param  boolean|null $test_mode Whether to get the test mode credentials.
		 * @return array
		 */
		public static function get_api_credentials( $test_mode = null ) {
			if ( is_null( $test_mode ) ) {
				$test_mode = charitable_get_option( 'test_mode' );
			}

			$mode = $test_mode ? 'sandbox_' : '';

			/**
			 * Filter the PayPal API Credentials.
			 *
			 * @since 1.6.0
			 *
			 * @param array $creds The API credentials.
			 */
			return apply_filters(
				'charitable_paypal_api_credentials',
				array(
					'username'  => charitable_get_option( array( 'gateways_paypal', $mode . 'api_username' ) ),
					'password'  => charitable_get_option( array( 'gateways_paypal', $mode . 'api_password' ) ),
					'signature' => charitable_get_option( array( 'gateways_paypal', $mode . 'api_signature' ) ),
				)
			);
		}

		/**
		 * Check whether we have the required PayPal API credentials.
		 *
		 * @since  1.6.0
		 *
		 * @param  boolean|null $test_mode Whether to get the test mode credentials.
		 * @return boolean
		 */
		public static function has_api_credentials( $test_mode = null ) {
			$creds    = self::get_api_credentials( $test_mode );
			$required = array(
				'username',
				'password',
				'signature',
			);

			foreach ( $required as $key ) {
				if ( ! array_key_exists( $key, $creds ) || empty( $creds[ $key ] ) ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Return the API endpoint, depending on whether we need the sandbox or the live one.
		 *
		 * @since  1.6.0
		 *
		 * @param  boolean|null $test_mode Whether to get the test mode credentials.
		 * @return string
		 */
		public static function get_api_endpoint( $test_mode = null ) {
			if ( is_null( $test_mode ) ) {
				$test_mode = charitable_get_option( 'test_mode' );
			}

			return $test_mode ? 'https://api-3t.sandbox.paypal.com/nvp' : 'https://api-3t.paypal.com/nvp';
		}

		/**
		 * Validate the submitted credit card details.
		 *
		 * @since  1.0.0
		 *
		 * @param  boolean $valid   Boolean value to be returned indicating whether the donation is valid.
		 * @param  string  $gateway The donation gateway.
		 * @param  mixed[] $values  Set of donation values.
		 * @return boolean
		 */
		public static function validate_donation( $valid, $gateway, $values ) {
			if ( 'paypal' != $gateway ) {
				return $valid;
			}

			$email = self::get_paypal_email();

			/* Make sure that the email is set. */
			if ( ! isset( $email ) || empty( $email ) ) {

				charitable_get_notices()->add_error( __( 'Missing PayPal email address. Unable to proceed with payment.', 'charitable' ) );
				return false;

			}

			return $valid;
		}

		/**
		 * Process the donation with PayPal.
		 *
		 * @since  1.0.0
		 *
		 * @param  boolean|array                 $return      The value to be returned.
		 * @param  int                           $donation_id The donation ID.
		 * @param  Charitable_Donation_Processor $processor   The Donation Processor object.
		 * @return array
		 */
		public static function process_donation( $return, $donation_id, $processor ) {
			$gateway          = new Charitable_Gateway_Paypal();
			$user_data        = $processor->get_donation_data_value( 'user' );
			$donation         = charitable_get_donation( $donation_id );
			$transaction_mode = $gateway->get_value( 'transaction_mode' );
			$donation_key     = $processor->get_donation_data_value( 'donation_key' );

			/**
			 * Filter the arguments that are passed to PayPal for the payment.
			 *
			 * @since 1.0.0
			 *
			 * @param array                         $args        The arguments.
			 * @param int                           $donation_id The donation ID.
			 * @param Charitable_Donation_Processor $processor   The Donation Processor object.
			 */
			$paypal_args = apply_filters( 'charitable_paypal_redirect_args', array(
				'business'      => self::get_paypal_email(),
				'email'         => isset( $user_data['email'] ) ? $user_data['email'] : '',
				'first_name'    => isset( $user_data['first_name'] ) ? $user_data['first_name'] : '',
				'last_name'     => isset( $user_data['last_name'] ) ? $user_data['last_name'] : '',
				'address1'      => isset( $user_data['address'] ) ? $user_data['address'] : '',
				'address2'      => isset( $user_data['address_2'] ) ? $user_data['address_2'] : '',
				'city'          => isset( $user_data['city'] ) ? $user_data['city'] : '',
				'country'       => isset( $user_data['country'] ) ? $user_data['country'] : '',
				'zip'           => isset( $user_data['postcode'] ) ? $user_data['postcode'] : '',
				'invoice'       => $donation_key,
				'amount'        => $donation->get_total_donation_amount( true ),
				'item_name'     => html_entity_decode( $donation->get_campaigns_donated_to(), ENT_COMPAT, 'UTF-8' ),
				'no_shipping'   => '1',
				'shipping'      => '0',
				'currency_code' => charitable_get_currency(),
				'charset'       => get_bloginfo( 'charset' ),
				'custom'        => $donation_id,
				'rm'            => '2',
				'return'        => charitable_get_permalink( 'donation_receipt_page', array( 'donation_id' => $donation_id ) ),
				'cancel_return' => charitable_get_permalink( 'donation_cancel_page', array( 'donation_id' => $donation_id ) ),
				'notify_url'    => charitable_get_ipn_url( Charitable_Gateway_Paypal::ID ),
				'bn'            => 'Charitable_SP',
				'cmd'           => 'donations' == $transaction_mode ? '_donations' : '_xclick',
			), $donation_id, $processor );

			/* Set up the PayPal redirect URL. */
			$paypal_redirect  = trailingslashit( $gateway->get_redirect_url() ) . '?';
			$paypal_redirect .= http_build_query( $paypal_args );
			$paypal_redirect  = str_replace( '&amp;', '&', $paypal_redirect );

			/* Redirect to PayPal */
			return array(
				'redirect' => $paypal_redirect,
				'safe'     => false,
			);

		}

		/**
		 * Handle a call to our IPN listener.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public static function process_ipn() {
			/* We only accept POST requests */
			if ( ! self::is_valid_request() ) {
				die( __( 'Invalid Request', 'charitable' ) );
			}

			$gateway = new Charitable_Gateway_Paypal();
			$data    = $gateway->get_encoded_ipn_data();

			if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
				error_log( var_export( $data, true ) );
			}

			if ( empty( $data ) ) {
				die( __( 'Empty Data', 'charitable' ) );
			}

			if ( ! $gateway->paypal_ipn_verification( $data ) ) {
				die( __( 'IPN Verification Failure', 'charitable' ) );
			}

			$defaults = array(
				'payment_status' => '',
				'custom'         => 0,
				'txn_type'       => '',
			);

			$data        = wp_parse_args( $data, $defaults );
			$custom      = json_decode( $data['custom'], true );
			$donation_id = is_array( $custom ) && array_key_exists( 'donation_id', $custom )
				? absint( $custom['donation_id'] )
				: absint( $custom );

			if ( ! $donation_id ) {
				die( __( 'Missing Donation ID', 'charitable' ) );
			}

			/**
			 * By default, all transactions are handled by the web_accept handler.
			 * To handle other transaction types in a different way, use the
			 * 'charitable_paypal_{transaction_type}' hook.
			 *
			 * @see Charitable_Gateway_Paypal::process_web_accept()
			 */
			$txn_type = strlen( $data['txn_type'] ) ? $data['txn_type'] : 'web_accept';

			if ( has_action( 'charitable_paypal_' . $txn_type ) ) {
				do_action( 'charitable_paypal_' . $txn_type, $data, $donation_id );
			} else {
				do_action( 'charitable_paypal_web_accept', $data, $donation_id );
			}

			exit;
		}

		/**
		 * Receives verified IPN data from PayPal and processes the donation.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $data        The data received in the IPN from PayPal.
		 * @param  int   $donation_id The donation ID received from PayPal.
		 * @return void
		 */
		public static function process_web_accept( $data, $donation_id ) {
			$gateway  = new Charitable_Gateway_Paypal();
			$donation = charitable_get_donation( $donation_id );

			if ( 'paypal' != $donation->get_gateway() ) {
				die( __( 'Incorrect Gateway', 'charitable' ) );
			}

			$custom = json_decode( $data['custom'], true );

			if ( array_key_exists( 'invoice', $data ) ) {
				$donation_key = $data['invoice'];
			} elseif ( is_array( $custom ) && array_key_exists( 'donation_key', $custom ) ) {
				$donation_key = $custom['donation_key'];
			} else {
				die( __( 'Missing Donation Key', 'charitable' ) );
			}

			$amount         = $data['mc_gross'];
			$payment_status = strtolower( $data['payment_status'] );
			$currency_code  = strtoupper( $data['mc_currency'] );
			$business_email = isset( $data['business'] ) && is_email( $data['business'] ) ? trim( $data['business'] ) : trim( $data['receiver_email'] );

			/* Verify that the business email matches the PayPal email in the settings */
			if ( strcasecmp( $business_email, trim( self::get_paypal_email() ) ) != 0 ) {

				$message = sprintf( '%s %s', __( 'Invalid Business email in the IPN response. IPN data:', 'charitable' ), json_encode( $data ) );
				$donation->log()->add( $message );
				$donation->update_status( 'charitable-failed' );
				die( __( 'Incorrect Business Email', 'charitable' ) );

			}

			/* Verify that the currency matches. */
			if ( charitable_get_currency() != $currency_code ) {

				$message = sprintf( '%s %s', __( 'The currency in the IPN response does not match the site currency. IPN data:', 'charitable' ), json_encode( $data ) );
				$donation->log()->add( $message );
				$donation->update_status( 'charitable-failed' );

				die( __( 'Incorrect Currency', 'charitable' ) );

			}

			/* Process a refunded donation. */
			if ( in_array( $payment_status, array( 'refunded', 'reversed' ) ) ) {

				/* It's a partial refund. */
				if ( $amount < $donation->get_total_donation_amount( true ) ) {
					$message = sprintf( '%s: #%s',
						__( 'Partial PayPal refund processed', 'charitable' ),
						isset( $data['parent_txn_id'] ) ? $data['parent_txn_id'] : ''
					);
				} else {
					$message = sprintf( '%s #%s %s: %s',
						__( 'PayPal Payment', 'charitable' ),
						isset( $data['parent_txn_id'] ) ? $data['parent_txn_id'] : '',
						__( 'refunded with reason', 'charitable' ),
						isset( $data['reason_code'] ) ? $data['reason_code'] : ''
					);
				}

				$donation->process_refund( $amount, $message );

				die( __( 'Refund Processed', 'charitable' ) );

			}

			/* Mark a payment as failed. */
			if ( in_array( $payment_status, array( 'declined', 'failed', 'denied', 'expired', 'voided' ) ) ) {

				$message = sprintf( '%s: %s', __( 'The donation has failed with the following status', 'charitable' ), $payment_status );
				$donation->log()->add( $message );
				$donation->update_status( 'charitable-failed' );

				die( __( 'Payment Failed', 'charitable' ) );

			}

			/* If we have already processed this donation, stop here. */
			if ( 'charitable-completed' == get_post_status( $donation_id ) ) {
				die( __( 'Donation Processed Already', 'charitable' ) );
			}

			/* Verify that the donation key matches the one stored for the donation. */
			if ( $donation_key != $donation->get_donation_key() ) {

				$message = sprintf( '%s %s', __( 'Donation key in the IPN response does not match the donation. IPN data:', 'charitable' ), json_encode( $data ) );
				$donation->log()->add( $message );
				$donation->update_status( 'charitable-failed' );

				die( __( 'Invalid Donation Key', 'charitable' ) );

			}

			/* Verify that the amount in the IPN matches the amount we expected. */
			if ( $amount < $donation->get_total_donation_amount( true ) ) {

				$message = sprintf( '%s %s', __( 'The amount in the IPN response does not match the expected donation amount. IPN data:', 'charitable' ), json_encode( $data ) );
				$donation->log()->add( $message );
				$donation->update_status( 'charitable-failed' );

				die( __( 'Incorrect Amount', 'charitable' ) );

			}

			/* Save the transation ID */
			$donation->set_gateway_transaction_id( $data['txn_id'] );

			/* Process a completed donation. */
			if ( 'completed' == $payment_status ) {

				$message = sprintf( '%s: %s', __( 'PayPal Transaction ID', 'charitable' ), $data['txn_id'] );
				$donation->log()->add( $message );
				$donation->update_status( 'charitable-completed' );

				die( __( 'Donation Completed', 'charitable' ) );

			}

			/* If the donation is set to pending but has a pending_reason provided, save that to the log. */
			if ( 'pending' == $payment_status ) {

				if ( array_key_exists( 'pending_reason', $data ) ) {

					$message = $gateway->get_pending_reason_note( strtolower( $data['pending_reason'] ) );
					$donation->log()->add( $message );

				}

				$donation->update_status( 'charitable-pending' );

				die( __( 'Donation Pending', 'charitable' ) );

			}

			die( __( 'Unknown Response', 'charitable' ) );
		}

		/**
		 * Return the posted IPN data.
		 *
		 * @since  1.0.0
		 *
		 * @return mixed[]
		 */
		public function get_encoded_ipn_data() {
			$post_data = '';

			/* Fallback just in case post_max_size is lower than needed. */
			if ( ini_get( 'allow_url_fopen' ) ) {
				$post_data = file_get_contents( 'php://input' );
			} else {
				ini_set( 'post_max_size', '12M' );
			}

			if ( strlen( $post_data ) ) {
				$arg_separator = ini_get( 'arg_separator.output' );
				$data_string   = 'cmd=_notify-validate' . $arg_separator . $post_data;

				/* Convert collected post data to an array */
				parse_str( $data_string, $data );

				return $data;
			}

			/* Return an empty array if there are no POST variables. */
			if ( empty( $_POST ) ) {
				return array();
			}

			$data = array(
				'cmd' => '_notify-validate',
			);

			return array_merge( $data, $_POST );

		}

		/**
		 * Validates an IPN request with PayPal.
		 *
		 * @since  1.0.0
		 *
		 * @param  mixed[] $data Data received from PayPal.
		 * @return boolean
		 */
		public function paypal_ipn_verification( $data ) {
			if ( $this->get_value( 'disable_ipn_verification' ) ) {
				return true;
			}

			$remote_post_vars = array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.1',
				'blocking'    => true,
				'headers'     => array(
					'host'         => 'www.paypal.com',
					'connection'   => 'close',
					'content-type' => 'application/x-www-form-urlencoded',
					'post'         => '/cgi-bin/webscr HTTP/1.1',

				),
				'sslverify'   => false,
				'body'        => $data,
			);

			/* Get response */
			$api_response = wp_remote_post( $this->get_redirect_url( true, true ), $remote_post_vars );

			/**
			 * Filter whether the PayPal IPN was verified.
			 *
			 * @since 1.0.0
			 *
			 * @param boolean        $valid        Whether it has been verified.
			 * @param array|WP_Error $api_response Array in case of successful request. WP_Error otherwise.
			 */
			return apply_filters( 'charitable_paypal_ipn_verification', $this->is_valid_api_response( $api_response ), $api_response );
		}

		/**
		 * Return a note to log for a pending payment.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $reason_code The reason code received from PayPal.
		 * @return string
		 */
		public function get_pending_reason_note( $reason_code ) {
			switch ( $reason_code ) {
				case 'echeck':
					$note = __( 'Payment made via eCheck and will clear automatically in 5-8 days', 'charitable' );
					break;

				case 'address':
					$note = __( 'Payment requires a confirmed customer address and must be accepted manually through PayPal', 'charitable' );
					break;

				case 'intl':
					$note = __( 'Payment must be accepted manually through PayPal due to international account regulations', 'charitable' );
					break;

				case 'multi-currency':
					$note = __( 'Payment received in non-shop currency and must be accepted manually through PayPal', 'charitable' );
					break;

				case 'paymentreview':
				case 'regulatory_review':
					$note = __( 'Payment is being reviewed by PayPal staff as high-risk or in possible violation of government regulations', 'charitable' );
					break;

				case 'unilateral':
					$note = __( 'Payment was sent to non-confirmed or non-registered email address.', 'charitable' );
					break;

				case 'upgrade':
					$note = __( 'PayPal account must be upgraded before this payment can be accepted', 'charitable' );
					break;

				case 'verify':
					$note = __( 'PayPal account is not verified. Verify account in order to accept this payment', 'charitable' );
					break;

				default:
					/* translators: %s: reason code given by PayPal */
					$note = sprintf( __( 'Payment is pending for unknown reasons. Contact PayPal support for assistance. Reason code: %s', 'charitable' ), $reason_code );
			}

			/**
			 * Filter the donation log note added about the reason for the pending donation.
			 *
			 * @since 1.0.0
			 *
			 * @param string $note        The note.
			 * @param string $reason_code The reason code received from PayPal.
			 */
			return apply_filters( 'charitable_paypal_gateway_pending_reason_note', $note, $reason_code );
		}

		/**
		 * Process a donation's refund.
		 *
		 * @since  1.6.0
		 *
		 * @param  int $donation_id The donation ID.
		 * @return boolean
		 */
		public static function process_refund( $donation_id ) {
			$donation = charitable_get_donation( $donation_id );

			if ( ! $donation ) {
				return false;
			}

			$credentials = self::get_api_credentials( $donation->get_test_mode( false ) );

			$n    = 0;
			$body = array(
				'METHOD'        => 'RefundTransaction',
				'TRANSACTIONID' => $donation->get_gateway_transaction_id(),
				'REFUNDTYPE'    => 'Full',
				'USER'          => $credentials['username'],
				'PWD'           => $credentials['password'],
				'SIGNATURE'     => $credentials['signature'],
				'VERSION'       => '204',
			);

			foreach ( $donation->get_campaign_donations() as $campaign_donation ) {
				$body[ 'L_INVOICEITEMNAME' . $n ]   = $campaign_donation->campaign_name;
				$body[ 'L_DESCRIPTION' . $n ]       = __( 'Donation', 'charitable' );
				$body[ 'L_PRICE' . $n ]             = Charitable_Currency::get_instance()->sanitize_monetary_amount( (string) $campaign_donation->amount );
				$body[ 'L_PRICECURRENCYCODE' . $n ] = charitable_get_currency();

				$n += 1;
			}

			/**
			 * Filter the PayPal refund args.
			 *
			 * @since 1.6.0
			 *
			 * @param array               $body     The arguments we're sending to PayPal.
			 * @param Charitable_Donation $donation The donation instance.
			 */
			$body    = apply_filters( 'charitable_paypal_refund_args', $body, $donation );
			$headers = array(
				'Content-Type'  => 'application/x-www-form-urlencoded',
				'Cache-Control' => 'no-cache',
			);
			$args    = array(
				'body'        => $body,
				'headers'     => $headers,
				'timeout'     => 30,
				'httpversion' => '1.1',
			);

			if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
				error_log( var_export( $args, true ) );
			}

			/* Post the arguments to PayPal. */
			$request = wp_remote_post( self::get_api_endpoint( $donation->get_test_mode( false ) ), $args );

			if ( is_wp_error( $request ) ) {
				$donation->log()->add( sprintf(
					/* translators: %s: error message. */
					__( 'PayPal refund failed: %s', 'charitable' ),
					$request->get_error_message()
				) );

				return false;
			}

			$body    = array();
			$code    = wp_remote_retrieve_response_code( $request );
			$message = wp_remote_retrieve_response_message( $request );

			wp_parse_str( wp_remote_retrieve_body( $request ), $body );

			if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
				error_log( var_export( $body, true ) );
				error_log( 'Response Code: ' . $code );
				error_log( 'Response Message: ' . $message );
			}

			if ( 200 === (int) $code
				&& 'OK' === $message
				&& isset( $body['ACK'] )
				&& 'success' === strtolower( $body['ACK'] )
			) {
				update_post_meta( $donation->ID, '_paypal_refunded', true );

				$donation->log()->add( sprintf(
					/* translators: %s: transaction reference. */
					__( 'PayPal refund transaction ID: %s', 'charitable' ),
					$body['REFUNDTRANSACTIONID']
				) );

				return true;
			}

			$error = array_key_exists( 'L_LONGMESSAGE0', $body ) ? $body['L_LONGMESSAGE0'] : __( 'Unknown reason', 'charitable' );

			$donation->log()->add( sprintf(
				/* translators: %s: error message. */
				__( 'PayPal refund failed: %s', 'charitable' ),
				$error
			) );

			return false;
		}

		/**
		 * Check whether a particular donation can have its status changed.
		 *
		 * @since  1.6.0
		 *
		 * @param  Charitable_Donation $donation The donation object.
		 * @return boolean
		 */
		public function is_donation_refundable( Charitable_Donation $donation ) {
			if ( ! self::has_api_credentials( $donation->get_test_mode( false ) ) ) {
				return false;
			}

			return strlen( $donation->get_gateway_transaction_id() )
				&& true != get_post_meta( $donation->ID, '_paypal_refunded', true );
		}

		/**
		 * Return the base of the PayPal request.
		 *
		 * @since  1.0.0
		 * @since  1.5.4 Added $ipn_check parameter.
		 *
		 * @param  boolean $ssl_check Whether to check SSL.
		 * @param  boolean $ipn_check Whether this is for an IPN request.
		 * @return string
		 */
		public function get_redirect_url( $ssl_check = false, $ipn_check = false ) {
			$paypal_uri = $this->use_ssl( $ssl_check, $ipn_check ) ? 'https://' : 'http://';

			if ( charitable_get_option( 'test_mode' ) ) {
				$paypal_uri .= $ipn_check ? 'ipnpb.sandbox.' : 'www.sandbox.';
			} else {
				$paypal_uri .= $ipn_check ? 'ipnpb.' : 'www.';
			}

			$paypal_uri .= 'paypal.com/cgi-bin/webscr';

			/**
			 * Filter the PayPal URI.
			 *
			 * @since 1.0.0
			 * @since 1.5.4 Added $ssl_check and $ipn_check parameters.
			 *
			 * @param string  $paypal_uri The URL.
			 * @param boolean $ssl_check Whether to check SSL.
			 * @param boolean $ipn_check Whether this is for an IPN request.
			 */
			return apply_filters( 'charitable_paypal_uri', $paypal_uri, $ssl_check, $ipn_check );
		}

		/**
		 * Return the PayPal business email.
		 *
		 * @since  1.6.0
		 *
		 * @return string
		 */
		public static function get_paypal_email() {
			$option = charitable_get_option( 'test_mode' ) ? 'sandbox_paypal_email' : 'paypal_email';

			/**
			 * Filter the PayPal Email.
			 *
			 * @since 1.6.0
			 *
			 * @param string $paypal_email The email address.
			 */
			return apply_filters( 'charitable_paypal_email', charitable_get_option( array( 'gateways_paypal', $option ) ) );
		}

		/**
		 * Returns the current gateway's ID.
		 *
		 * @since  1.0.3
		 *
		 * @return string
		 */
		public static function get_gateway_id() {
			return self::ID;
		}

		/**
		 * Cancel a subscription in the payment gateway.
		 *
		 * @since  1.5.9
		 *
		 * @param  int $subscription_id The ID of the subscription/recurring donation.
		 * @return boolean True if the subscription was successfully cancelled. False otherwise.
		 */
		public function cancel_subscription( $subscription_id ) {
			/* @todo Handle subscription cancellation. */
			return true;
		}

		/**
		 * Return whether to use SSL.
		 *
		 * @since  1.5.4
		 *
		 * @param  boolean $ssl_check Whether to check SSL.
		 * @param  boolean $ipn_check Whether this is for an IPN request.
		 * @return boolean
		 */
		private function use_ssl( $ssl_check = false, $ipn_check = false ) {
			return $ipn_check || ! $ssl_check || is_ssl();
		}

		/**
		 * Returns whether the API response we received is valid.
		 *
		 * @since  1.5.4
		 *
		 * @param  array|WP_Error $api_response Array in case of successful request. WP_Error otherwise.
		 * @return boolean
		 */
		private function is_valid_api_response( $api_response ) {
			return ! is_wp_error( $api_response ) && 'VERIFIED' == $api_response['body'];
		}

		/**
		 * Returns whether the IPN request is valid.
		 *
		 * @since  1.5.4
		 *
		 * @return boolean
		 */
		private static function is_valid_request() {
			return ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' == $_SERVER['REQUEST_METHOD'];
		}
	}

endif;
