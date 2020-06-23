<?php
/**
 * Charitable Donors DB class.
 *
 * @package   Charitable/Classes/Charitable_Donors_DB
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.5
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donors_DB' ) ) :

	/**
	 * Charitable_Donors_DB
	 *
	 * @since 1.0.0
	 */
	class Charitable_Donors_DB extends Charitable_DB {

		/**
		 * The version of our database table
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		public $version = '1.6.5';

		/**
		 * The name of the primary column
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		public $primary_key = 'donor_id';

		/**
		 * Columns found in the database.
		 *
		 * @since 1.6.5
		 *
		 * @var   array
		 */
		protected $db_columns;

		/**
		 * Set up the database table name.
		 *
		 * @since 1.0.0
		 *
		 * @global WPDB $wpdb
		 */
		public function __construct() {
			global $wpdb;

			$this->table_name = $wpdb->prefix . 'charitable_donors';
		}

		/**
		 * Create the table.
		 *
		 * @since 1.0.0
		 *
		 * @global WPDB $wpdb
		 */
		public function create_table() {
			global $wpdb;

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$this->table_name} (
				donor_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				email varchar(100) NOT NULL,
				first_name varchar(255) default '',
				last_name varchar(255) default '',
				date_joined datetime NOT NULL default '0000-00-00 00:00:00',
				data_erased datetime default '0000-00-00 00:00:00',
				contact_consent tinyint(1) unsigned default NULL,
				PRIMARY KEY  (donor_id),
				KEY user_id (user_id),
				KEY email (email),
				KEY data_erased (data_erased),
				KEY contact_consent (contact_consent)
				) $charset_collate;";

			$this->_create_table( $sql );
		}

		/**
		 * Return the list of tables that currently exist in the database.
		 *
		 * This allows us to handle backwards compatibility in cases where
		 * columns have been added to the table structure, but have not yet
		 * been added in the database.
		 *
		 * @since  1.6.5
		 *
		 * @global WPDB $wpdb
		 * @return array
		 */
		public function get_db_columns() {
			if ( ! isset( $this->db_columns ) ) {
				global $wpdb;

				$columns          = $wpdb->get_results( "SHOW COLUMNS FROM $this->table_name" );
				$this->db_columns = $columns ? wp_list_pluck( $columns, 'Field' ) : array();
			}

			return $this->db_columns;
		}

		/**
		 * Whitelist of columns.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_columns() {
			$columns = array(
				'donor_id'        => '%d',
				'user_id'         => '%d',
				'email'           => '%s',
				'first_name'      => '%s',
				'last_name'       => '%s',
				'date_joined'     => '%s',
				'data_erased'     => '%s',
				'contact_consent' => '%d',
			);

			return array_intersect_key( $columns, array_flip( $this->get_db_columns() ) );
		}

		/**
		 * Default column values.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_column_defaults() {
			$defaults = array(
				'user_id'     => 0,
				'date_joined' => date( 'Y-m-d H:i:s' ),
			);

			return array_intersect_key( $defaults, array_flip( $this->get_db_columns() ) );
		}

		/**
		 * Add a new donor.
		 *
		 * @since  1.0.0
		 *
		 * @param  array  $data Donor data to insert.
		 * @param  string $type Should always be 'donors'.
		 * @return int The ID of the inserted donor.
		 */
		public function insert( $data, $type = 'donors' ) {
			if ( ! $this->validate_donor_email( $data ) ) {
				charitable_get_notices()->add_error(
					__( 'Unable to insert donor. This email address is already in use.', 'charitable' )
				);
				return 0;
			}

			$donor_id = parent::insert( $data, $type );

			$this->maybe_log_consent( $data, $donor_id );

			return $donor_id;
		}

		/**
		 * Update a donor record.
		 *
		 * @since  1.0.0
		 *
		 * @param  int    $row_id The record to update.
		 * @param  array  $data   Donor data to update.
		 * @param  string $where  Column used in where argument.
		 * @return boolean Whether the donor record was updated.
		 */
		public function update( $row_id, $data = array(), $where = 'donor_id' ) {
			if ( ! $this->validate_donor_email( $data, $row_id, $where ) ) {
				charitable_get_notices()->add_error(
					__( 'Unable to update donor. This email address is already in use.', 'charitable' )
				);
				return false;
			}

			$updated = parent::update( $row_id, $data, $where );

			if ( $updated ) {
				$this->maybe_log_consent( $data, $row_id, $where );
			}

			return $updated;
		}

		/**
		 * Validate a donor's email.
		 *
		 * @since  1.6.2
		 *
		 * @param  array  $data   The donor data.
		 * @param  mixed  $row_id The id of the donor record to update.
		 * @param  string $where  Column used in where argument.
		 * @return boolean
		 */
		public function validate_donor_email( $data, $row_id = 0, $where = 'donor_id' ) {
			if ( ! array_key_exists( 'email', $data ) ) {
				return true;
			}

			/* We're anonymizing their email address. */
			if ( $this->is_anonymized_email( $data['email'] ) ) {
				return true;
			}

			$email_donor_id = $this->get_donor_id_by_email( $data['email'] );

			/* No one else has this email address. */
			if ( 0 == $email_donor_id ) {
				return true;
			}

			/* This is a new donor, but the email address already belongs to another. */
			if ( 0 == $row_id ) {
				return false;
			}

			/* For an existing donor, the email address isn't being changed. */
			return $this->get_column_by( 'email', $where, $row_id ) == $data['email'];
		}

		/**
		 * Log consent after a donor record has been added or updated, if contact_consent
		 * was explicitly set.
		 *
		 * @since  1.6.2
		 *
		 * @param  array  $data   The donor data.
		 * @param  mixed  $row_id The id of the donor record to update.
		 * @param  string $where  Column used in where argument.
		 * @return boolean Whether consent was logged.
		 */
		public function maybe_log_consent( $data, $row_id, $where = 'donor_id' ) {
			if ( ! array_key_exists( 'contact_consent', $data ) || is_null( $data['contact_consent'] ) ) {
				return false;
			}

			$donor_id = 'donor_id' == $where ? $row_id : $this->get_column_by( 'donor_id', $where, $row_id );
			$log      = new Charitable_Donor_Consent_Log( $donor_id );

			return $log->add(
				$data['contact_consent'],
				charitable_get_option( 'contact_consent_label', __( 'Yes, I am happy for you to contact me via email or phone.', 'charitable' ) )
			);
		}

		/**
		 * Return a user's ID, based on their donor ID.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $donor_id The Donor ID.
		 * @return int
		 */
		public function get_user_id( $donor_id ) {
			$user_id = $this->get_column_by( 'user_id', 'donor_id', $donor_id );

			return is_null( $user_id ) ? 0 : (int) $user_id;
		}

		/**
		 * Return a donor ID, based on their user ID.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $user_id Donor User ID.
		 * @return int
		 */
		public function get_donor_id( $user_id ) {
			$donor_id = $this->get_column_by( 'donor_id', 'user_id', $user_id );

			return is_null( $donor_id ) ? 0 : (int) $donor_id;
		}

		/**
		 * Return a donor ID, based on their email address.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $email Donor email address.
		 * @return int
		 */
		public function get_donor_id_by_email( $email ) {
			$donor_id = $this->get_column_by( 'donor_id', 'email', $email );

			return is_null( $donor_id ) ? 0 : (int) $donor_id;
		}

		/**
		 * Return a donor's personal data, given their email address.
		 *
		 * @since  1.6.0
		 *
		 * @global $wpdb WPDB
		 * @param  string $email Donor email address.
		 * @return array|object|null Database query results
		 */
		public function get_personal_data( $email ) {
			global $wpdb;

			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT donor_id, email, first_name, last_name FROM {$this->table_name} WHERE email = %s;",
					$email
				)
			);
		}

		/**
		 * Count the number of donors with donations.
		 *
		 * @since  1.3.4
		 *
		 * @global WPDB $wpdb
		 * @param  string|array $statuses One or more donation statuses.
		 * @return int
		 */
		public function count_donors_with_donations( $statuses = array( 'charitable-completed' ) ) {
			global $wpdb;

			if ( ! is_array( $statuses ) ) {
				$statuses = array( $statuses );
			}

			if ( empty( $statuses ) ) {
				$status_clause = '';
			} else {
				$statuses 	   = array_filter( $statuses, 'charitable_is_valid_donation_status' );
				$placeholders  = array_fill( 0, count( $statuses ), '%s' );
				$in 		   = implode( ', ', $placeholders );
				$status_clause = "AND p.post_status IN ( $in )";
			}

			$sql = "SELECT COUNT( DISTINCT(d.donor_id) )
				FROM {$wpdb->prefix}charitable_donors d
				INNER JOIN {$wpdb->prefix}charitable_campaign_donations cd ON cd.donor_id = d.donor_id
				INNER JOIN $wpdb->posts p ON cd.donation_id = p.ID
				WHERE 1 = 1
				$status_clause;";

			return $wpdb->get_var( $wpdb->prepare( $sql, $statuses ) );
		}

		/**
		 * Erase personal data for given donor IDs.
		 *
		 * @since  1.6.0
		 *
		 * @param  int|int[] $donor_id The donor IDs.
		 * @return int|false Number of rows affected/selected or false on error.
		 */
		public function erase_donor_data( $donor_id ) {
			global $wpdb;

			if ( ! is_array( $donor_id ) ) {
				$donor_id = array( $donor_id );
			}

			/* Filter out any non absolute integers. */
			$donor_id = array_filter( $donor_id, 'absint' );

			if ( empty( $donor_id ) ) {
				return false;
			}

			$placeholders = charitable_get_query_placeholders( count( $donor_id ), '%d' );

			if ( in_array( 'data_erased', $this->get_db_columns() ) ) {

				$parameters = array_merge(
					array(
						wp_privacy_anonymize_data( 'email' ),
						current_time( 'mysql', 0 ),
					),
					$donor_id
				);

				$sql = "UPDATE {$this->table_name}
					SET email = %s, first_name = '', last_name = '', data_erased = %s
					WHERE donor_id IN ( $placeholders )";

			} else {

				$parameters = array_merge(
					array(
						wp_privacy_anonymize_data( 'email' ),
					),
					$donor_id
				);

				$sql = "UPDATE {$this->table_name}
					SET email = %s, first_name = '', last_name = ''
					WHERE donor_id IN ( $placeholders )";

			}

			return $wpdb->query( $wpdb->prepare( $sql, $parameters ) );
		}

		/**
		 * Checks whether the passed email is an anonymized email address.
		 *
		 * @since  1.6.4
		 *
		 * @param  string $email The email address.
		 * @return boolean
		 */
		private function is_anonymized_email( $email ) {
			return function_exists( 'wp_privacy_anonymize_data' ) && wp_privacy_anonymize_data( 'email' ) == $email;
		}
	}

endif;
