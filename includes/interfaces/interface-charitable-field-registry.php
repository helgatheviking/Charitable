<?php
/**
 * Strict interface for Charitable Field Registry APIs.
 *
 * @package   Charitable/Interfaces/Charitable_Field_Registry_Interface
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! interface_exists( 'Charitable_Field_Registry_Interface' ) ) :

	/**
	 * Charitable_Field_Registry_Interface interface.
	 *
	 * @since 1.5.0
	 */
	interface Charitable_Field_Registry_Interface {

		/**
		 * Return all the fields.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function get_fields();

		/**
		 * Return a single field.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $field_key The field's key.
		 * @return array|false       Array if the field is registered. False otherwise.
		 */
		public function get_field( $field_key );

		/**
		 * Register a field.
		 *
		 * @since  1.5.0
		 *
		 * @param  Charitable_Field_Interface $field Object of type `Charitable_Field_Interface`.
		 * @return boolean
		 */
		public function register_field( Charitable_Field_Interface $field );
	}

endif;
