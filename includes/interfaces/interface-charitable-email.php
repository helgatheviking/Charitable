<?php
/**
 * Charitable Email interface.
 *
 * This defines a strict interface that emails must implement.
 *
 * @version   1.5.0
 * @package   Charitable/Interfaces/Charitable_Email_Interface
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! interface_exists( 'Charitable_Email_Interface' ) ) :

    /**
     * Charitable_Email_Interface interface.
     *
     * @since 1.2.0
     */
    interface Charitable_Email_Interface {

        /**
         * Returns the email's ID.
         *
         * @since  1.2.0
         *
         * @return string
         */
        public static function get_email_id();

        /**
         * Resend an email.
         *
         * @since  1.5.0
         *
         * @param  int   $object_id An object ID.
         * @param  array $args      Mixed set of arguments.
         * @return boolean
         */
        public static function resend( $object_id, $args = array() );

        /**
         * Checks whether an email can be resent.
         *
         * @since  1.5.0
         *
         * @param  int   $object_id An object ID.
         * @param  array $args      Mixed set of arguments.
         * @return boolean
         */
        public static function can_be_resent( $object_id, $args = array() );
    }

endif;
