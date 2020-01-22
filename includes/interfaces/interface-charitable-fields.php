<?php
/**
 * A strict interface for Charitable_Fields models.
 *
 * @package   Charitable/Classes/Charitable_Fields_Interface
 * @version   1.5.0
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Fields_Interface' ) ) :

    /**
     * Charitable_Fields_Interface
     *
     * @since 1.5.0
     */
    interface Charitable_Fields_Interface {

        /**
         * Get the set value for a particular field.
         *
         * @since  1.5.0
         *
         * @param  string $field The field to get a value for.
         * @return mixed
         */
        public function get( $field );

        /**
         * Check whether a particular field is registered.
         *
         * @since  1.5.0
         *
         * @param  string $field The field to check for.
         * @return boolean
         */
        public function has( $field );

        /**
         * Check whether a particular field has a callback for getting the value.
         *
         * @since  1.5.0
         *
         * @param  string $field The field to check for.
         * @return boolean
         */
        public function has_value_callback( $field );
    }

endif;
