<?php
/**
 * Gateway abstract model
 *
 * @version     1.0.0
 * @package     Charitable/Classes/Charitable_Gateway
 * @author      Eric Daams
 * @copyright   Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Gateway' ) ) :

	/**
	 * Charitable_Gateway
	 *
	 * @abstract
	 * @since   1.0.0
	 */
	abstract class Charitable_Gateway implements Charitable_Gateway_Interface {

		/**
		 * @var     string The gateway's unique identifier.
		 */
		const ID = '';

		/**
		 * @var     string Name of the payment gateway.
		 * @since   1.0.0
		 */
		protected $name;

		/**
		 * @var     array The default values for all settings added by the gateway.
		 * @since   1.0.0
		 */
		protected $defaults;

		/**
		 * Supported features such as 'credit-card', and 'recurring' donations
		 *
		 * @var     string[]
		 * @since   1.3.0
		 */
		protected $supports = array();

		/**
		 * Return the gateway name.
		 *
		 * @since   1.0.0
		 *
		 * @return  string
		 */
		public function get_name() {
			return $this->name;
		}

		/**
		 * Returns the default gateway label to be displayed to donors.
		 *
		 * @since   1.0.0
		 *
		 * @return  string
		 */
		public function get_default_label() {
			return isset( $this->defaults['label'] ) ? $this->defaults['label'] : $this->get_name();
		}

		/**
		 * Provide default gateway settings fields.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function default_gateway_settings() {
			return array(
				'section_gateway' => array(
					'type'     => 'heading',
					'title'    => $this->get_name(),
					'priority' => 2,
				),
				'label'           => array(
					'type'     => 'text',
					'title'    => __( 'Gateway Label', 'charitable' ),
					'help'     => __( 'The label that will be shown to donors on the donation form.', 'charitable' ),
					'priority' => 4,
					'default'  => $this->get_default_label(),
				),
			);
		}

		/**
		 * Return the settings for this gateway.
		 *
		 * @since   1.0.0
		 *
		 * @return  array
		 */
		public function get_settings() {
			return charitable_get_option( 'gateways_' . $this->get_gateway_id(), array() );
		}

		/**
		 * Retrieve the gateway label.
		 *
		 * @since   1.0.0
		 *
		 * @return  string
		 */
		public function get_label() {
			return charitable_get_option( 'label', $this->get_default_label(), $this->get_settings() );
		}

		/**
		 * Return the value for a particular gateway setting.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $setting
		 * @return mixed
		 */
		public function get_value( $setting ) {
			$default = isset( $this->defaults[ $setting ] ) ? $this->defaults[ $setting ] : '';
			return charitable_get_option( $setting, $default, $this->get_settings() );
		}

		/**
		 * Check if a gateway supports a given feature.
		 *
		 * Gateways should override this to declare support (or lack of support) for a feature.
		 *
		 * @since   1.3.0
		 *
		 * @param   string $feature string The name of a feature to test support for.
		 * @return  bool True if the gateway supports the feature, false otherwise.
		 */
		public function supports( $feature ) {
			$supported = in_array( $feature, $this->supports ) ? true : false;

			/* Provide backwards compatibility for gateways that have not been updated. */
			if ( ! $supported && 'credit-card' == $feature && isset( $this->credit_card_form ) ) {
				$supported = $this->credit_card_form;
			}

			return apply_filters( 'charitable_payment_gateway_supports', $supported, $feature, $this );
		}

		/**
		 * Checks whether a particular donation is refundable.
		 *
		 * @since  1.6.0
		 *
		 * @param  Charitable_Donation $donation The donation object.
		 * @return boolean
		 */
		public function is_donation_refundable( Charitable_Donation $donation ) {
			return $this->supports( 'refunds' );
		}

		/**
		 * Returns an array of credit card fields.
		 *
		 * If the gateway requires different fields, this can simply be redefined
		 * in the child class.
		 *
		 * @since   1.0.0
		 *
		 * @return  array[]
		 */
		public function get_credit_card_fields() {
			/**
			 * Filter the credit card fields.
			 *
			 * @since 1.0.0
			 *
			 * @param array $fields The credit card fields.
			 */
			return apply_filters(
				'charitable_credit_card_fields',
				array(
					'cc_name'       => array(
						'label'     => __( 'Name on Card', 'charitable' ),
						'type'      => 'text',
						'required'  => true,
						'priority'  => 2,
						'data_type' => 'gateway',
					),
					'cc_number'     => array(
						'label'     => __( 'Card Number', 'charitable' ),
						'type'      => 'text',
						'required'  => true,
						'priority'  => 4,
						'data_type' => 'gateway',
					),
					'cc_cvc'        => array(
						'label'     => __( 'CVV Number', 'charitable' ),
						'type'      => 'text',
						'required'  => true,
						'priority'  => 6,
						'data_type' => 'gateway',
					),
					'cc_expiration' => array(
						'label'     => __( 'Expiration', 'charitable' ),
						'type'      => 'cc-expiration',
						'required'  => true,
						'priority'  => 8,
						'data_type' => 'gateway',
					),
				),
				$this
			);
		}

		/**
		 * Redirect the donation to the processing page.
		 *
		 * @since  1.0.0
		 *
		 * @param  mixed $result      The result to be returned to the AJAX process.
		 * @param  int   $donation_id The donation ID.
		 * @return array
		 */
		public static function redirect_to_processing( $result, $donation_id ) {
			return array(
				'safe'     => true,
				'redirect' => charitable_get_permalink(
					'donation_processing_page',
					array(
						'donation_id' => $donation_id,
					)
				),
			);
		}

		/**
		 * Returns whether a credit card form is required for this gateway.
		 *
		 * @since   1.0.0
		 *
		 * @return  boolean
		 *
		 * @deprecated
		 */
		public function requires_credit_card_form() {
			charitable_get_deprecated()->deprecated_function( __METHOD__, '1.3.0', 'Charitable_Gateway::supports( \'credit-card\' )' );
			return $this->supports( 'credit-card' );
		}

		/**
		 * Register gateway settings.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $settings
		 * @return  array
		 */
		abstract public function gateway_settings( $settings );
	}

endif;
