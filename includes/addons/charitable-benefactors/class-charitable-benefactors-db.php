<?php
/**
 * Charitable Benefactors DB class.
 *
 * @package     Charitable/Classes/Charitable_Benefactors_DB
 * @version  	1.0.0
 * @author 		Eric Daams
 * @copyright 	Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Benefactors_DB' ) ) :

	/**
	 * Charitable_Benefactors_DB
	 *
	 * @since   1.0.0
	 */
	class Charitable_Benefactors_DB extends Charitable_DB {

		/**
		 * The version of our database table
		 *
		 * @since   1.0.0
		 */
		public $version = '1.0.0';

		/**
		 * The name of the primary column
		 *
		 * @since   1.0.0
		 */
		public $primary_key = 'campaign_benefactor_id';

		/**
		 * Set up the database table name.
		 *
		 * @since   1.0.0
		 */
		public function __construct() {
			global $wpdb;

			$this->table_name = $wpdb->prefix . 'charitable_benefactors';
		}

		/**
		 * Create the table.
		 *
		 * @global 	$wpdb
		 * @since   1.0.0
		 */
		public function create_table() {
			global $wpdb;

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
				`campaign_benefactor_id` bigint(20) NOT NULL AUTO_INCREMENT,
				`campaign_id` bigint(20) NOT NULL,
				`contribution_amount` float NOT NULL,
				`contribution_amount_is_percentage` tinyint(1) NOT NULL DEFAULT 0,
				`contribution_amount_is_per_item` tinyint(1) NOT NULL DEFAULT 0,
				`date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				`date_deactivated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				PRIMARY KEY (`campaign_benefactor_id`),
				KEY `campaign` (`campaign_id`),
				KEY `active_dates` (`date_created`, `date_deactivated`)
				) $charset_collate;";

			$this->_create_table( $sql );
		}

		/**
		 * Whitelist of columns.
		 *
		 * @since   1.0.0
		 *
		 * @return  array
		 */
		public function get_columns() {
			return array(
				'campaign_benefactor_id'			=> '%d',
				'campaign_id'						=> '%d',
				'contribution_amount'				=> '%f',
				'contribution_amount_is_percentage'	=> '%d',
				'contribution_amount_is_per_item'	=> '%d',
				'date_created'						=> '%s',
				'date_deactivated'					=> '%s',
			);
		}

		/**
		 * Default column values.
		 *
		 * @since   1.0.0
		 *
		 * @return 	array
		 */
		public function get_column_defaults() {
			return array(
				'contribution_amount_is_percentage'	=> 1,
				'contribution_amount_is_per_item'	=> 0,
				'date_created'						=> date( 'Y-m-d H:i:s' ),
				'date_deactivated'					=> '0000-00-00 00:00:00',
			);
		}

		/**
		 * Add a new benefactor object.
		 *
		 * @since   1.0.0
		 *
		 * @param 	array 	$data
		 * @return 	int 				Positive ID if successful. 0 if failed.
		 */
		public function insert( $data, $type = 'campaign_benefactor' ) {

			/* Allow plugins to filter the data before inserting to database */
			$data = apply_filters( 'charitable_benefactor_data', $data );

			/* An array detailing the benefactor must be provided. */
			if ( ! isset( $data['benefactor'] ) || ! is_array( $data['benefactor'] ) || empty( $data['benefactor'] ) ) {

				charitable_get_deprecated()->doing_it_wrong(
					__METHOD__,
					__( 'Campaign benefactors cannot be created without benefactor details.', 'charitable' ),
					'1.0.0'
				);

				return 0;

			}

			/* A contribution amount must be set */
			if ( empty( $data['contribution_amount'] ) || ! is_numeric( $data['contribution_amount'] ) ) {

				charitable_get_deprecated()->doing_it_wrong(
					__METHOD__,
					__( 'Campaign benefactors cannot be created without a contribution amount.', 'charitable' ),
					'1.0.0'
				);

				return 0;

			}

			/* Pull out the benefactor details. These are passed to the 3rd party plugins */
			$benefactor_details = $data['benefactor'];

			unset( $data['benefactor'] );

			/* Create the record */
			$campaign_benefactor_id = parent::insert( $data, $type );

			/* Allow plugins to hook into this event */
			do_action( 'charitable_benefactor_added', $campaign_benefactor_id, $benefactor_details, $data );

			return $campaign_benefactor_id;
		}

		/**
		 * Update a benefactor object.
		 *
		 * @since   1.0.0
		 *
		 * @param 	int 		$row_id
		 * @param 	array 		$data
		 * @param 	string 		$where 			Column used in where argument.
		 * @return 	boolean
		 */
		public function update( $row_id, $data = array(), $where = '' ) {

			/* Allow plugins to filter the data before inserting to database */
			$data = apply_filters( 'charitable_benefactor_data', $data );

			if ( isset( $data['benefactor'] ) ) {

				$benefactor_details = $data['benefactor'];

				unset( $data['benefactor'] );

				/* Allow plugins to hook into this event */
				do_action( 'charitable_benefactor_updated', $row_id, $benefactor_details, $where );
			}

			return parent::update( $row_id, $data, $where );
		}

		/**
		 * Delete a row identified by the primary key.
		 *
		 * @since   1.0.0
		 *
		 * @param 	int 		$row_id
		 * @return  bool
		 */
		public function delete( $row_id = 0 ) {

			/* Allow plugins to hook into this event */
			do_action( 'charitable_benefactor_deleted', $row_id );

			return parent::delete( $row_id );
		}

		/**
		 * Get all active benefactors for a campaign.
		 *
		 * @global 	WPDB 		$wpdb
		 * @since   1.0.0
		 *
		 * @param 	int 		$campaign_id
		 * @return 	Object[]
		 */
		public function get_campaign_benefactors( $campaign_id ) {
			global $wpdb;

			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT *
					FROM $this->table_name
					WHERE campaign_id = %d
					AND date_created < UTC_TIMESTAMP()
					AND ( date_deactivated = '0000-00-00 00:00:00' OR date_deactivated > UTC_TIMESTAMP() );",
					$campaign_id
				),
				OBJECT_K
			);
		}

		/**
		 * Get active benefactors for a campaign created through a specific extension.
		 *
		 * @since   1.0.0
		 *
		 * @param 	int 			$campaign_id
		 * @param 	string 			$extension
		 * @return 	Object[]|false 	False if extensions return nothing. Object otherwise.
		 */
		public function get_campaign_benefactors_by_extension( $campaign_id, $extension ) {
			return apply_filters( 'charitable_get_campaign_benefactors', false, $campaign_id, $extension );
		}
	}

endif;
