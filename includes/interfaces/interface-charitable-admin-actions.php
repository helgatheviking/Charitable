<?php
/**
 * Admin actions interface.
 *
 * @package   Charitable/Interfaces/Charitable_Admin_Actions_Interface
 * @version   1.5.0
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! interface_exists( 'Charitable_Admin_Actions_Interface' ) ) :

    /**
     * Charitable_Admin_Actions_Interface
     *
     * @since 1.5.0
     */
    interface Charitable_Admin_Actions_Interface {

        /**
         * Return the action type.
         *
         * This will be used to construct the hook that runs the actual actions, and is
         * required to differentiate between actions with the same key added by different
         * implementation classes.
         *
         * @since  1.5.0
         *
         * @return string
         */
        public function get_type();

        /**
         * Register a new action.
         *
         * @since  1.5.0
         *
         * @param  string $action The action key.
         * @param  array  $args   {
         *     Array of arguments for the action.
         *
         *     @type string   $label           The label to display in the admin.
         *     @type callable $callback        A callback function to run when the action is processed.
         *     @type string   $button_text     Optional. The text to show in the button when this action is selected.
         *     @type callable $active_callback Optional. Any passed callback will receive a donation ID as its only parameter
         *                                     and should return a boolean result: TRUE if the action should be shown for
         *                                     the donation; FALSE if it should not.
         * }
         * @param  string $group  Optional. If set, action will be added to a group of other related actions, which will be
         *                        shown as an optgroup.
         * @return boolean True if the action was registerd. False if not.
         */
        public function register( $action, $args, $group = 'default' );

        /**
         * Do a particular action.
         *
         * @since  1.5.0
         *
         * @param  string $action    The action to do.
         * @param  int    $object_id The object ID. This could be the ID of the donation, campaign, donor, etc.
         * @param  array  $args      Optional. Mixed set of arguments.
         * @return mixed|WP_Error WP_Error in case of error. Mixed results if the action was performed.
         */
        public function do_action( $action, $object_id, $args = array() );
    }

endif;
