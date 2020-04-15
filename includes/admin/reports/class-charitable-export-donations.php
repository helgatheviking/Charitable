<?php
/**
 * Class that is responsible for generating a CSV export of donations.
 *
 * @package   Charitable/Classes/Charitable_Export_Donations
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.25
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Export_Donations' ) ) :

	/**
	 * Charitable_Export_Donations
	 *
	 * @since 1.0.0
	 */
	class Charitable_Export_Donations extends Charitable_Export {

		/* The type of export. */
		const EXPORT_TYPE = 'donations';

		/**
		 * Default arguments.
		 *
		 * @since 1.0.0
		 *
		 * @var   mixed[]
		 */
		protected $defaults;

		/**
		 * List of donation statuses.
		 *
		 * @since 1.0.0
		 *
		 * @var   string[]
		 */
		protected $statuses;

		/**
		 * Exportable donation fields.
		 *
		 * @since 1.5.0
		 *
		 * @var   array
		 */
		protected $fields;

		/**
		 * Set Charitable_Donation objects.
		 *
		 * @since 1.5.0
		 *
		 * @var   Charitable_Donation[]
		 */
		protected $donations = array();

		/**
		 * Create class object.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed[] $args Arguments for the report.
		 */
		public function __construct( $args ) {
			$this->defaults = array(
				'start_date'  => '',
				'end_date'    => '',
				'campaign_id' => 'all',
				'status'      => 'all',
			);

			$this->statuses = charitable_get_valid_donation_statuses();
			$this->fields   = array_map( array( $this, 'get_field_label' ), charitable()->donation_fields()->get_export_fields() );

			parent::__construct( $args );
		}

		/**
		 * Filter the date and time fields.
		 *
		 * @since  1.0.0
		 *
		 * @param  mixed  $value The value to set.
		 * @param  string $key   The key to set.
		 * @param  array  $data  The set of data.
		 * @return mixed
		 */
		public function set_custom_field_data( $value, $key, $data ) {
			if ( array_key_exists( $key, $this->fields ) ) {
				$donation = $this->get_donation( $data['donation_id'] );
				$value    = charitable_get_sanitized_donation_field_value( $donation->get( $key ), $key );
			}

			return $value;
		}

		/**
		 * Return the CSV column headers.
		 *
		 * The columns are set as a key=>label array, where the key is used to retrieve the data for that column.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		protected function get_csv_columns() {
			$non_field_columns = array(
				'campaign_id'   => '',
				'campaign_name' => '',
				'amount'        => '',
			);

			$default_columns = array(
				'donation_id'     => __( 'Donation ID', 'charitable' ),
				'campaign_id'     => __( 'Campaign ID', 'charitable' ),
				'campaign_name'   => __( 'Campaign Title', 'charitable' ),
				'first_name'      => __( 'First Name', 'charitable' ),
				'last_name'       => __( 'Last Name', 'charitable' ),
				'email'           => __( 'Email', 'charitable' ),
				'address'         => __( 'Address', 'charitable' ),
				'address_2'       => __( 'Address 2', 'charitable' ),
				'city'            => __( 'City', 'charitable' ),
				'state'           => __( 'State', 'charitable' ),
				'postcode'        => __( 'Postcode', 'charitable' ),
				'country'         => __( 'Country', 'charitable' ),
				'phone'           => __( 'Phone Number', 'charitable' ),
				'donor_address'   => __( 'Address Formatted', 'charitable' ),
				'amount'          => __( 'Donation Amount', 'charitable' ),
				'date'            => __( 'Date of Donation', 'charitable' ),
				'time'            => __( 'Time of Donation', 'charitable' ),
				'status_label'    => __( 'Donation Status', 'charitable' ),
				'gateway_label'   => __( 'Donation Gateway', 'charitable' ),
				'test_mode'       => __( 'Made in Test Mode', 'charitable' ),
				'contact_consent' => __( 'Contact Consent', 'charitable' ),
			);

			/**
			 * Filter the list of columns in the export.
			 *
			 * As of Charitable 1.5, the recommended way to add or remove columns to the
			 * donation export is through the Donation Fields API. This filter is primarily
			 * provided for backwards compatibility and also provides a way to change export
			 * column headers without changing the label for the Donation Field.
			 *
			 * @since 1.0.0
			 *
			 * @param array $columns List of columns.
			 * @param array $args    Export args.
			 */
			$filtered = apply_filters( 'charitable_export_donations_columns', $default_columns, $this->args );

			return $this->get_merged_fields( $default_columns, $this->fields, $non_field_columns, $filtered );
		}

		/**
		 * Return a Donation object for the given ID.
		 *
		 * @since  1.5.0
		 *
		 * @param  int $donation_id The donation ID.
		 * @return Charitable_Donation
		 */
		protected function get_donation( $donation_id ) {
			if ( ! array_key_exists( $donation_id, $this->donations ) ) {
				$this->donations[ $donation_id ] = charitable_get_donation( $donation_id );
			}

			return $this->donations[ $donation_id ];
		}

		/**
		 * Get the data to be exported.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		protected function get_data() {
			$query_args = array();

			if ( strlen( $this->args['start_date'] ) ) {
				$query_args['start_date'] = charitable_sanitize_date( $this->args['start_date'], 'Y-m-d 00:00:00' );
			}

			if ( strlen( $this->args['end_date'] ) ) {
				$query_args['end_date'] = charitable_sanitize_date( $this->args['end_date'], 'Y-m-d 23:59:59' );
			}

			if ( 'all' != $this->args['campaign_id'] ) {
				$query_args['campaign_id'] = $this->args['campaign_id'];
			}

			if ( 'all' != $this->args['status'] ) {
				$query_args['status'] = $this->args['status'];
			}

			/**
			 * Filter name with misspelling.
			 *
			 * @deprecated 1.7.0
			 *
			 * @since 1.3.5
			 */
			$query_args = apply_filters( 'chairtable_export_donations_query_args', $query_args, $this->args );

			/**
			 * Filter donations query arguments.
			 *
			 * @since 1.3.5
			 *
			 * @param array $query_args The query arguments.
			 * @param array $args       The export arguments.
			 */
			$query_args = apply_filters( 'charitable_export_donations_query_args', $query_args, $this->args );

			return charitable_get_table( 'campaign_donations' )->get_donations_report( $query_args );
		}

		/**
		 * Return the field label for a registered Donation Field.
		 *
		 * @since  1.5.0
		 *
		 * @param  Charitable_Donation_Field $field Instance of `Charitable_Donation_Field`.
		 * @return string
		 */
		protected function get_field_label( Charitable_Donation_Field $field ) {
			return $field->label;
		}
	}

endif;
