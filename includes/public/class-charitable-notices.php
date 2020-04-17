<?php
/**
 * Contains the class that is used to register and retrieve notices like errors, warnings, success messages, etc.
 *
 * @package   Charitable/Classes/Charitable_Notices
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.37
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Notices' ) ) :

	/**
	 * Charitable_Notices
	 *
	 * @since 1.0.0
	 */
	class Charitable_Notices {

		/**
		 * The array of notices.
		 *
		 * @since 1.0.0
		 *
		 * @var   array
		 */
		protected $notices;

		/**
		 * Create class object.
		 *
		 * @since 1.0.0
		 * @since 1.5.4 Access changed to public.
		 */
		public function __construct() {
			/* Retrieve the notices from the session */
			$this->notices = charitable_get_session()->get_notices();

			/* Remove the notices from the session. */
			charitable_get_session()->remove( 'notices' );
		}

		/**
		 * Returns the single instance of this class.
		 *
		 * @since  1.0.0
		 *
		 * @return Charitable_Notices
		 */
		public static function get_instance() {
			return charitable()->registry()->get( 'notices' );
		}

		/**
		 * Adds a notice message.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $message The message to display.
		 * @param  string $type    The type of message.
		 * @param  string $key     Optional. If not set, next numeric key is used.
		 * @return void
		 */
		public function add_notice( $message, $type, $key = false ) {
			/* Avoid adding the same notice twice. */
			if ( in_array( $message, $this->notices[ $type ] ) ) {
				return;
			}

			if ( false === $key ) {
				$this->notices[ $type ][] = $message;
			} else {
				$this->notices[ $type ][ $key ] = $message;
			}
		}

		/**
		 * Add multiple notices at once.
		 *
		 * @since  1.0.0
		 *
		 * @param  array  $messages Array of messages.
		 * @param  string $type     Type of message we're adding.
		 * @return void
		 */
		public function add_notices( $messages, $type ) {
			if ( ! is_array( $messages ) ) {
				$messages = array( $messages );
			}

			$this->notices[ $type ] = array_merge( $this->notices[ $type ], $messages );
		}

		/**
		 * Adds an error message.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $message The error message to add.
		 * @param  string $key     Optional. If not set, next numeric key is used.
		 * @return void
		 */
		public function add_error( $message, $key = false ) {
			$this->add_notice( $message, 'error', $key );
		}

		/**
		 * Adds a warning message.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $message The warning message to add.
		 * @param  string $key     Optional. If not set, next numeric key is used.
		 * @return void
		 */
		public function add_warning( $message, $key = false ) {
			$this->add_notice( $message, 'warning', $key );
		}

		/**
		 * Adds a success message.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $message The success message to add.
		 * @param  string $key     Optional. If not set, next numeric key is used.
		 * @return void
		 */
		public function add_success( $message, $key = false ) {
			$this->add_notice( $message, 'success', $key );
		}

		/**
		 * Adds an info message.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $message The info message to add.
		 * @param  string $key     Optional. If not set, next numeric key is used.
		 * @return void
		 */
		public function add_info( $message, $key = false ) {
			$this->add_notice( $message, 'info', $key );
		}

		/**
		 * Receives a WP_Error object and adds the error messages to our array.
		 *
		 * @since  1.0.0
		 *
		 * @param  WP_Error $error The WP_Error object to add to the messages queue.
		 * @return void
		 */
		public function add_errors_from_wp_error( WP_Error $error ) {
			$this->add_notices( $error->get_error_messages(), 'error' );
		}

		/**
		 * Return all errors as an array.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_errors() {
			return $this->notices['error'];
		}

		/**
		 * Return all warnings as an array.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_warnings() {
			return $this->notices['warning'];
		}

		/**
		 * Return all successs as an array.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_success_notices() {
			return $this->notices['success'];
		}

		/**
		 * Return all infos as an array.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_info_notices() {
			return $this->notices['info'];
		}

		/**
		 * Return all notices as an array.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_notices() {
			return $this->notices;
		}

		/**
		 * Remove a notice, based on the text of the notice.
		 *
		 * @since  1.6.37
		 *
		 * @param  string $text The text to search for. Regex supported.
		 * @return void
		 */
		public function clear_notice( $text ) {
			foreach ( $this->notices as $type => $notices ) {
				foreach ( $notices as $i => $notice_text ) {
					if ( $notice_text === $text || preg_match( $text, $notice_text ) ) {
						unset( $this->notices[ $type ][ $i ] );
					}
				}
			}

			charitable_get_session()->set( 'notices', $this->notices );
		}

		/**
		 * Clear notices by type.
		 *
		 * @since  1.6.37
		 *
		 * @param  string $type The type of notice.
		 * @return void
		 */
		public function clear_notices_by_type( $type ) {
			if ( array_key_exists( $type, $this->notices ) ) {
				$this->notices[ $type ] = array();
			}

			charitable_get_session()->set( 'notices', $this->notices );
		}

		/**
		 * Clear out all existing notices.
		 *
		 * @since  1.4.0
		 *
		 * @return void
		 */
		public function clear() {
			$clear = array(
				'error'   => array(),
				'warning' => array(),
				'success' => array(),
				'info'    => array(),
			);

			$this->notices = $clear;

			charitable_get_session()->set( 'notices', $clear );
		}
	}

endif;
