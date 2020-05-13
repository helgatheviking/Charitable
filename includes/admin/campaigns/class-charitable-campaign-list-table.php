<?php
/**
 * Sets up the campaign list table in the admin.
 *
 * @package   Charitable/Classes/Charitable_Campaign_List_Table
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.39
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Campaign_List_Table' ) ) :

	/**
	 * Charitable_Campaign_List_Table class.
	 *
	 * @since 1.5.0
	 */
	final class Charitable_Campaign_List_Table {

		/**
		 * The single instance of this class.
		 *
		 * @since 1.5.0
		 *
		 * @var   Charitable_Campaign_List_Table|null
		 */
		private static $instance = null;

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.5.0
		 *
		 * @return Charitable_Campaign_List_Table
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Customize campaigns columns.
		 *
		 * @see    get_column_headers
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function dashboard_columns() {
			/**
			 * Filter the columns shown in the campaigns list table.
			 *
			 * @since 1.5.0
			 *
			 * @param array $columns The list of columns.
			 */
			return apply_filters(
				'charitable_campaign_dashboard_column_names',
				array(
					'cb'       => '<input type="checkbox"/>',
					'ID'       => __( '#', 'charitable' ),
					'title'    => __( 'Title', 'charitable' ),
					'author'   => __( 'Creator', 'charitable' ),
					'donated'  => __( 'Donations', 'charitable' ),
					'end_date' => __( 'End Date', 'charitable' ),
					'status'   => __( 'Status', 'charitable' ),
					'date'     => __( 'Date Created', 'charitable' ),
				)
			);
		}

		/**
		 * Add information to the dashboard campaign table listing.
		 *
		 * @see    WP_Posts_List_Table::single_row()
		 *
		 * @since  1.5.0
		 *
		 * @param  string $column_name The name of the column to display.
		 * @param  int    $post_id     The current post ID.
		 * @return void
		 */
		public function dashboard_column_item( $column_name, $post_id ) {
			$campaign = charitable_get_campaign( $post_id );

			switch ( $column_name ) {
				case 'ID':
					$display = $post_id;
					break;

				case 'donated':
					$display = charitable_format_money( $campaign->get_donated_amount() );
					break;

				case 'end_date':
					$display = $campaign->is_endless() ? '&#8734;' : $campaign->get_end_date();
					break;

				case 'status':
					$status = $campaign->get_status();

					if ( 'finished' == $status && $campaign->has_goal() ) {
						$status = $campaign->has_achieved_goal() ? 'successful' : 'unsuccessful';
					}

					$display = '<mark class="' . esc_attr( $status ) . '">' . $status . '</mark>';
					break;

				default:
					$display = '';
			}

			/**
			 * Filter the output of the cell.
			 *
			 * @since 1.5.0
			 *
			 * @param string              $display     The content that will be displayed.
			 * @param string              $column_name The name of the column.
			 * @param int                 $post_id     The ID of the campaign being shown.
			 * @param Charitable_Campaign $campaign    The Charitable_Campaign object.
			 */
			echo apply_filters( 'charitable_campaign_column_display', $display, $column_name, $post_id, $campaign );
		}

		/**
		 * Modify bulk messages
		 *
		 * @since  1.5.0
		 *
		 * @param  array $bulk_messages Messages to show after bulk actions.
		 * @param  array $bulk_counts   Array showing how many items were affected by the action.
		 * @return array
		 */
		public function bulk_messages( $bulk_messages, $bulk_counts ) {
			$bulk_messages[ Charitable::CAMPAIGN_POST_TYPE ] = array(
				'updated'   => _n( '%d campaign updated.', '%d campaigns updated.', $bulk_counts['updated'], 'charitable' ),
				'locked'    => ( 1 == $bulk_counts['locked'] )
								? __( '1 campaign not updated, somebody is editing it.' )
								: _n( '%s campaign not updated, somebody is editing it.', '%s campaigns not updated, somebody is editing them.', $bulk_counts['locked'], 'charitable' ),
				'deleted'   => _n( '%s campaign permanently deleted.', '%s campaigns permanently deleted.', $bulk_counts['deleted'], 'charitable' ),
				'trashed'   => _n( '%s campaign moved to the Trash.', '%s campaigns moved to the Trash.', $bulk_counts['trashed'], 'charitable' ),
				'untrashed' => _n( '%s campaign restored from the Trash.', '%s campaigns restored from the Trash.', $bulk_counts['untrashed'], 'charitable' ),
			);

			return $bulk_messages;
		}

		/**
		 * Add extra buttons after filters
		 *
		 * @since  1.6.0
		 *
		 * @param  string $which The context where this is being called.
		 * @return void
		 */
		public function add_export( $which ) {
			if ( ! current_user_can( 'export_charitable_reports' ) ) {
				return;
			}

			if ( 'top' == $which && $this->is_campaigns_page() ) {
				charitable_admin_view( 'campaigns-page/export' );
			}
		}

		/**
		 * Add filters above the campaigns table.
		 *
		 * @since  1.6.36
		 *
		 * @global string $typenow The current post type.
		 *
		 * @return void
		 */
		public function add_filters() {
			global $typenow;

			/* Show custom filters to filter orders by donor. */
			if ( in_array( $typenow, array( Charitable::CAMPAIGN_POST_TYPE ) ) ) {
				charitable_admin_view( 'campaigns-page/filters' );
			}
		}

		/**
		 * Add modal template to footer.
		 *
		 * @since  1.6.0
		 *
		 * @return void
		 */
		public function modal_forms() {
			if ( $this->is_campaigns_page() ) {
				charitable_admin_view( 'campaigns-page/filter-form' );
				charitable_admin_view( 'campaigns-page/export-form' );
			}
		}

		/**
		 * Admin scripts and styles.
		 *
		 * Set up the scripts & styles used for the modal.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $hook The current page hook/slug.
		 * @return void
		 */
		public function load_scripts( $hook ) {
			if ( 'edit.php' != $hook ) {
				return;
			}

			if ( $this->is_campaigns_page() ) {
				wp_enqueue_style( 'lean-modal-css' );
				wp_enqueue_script( 'jquery-core' );
				wp_enqueue_script( 'lean-modal' );
				wp_enqueue_script( 'charitable-admin-tables' );
			}
		}

		/**
		 * Add custom filters to the query that returns the campaigns to be displayed.
		 *
		 * @since  1.6.36
		 *
		 * @global string $typenow The current post type.
		 *
		 * @param  array $vars The array of args to pass to WP_Query.
		 * @return array
		 */
		public function filter_request_query( $vars ) {
			if ( ! $this->is_campaigns_page() ) {
				return $vars;
			}

			/* No Status: fix WP's crappy handling of "all" post status. */
			if ( ! isset( $_GET['post_status'] ) || empty( $_GET['post_status'] ) || 'all' === $_GET['post_status'] ) {
				$vars['post_status'] = array_keys( get_post_statuses() );
				$vars['perm']        = 'readable';
			} else {
				switch ( $_GET['post_status'] ) {
					case 'active':
						$vars['post_status'] = 'publish';
						$vars['meta_query']  = array(
							'relation' => 'OR',
							array(
								'key'     => '_campaign_end_date',
								'value'   => date( 'Y-m-d H:i:s' ),
								'compare' => '>',
								'type'    => 'datetime',
							),
							array(
								'key'     => '_campaign_end_date',
								'value'   => 0,
								'compare' => '=',
							),
						);
						break;

					case 'finish':
						$vars['post_status'] = 'publish';
						$vars['meta_query']  = array(
							array(
								'key'     => '_campaign_end_date',
								'value'   => date( 'Y-m-d H:i:s' ),
								'compare' => '<=',
								'type'    => 'datetime',
							),
						);
						break;

					default:
						$vars['post_status'] = $_GET['post_status'];
						$vars['perm']        = 'readable';
				}
			}

			/* Set up start date query */
			if ( isset( $_GET['start_date_from'] ) && ! empty( $_GET['start_date_from'] ) ) {
				$start_date_from             = $this->get_parsed_date( $_GET['start_date_from'] );
				$vars['date_query']['after'] = array(
					'year'  => $start_date_from['year'],
					'month' => $start_date_from['month'],
					'day'   => $start_date_from['day'],
				);
			}

			if ( isset( $_GET['start_date_to'] ) && ! empty( $_GET['start_date_to'] ) ) {
				$start_date_to                = $this->get_parsed_date( $_GET['start_date_to'] );
				$vars['date_query']['before'] = array(
					'year'  => $start_date_to['year'],
					'month' => $start_date_to['month'],
					'day'   => $start_date_to['day'],
				);
			}

			/* Set up end date query */
			if ( isset( $_GET['end_date_from'] ) && ! empty( $_GET['end_date_from'] ) ) {
				$end_date_from               = $this->get_parsed_date( $_GET['end_date_from'] );
				$vars['meta_query'][] = array(
					'key'     => '_campaign_end_date',
					'value'   => sprintf( '%d-%d-%d 00:00:00', $end_date_from['year'], $end_date_from['month'], $end_date_from['day'] ),
					'compare' => '>=',
					'type'    => 'datetime',
				);
			}

			if ( isset( $_GET['end_date_to'] ) && ! empty( $_GET['end_date_to'] ) ) {
				$end_date_to                  = $this->get_parsed_date( $_GET['end_date_to'] );
				$vars['meta_query'][] = array(
					'key'     => '_campaign_end_date',
					'value'   => sprintf( '%d-%d-%d 00:00:00', $end_date_to['year'], $end_date_to['month'], $end_date_to['day'] ),
					'compare' => '<=',
					'type'    => 'datetime',
				);
			}

			if ( isset( $vars['date_query'] ) ) {
				$vars['date_query']['inclusive'] = true;
			}

			/* Filter by campaign. */
			if ( isset( $_GET['campaign_id'] ) && 'all' != $_GET['campaign_id'] ) {
				$vars['post__in'] = charitable_get_table( 'campaign_donations' )->get_donation_ids_for_campaign( $_GET['campaign_id'] );
			}

			/* Restrict by author if user can only edit their own. */
			if ( ! current_user_can( 'edit_others_campaigns' ) ) {
				$vars['author'] = get_current_user_id();
			}

			/**
			 * Filter the campaign list query vars.
			 *
			 * @since 1.6.36
			 *
			 * @param array $vars The request query vars.
			 */
			return apply_filters( 'charitable_filter_campaigns_list_request_vars', $vars );
		}

		/**
		 * Given a date, returns an array containing the date, month and year.
		 *
		 * @since  1.6.36
		 *
		 * @param  string $date A date as a string that can be parsed by strtotime.
		 * @return string[]
		 */
		protected function get_parsed_date( $date ) {
			$time = charitable_sanitize_date( $date );

			return array(
				'year'  => date( 'Y', $time ),
				'month' => date( 'm', $time ),
				'day'   => date( 'd', $time ),
			);
		}

		/**
		 * Checks whether this is the campaigns page.
		 *
		 * @since  1.6.0
		 *
		 * @global string $typenow The current post type.
		 * @return boolean
		 */
		private function is_campaigns_page() {
			global $typenow;

			return in_array( $typenow, array( Charitable::CAMPAIGN_POST_TYPE ) );
		}
	}

endif;
