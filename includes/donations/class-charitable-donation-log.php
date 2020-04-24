<?php
/**
 * Responsible for getting and updating a donation's log.
 *
 * @package   Charitable/Classes/Charitable_Donation_Log
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.4
 * @version   1.6.37
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donation_Log' ) ) :

	/**
	 * Charitable_Donation_Log
	 *
	 * @since 1.5.4
	 */
	class Charitable_Donation_Log {

		/**
		 * The donation ID.
		 *
		 * @since 1.5.4
		 *
		 * @var   int
		 */
		private $donation_id;

		/**
		 * The log.
		 *
		 * @since 1.5.4
		 *
		 * @var   array
		 */
		private $log;

		/**
		 * The meta log.
		 *
		 * @since 1.5.4
		 *
		 * @var   array
		 */
		private $meta_log;

		/**
		 * The email logs.
		 *
		 * @since 1.5.4
		 *
		 * @var   array
		 */
		private $email_logs;

		/**
		 * Create class object.
		 *
		 * @since 1.5.4
		 *
		 * @param int $donation_id The donation ID.
		 */
		public function __construct( $donation_id ) {
			$this->donation_id = $donation_id;
		}

		/**
		 * Add a meta log.
		 *
		 * @since  1.5.4
		 *
		 * @param  string $message The message to be logged.
		 * @return int|bool Meta ID if the key didn't exist, true on successful update,
		 *                  false on failure.
		 */
		public function add( $message ) {
			$log = $this->get_meta_log();

			array_push(
				$log,
				array(
					'time'    => time(),
					'message' => $message,
				)
			);

			$ret = update_post_meta( $this->donation_id, '_donation_log', $log );

			/* Clear the meta_log */
			unset(
				$this->meta_log,
				$this->log
			);

			return $ret;
		}

		/**
		 * Return the raw meta log.
		 *
		 * This is stored as post meta with the `_donation_log` meta key.
		 *
		 * @since  1.5.4
		 *
		 * @return array
		 */
		public function get_meta_log() {
			if ( ! isset( $this->meta_log ) ) {
				$this->meta_log = get_post_meta( $this->donation_id, '_donation_log', true );

				if ( ! is_array( $this->meta_log ) ) {
					$this->meta_log = array();
				}
			}

			return $this->meta_log;
		}

		/**
		 * Get all email sending logs.
		 *
		 * @since  1.5.4
		 *
		 * @return array
		 */
		public function get_email_logs() {
			if ( ! isset( $this->email_logs ) ) {
				$logs             = array_filter( $this->query_email_logs(), array( $this, 'is_valid_email_log' ) );
				$this->email_logs = array();

				foreach ( $logs as $log ) {
					$this->email_logs = array_merge( $this->email_logs, $this->parse_email_log( $log ) );
				}
			}

			return $this->email_logs;
		}

		/**
		 * Returns all logs combined into a single array, ordered by time.
		 *
		 * @since  1.5.4
		 *
		 * @return array
		 */
		public function get_log() {
			if ( ! isset( $this->log ) ) {
				$this->log = array_merge_recursive( $this->get_meta_log(), $this->get_email_logs() );

				/**
				 * Filter the list of donation logs.
				 *
				 * @since 1.5.0
				 *
				 * @param array               $logs     All of the logs.
				 * @param Charitable_Donation $donation Instance of `Charitable_Donation`.
				 */
				$this->log = apply_filters( 'charitable_donation_logs', $this->log, charitable_get_donation( $this->donation_id ) );

				/* Order the logs by time. */
				usort( $this->log, 'charitable_timestamp_sort' );
			}

			return $this->log;
		}

		/**
		 * Executes a query to retrieve all email logs.
		 *
		 * @since  1.5.4
		 *
		 * @global WPDB $wpdb
		 * @return array
		 */
		private function query_email_logs() {
			global $wpdb;

			$logs = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT meta_key, meta_value
					FROM $wpdb->postmeta
					WHERE meta_key LIKE %s
					AND post_id = %d",
					'_email_%_log',
					$this->donation_id
				)
			);

			if ( is_null( $logs ) ) {
				$logs = array();
			}

			return $logs;
		}

		/**
		 * Receives an email log record as an object and returns an array
		 * with the time and message to show in the log.
		 *
		 * @since  1.5.0
		 *
		 * @param  object $log The email log record.
		 * @return array
		 */
		private function parse_email_log( $log ) {
			$email      = $this->get_email_log_email_id( $log );
			$email_name = $this->get_email_name( $email );
			$action     = $this->get_email_action( $email );
			$logs       = array();

			foreach ( maybe_unserialize( $log->meta_value ) as $time => $sent ) {
				$logs[] = array(
					'time'    => $time,
					'message' => $this->get_email_log_message( $sent, $email_name, $action ),
				);
			}

			return $logs;
		}

		/**
		 * Checks whether the email log record is valid.
		 *
		 * @since  1.5.4
		 *
		 * @param  object $log An email log object containing meta_key and meta_value properties.
		 * @return boolean
		 */
		private function is_valid_email_log( $log ) {
			return $this->log_has_required_props( $log ) && $this->is_valid_email_log_value( $log );
		}

		/**
		 * Checks whether the email log has the required properties.
		 *
		 * @since  1.5.4
		 *
		 * @param  object $log An email log.
		 * @return boolean
		 */
		private function log_has_required_props( $log ) {
			return isset( $log->meta_key ) && isset( $log->meta_value );
		}

		/**
		 * Returns whether the log is valid.
		 *
		 * @since  1.5.4
		 *
		 * @param  object $log An email log.
		 * @return boolean
		 */
		private function is_valid_email_log_value( $log ) {
			return is_array( maybe_unserialize( $log->meta_value ) );
		}

		/**
		 * Returns the email log's email id.
		 *
		 * @since  1.5.4
		 *
		 * @param  object $log An email log.
		 * @return string|false The string email ID if log key has a match, or false if not.
		 */
		private function get_email_log_email_id( $log ) {
			$found = preg_match( '/^_email_(.*)_log$/', $log->meta_key, $matches );
			$email = $found ? $matches[1] : false;

			/**
			 * Filter the email id we've guessed from the log.
			 *
			 * @since 1.5.6
			 *
			 * @param string $email    The email id we've come up with.
			 * @param string $meta_key The email log's meta key.
			 */
			return apply_filters( 'charitable_email_id_from_log', $email, $log->meta_key );
		}

		/**
		 * Return the name of the email.
		 *
		 * If the email still exists in the system as a registered email type, the official name of the
		 * email will be used. If it doesn't exist, such as in cases where the email was sent by an
		 * extension which is no long installed, the email ID will be formatted into a readable format.
		 *
		 * @since  1.5.4
		 *
		 * @param  string $email Email ID.         * @return string
		 */
		private function get_email_name( $email ) {
			$class = Charitable_Emails::get_instance()->get_email( $email );

			/* Fallback to formatting the email ID as readable text. */
			if ( false === $class ) {
				return ucwords( str_replace( '_', ' ', $email ) );
			}

			$object = new $class;

			return $object->get_name();
		}

		/**
		 * Return the email resend action.
		 *
		 * If an email no longer exists, such as in cases where the email was sent by an extension which
		 * is no long installed, false will be returned.
		 *
		 * @since  1.5.4
		 *
		 * @param  string $email Email ID.
		 * @return string|false The email action link, or false if no action can be taken.
		 */
		private function get_email_action( $email ) {
			if ( false === Charitable_Emails::get_instance()->get_email( $email ) ) {
				return false;
			}

			return Charitable_Admin::get_instance()->get_donation_actions()->get_action_link( 'resend_' . $email, $this->donation_id );
		}

		/**
		 * Return an email log message.
		 *
		 * @since  1.5.4
		 *
		 * @param  boolean      $sent       Whether the email was sent.
		 * @param  string       $email_name The email name.
		 * @param  string|false $action     Action link if available, or false if not.
		 * @return string
		 */
		private function get_email_log_message( $sent, $email_name, $action ) {
			if ( $sent ) {
				return $this->get_sent_email_log_message( $email_name, $action );
			}

			return $this->get_unsent_email_log_message( $email_name, $action );
		}

		/**
		 * Return an email log message for a sent email.
		 *
		 * @since  1.5.4
		 *
		 * @param  string       $email_name The email name.
		 * @param  string|false $action     Action link if available, or false if not.
		 * @return string
		 */
		private function get_sent_email_log_message( $email_name, $action ) {
			$message = sprintf( __( '%s was sent successfully.', 'charitable' ), $email_name );

			if ( false !== $action ) {
				$message .= sprintf( '&nbsp;<a href="%s">%s</a>', $action, __( 'Resend it now', 'charitable' ) );
			}

			return $message;
		}

		/**
		 * Return an email log message for an unsent email.
		 *
		 * @since  1.5.4
		 *
		 * @param  string       $email_name The email name.
		 * @param  string|false $action     Action link if available, or false if not.
		 * @return string
		 */
		private function get_unsent_email_log_message( $email_name, $action ) {
			$message = sprintf( __( '%s failed to send.', 'charitable' ), $email_name );

			if ( false !== $action ) {
				$message .= sprintf( '&nbsp;<a href="%s">%s</a>', $action, __( 'Retry email send', 'charitable' ) );
			}

			return $message;
		}
	}

endif;
