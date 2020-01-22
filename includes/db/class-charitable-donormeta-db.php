<?php
/**
 * Charitable Donormeta DB class.
 *
 * @package   Charitable/Classes/Charitable_Donormeta_DB
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.0
 * @version   1.6.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donormeta_DB' ) ) :

	/**
	 * Charitable_Donormeta_DB
	 *
	 * @since 1.6.0
	 */
	class Charitable_Donormeta_DB extends Charitable_DB {

		/**
		 * The version of our database table
		 *
		 * @since 1.6.0
		 *
		 * @var   string
		 */
		public $version = '1.6.0';

		/**
		 * The name of the primary column
		 *
		 * @since 1.6.0
		 *
		 * @var   string
		 */
		public $primary_key = 'meta_id';

		/**
		 * Set up the database table name.
		 *
		 * @since 1.6.0
		 *
		 * @global WPDB $wpdb
		 */
		public function __construct() {
			global $wpdb;

			$this->table_name = $wpdb->prefix . 'charitable_donormeta';
		}

		/**
		 * Create the table.
		 *
		 * @since 1.6.0
		 *
		 * @global WPDB $wpdb
		 */
		public function create_table() {
			global $wpdb;

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$this->table_name} (
                meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                donor_id bigint(20) unsigned NOT NULL DEFAULT '0',
                meta_key varchar(255) DEFAULT NULL,
                meta_value longtext,
				PRIMARY KEY  (meta_id),
				KEY donor_id (donor_id),
				KEY meta_key (meta_key(191))
				) $charset_collate;";

			$this->_create_table( $sql );
		}

		/**
		 * Whitelist of columns.
		 *
		 * @since  1.6.0
		 *
		 * @return array
		 */
		public function get_columns() {
			return array(
				'meta_id'    => '%d',
				'donor_id'   => '%d',
				'meta_key'   => '%s',
				'meta_value' => '%s',
			);
		}

		/**
		 * Default column values.
		 *
		 * @since  1.6.0
		 *
		 * @return array
		 */
		public function get_column_defaults() {
			return array(
				'meta_id'    => '',
				'donor_id'   => 0,
				'meta_key'   => '',
				'meta_value' => '',
			);
		}

		/**
		 * Add a new donor meta item.
		 *
		 * @since  1.6.0
		 *
		 * @param  array  $data Donor data to insert.
		 * @param  string $type Should always be 'donormeta'.
		 * @return int The ID of the inserted donor.
		 */
		public function insert( $data, $type = 'donormeta' ) {
			return parent::insert( $data, $type );
		}
	}

endif;
