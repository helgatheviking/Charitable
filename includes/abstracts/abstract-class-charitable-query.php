<?php
/**
 * An abstract base class defining common methods used by Charitable queries.
 *
 * @package   Charitable/Classes/Charitable_Query
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.8
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Query' ) ) :

	/**
	 * Charitable_Query
	 *
	 * @since  1.0.0
	 */
	abstract class Charitable_Query implements Iterator {

		/**
		 * User-defined arguments.
		 *
		 * @var array
		 */
		protected $args;

		/**
		 * Internal iterator position.
		 *
		 * @var int
		 */
		protected $position = 0;

		/**
		 * The raw query results.
		 *
		 * @var array
		 */
		protected $query;

		/**
		 * Number of items found.
		 *
		 * @var int|null
		 */
		protected $found_items;

		/**
		 * Result set.
		 *
		 * @var array
		 */
		protected $results;

		/**
		 * Parameters to pass to the query.
		 *
		 * @var mixed[]
		 */
		protected $parameters = array();

		/**
		 * Meta SQL query.
		 *
		 * @since 1.6.5
		 *
		 * @var   array|false
		 */
		protected $meta_sql;

		/**
		 * Return the query argument value for the given key.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $key      The key of the argument.
		 * @param  mixed  $fallback Default value to fall back to.
		 * @return mixed|false      Returns fallback if the argument is not found.
		 */
		public function get( $key, $fallback = false ) {
			return isset( $this->args[ $key ] ) ? $this->args[ $key ] : $fallback;
		}

		/**
		 * Set the query argument for the given key.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $key   Key of argument to set.
		 * @param  mixed  $value Value to be set.
		 * @return void
		 */
		public function set( $key, $value ) {
			$this->args[ $key ] = apply_filters( 'charitable_query_sanitize_argument_' . $key, $value, $this );
		}

		/**
		 * Remove the given query argument.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $key Key of argument to remove.
		 * @return void
		 */
		public function remove( $key ) {
			unset( $this->args[ $key ] );
		}

		/**
		 * Return the results of the query.
		 *
		 * @global WPDB $wpdb
		 *
		 * @since  1.6.8
		 *
		 * @return object[]
		 */
		public function get_query() {
			if ( ! isset( $this->query ) ) {
				/**
				 * Fires right before the query is executed.
				 *
				 * @since 1.0.0
				 *
				 * @param Charitable_Query The `Charitable_Query` instance.
				 */
				do_action( 'charitable_pre_query', $this );

				$this->query       = $this->run_query( $this->get( 'output' ) );
				$this->found_items = $this->get_found_items();

				/**
				 * Fires right after the query is executed.
				 *
				 * @since 1.0.0
				 *
				 * @param Charitable_Query The `Charitable_Query` instance.
				 */
				do_action( 'charitable_post_query', $this );

			}//end if

			return $this->query;
		}

		/**
		 * Return the results.
		 *
		 * @since  1.6.8
		 *
		 * @return object[]
		 */
		public function get_results() {
			return $this->results;
		}

		/**
		 * Return the results of the query.
		 *
		 * @global WPDB $wpdb
		 *
		 * @since  1.0.0
		 *
		 * @return object[]
		 */
		public function query() {
			return $this->get_query();
		}

		/**
		 * Run query, returning the results.
		 *
		 * @global WPDB $wpdb
		 *
		 * @since  1.5.0
		 *
		 * @param  string $output The output type.
		 * @return array
		 */
		public function run_query( $output ) {
			if ( method_exists( $this, 'run_query_' . $output ) ) {
				return call_user_func( array( $this, 'run_query_' . $output ) );
			}

			global $wpdb;

			$sql = "SELECT {$this->fields()} {$this->from()} {$this->join()} {$this->where()} {$this->groupby()} {$this->orderby()} {$this->order()} {$this->limit()} {$this->offset()};";

			return $wpdb->get_results( $this->get_prepared_sql( $sql ) );
		}

		/**
		 * Run count query, returning the results.
		 *
		 * @global WPDB $wpdb
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function run_query_count() {
			global $wpdb;

			$sql = "SELECT {$this->select_count()} {$this->from()} {$this->join()} {$this->where()};";

			return array( $wpdb->get_var( $this->get_prepared_sql( $sql ) ) );
		}

		/**
		 * Prepare a query with any passed parameters.
		 *
		 * @global WPDB $wpdb
		 *
		 * @since  1.5.0
		 *
		 * @param  string $sql The SQL query with placeholders.
		 * @return string
		 */
		public function get_prepared_sql( $sql ) {
			global $wpdb;

			if ( empty( $this->parameters ) ) {
				return $sql;
			}

			return $wpdb->prepare( $sql, $this->parameters );
		}

		/**
		 * After a query has been executed, check how many found rows there are.
		 *
		 * @global WPDB $wpdb
		 *
		 * @since  1.5.0
		 *
		 * @return int
		 */
		public function get_found_items() {
			global $wpdb;

			if ( empty( $this->query ) ) {
				return 0;
			}

			if ( 'count' == $this->get( 'output' ) ) {
				return current( $this->query );
			}

			if ( $this->show_all() ) {
				return count( $this->query );
			}

			/**
			 * Filters the query used to retrieve the query result count.
			 *
			 * @since 1.5.0
			 *
			 * @param string           $found_customers_query SQL query. Default 'SELECT FOUND_ROWS()'.
			 * @param Charitable_Query $query                 The `Charitable_Query` instance.
			 */
			$found_items_query = apply_filters( 'charitable_found_items_query', 'SELECT FOUND_ROWS()', $this );

			return (int) $wpdb->get_var( $found_items_query );
		}

		/**
		 * Return the count query right after the SELECT part of the query.
		 *
		 * This is used instead of Charitable_Query::fields and is only appropriate
		 * when all you want to do with a query is get the number of something.
		 *
		 * @global WPBD $wpdb
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		public function select_count() {
			global $wpdb;

			/**
			 * Filter the `SELECT COUNT` statement.
			 *
			 * @since 1.5.0
			 *
			 * @param string           $sql   The `SELECT COUNT` statement.
			 * @param Charitable_Query $query The `Charitable_Query` instance.
			 */
			return apply_filters( 'charitable_select_count_fields', "COUNT(DISTINCT {$wpdb->posts}.ID)", $this );
		}

		/**
		 * Return the fields right after the SELECT part of the query.
		 *
		 * @global WPBD $wpdb
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function fields() {
			global $wpdb;

			$found_rows = $this->show_all() ? '' : 'SQL_CALC_FOUND_ROWS';

			/**
			 * Filter the `SELECT` statement.
			 *
			 * @since 1.0.0
			 *
			 * @param string           $sql   The `SELECT` statement.
			 * @param Charitable_Query $query The `Charitable_Query` instance.
			 */
			return apply_filters( 'charitable_query_fields', "$found_rows {$wpdb->posts}.ID", $this );
		}

		/**
		 * Return the FROM part of the query.
		 *
		 * @global WPBD $wpdb
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function from() {
			global $wpdb;

			/**
			 * Filter the `FROM` statement.
			 *
			 * @since 1.0.0
			 *
			 * @param string           $sql   The `FROM` statement.
			 * @param Charitable_Query $query The `Charitable_Query` instance.
			 */
			return apply_filters( 'charitable_query_from', "FROM $wpdb->posts", $this );
		}

		/**
		 * Return the JOIN part of the query.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function join() {
			/**
			 * Filter the `JOIN` statement.
			 *
			 * @since 1.0.0
			 *
			 * @param string           $sql   The `JOIN` statement. Empty by default.
			 * @param Charitable_Query $query The `Charitable_Query` instance.
			 */
			return apply_filters( 'charitable_query_join', '', $this );
		}

		/**
		 * Return the WHERE part of the query.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function where() {
			/**
			 * Filter the `WHERE` statement.
			 *
			 * @since 1.0.0
			 *
			 * @param string           $sql   The `WHERE` statement.
			 * @param Charitable_Query $query This query object.
			 */
			return apply_filters( 'charitable_query_where', 'WHERE 1=1 ', $this );
		}

		/**
		 * Return the GROUPBY part of the query.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function groupby() {
			/**
			 * Filter the `GROUP BY` statement.
			 *
			 * @since 1.0.0
			 *
			 * @param string           $sql   The `GROUP BY` statement. Empty by default.
			 * @param Charitable_Query $query This query object.
			 */
			return apply_filters( 'charitable_query_groupby', '', $this );
		}

		/**
		 * Return the ORDER BY part of the query.
		 *
		 * @global WPBD $wpdb
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function orderby() {
			global $wpdb;

			/**
			 * Filter the `ORDER BY` statement.
			 *
			 * @since 1.0.0
			 *
			 * @param string           $sql   The `ORDER BY` statement.
			 * @param Charitable_Query $query The `Charitable_Query` instance.
			 */
			return apply_filters( 'charitable_query_orderby', "ORDER BY {$wpdb->posts}.ID", $this );
		}

		/**
		 * Return the ORDER part of the query.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function order() {
			/**
			 * Filter the `ORDER` statement.
			 *
			 * @since 1.0.0
			 *
			 * @param string           $sql   The `ORDER` statement. DESC by default.
			 * @param Charitable_Query $query The `Charitable_Query` instance.
			 */
			return apply_filters( 'charitable_query_order', $this->get( 'order', 'DESC' ), $this );
		}

		/**
		 * Return the LIMIT part of the query.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function limit() {
			if ( $this->show_all() ) {
				return '';
			}

			/**
			 * Filter the `LIMIT` statement.
			 *
			 * @since 1.0.0
			 *
			 * @param string           $sql   The `LIMIT` statement.
			 * @param Charitable_Query $query The `Charitable_Query` instance.
			 */
			return apply_filters( 'charitable_query_limit', sprintf( 'LIMIT %d', $this->get( 'number', 20 ) ), $this );
		}

		/**
		 * Return the OFFSET part of the query.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function offset() {
			if ( $this->show_all() ) {
				return '';
			}

			$offset = $this->get( 'number' ) * ( $this->get( 'paged', 1 ) - 1 );

			/**
			 * Filter the `OFFSET` statement.
			 *
			 * @since 1.0.0
			 *
			 * @param string           $sql   The `OFFSET` statement.
			 * @param Charitable_Query $query The `Charitable_Query` instance.
			 */
			return apply_filters( 'charitable_query_offset', sprintf( 'OFFSET %d', $offset ), $this );
		}

		/**
		 * Select donor-specific fields.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $select_statement The default select statement.
		 * @return string
		 */
		public function donor_fields( $select_statement ) {
			return $select_statement . ', d.donor_id, d.user_id, d.first_name, d.last_name, d.email, d.date_joined';
		}

		/**
		 * Retrieve the donation ID and campaigns.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $select_statement The default select statement.
		 * @return string
		 */
		public function donation_fields( $select_statement ) {
			return $select_statement . ", cd.donation_id, GROUP_CONCAT(cd.campaign_name SEPARATOR ', ') AS campaigns, GROUP_CONCAT(cd.campaign_id SEPARATOR ',') AS campaign_ids, cd.donor_id";
		}

		/**
		 * Select donation-specific fields.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $select_statement The default select statement.
		 * @return string
		 */
		public function donation_calc_fields( $select_statement ) {
			return $select_statement . ', COUNT(cd.campaign_donation_id) AS donations, SUM(cd.amount) AS amount';
		}

		/**
		 * Select total amount field.
		 *
		 * @since  1.2.0
		 *
		 * @param  string $select_statement The default select statement.
		 * @return string
		 */
		public function donation_amount_sum_field( $select_statement ) {
			return $select_statement . ', SUM(cd.amount) AS amount';
		}

		/**
		 * Return the SQL to be used for meta queries.
		 *
		 * @since  1.6.5
		 *
		 * @global WPDB $wpdb
		 *
		 * @return false|array {
		 *     Array containing JOIN and WHERE SQL clauses to append to the main query.
		 *
		 *     @type string $join  SQL fragment to append to the main JOIN clause.
		 *     @type string $where SQL fragment to append to the main WHERE clause.
		 * }
		 */
		public function get_meta_sql() {
			if ( ! isset( $this->meta_sql ) ) {
				global $wpdb;

				$meta_args = $this->get( 'meta_query' );

				if ( empty( $meta_args ) ) {
					return false;
				}

				$meta_query = new WP_Meta_Query( $meta_args );

				$this->meta_sql = $meta_query->get_sql( 'post', $wpdb->posts, 'ID' );
			}

			return $this->meta_sql;
		}

		/**
		 * A helper function used to populate the query parameters ith an item and a return a string
		 * of placeholders that can be used in a MySQL `IN` statement.
		 *
		 * @since  1.5.0
		 *
		 * @param  mixed    $item             The item.
		 * @param  callable $filter_callback  A callback to use to filter out invalid items.
		 * @param  string   $placeholder_type The type of placeholder to use. May be %s, %d or %f.
		 * @return string                     A string containing the correct number of placeholders.
		 */
		public function get_where_in_placeholders( $item, $filter_callback, $placeholder_type ) {
			if ( ! is_array( $item ) ) {
				$item = array( $item );
			}

			$item = array_filter( $item, $filter_callback );

			$this->add_parameters( $item );

			return $this->get_placeholders( count( $item ), $placeholder_type );
		}

		/**
		 * Filter query by donation_plan ID.
		 *
		 * @since  1.6.8
		 *
		 * @global WPBD   $wpdb
		 * @param  string $where_statement The default where statement.
		 * @return string
		 */
		public function where_donation_plan_is_in( $where_statement ) {
			global $wpdb;

			$donation_plan = $this->get( 'donation_plan', false );

			if ( ! $donation_plan ) {
				return $where_statement;
			}

			$placeholders = $this->get_where_in_placeholders( $donation_plan, 'charitable_validate_absint', '%d' );

			return $where_statement . " AND {$wpdb->posts}.post_parent IN ({$placeholders})";
		}

		/**
		 * Filter query by campaign receiving the donation.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $where_statement The default where statement.
		 * @return string
		 */
		public function where_campaign_is_in( $where_statement ) {
			$campaign = $this->get( 'campaign', 0 );

			if ( ! $campaign ) {
				return $where_statement;
			}

			$placeholders = $this->get_where_in_placeholders( $this->get( 'campaign', 0 ), 'charitable_validate_absint', '%d' );

			return $where_statement . " AND cd.campaign_id IN ({$placeholders})";
		}

		/**
		 * Filter query by status of the post.
		 *
		 * @global WPBD $wpdb
		 *
		 * @since  1.0.0
		 *
		 * @param  string $where_statement The default where statement.
		 * @return string
		 */
		public function where_status_is_in( $where_statement ) {
			global $wpdb;

			$status = $this->get( 'status', false );

			if ( ! $status ) {
				return $where_statement;
			}

			$placeholders = $this->get_where_in_placeholders( $status, 'charitable_is_valid_donation_status', '%s' );

			return $where_statement . " AND {$wpdb->posts}.post_status IN ({$placeholders})";
		}

		/**
		 * Filter query by donor ID.
		 *
		 * @global  WPBD $wpdb
		 * @since  1.0.0
		 *
		 * @param  string $where_statement The default where statement.
		 * @return string
		 */
		public function where_donor_id_is_in( $where_statement ) {
			$donor_id = $this->get( 'donor_id', false );

			if ( ! $donor_id ) {
				return $where_statement;
			}

			$placeholders = $this->get_where_in_placeholders( $donor_id, 'charitable_validate_absint', '%d' );

			return $where_statement . " AND cd.donor_id IN ({$placeholders})";
		}

		/**
		 * Filter query by user ID.
		 *
		 * @since  1.5.0
		 *
		 * @global WPBD   $wpdb
		 * @param  string $where_statement The default where statement.
		 * @return string
		 */
		public function where_user_id_is_in( $where_statement ) {
			global $wpdb;

			$user_id = $this->get( 'user_id', false );

			if ( ! $user_id ) {
				return $where_statement;
			}

			$placeholders = $this->get_where_in_placeholders( $user_id, 'charitable_validate_absint', '%d' );

			return $where_statement . " AND {$wpdb->posts}.post_author IN ({$placeholders})";
		}

		/**
		 * Filter a query by date.
		 *
		 * @uses   WP_Date_Query
		 *
		 * @since  1.5.0
		 *
		 * @param  string $where_statement The where query.
		 * @return string
		 */
		public function where_date( $where_statement ) {
			$date_args = $this->get( 'date_query' );

			if ( empty( $date_args ) ) {
				return $where_statement;
			}

			$date_query = new WP_Date_Query( $date_args );

			return $where_statement . $date_query->get_sql();
		}

		/**
		 * Filter a query by meta data.
		 *
		 * @uses   WP_Meta_Query
		 *
		 * @since  1.6.5
		 *
		 * @param  string $where_statement The where query.
		 * @return string
		 */
		public function where_meta( $where_statement ) {
			$meta_sql = $this->get_meta_sql();

			if ( ! $meta_sql || empty( $meta_sql['where'] ) ) {
				return $where_statement;
			}

			return $where_statement . $meta_sql['where'];
		}

		/**
		 * A method used to join the campaign donations table on the campaigns query.
		 *
		 * @global WPBD $wpdb
		 *
		 * @since  1.0.0
		 *
		 * @param  string $join_statement The default join statement.
		 * @return string
		 */
		public function join_campaign_donations_table_on_campaign( $join_statement ) {
			global $wpdb;
			return $join_statement . " INNER JOIN {$wpdb->prefix}charitable_campaign_donations cd ON cd.campaign_id = $wpdb->posts.ID ";
		}

		/**
		 * Add one or more joins to the post meta table.
		 *
		 * @uses   WP_Meta_Query
		 *
		 * @since  1.6.5
		 *
		 * @param  string $join_statement The where query.
		 * @return string
		 */
		public function join_meta( $join_statement ) {
			$meta_sql = $this->get_meta_sql();

			if ( ! $meta_sql || empty( $meta_sql['join'] ) ) {
				return $join_statement;
			}

			return $join_statement . $meta_sql['join'];
		}

		/**
		 * A method used to join the campaign donations table on the campaigns query.
		 *
		 * @global WPBD $wpdb
		 *
		 * @since  1.0.0
		 *
		 * @param  string $join_statement The default join statement.
		 * @return string
		 */
		public function join_campaign_donations_table_on_donation( $join_statement ) {
			global $wpdb;
			return $join_statement . " INNER JOIN {$wpdb->prefix}charitable_campaign_donations cd ON cd.donation_id = $wpdb->posts.ID ";
		}

		/**
		 * A method used to join the donors table on the query.
		 *
		 * @global  WPBD $wpdb
		 * @since  1.5.0
		 *
		 * @param  string $join_statement The default join statement.
		 * @return string
		 */
		public function join_post_meta_table_on_donation( $join_statement ) {
			global $wpdb;
			return $join_statement . " INNER JOIN $wpdb->postmeta pm ON pm.post_id = $wpdb->posts.ID ";
		}

		/**
		 * A method used to join the donors table on the query.
		 *
		 * @global  WPBD $wpdb
		 * @since  1.0.0
		 *
		 * @param  string $join_statement The default join statement.
		 * @return string
		 */
		public function join_donors_table( $join_statement ) {
			global $wpdb;
			return $join_statement . " INNER JOIN {$wpdb->prefix}charitable_donors d ON d.donor_id = cd.donor_id ";
		}

		/**
		 * Group results by the ID.
		 *
		 * @global  WPBD $wpdb
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function groupby_ID() {
			global $wpdb;
			return "GROUP BY {$wpdb->posts}.ID";
		}

		/**
		 * Group a query by the donor ID.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function groupby_donor_id() {
			return 'GROUP BY cd.donor_id';
		}

		/**
		 * Group a query by the donation ID.
		 *
		 * @since  1.4.0
		 *
		 * @return string
		 */
		public function groupby_donation_id() {
			return 'GROUP BY cd.donation_id';
		}

		/**
		 * Order by the date of the post.
		 *
		 * @global  WPBD $wpdb
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function orderby_date() {
			global $wpdb;
			return "ORDER BY {$wpdb->posts}.post_date";
		}

		/**
		 * Order by the results count of the ID column.
		 *
		 * This is useful when used in combination with a group statement.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function orderby_count() {
			return 'ORDER BY COUNT(*)';
		}

		/**
		 * A method used to change the ordering of the campaigns query, to order by the amount donated.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function orderby_donation_amount() {
			return 'ORDER BY COALESCE(SUM(cd.amount), 0)';
		}

		/**
		 * Return number of results.
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function count() {
			return 'count' == $this->get( 'output' ) ? $this->found_items : count( $this->results );
		}

		/**
		 * Return total result count.
		 *
		 * @since  1.5.0
		 *
		 * @return int
		 */
		public function found_items() {
			return $this->found_items;
		}

		/**
		 * Rewind to first result.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function rewind() {
			$this->position = 0;
		}

		/**
		 * Return current element.
		 *
		 * @since  1.0.0
		 *
		 * @return object
		 */
		public function current() {
			return $this->results[ $this->position ];
		}

		/**
		 * Return current key.
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function key() {
			return $this->position;
		}

		/**
		 * Advance to next item.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function next() {
			++$this->position;
		}

		/**
		 * Ensure that current position is valid.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function valid() {
			return isset( $this->results[ $this->position ] );
		}

		/**
		 * Add parameters to pass to the prepared query.
		 *
		 * @since  1.0.0
		 *
		 * @param  mixed $parameters Parameters to be set for the query.
		 * @return void
		 */
		public function add_parameters( $parameters ) {
			$this->parameters = array_merge( $this->parameters, $parameters );
		}

		/**
		 * Whether to show all results.
		 *
		 * @since  1.1.0
		 *
		 * @return boolean
		 */
		public function show_all() {
			return -1 == $this->get( 'number' );
		}

		/**
		 * Return the correct number of placeholders given a symbol and count.
		 *
		 * @since  1.0.0
		 *
		 * @param  int    $count       Number of placeholders.
		 * @param  string $placeholder Placeholder symbol.
		 * @return string
		 */
		protected function get_placeholders( $count = 1, $placeholder = '%s' ) {
			return charitable_get_query_placeholders( $count, $placeholder );
		}
	}

endif;
