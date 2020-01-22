<?php
/**
 * Charitable Gateway Settings UI.
 *
 * @package   Charitable/Classes/Charitable_Gateway_Settings
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Gateway_Settings' ) ) :

	/**
	 * Charitable_Gateway_Settings
	 *
	 * @final
	 * @since   1.0.0
	 */
	final class Charitable_Gateway_Settings {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Gateway_Settings|null
		 */
		private static $instance = null;

		/**
		 * Create object instance.
		 *
		 * @since   1.0.0
		 */
		private function __construct() {
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.2.0
		 *
		 * @return  Charitable_Gateway_Settings
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Returns all the payment gateway settings fields.
		 *
		 * @since   1.0.0
		 *
		 * @return  array
		 */
		public function add_gateway_fields() {
			if ( ! charitable_is_settings_view( 'gateways' ) ) {
				return array();
			}

			return array(
				'section' => array(
					'title'             => '',
					'type'              => 'hidden',
					'priority'          => 10000,
					'value'             => 'gateways',
					'save'              => false,
				),
				'section_gateways' => array(
					'title'             => __( 'Available Payment Gateways', 'charitable' ),
					'type'              => 'heading',
					'priority'          => 5,
				),
				'gateways' => array(
					'title'             => false,
					'callback'          => array( $this, 'render_gateways_table' ),
					'priority'          => 10,
				),
				'test_mode' => array(
					'title'             => __( 'Turn on Test Mode', 'charitable' ),
					'type'              => 'checkbox',
					'priority'          => 15,
				),
			);
		}

		/**
		 * Add settings for each individual payment gateway.
		 *
		 * @since   1.0.0
		 *
		 * @return  array[]
		 */
		public function add_individual_gateway_fields( $fields ) {
			foreach ( charitable_get_helper( 'gateways' )->get_active_gateways() as $gateway ) {
				if ( ! class_exists( $gateway ) ) {
					continue;
				}

				$gateway = new $gateway;
				$fields[ 'gateways_' . $gateway->get_gateway_id() ] = apply_filters( 'charitable_settings_fields_gateways_gateway', array(), $gateway );
			}

			return $fields;
		}

		/**
		 * Add gateway keys to the settings groups.
		 *
		 * @since  1.0.0
		 *
		 * @param  string[] $groups
		 * @return string[]
		 */
		public function add_gateway_settings_dynamic_groups( $groups ) {
			foreach ( charitable_get_helper( 'gateways' )->get_active_gateways() as $gateway_key => $gateway ) {
				if ( ! class_exists( $gateway ) ) {
					continue;
				}

				$groups[ 'gateways_' . $gateway_key ] = apply_filters( 'charitable_gateway_settings_fields_gateways_gateway', array(), new $gateway );
			}

			return $groups;
		}

		/**
		 * Display table with available payment gateways.
		 *
		 * @since   1.0.0
		 *
		 * @return  void
		 */
		public function render_gateways_table( $args ) {
			charitable_admin_view( 'settings/gateways', $args );
		}
	}

endif;
