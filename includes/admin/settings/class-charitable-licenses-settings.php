<?php
/**
 * Charitable Licenses Settings UI.
 *
 * @package     Charitable/Classes/Charitable_Licenses_Settings
 * @version     1.0.0
 * @author      Eric Daams
 * @copyright   Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Licenses_Settings' ) ) :

	/**
	 * Charitable_Licenses_Settings
	 *
	 * @final
	 * @since   1.0.0
	 */
	final class Charitable_Licenses_Settings {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Licenses_Settings|null
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
		 * @return  Charitable_Licenses_Settings
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Optionally add the licenses tab.
		 *
		 * @since   1.4.7
		 *
		 * @param   string[] $tabs Settings tabs.
		 * @return  string[]
		 */
		public function maybe_add_licenses_tab( $tabs ) {

			$products = charitable_get_helper( 'licenses' )->get_products();

			if ( empty( $products ) ) {
				return $tabs;
			}

			$tabs = charitable_add_settings_tab(
				$tabs,
				'licenses',
				__( 'Licenses', 'charitable' ),
				array(
					'index' => 4,
				)
			);

			return $tabs;

		}

		/**
		 * Add the licenses tab settings fields.
		 *
		 * @since   1.0.0
		 *
		 * @return  array
		 */
		public function add_licenses_fields() {
			if ( ! charitable_is_settings_view( 'licenses' ) ) {
				return array();
			}

			$fields = array(
				'section'  => array(
					'title'    => '',
					'type'     => 'hidden',
					'priority' => 10000,
					'value'    => 'licenses',
					'save'     => false,
				),
				'licenses' => array(
					'title'    => false,
					'callback' => array( $this, 'render_licenses_table' ),
					'priority' => 4,
				),
			);

			foreach ( charitable_get_helper( 'licenses' )->get_products() as $key => $product ) {
				$fields[ $key ] = array(
					'type'     => 'text',
					'render'   => false,
					'priority' => 6,
				);
			}

			return $fields;
		}

		/**
		 * Add the licenses group.
		 *
		 * @since   1.0.0
		 *
		 * @param   string[] $groups Settings groups.
		 * @return  string[]
		 */
		public function add_licenses_group( $groups ) {
			$groups['licenses'] = array();
			return $groups;
		}

		/**
		 * Render the licenses table.
		 *
		 * @since   1.0.0
		 *
		 * @return  void
		 */
		public function render_licenses_table() {
			charitable_admin_view( 'settings/licenses' );
		}

		/**
		 * Add an extra button to the Licenses tab to re-check licenses.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $button The button HTML.
		 * @return string
		 */
		public function add_license_recheck_button( $button ) {
			$licenses = array_filter( charitable_get_helper( 'licenses' )->get_licenses(), 'is_array' );

			if ( empty( $licenses ) ) {
				return $button;
			}

			$html = '<input style="margin-left:8px;height:29px;" type="submit" class="button button-secondary" name="recheck" value="' . esc_attr__( 'Save & Re-check All Licenses', 'charitable' ) . '" /></p>';

			return str_replace(
				'</p>',
				$html,
				$button
			);
		}

		/**
		 * Checks for updated license and invalidates status field if not set.
		 *
		 * @since   1.0.0
		 *
		 * @param   mixed[] $values The parsed values combining old values & new values.
		 * @param   mixed[] $new_values The newly submitted values.
		 * @return  mixed[]
		 */
		public function save_license( $values, $new_values ) {
			/* If we didn't just submit licenses, stop here. */
			if ( ! isset( $new_values['licenses'] ) ) {
				return $values;
			}

			$re_check = array_key_exists( 'recheck', $_POST );
			$licenses = $new_values['licenses'];

			foreach ( $licenses as $product_key => $license ) {
				$license = trim( $license );

				if ( empty( $license ) ) {
					$values['licenses'][ $product_key ] = '';
					continue;
				}

				$license_data = charitable_get_helper( 'licenses' )->verify_license( $product_key, $license, $re_check );

				if ( empty( $license_data ) ) {
					continue;
				}

				$values['licenses'][ $product_key ] = $license_data;
			}

			return $values;
		}
	}

endif;
