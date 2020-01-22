<?php
/**
 * Charitable Object Fields model.
 *
 * @package   Charitable/Classes/Charitable_Object_Fields
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.0
 * @version   1.6.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Object_Fields' ) ) :

	/**
	 * Charitable_Object_Fields
	 *
	 * @since 1.6.0
	 */
	class Charitable_Object_Fields implements Charitable_Fields_Interface {

		/**
		 * The `Charitable_Field_Registry` instance for this object.
		 *
		 * @since 1.6.0
		 *
		 * @var   Charitable_Field_Registry
		 */
		private $registry;

		/**
		 * An object.
		 *
		 * @since 1.6.0
		 *
		 * @var   mixed
		 */
		private $object;

		/**
		 * Create class object.
		 *
		 * @since 1.6.0
		 *
		 * @param Charitable_Field_Registry $registry An instance of `Charitable_Field_Registry`.
		 * @param mixed                     $object   The object that will be passed to the value callback.
		 */
		public function __construct( Charitable_Field_Registry $registry, $object ) {
			$this->registry = $registry;
			$this->object   = $object;
		}

		/**
		 * Get the set value for a particular field.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $field_key The field to get a value for.
		 * @return mixed
		 */
		public function get( $field_key ) {
			$field = $this->registry->get_field( $field_key );

			if ( ! $field ) {
				return null;
			}

			return call_user_func( $field->value_callback, $this->object, $field_key );
		}

		/**
		 * Check whether a particular field is registered.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $field_key The field to check for.
		 * @return boolean
		 */
		public function has( $field_key ) {
			return false !== $this->registry->get_field( $field_key );
		}

		/**
		 * Check whether a particular field has a callback for getting the value.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $field_key The field to check for.
		 * @return boolean
		 */
		public function has_value_callback( $field_key ) {
			$field = $this->registry->get_field( $field_key );

			return $field && false !== $field->value_callback;
		}
	}

endif;
