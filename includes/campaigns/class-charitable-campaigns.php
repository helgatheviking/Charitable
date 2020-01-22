<?php
/**
 * The class responsible for querying data about campaigns.
 *
 * @package   Charitable/Classes/Charitable_Campaigns
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Campaigns' ) ) :

	/**
	 * Charitable_Campaigns.
	 *
	 * @since 1.0.0
	 */
	class Charitable_Campaigns {

		/**
		 * Return WP_Query object with predefined defaults to query only campaigns.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $args Query arguments.
		 * @return WP_Query
		 */
		public static function query( $args = array() ) {
			$defaults = array(
				'post_type'      => array( 'campaign' ),
				'posts_per_page' => get_option( 'posts_per_page' ),
			);

			$args = wp_parse_args( $args, $defaults );

			return new WP_Query( $args );
		}

		/**
		 * Returns a WP_Query that will return active campaigns, ordered by the date they're ending.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $args Additional arguments to pass to WP_Query.
		 * @return WP_Query
		 */
		public static function ordered_by_ending_soon( $args = array() ) {
			$defaults = array(
				'meta_query' => array(
					array(
						'key'     => '_campaign_end_date',
						'value'   => date( 'Y-m-d H:i:s' ),
						'compare' => '>=',
						'type'    => 'datetime',
					),
				),
				'meta_key'   => '_campaign_end_date',
				'orderby'    => 'meta_value',
				'order'      => 'ASC',
			);

			$args = wp_parse_args( $args, $defaults );

			return Charitable_Campaigns::query( $args );
		}

		/**
		 * Returns a WP_Query that will return campaigns, ordered by the amount they raised.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $args Additional arguments to pass to WP_Query.
		 * @return WP_Query
		 */
		public static function ordered_by_amount( $args = array() ) {
			$defaults = array(
				'order' => 'DESC',
			);

			$args = wp_parse_args( $args, $defaults );

			/* Set up filters to order by amount */
			add_filter( 'posts_join_paged', array( 'Charitable_Campaigns', 'join_campaign_donations_table' ) );
			add_filter( 'posts_groupby', array( 'Charitable_Campaigns', 'groupby_campaign_id' ) );
			add_filter( 'posts_orderby', array( 'Charitable_Campaigns', 'orderby_campaign_donation_amount' ), 10, 2 );

			$query = Charitable_Campaigns::query( $args );

			/* Clean up filters */
			remove_filter( 'posts_join_paged', array( 'Charitable_Campaigns', 'join_campaign_donations_table' ) );
			remove_filter( 'posts_groupby', array( 'Charitable_Campaigns', 'groupby_campaign_id' ) );
			remove_filter( 'posts_orderby', array( 'Charitable_Campaigns', 'orderby_campaign_donation_amount' ), 10, 2 );

			return $query;
		}

		/**
		 * A method used to join the campaign donations table on the campaigns query.
		 *
		 * @since  1.0.0
		 *
		 * @global WPDB $wpdb
		 * @param  string $join_statement The join statement.
		 * @return string
		 */
		public static function join_campaign_donations_table( $join_statement ) {
			global $wpdb;

			$statuses = "'" . implode( "','", charitable_get_approval_statuses() ) . "'";

			return $join_statement . " LEFT JOIN ( SELECT cd1.campaign_donation_id, cd1.donation_id, cd1.donor_id, cd1.amount, cd1.campaign_id
                FROM {$wpdb->prefix}charitable_campaign_donations cd1
                INNER JOIN $wpdb->posts po1 ON cd1.donation_id = po1.ID
                WHERE po1.post_status IN ( $statuses )
			) cd ON cd.campaign_id = $wpdb->posts.ID";
		}

		/**
		 * A method used to change the group by parameter of the campaigns query.
		 *
		 * @since  1.0.0
		 *
		 * @global WPDB $wpdb
		 * @return string
		 */
		public static function groupby_campaign_id() {
			global $wpdb;
			return "$wpdb->posts.ID";
		}

		/**
		 * A method used to change the ordering of the campaigns query, to order by the amount donated.
		 *
		 * @since  1.0.0
		 *
		 * @param  string   $orderby The current orderby value.
		 * @param  WP_Query $wp_query The WP_Query object.
		 * @return string
		 */
		public static function orderby_campaign_donation_amount( $orderby, WP_Query $wp_query ) {
			return 'COALESCE(SUM(cd.amount), 0) ' . $wp_query->get( 'order' );
		}
	}

endif;
