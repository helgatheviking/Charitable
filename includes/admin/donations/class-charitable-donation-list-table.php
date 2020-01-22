<?php
/**
 * Sets up the donations list table in the admin.
 *
 * @package   Charitable/Classes/Charitable_Donation_List_Table
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donation_List_Table' ) ) :

	/**
	 * Charitable_Donation_List_Table class.
	 *
	 * @final
	 * @since 1.5.0
	 */
	final class Charitable_Donation_List_Table {

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_Donation_List_Table|null
		 */
		private static $instance = null;

		/**
		 * @var array
		 */
		private $status_counts;

		/**
		 * Create object instance.
		 *
		 * @since 1.5.0
		 */
		public function __construct() {
			do_action( 'charitable_admin_donation_post_type_start', $this );
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.5.0
		 *
		 * @return Charitable_Donation_List_Table
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Customize donations columns.
		 *
		 * @see     get_column_headers
		 *
		 * @since   1.5.0
		 *
		 * @return  array
		 */
		public function dashboard_columns() {
			/**
			 * Filter the columns shown in the donations list table.
			 *
			 * @since 1.0.0
			 *
			 * @param array $columns The list of columns.
			 */
			return apply_filters( 'charitable_donation_dashboard_column_names', array(
				'cb'            => '<input type="checkbox"/>',
				'id'            => __( 'Donation', 'charitable' ),
				'amount'        => __( 'Amount Donated', 'charitable' ),
				'campaigns'     => __( 'Campaign(s)', 'charitable' ),
				'donation_date' => __( 'Date', 'charitable' ),
				'post_status'   => __( 'Status', 'charitable' ),
			) );
		}

		/**
		 * Add information to the dashboard donations table listing.
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
			$donation = charitable_get_donation( $post_id );

			switch ( $column_name ) {

				case 'id':
					$title = esc_attr__( 'View Donation Details', 'charitable' );
					$name  = $donation->get_donor()->get_name();

					if ( $name ) {
						$text = sprintf( _x( '#%d by %s', 'number symbol', 'charitable' ),
							$post_id,
							$name
						);
					} else {
						$text = sprintf( _x( '#%d', 'number symbol', 'charitable' ),
							$post_id
						);
					}

					$url = esc_url( add_query_arg( array(
						'post'   => $post_id,
						'action' => 'edit',
					), admin_url( 'post.php' ) ) );

					$display = sprintf( '<a href="%s" aria-label="%s">%s</a>', $url, $title, $text );
					break;

				case 'post_status':
					$display = sprintf( '<mark class="status %s">%s</mark>',
						esc_attr( $donation->get_status() ),
						strtolower( $donation->get_status_label() )
					);
					break;

				case 'amount':
					$display  = charitable_format_money( $donation->get_total_donation_amount() );
					$display .= '<span class="meta">' . sprintf( _x( 'via %s', 'charitable' ), $donation->get_gateway_label() ). '</span>';
					break;

				case 'campaigns':
					$campaigns = array();

					foreach ( $donation->get_campaign_donations() as $cd ) {
						$campaigns[] = sprintf( '<a href="edit.php?post_type=%s&campaign_id=%s">%s</a>',
							Charitable::DONATION_POST_TYPE,
							$cd->campaign_id,
							$cd->campaign_name
						);
					}

					$display = implode( ', ', $campaigns );
					break;

				case 'donation_date':
					$display = $donation->get_date();
					break;

				default:
					$display = '';
					break;

			}

			/**
			 * Filter the output of the cell.
			 *
			 * @since 1.0.0
			 *
			 * @param string              $display     The content that will be displayed.
			 * @param string              $column_name The name of the column.
			 * @param int                 $post_id     The ID of the donation being shown.
			 * @param Charitable_Donation $donation    The Charitable_Donation object.
			 */
			echo apply_filters( 'charitable_donation_column_display', $display, $column_name, $post_id, $donation );
		}

		/**
		 * Make columns sortable.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $columns List of columns that are sortable by default.
		 * @return array
		 */
		public function sortable_columns( $columns ) {
			$sortable_columns = array(
				'id'            => 'ID',
				'amount'        => 'amount',
				'donation_date' => 'date',
			);

			return wp_parse_args( $sortable_columns, $columns );
		}

		/**
		 * Set list table primary column for donations.
		 *
		 * Support for WordPress 4.3.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $default
		 * @param  string $screen_id
		 * @return string
		 */
		public function primary_column( $default, $screen_id ) {
			if ( 'edit-donation' === $screen_id ) {
				return 'id';
			}

			return $default;
		}

		/**
		 * Set row actions for donations.
		 *
		 * @since  1.5.0
		 *
		 * @param  array   $actions
		 * @param  WP_Post $post
		 * @return array
		 */
		public function row_actions( $actions, $post ) {
			if ( Charitable::DONATION_POST_TYPE !== $post->post_type ) {
				return $actions;
			}

			if ( isset( $actions['inline hide-if-no-js'] ) ) {
				unset( $actions['inline hide-if-no-js'] );
			}

			$actions['edit'] = sprintf( '<a href="%s" aria-label="%s">%s</a>',
				esc_url( add_query_arg( array(
					'post'      => $post->ID,
					'action'    => 'edit',
					'show_form' => true,
				), admin_url( 'post.php' ) ) ),
				esc_attr__( 'Edit Donation', 'charitable' ),
				__( 'Edit', 'charitable' )
			);

			$actions = array_merge( array(
				'view' => sprintf( '<a href="%s" aria-label="%s">%s</a>',
					esc_url( add_query_arg( array(
						'post'      => $post->ID,
						'action'    => 'edit',
					), admin_url( 'post.php' ) ) ),
					esc_attr__( 'View Details', 'charitable' ),
					__( 'View', 'charitable' )
				)
			), $actions );

			return $actions;
		}

		/**
		 * Customize the output of the status views.
		 *
		 * @since  1.5.0
		 *
		 * @param  string[] $views The default list of status views.
		 * @return string[]
		 */
		public function set_status_views( $views ) {
			$counts  = $this->get_status_counts();
			$current = array_key_exists( 'post_status', $_GET ) ? $_GET['post_status'] : '';

			foreach ( charitable_get_valid_donation_statuses() as $key => $label ) {
				$views[ $key ] = sprintf( '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
					add_query_arg( array(
						'post_status' => $key,
						'paged'       => false,
					) ),
					$current === $key ? ' class="current"' : '',
					$label,
					array_key_exists( $key, $counts ) ? $counts[ $key ] : '0'
				);
			}

			$views['all'] = sprintf( '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				remove_query_arg( array( 'post_status', 'paged' ) ),
				'all' === $current || '' === $current ? ' class="current"' : '',
				__( 'All', 'charitable' ),
				array_sum( $counts )
			);

			unset( $views['mine'] );

			return $views;
		}

		/**
		 * Add Custom bulk actions
		 *
		 * @param   array $actions
		 * @return  array
		 * @since   1.5.0
		 */
		public function custom_bulk_actions( $actions ) {
			if ( isset( $actions['edit'] ) ) {
				unset( $actions['edit'] );
			}

			return array_merge( $actions, $this->get_bulk_actions() );
		}

		/**
		 * Process bulk actions
		 *
		 * @param   int    $redirect_to
		 * @param   string $action
		 * @param   int[]  $post_ids
		 * @return  string
		 * @since   1.5.0
		 */
		public function bulk_action_handler( $redirect_to, $action, $post_ids ) {

			// Bail out if this is not a status-changing action
			if ( strpos( $action, 'set-' ) === false ) {
				$sendback = remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'locked', 'ids' ), wp_get_referer() );
				wp_redirect( esc_url_raw( $sendback ) );

				exit();
			}

			$donation_statuses = charitable_get_valid_donation_statuses();

			$new_status    = str_replace( 'set-', '', $action ); // get the status name from action

			$report_action = 'bulk_' . Charitable::DONATION_POST_TYPE . '_status_update';

			// Sanity check: bail out if this is actually not a status, or is
			// not a registered status
			if ( ! isset( $donation_statuses[ $new_status ] ) ) {
				return $redirect_to;
			}

			foreach ( $post_ids as $post_id ) {
				$donation = charitable_get_donation( $post_id );
				$donation->update_status( $new_status );
				do_action( 'charitable_donations_table_do_bulk_action', $post_id, $new_status );
			}

			$redirect_to = add_query_arg( $report_action, count( $post_ids ), $redirect_to );

			return $redirect_to;
		}


		/**
		 * Remove edit from the bulk actions.
		 *
		 * @param   array $actions
		 * @return  array
		 * @since   1.5.0
		 */
		public function remove_bulk_actions( $actions ) {
			if ( isset( $actions['edit'] ) ) {
				unset( $actions['edit'] );
			}

			return $actions;
		}

		/**
		 * Retrieve the bulk actions.
		 *
		 * @since  1.5.0
		 *
		 * @return array $actions Array of the bulk actions
		 */
		public function get_bulk_actions() {
			$actions = array();

			foreach ( charitable_get_valid_donation_statuses() as $status_key => $label ) {
				$actions[ 'set-' . $status_key ] = sprintf( _x( 'Set to %s', 'set donation status to x', 'charitable' ), $label );
			}

			/**
			 * Filter the list of bulk actions for donations.
			 *
			 * @since 1.4.0
			 *
			 * @param array $actions The list of bulk actions.
			 */
			return apply_filters( 'charitable_donations_table_bulk_actions', $actions );
		}

		/**
		 * Add extra bulk action options to mark orders as complete or processing.
		 *
		 * Using Javascript until WordPress core fixes: https://core.trac.wordpress.org/ticket/16031
		 *
		 * @since  1.5.0
		 *
		 * @global string $post_type
		 *
		 * @return void
		 */
		public function bulk_admin_footer() {
			global $post_type;

			if ( Charitable::DONATION_POST_TYPE == $post_type ) {

				$js  = '<script type="text/javascript">';
				$js .= '(function($) {';
				foreach ( $this->get_bulk_actions() as $status_key => $label ) {
					$js .= sprintf( "jQuery('<option>').val('%s').text('%s').appendTo( [ '#bulk-action-selector-top', '#bulk-action-selector-bottom' ] );", $status_key, $label );
				}
				$js .= '})(jQuery);';
				$js .= '</script>';

				echo $js;
			}
		}

		/**
		 * Process the new bulk actions for changing order status.
		 *
		 * @since  1.5.0
		 *
		 * @return void
		 */
		public function process_bulk_action() {
			/* We only want to deal with donations. In case any other CPTs have an 'active' action. */
			if ( ! isset( $_REQUEST['post_type'] ) || Charitable::DONATION_POST_TYPE !== $_REQUEST['post_type'] || ! isset( $_REQUEST['post'] ) ) {
				return;
			}

			check_admin_referer( 'bulk-posts' );

			/* Get the action. */
			$action = '';

			if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] ) {
				$action = $_REQUEST['action'];
			} else if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] ) {
				$action = $_REQUEST['action2'];
			}

			$post_ids    = array_map( 'absint', (array) $_REQUEST['post'] );
			$redirect_to = add_query_arg( array(
				'post_type' => Charitable::DONATION_POST_TYPE
			), admin_url( 'edit.php' ) );
			$redirect_to = $this->bulk_action_handler( $redirect_to, $action, $post_ids );

			wp_redirect( esc_url_raw( $redirect_to ) );

			exit();
		}

		/**
		 * Show confirmation message that order status changed for number of orders.
		 *
		 * @since  1.5.0
		 *
		 * @global string $post_type
		 * @global string $pagenow
		 *
		 * @return void
		 */
		public function bulk_admin_notices() {
			global $post_type, $pagenow;

			/* Bail out if not on shop order list page. */
			if ( 'edit.php' !== $pagenow || Charitable::DONATION_POST_TYPE !== $post_type ) {
				return;
			}

			/* Check if any status changes happened. */
			$report_action = 'bulk_' . Charitable::DONATION_POST_TYPE . '_status_update';

			if ( ! empty( $_REQUEST[ $report_action ] ) ) {
				$number = absint( $_REQUEST[ $report_action ] );
				$message = sprintf( _n( 'Donation status changed.', '%s donation statuses changed.', $number, 'charitable' ), number_format_i18n( $number ) );
				echo '<div class="updated"><p>' . $message . '</p></div>';
			}
		}

		/**
		 * Change messages when a post type is updated.
		 *
		 * @since  1.5.0
		 *
		 * @global WP_Post $post
		 * @global int     $post_ID
		 * @param  array   $messages The default list of messages.
		 * @return array
		 */
		public function post_messages( $messages ) {
			global $post, $post_ID;

			$messages[ Charitable::DONATION_POST_TYPE ] = array(
				0 => '', // Unused. Messages start at index 1.
				1 => sprintf( __( 'Donation updated. <a href="%s">View Donation</a>', 'charitable' ), esc_url( get_permalink( $post_ID ) ) ),
				2 => __( 'Custom field updated.', 'charitable' ),
				3 => __( 'Custom field deleted.', 'charitable' ),
				4 => __( 'Donation updated.', 'charitable' ),
				5 => isset( $_GET['revision'] ) ? sprintf( __( 'Donation restored to revision from %s', 'charitable' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6 => sprintf( __( 'Donation published. <a href="%s">View Donation</a>', 'charitable' ), esc_url( get_permalink( $post_ID ) ) ),
				7 => __( 'Donation saved.', 'charitable' ),
				8 => sprintf(
					__( 'Donation submitted. <a target="_blank" href="%s">Preview Donation</a>', 'charitable' ),
					esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) )
				),
				9 => sprintf(
					__( 'Donation scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Donation</a>', 'charitable' ),
					date_i18n( __( 'M j, Y @ G:i', 'charitable' ), strtotime( $post->post_date ) ),
					esc_url( get_permalink( $post_ID ) )
				),
				10 => sprintf(
					__( 'Donation draft updated. <a target="_blank" href="%s">Preview Donation</a>', 'charitable' ),
					esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) )
				),
				11 => __( 'Donation updated and email sent.', 'charitable' ),
				12 => __( 'Email could not be sent.', 'charitable' ),
			);

			return $messages;
		}

		/**
		 * Modify bulk messages
		 *
		 * @since  1.5.0
		 *
		 * @param  array $bulk_messages
		 * @param  array $bulk_counts
		 * @return array
		 */
		public function bulk_messages( $bulk_messages, $bulk_counts ) {
			$bulk_messages[ Charitable::DONATION_POST_TYPE ] = array(
				'updated'   => _n( '%d donation updated.', '%d donations updated.', $bulk_counts['updated'], 'charitable' ),
				'locked'    => ( 1 == $bulk_counts['locked'] ) ? __( '1 donation not updated, somebody is editing it.' ) :
								   _n( '%s donation not updated, somebody is editing it.', '%s donations not updated, somebody is editing them.', $bulk_counts['locked'], 'charitable' ),
				'deleted'   => _n( '%s donation permanently deleted.', '%s donations permanently deleted.', $bulk_counts['deleted'], 'charitable' ),
				'trashed'   => _n( '%s donation moved to the Trash.', '%s donations moved to the Trash.', $bulk_counts['trashed'], 'charitable' ),
				'untrashed' => _n( '%s donation restored from the Trash.', '%s donations restored from the Trash.', $bulk_counts['untrashed'], 'charitable' ),
			);

			return $bulk_messages;
		}

		/**
		 * Disable the month's dropdown (will replace with custom range search).
		 *
		 * @since  1.5.0
		 *
		 * @param  mixed  $public_query_vars Public query vars. Unused.
		 * @param  string $post_type         The current post type.
		 * @return boolean
		 */
		public function disable_months_dropdown( $disable, $post_type ) {
			if ( Charitable::DONATION_POST_TYPE == $post_type ) {
				$disable = true;
			}

			return $disable;
		}

		/**
		 * Add date-based filters above the donations table.
		 *
		 * @since  1.5.0
		 *
		 * @global string $typenow The current post type.
		 *
		 * @return void
		 */
		public function add_filters() {
			global $typenow;

			/* Show custom filters to filter orders by donor. */
			if ( in_array( $typenow, array( Charitable::DONATION_POST_TYPE ) ) ) {
				charitable_admin_view( 'donations-page/filters' );
			}
		}

		/**
		 * Add extra buttons after filters
		 *
		 * @since  1.5.0
		 *
		 * @global string $typenow The current post type.
		 *
		 * @param  string $which The context where this is being called.
		 * @return void
		 */
		public function add_export( $which ) {
			global $typenow;

			/* Add the export button. */
			if ( 'top' == $which && in_array( $typenow, array( Charitable::DONATION_POST_TYPE ) ) ) {
				charitable_admin_view( 'donations-page/export' );
			}
		}

		/**
		 * Add modal template to footer.
		 *
		 * @since  1.5.0
		 *
		 * @global string $typenow The current post type.
		 *
		 * @param  string $which
		 * @return void
		 */
		public function modal_forms() {
			global $typenow;

			/* Add the modal form. */
			if ( in_array( $typenow, array( Charitable::DONATION_POST_TYPE ) ) ) {
				charitable_admin_view( 'donations-page/export-form' );
				charitable_admin_view( 'donations-page/filter-form' );
			}

		}

		/**
		 * Admin scripts and styles.
		 *
		 * Set up the scripts & styles used for the modal.
		 *
		 * @since  1.5.0
		 *
		 * @global string $typenow The current post type.
		 *
		 * @return void
		 */
		public function load_scripts( $hook ) {
			if ( 'edit.php' != $hook ) {
				return;
			}

			global $typenow;

			/* Enqueue the scripts for donation page */
			if ( in_array( $typenow, array( Charitable::DONATION_POST_TYPE ) ) ) {
				wp_enqueue_style( 'lean-modal-css' );
				wp_enqueue_script( 'jquery-core' );
				wp_enqueue_script( 'lean-modal' );
				wp_enqueue_script( 'charitable-admin-tables' );
			}
		}

		/**
		 * Add custom filters to the query that returns the donations to be displayed.
		 *
		 * @since  1.5.0
		 *
		 * @global string $typenow The current post type.
		 *
		 * @param  array  $vars    The array of args to pass to WP_Query.
		 * @return array
		 */
		public function filter_request_query( $vars ) {
			global $typenow;

			if ( Charitable::DONATION_POST_TYPE != $typenow ) {
				return $vars;
			}

			/* No Status: fix WP's crappy handling of "all" post status. */
			if ( ! isset( $vars['post_status'] ) || empty( $vars['post_status'] ) ) {
				$vars['post_status'] = array_keys( charitable_get_valid_donation_statuses() );
			}

			/* Set up date query */
			if ( isset( $_GET['start_date'] ) && ! empty( $_GET['start_date'] ) ) {
				$start_date                  = $this->get_parsed_date( $_GET['start_date'] );
				$vars['date_query']['after'] = array(
					'year'  => $start_date['year'],
					'month' => $start_date['month'],
					'day'   => $start_date['day'],
				);
			}

			if ( isset( $_GET['end_date'] ) && ! empty( $_GET['end_date'] ) ) {
				$end_date                     = $this->get_parsed_date( $_GET['end_date'] );
				$vars['date_query']['before'] = array(
					'year'  => $end_date['year'],
					'month' => $end_date['month'],
					'day'   => $end_date['day'],
				);
			}

			if ( isset( $vars['date_query'] ) ) {
				$vars['date_query']['inclusive'] = true;
			}

			/* Filter by campaign. */
			if ( isset( $_GET['campaign_id'] ) && 'all' != $_GET['campaign_id'] ) {
				$vars['post__in'] = charitable_get_table( 'campaign_donations' )->get_donation_ids_for_campaign( $_GET['campaign_id'] );
			}

			return $vars;
		}

		/**
		 * Column sorting handler.
		 *
		 * @since  1.5.0
		 *
		 * @global string $typenow The current post type.
		 * @global WPDB   $wpdb    The WPDB object.
		 *
		 * @param  array $clauses Array of SQL query clauses.
		 * @return array
		 */
		public function sort_donations( $clauses ) {
			global $typenow, $wpdb;

			if ( Charitable::DONATION_POST_TYPE != $typenow ) {
				return $clauses;
			}

			if ( ! isset( $_GET['orderby'] ) ) {
				return $clauses;
			}

			/* Sorting */
			$order = isset( $_GET['order'] ) && strtoupper( $_GET['order'] ) == 'ASC' ? 'ASC' : 'DESC';

			switch ( $_GET['orderby'] ) {
				case 'amount' :
					$clauses['join'] = "JOIN {$wpdb->prefix}charitable_campaign_donations cd ON cd.donation_id = $wpdb->posts.ID ";
					$clauses['orderby'] = 'cd.amount ' . $order;
					break;
			}

			return $clauses;
		}

		/**
		 * Return the status counts, taking into account any current filters.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		protected function get_status_counts() {
			if ( ! isset( $this->status_counts ) ) {

				$args = array();

				if ( isset( $_GET['s'] ) && strlen( $_GET['s'] ) ) {
					$args['s'] = $_GET['s'];
				}

				if ( isset( $_GET['start_date'] ) && strlen( $_GET['start_date'] ) ) {
					$args['start_date'] = $this->get_parsed_date( $_GET['start_date'] );
				}

				if ( isset( $_GET['end_date'] ) && strlen( $_GET['end_date'] ) ) {
					$args['end_date'] = $this->get_parsed_date( $_GET['end_date'] );
				}

				$status_counts = Charitable_Donations::count_by_status( $args );

				foreach ( charitable_get_valid_donation_statuses() as $key => $label ) {
					$this->status_counts[ $key ] = array_key_exists( $key, $status_counts )
						? $status_counts[ $key ]->num_donations
						: 0;
				}
			}

			return $this->status_counts;
		}

		/**
		 * Given a date, returns an array containing the date, month and year.
		 *
		 * @since  1.5.0
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
	}

endif;
