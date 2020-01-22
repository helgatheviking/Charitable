<?php
/**
 * Charitable Donation Fields model.
 *
 * @package   Charitable/Classes/Charitable_Donation_Fields
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donation_Fields' ) ) :

	/**
	 * Charitable_Donation_Fields
	 *
	 * @deprecated 1.9.0
	 *
	 * @since 1.5.0
	 * @since 1.6.0 Deprecated. Use Charitable_Object_Fields instead.
	 */
	class Charitable_Donation_Fields extends Charitable_Object_Fields {

		/**
		 * Create class object.
		 *
		 * @deprecated 1.9.0
		 *
		 * @since 1.5.0
		 * @since 1.6.0 Deprecated. Use Charitable_Object_Fields instead.
		 *
		 * @param Charitable_Field_Registry $registry An instance of `Charitable_Field_Registry`.
		 * @param mixed                     $object   The object that will be passed to the value callback.
		 */
		public function __construct( Charitable_Field_Registry $registry, $object ) {
			parent::__construct( $registry, $object );

			charitable_get_deprecated()->doing_it_wrong(
				'Charitable_Donation_Class',
				__( 'Charitable_Donation_Fields is deprecated as of Charitable 1.6.0. Use Charitable_Object_Fields instead.', 'charitable' ),
				'1.6.0'
			);
		}
	}

endif;
