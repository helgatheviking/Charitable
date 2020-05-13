<?php
/**
 * The class that is responsible for querying data about donations.
 *
 * @package   Charitable/Classes/Charitable_Donations
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.39
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donations' ) ) :

	/**
	 * Charitable_Donations
	 *
	 * @since 1.0.0
	 */
	class Charitable_Donations {

		/**
		 * Return WP_Query object with predefined defaults to query only donations.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $args Query arguments.
		 * @return WP_Query
		 */
		public static function query( $args = array() ) {
			$defaults = array(
				'post_type'      => array( Charitable::DONATION_POST_TYPE ),
				'posts_per_page' => get_option( 'posts_per_page' ),
			);

			$args = wp_parse_args( $args, $defaults );

			return new WP_Query( $args );
		}

		/**
		 * Return the number of all donations.
		 *
		 * @since  1.0.0
		 *
		 * @global WPDB $wpdb
		 * @param  string $post_type Type of post to count.
		 * @return int
		 */
		public static function count_all( $post_type = 'donation' ) {
			global $wpdb;

			$sql = "SELECT COUNT( * )
					FROM $wpdb->posts
					WHERE post_type = %s";

			return $wpdb->get_var( $wpdb->prepare( $sql, $post_type ) );
		}

		/**
		 * Return count of donations grouped by status.
		 *
		 * @since  1.0.0
		 *
		 * @global WPDB $wpdb
		 * @param  array $args Additional query arguments.
		 * @return array
		 */
		public static function count_by_status( $args = array() ) {
			global $wpdb;

			$defaults = array(
				's'          => null,
				'start_date' => null,
				'end_date'   => null,
				'post_type'  => 'donation',
				'author'     => null,
			);

			$args = wp_parse_args( $args, $defaults );

			$where_clause = $wpdb->prepare( 'post_type = %s', $args['post_type'] );

			if ( ! empty( $args['s'] ) ) {
				$where_clause .= " AND ((post_title LIKE '%{$args['s']}%') OR (post_content LIKE '%{$args['s']}%'))";
			}

			if ( ! empty( $args['start_date'] ) ) {
				$year  = $args['start_date']['year'];
				$month = $args['start_date']['month'];
				$day   = $args['start_date']['day'];

				if ( false !== checkdate( $month, $day, $year ) ) {
					$where_clause .= $wpdb->prepare( " AND post_date >= '%s'", date( 'Y-m-d', mktime( 0, 0, 0, $month, $day, $year ) ) );
				}
			}

			if ( ! empty( $args['end_date'] ) ) {
				$year  = $args['end_date']['year'];
				$month = $args['end_date']['month'];
				$day   = $args['end_date']['day'];

				if ( false !== checkdate( $month, $day, $year ) ) {
					$where_clause .= $wpdb->prepare( " AND post_date <= '%s'", date( 'Y-m-d', mktime( 0, 0, 0, $month, $day, $year ) ) );
				}
			}

			if ( ! is_null( $args['author'] ) ) {
				$where_clause .= $wpdb->prepare( " AND post_author = %d", $args['author'] );
			}

			$sql = "SELECT post_status, COUNT( * ) AS num_donations
				FROM $wpdb->posts
				WHERE $where_clause
				GROUP BY post_status";

			return $wpdb->get_results( $sql, OBJECT_K );
		}
	}

endif;
