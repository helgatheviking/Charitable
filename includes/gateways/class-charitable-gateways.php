<?php
/**
 * Class that sets up the gateways.
 *
 * @package   Charitable/Classes/Charitable_Gateways
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.38
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Gateways' ) ) :

	/**
	 * Charitable_Gateways
	 *
	 * @since 1.0.0
	 */
	class Charitable_Gateways {

		/**
		 * The single instance of this class.
		 *
		 * @since 1.0.0
		 *
		 * @var   Charitable_Gateways|null
		 */
		private static $instance = null;

		/**
		 * All available payment gateways.
		 *
		 * @since 1.0.0
		 *
		 * @var   array
		 */
		private $gateways;

		/**
		 * Set up the class.
		 *
		 * Note that the only way to instantiate an object is with the charitable_start method,
		 * which can only be called during the start phase. In other words, don't try
		 * to instantiate this object.
		 *
		 * @since 1.0.0
		 */
		protected function __construct() {
			add_action( 'init', array( $this, 'register_gateways' ) );
			add_action( 'charitable_make_default_gateway', array( $this, 'handle_gateway_settings_request' ) );
			add_action( 'charitable_enable_gateway', array( $this, 'handle_gateway_settings_request' ) );
			add_action( 'charitable_disable_gateway', array( $this, 'handle_gateway_settings_request' ) );
			add_filter( 'charitable_settings_fields_gateways_gateway', array( $this, 'register_gateway_settings' ), 10, 2 );

			do_action( 'charitable_gateway_start', $this );
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Gateways
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Register gateways.
		 *
		 * To register a new gateway, you need to hook into the `charitable_payment_gateways`
		 * hook and give Charitable the name of your gateway class.
		 *
		 * @since  1.2.0
		 *
		 * @return void
		 */
		public function register_gateways() {
			/**
			 * Filter the list of payment gateways.
			 *
			 * @since 1.2.0
			 *
			 * @param array $gateways The list of gateways in gateway ID => gateway class format.
			 */
			$this->gateways = apply_filters(
				'charitable_payment_gateways',
				array(
					'offline' => 'Charitable_Gateway_Offline',
					'paypal'  => 'Charitable_Gateway_Paypal',
				)
			);
		}

		/**
		 * Receives a request to enable or disable a payment gateway and validates it before passing it off.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function handle_gateway_settings_request() {
			if ( ! wp_verify_nonce( $_REQUEST['_nonce'], 'gateway' ) ) {
				wp_die( __( 'Cheatin\' eh?!', 'charitable' ) );
			}

			$gateway = isset( $_REQUEST['gateway_id'] ) ? $_REQUEST['gateway_id'] : false;

			/* Gateway must be set */
			if ( false === $gateway ) {
				wp_die( __( 'Missing gateway.', 'charitable' ) );
			}

			/* Validate gateway. */
			if ( ! isset( $this->gateways[ $gateway ] ) ) {
				wp_die( __( 'Invalid gateway.', 'charitable' ) );
			}

			switch ( $_REQUEST['charitable_action'] ) {
				case 'disable_gateway':
					$this->disable_gateway( $gateway );
					break;
				case 'enable_gateway':
					$this->enable_gateway( $gateway );
					break;
				case 'make_default_gateway':
					$this->set_default_gateway( $gateway );
					break;
				default:
					/**
					 * Do something when a gateway settings request takes place.
					 *
					 * @since 1.0.0
					 *
					 * @param string $action     The action taking place.
					 * @param string $gateway_id The gateway ID.
					 */
					do_action( 'charitable_gateway_settings_request', $_REQUEST['charitable_action'], $gateway );
			}
		}

		/**
		 * Returns all available payment gateways.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_available_gateways() {
			return $this->gateways;
		}

		/**
		 * Returns the current active gateways.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function get_active_gateways() {
			$active_gateways = charitable_get_option( 'active_gateways', array() );

			foreach ( $active_gateways as $gateway_id => $gateway_class ) {
				if ( ! class_exists( $gateway_class ) ) {
					unset( $active_gateways[ $gateway_id ] );
				}
			}

			uksort( $active_gateways, array( $this, 'sort_by_default' ) );

			/**
			 * Filter the list of active gateways.
			 *
			 * @since 1.0.0
			 *
			 * @param array $gateways Active gateways.
			 */
			return apply_filters( 'charitable_active_gateways', $active_gateways );
		}

		/**
		 * Returns an array of the active gateways, in ID => name format.
		 *
		 * This is useful for select/radio input fields.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function get_gateway_choices() {
			$gateways = array();

			foreach ( $this->get_active_gateways() as $id => $class ) {
				$gateway         = new $class;
				$gateways[ $id ] = $gateway->get_label();
			}

			return $gateways;
		}

		/**
		 * Returns a text description of the active gateways.
		 *
		 * @since  1.3.0
		 *
		 * @return string[]
		 */
		public function get_active_gateways_names() {
			$gateways = array();

			foreach ( $this->get_active_gateways() as $id => $class ) {
				$gateway    = new $class;
				$gateways[] = $gateway->get_name();
			}

			return $gateways;
		}

		/**
		 * Return the gateway class name for a given gateway.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $gateway Gateway ID.
		 * @return string|false
		 */
		public function get_gateway( $gateway ) {
			return isset( $this->gateways[ $gateway ] ) ? $this->gateways[ $gateway ] : false;
		}

		/**
		 * Return the gateway object for a given gateway.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $gateway Gateway ID.
		 * @return Charitable_Gateway|null
		 */
		public function get_gateway_object( $gateway ) {
			$class  = $this->get_gateway( $gateway );
			$object = $class ? new $class : null;

			/**
			 * Filter the gateway object.
			 *
			 * @since 1.6.30
			 *
			 * @param Charitable_Gateway|null $object  The gateway object.
			 */
			return apply_filters( 'charitable_gateway_object_' . $gateway, $object, $gateway );
		}

		/**
		 * Returns whether the passed gateway is active.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $gateway_id Gateway ID.
		 * @return boolean
		 */
		public function is_active_gateway( $gateway_id ) {
			return array_key_exists( $gateway_id, $this->get_active_gateways() );
		}

		/**
		 * Checks whether the submitted gateway is valid.
		 *
		 * @since  1.4.3
		 *
		 * @param  string $gateway Gateway ID.
		 * @return boolean
		 */
		public function is_valid_gateway( $gateway ) {
			/**
			 * Validate a particular gatewya.
			 *
			 * @since 1.4.3
			 *
			 * @param boolean $valid   Whether a gateway is valid.
			 * @param string  $gateway The gateway ID.
			 */
			return apply_filters( 'charitable_is_valid_gateway', array_key_exists( $gateway, $this->gateways ), $gateway );
		}

		/**
		 * Returns the default gateway.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_default_gateway() {
			return charitable_get_option( 'default_gateway', '' );
		}

		/**
		 * Provide default gateway settings fields.
		 *
		 * @since  1.0.0
		 *
		 * @param  array              $settings Gateway settings.
		 * @param  Charitable_Gateway $gateway  The gateway's helper object.
		 * @return array
		 */
		public function register_gateway_settings( $settings, Charitable_Gateway $gateway ) {
			add_filter( 'charitable_settings_fields_gateways_gateway_' . $gateway->get_gateway_id(), array( $gateway, 'default_gateway_settings' ), 5 );
			add_filter( 'charitable_settings_fields_gateways_gateway_' . $gateway->get_gateway_id(), array( $gateway, 'gateway_settings' ), 15 );

			/**
			 * Filter the settings to show for a particular gateway.
			 *
			 * @since 1.0.0
			 *
			 * @param array $settings Gateway settings.
			 */
			return apply_filters( 'charitable_settings_fields_gateways_gateway_' . $gateway->get_gateway_id(), $settings );
		}

		/**
		 * Returns true if test mode is enabled.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function in_test_mode() {
			$enabled = charitable_get_option( 'test_mode', false );

			/**
			 * Return whether Charitable is in test mode.
			 *
			 * @since 1.0.0
			 *
			 * @param boolean $enabled Whether test mode is on.
			 */
			return apply_filters( 'charitable_in_test_mode', $enabled );
		}

		/**
		 * Checks whether all of the active gateways support a feature.
		 *
		 * If ANY gateway doesn't support the feature, this returns false.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $feature Feature to search for.
		 * @return boolean
		 */
		public function all_gateways_support( $feature ) {
			foreach ( $this->get_active_gateways() as $gateway_id => $gateway_class ) {

				$gateway_object = new $gateway_class;

				if ( false === $gateway_object->supports( $feature ) ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Checks whether any of the active gateways support a feature.
		 *
		 * If any gateway supports the feature, this returns true. Otherwise false.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $feature Feature to check for.
		 * @return boolean
		 */
		public function any_gateway_supports( $feature ) {
			foreach ( $this->get_active_gateways() as $gateway_id => $gateway_class ) {

				$gateway_object = new $gateway_class;

				if ( true === $gateway_object->supports( $feature ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Checks whether all of the active gateways support AJAX.
		 *
		 * If ANY gateway doesn't support AJAX, this returns false.
		 *
		 * @since  1.3.0
		 *
		 * @return boolean
		 */
		public function gateways_support_ajax() {
			return $this->all_gateways_support( '1.3.0' );
		}

		/**
		 * Return an array of recommended gateways for the current site.
		 *
		 * Note that this will only return gateways that are not already
		 * available on the site. i.e. If you have Stripe installed, it
		 * will not suggest that.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function get_recommended_gateways() {
			$available = $this->get_available_gateways();
			$gateways  = array(
				'payfast'       => __( 'Payfast', 'charitable' ),
				'payumoney'     => __( 'PayUMoney', 'charitable' ),
				'stripe'        => __( 'Stripe', 'charitable' ),
				'authorize_net' => __( 'Authorize.Net', 'charitable' ),
				'windcave'      => __( 'Windcave', 'charitable' ),
			);

			/* If the user has already enabled one of these, leave them alone. :) */
			foreach ( $gateways as $gateway_id => $gateway ) {
				if ( array_key_exists( $gateway_id, $available ) ) {
					return array();
				}
			}

			$currency = charitable_get_option( 'currency', 'AUD' );
			$locale   = get_locale();

			if ( 'en_ZA' == $locale || 'ZAR' == $currency ) {
				return charitable_array_subset( $gateways, array( 'payfast' ) );
			}

			if ( 'hi_IN' == $locale || 'INR' == $currency ) {
				return charitable_array_subset( $gateways, array( 'payumoney' ) );
			}

			if ( in_array( $locale, array( 'en_NZ', 'ms_MY', 'ja', 'zh_HK' ) ) || in_array( $currency, array( 'NZD', 'MYR', 'JPY', 'HKD' ) ) ) {
				return charitable_array_subset( $gateways, array( 'stripe', 'windcave' ) );
			}

			if ( in_array( $locale, array( 'th' ) ) || in_array( $currency, array( 'BND', 'FJD', 'KWD', 'PGK', 'SBD', 'THB', 'TOP', 'VUV', 'WST' ) ) ) {
				return charitable_array_subset( $gateways, array( 'windcave' ) );
			}

			return charitable_array_subset( $gateways, array( 'stripe', 'authorize_net' ) );
		}

		/**
		 * Sets the default gateway.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $gateway Gateway ID.
		 * @return void
		 */
		protected function set_default_gateway( $gateway ) {
			$settings                    = get_option( 'charitable_settings' );
			$settings['default_gateway'] = $gateway;

			update_option( 'charitable_settings', $settings );

			charitable_get_admin_notices()->add_success( __( 'Default Gateway Updated', 'charitable' ) );

			do_action( 'charitable_set_gateway_gateway', $gateway );
		}

		/**
		 * Enable a payment gateway.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $gateway Gateway ID.
		 * @return void
		 */
		protected function enable_gateway( $gateway ) {
			$settings = get_option( 'charitable_settings' );

			$active_gateways             = isset( $settings['active_gateways'] ) ? $settings['active_gateways'] : array();
			$active_gateways[ $gateway ] = $this->gateways[ $gateway ];
			$settings['active_gateways'] = $active_gateways;

			/* If this is the only gateway, make it the default gateway */
			if ( 1 == count( $settings['active_gateways'] ) ) {
				$settings['default_gateway'] = $gateway;
			}

			update_option( 'charitable_settings', $settings );

			Charitable_Settings::get_instance()->add_update_message( __( 'Gateway enabled', 'charitable' ), 'success' );

			/**
			 * Do something when a payment gateway is enabled.
			 *
			 * @since 1.0.0
			 *
			 * @param string $gateway The payment gateway.
			 */
			do_action( 'charitable_gateway_enable', $gateway );
		}

		/**
		 * Disable a payment gateway.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $gateway Gateway ID.
		 * @return void
		 */
		protected function disable_gateway( $gateway ) {
			$settings = get_option( 'charitable_settings' );

			if ( ! isset( $settings['active_gateways'][ $gateway ] ) ) {
				return;
			}

			unset( $settings['active_gateways'][ $gateway ] );

			/* Set a new default gateway */
			if ( $gateway == $this->get_default_gateway() ) {

				$settings['default_gateway'] = count( $settings['active_gateways'] ) ? key( $settings['active_gateways'] ) : '';

			}

			update_option( 'charitable_settings', $settings );

			Charitable_Settings::get_instance()->add_update_message( __( 'Gateway disabled', 'charitable' ), 'success' );

			/**
			 * Do something when a payment gateway is disabled.
			 *
			 * @since 1.0.0
			 *
			 * @param string $gateway The payment gateway.
			 */
			do_action( 'charitable_gateway_disable', $gateway );
		}

		/**
		 * Sort the active gateways, placing the default gateway first.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $a Gateway to compare.
		 * @param  string $b Gateway to compare against.
		 * @return int
		 */
		protected function sort_by_default( $a, $b ) {
			$default = $this->get_default_gateway();

			if ( $a == $default ) {
				return -1;
			}

			if ( $b == $default ) {
				return 1;
			}

			return 0;
		}
	}

endif;
