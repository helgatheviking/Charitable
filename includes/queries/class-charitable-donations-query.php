<?php
/**
 * A helper class to retrieve Donations.
 *
 * @package   Charitable/Classes/Charitable_Donations_Query
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.4.0
 * @version   1.6.8
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donations_Query' ) ) :

	/**
	 * Charitable_Donations_Query
	 *
	 * @since 1.4.0
	 */
	class Charitable_Donations_Query extends Charitable_Query {

		/**
		 * Create class object.
		 *
		 * @since 1.4.0
		 *
		 * @param array $args Arguments used in query.
		 */
		public function __construct( $args = array() ) {
			$defaults = array(
				// Use 'posts' to get standard post objects.
				'output'        => 'donations',
				// Set to an array with statuses to only show certain statuses.
				'status'        => false,
				// Currently only supports 'date'.
				'orderby'       => 'date',
				// May be 'DESC' or 'ASC'.
				'order'         => 'DESC',
				// Number of donations to retrieve.
				'number'        => 20,
				// For paged results.
				'paged'         => 1,
				// Only get donations for a specific campaign.
				'campaign'      => 0,
				// Only get donations by a specific donor.
				'donor_id'      => 0,
				// Only get donations by a specific user.
				'user_id'       => 0,
				// Donation plan is essentially post_parent.
				'donation_plan' => 0,
				// Filter donations by date.
				'date_query'    => array(),
				// Filter donations by meta.
				'meta_query'    => array(),
			);

			$this->args = wp_parse_args( $args, $defaults );

			$this->position = 0;
			$this->prepare_query();
			$this->results = $this->get_donations();
		}

		/**
		 * Return list of donation IDs together with the number of donations they have made.
		 *
		 * @since  1.4.0
		 *
		 * @return object[]
		 */
		public function get_donations() {
			$records = $this->query();

			/* Return the raw records. */
			if ( in_array( $this->get( 'output' ), array( 'count', 'ids' ) ) ) {
				return $records;
			}

			/* Return Donations objects. */
			if ( 'donations' == $this->get( 'output' ) ) {
				return array_map( 'charitable_get_donation', wp_list_pluck( $records, 'ID' ) );
			}

			$currency_helper = charitable_get_currency_helper();

			/**
			 * When the currency uses commas for decimals and periods for thousands,
			 * the amount returned from the database needs to be sanitized.
			 */
			if ( $currency_helper->is_comma_decimal() ) {

				foreach ( $records as $i => $row ) {
					$records[ $i ]->amount = $currency_helper->sanitize_database_amount( $row->amount );
				}
			}

			return $records;
		}

		/**
		 * Set up fields query argument.
		 *
		 * @since  1.4.0
		 *
		 * @return void
		 */
		public function setup_fields() {
			/* If we are returning IDs or Donation objects, we only need to return the donation IDs. */
			if ( in_array( $this->get( 'output' ), array( 'donations', 'ids' ) ) ) {
				return;
			}

			add_filter( 'charitable_query_fields', array( $this, 'donation_fields' ), 4 );
			add_filter( 'charitable_query_fields', array( $this, 'donation_calc_fields' ), 5 );
		}

		/**
		 * Set up orderby query argument.
		 *
		 * @since  1.4.0
		 *
		 * @return void
		 */
		public function setup_orderby() {
			$orderby = $this->get( 'orderby', false );

			if ( ! $orderby ) {
				return;
			}

			switch ( $orderby ) {
				case 'date':
					add_filter( 'charitable_query_orderby', array( $this, 'orderby_date' ) );
					break;

				case 'amount':
					add_filter( 'charitable_query_orderby', array( $this, 'orderby_donation_amount' ) );
					break;
			}
		}

		/**
		 * Remove any hooks that have been attached by the class to prevent contaminating other queries.
		 *
		 * @since  1.4.0
		 *
		 * @return void
		 */
		public function unhook_callbacks() {
			remove_action( 'charitable_pre_query', array( $this, 'setup_fields' ) );
			remove_filter( 'charitable_query_fields', array( $this, 'donation_fields' ), 4 );
			remove_filter( 'charitable_query_fields', array( $this, 'donation_calc_fields' ), 5 );
			remove_filter( 'charitable_query_join', array( $this, 'join_campaign_donations_table_on_donation' ), 5 );
			remove_filter( 'charitable_query_join', array( $this, 'join_meta' ), 6 );
			remove_filter( 'charitable_query_where', array( $this, 'where_donation_plan_is_in' ), 4 );
			remove_filter( 'charitable_query_where', array( $this, 'where_status_is_in' ), 5 );
			remove_filter( 'charitable_query_where', array( $this, 'where_campaign_is_in' ), 6 );
			remove_filter( 'charitable_query_where', array( $this, 'where_donor_id_is_in' ), 7 );
			remove_filter( 'charitable_query_where', array( $this, 'where_user_id_is_in' ), 8 );
			remove_filter( 'charitable_query_where', array( $this, 'where_date' ), 9 );
			remove_filter( 'charitable_query_where', array( $this, 'where_meta' ), 10 );
			remove_action( 'charitable_pre_query', array( $this, 'setup_orderby' ) );
			remove_filter( 'charitable_query_orderby', array( $this, 'orderby_date' ) );
			remove_filter( 'charitable_query_orderby', array( $this, 'orderby_donation_amount' ) );
			remove_filter( 'charitable_query_groupby', array( $this, 'groupby_donation_id' ) );
			remove_action( 'charitable_post_query', array( $this, 'unhook_callbacks' ) );
		}

		/**
		 * Set up callbacks for WP_Query filters.
		 *
		 * @since  1.4.0
		 *
		 * @return void
		 */
		protected function prepare_query() {
			add_action( 'charitable_pre_query', array( $this, 'setup_fields' ) );
			add_action( 'charitable_pre_query', array( $this, 'setup_orderby' ) );
			add_filter( 'charitable_query_join', array( $this, 'join_campaign_donations_table_on_donation' ), 5 );
			add_filter( 'charitable_query_join', array( $this, 'join_meta' ), 6 );
			add_filter( 'charitable_query_where', array( $this, 'where_donation_plan_is_in' ), 4 );
			add_filter( 'charitable_query_where', array( $this, 'where_status_is_in' ), 5 );
			add_filter( 'charitable_query_where', array( $this, 'where_campaign_is_in' ), 6 );
			add_filter( 'charitable_query_where', array( $this, 'where_donor_id_is_in' ), 7 );
			add_filter( 'charitable_query_where', array( $this, 'where_user_id_is_in' ), 8 );
			add_filter( 'charitable_query_where', array( $this, 'where_date' ), 9 );
			add_filter( 'charitable_query_where', array( $this, 'where_meta' ), 10 );
			add_filter( 'charitable_query_groupby', array( $this, 'groupby_donation_id' ) );
			add_action( 'charitable_post_query', array( $this, 'unhook_callbacks' ) );
		}
	}

endif;
