<?php
/**
 * Contains the class that is used to register and retrieve notices in the admin like errors, warnings, success messages, etc.
 *
 * @package   Charitable/Classes/Charitable_Admin_Notices
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.4.6
 * @version   1.6.24
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Admin_Notices' ) ) :

	/**
	 * Charitable_Admin_Notices
	 *
	 * @since 1.4.6
	 */
	class Charitable_Admin_Notices extends Charitable_Notices {

		/**
		 * Whether the script has been enqueued.
		 *
		 * @since 1.4.6
		 *
		 * @var   boolean
		 */
		private $script_enqueued;

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.4.6
		 *
		 * @return Charitable_Admin_Notices
		 */
		public static function get_instance() {
			return charitable()->registry()->get( 'admin_notices' );
		}

		/**
		 * Create class object. A private constructor, so this is used in a singleton context.
		 *
		 * @since 1.4.6
		 * @since 1.5.4 Access changed to public.
		 */
		public function __construct() {
			$this->load_notices();
		}

		/**
		 * Adds a notice message.
		 *
		 * @since  1.4.6
		 *
		 * @param  string $message
		 * @param  string $type
		 * @param  string $key     Optional. If not set, next numeric key is used.
		 * @return void
		 */
		public function add_notice( $message, $type, $key = false, $dismissible = false ) {
			if ( false === $key ) {
				$this->notices[ $type ][] = array(
					'message'     => $message,
					'dismissible' => $dismissible,
				);
			} else {
				$this->notices[ $type ][ $key ] = array(
					'message'     => $message,
					'dismissible' => $dismissible,
				);
			}
		}

		/**
		 * Adds an error message.
		 *
		 * @since  1.4.6
		 *
		 * @param  string $message
		 * @param  string $key     Optional. If not set, next numeric key is used.
		 * @return void
		 */
		public function add_error( $message, $key = false, $dismissible = false ) {
			$this->add_notice( $message, 'error', $key, $dismissible );
		}

		/**
		 * Adds a warning message.
		 *
		 * @since  1.4.6
		 *
		 * @param  string $message
		 * @param  string $key     Optional. If not set, next numeric key is used.
		 * @return void
		 */
		public function add_warning( $message, $key = false, $dismissible = false ) {
			$this->add_notice( $message, 'warning', $key, $dismissible );
		}

		/**
		 * Adds a success message.
		 *
		 * @since  1.4.6
		 *
		 * @param  string $message
		 * @param  string $key     Optional. If not set, next numeric key is used.
		 * @return void
		 */
		public function add_success( $message, $key = false, $dismissible = false ) {
			$this->add_notice( $message, 'success', $key, $dismissible );
		}

		/**
		 * Adds an info message.
		 *
		 * @since  1.4.6
		 *
		 * @param  string $message
		 * @param  string $key     Optional. If not set, next numeric key is used.
		 * @return void
		 */
		public function add_info( $message, $key = false, $dismissible = false ) {
			$this->add_notice( $message, 'info', $key, $dismissible );
		}

		/**
		 * Adds a version update message.
		 *
		 * @since  1.4.6
		 *
		 * @param  string  $message
		 * @param  string  $key         Optional. If not set, next numeric key is used.
		 * @param  boolean $dismissible Optional. Set to true by default.
		 * @return void
		 */
		public function add_version_update( $message, $key = false, $dismissible = true ) {
			$this->add_notice( $message, 'version', $key, $dismissible );
		}

		/**
		 * Render notices.
		 *
		 * @since  1.4.6
		 *
		 * @return void
		 */
		public function render() {
			foreach ( charitable_get_admin_notices()->get_notices() as $type => $notices ) {
				foreach ( $notices as $key => $notice ) {
					$this->render_notice( $notice['message'], $type, $notice['dismissible'], $key );
				}
			}
		}

		/**
		 * Render a notice.
		 *
		 * @since  1.4.6
		 *
		 * @param  string  $notice
		 * @param  string  $type
		 * @param  boolean $dismissible
		 * @param  string  $notice_key
		 * @return void
		 */
		public function render_notice( $notice, $type, $dismissible = false, $notice_key = '' ) {
			if ( ! isset( $this->script_enqueued ) ) {
				if ( ! wp_script_is( 'charitable-admin-notice' ) ) {
					wp_enqueue_script( 'charitable-admin-notice' );
				}

				$this->script_enqueued = true;
			}

			$class = 'notice charitable-notice';

			switch ( $type ) {
				case 'error':
					$class .= ' notice-error';
					break;

				case 'warning':
					$class .= ' notice-warning';
					break;

				case 'success':
					$class .= ' updated';
					break;

				case 'info':
					$class .= ' notice-info';
					break;

				case 'version':
					$class .= ' charitable-upgrade-notice';
					break;
			}

			if ( $dismissible ) {
				$class .= ' is-dismissible';
			}

			printf(
				'<div class="%s" %s><p>%s</p></div>',
				esc_attr( $class ),
				strlen( $notice_key ) ? 'data-notice="' . esc_attr( $notice_key ) . '"' : '',
				$notice
			);

			if ( strlen( $notice_key ) ) {
				unset( $this->notices[ $type ][ $notice_key ] );
			}
		}

		/**
		 * When PHP finishes executing, stash any notices that haven't been rendered yet.
		 *
		 * @since  1.4.13
		 *
		 * @return void
		 */
		public function shutdown() {
			set_transient( 'charitable_notices', $this->notices );
		}

		/**
		 * Load the notices array.
		 *
		 * If there are any stuffed in a transient, pull those out. Otherwise, reset a clear array.
		 *
		 * @since  1.4.13
		 *
		 * @return void
		 */
		public function load_notices() {
			$this->notices = get_transient( 'charitable_notices' );

			if ( ! is_array( $this->notices ) ) {
				$this->clear();
			}
		}

		/**
		 * Fill admin notices from the front-end notices array.
		 *
		 * @since  1.6.24
		 *
		 * @return void
		 */
		public function fill_notices_from_frontend() {
			$notices = charitable_get_notices();

			foreach ( $notices->get_notices() as $type => $type_notices ) {
				foreach ( $type_notices as $notice ) {
					$this->add_notice( $notice, $type );
				}
			}

			$notices->clear();
		}

		/**
		 * Clear out all existing notices.
		 *
		 * @since  1.4.6
		 *
		 * @return void
		 */
		public function clear() {
			$clear = array(
				'error'   => array(),
				'warning' => array(),
				'success' => array(),
				'info'    => array(),
				'version' => array(),
			);

			$this->notices = $clear;
		}
	}

endif;
