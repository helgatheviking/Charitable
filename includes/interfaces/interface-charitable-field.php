<?php
/**
 * Strict interface for Charitable Fields APIs.
 *
 * @package   Charitable/Interfaces/Charitable_Field_Interface
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @version   1.5.0
 * @version   1.5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! interface_exists( 'Charitable_Field_Interface' ) ) :

	/**
	 * Charitable_Field_Interface interface.
	 *
	 * @since 1.5.0
	 */
	interface Charitable_Field_Interface {

		/**
		 * Returna particular field argument.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $key The field's key.
		 * @return mixed
		 */
		public function __get( $key );

		/**
		 * Return a single field.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $key   The field's key.
		 * @param  mixed  $value The field's value.
		 * @return void
		 */
		public function __set( $key, $value );
	}

endif;
