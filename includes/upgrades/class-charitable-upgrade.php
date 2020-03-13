<?php
/**
 * Charitable Upgrade class.
 *
 * The responsibility of this class is to manage migrations between versions of Charitable.
 *
 * @package   Charitable/Classes/Charitable_Upgrade
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-1.0.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Upgrade' ) ) :

	/**
	 * Charitable_EDD_Upgrade
	 *
	 * @since 1.0.0
	 */
	class Charitable_Upgrade {

		/**
		 * The single class instance.
		 *
		 * @since 1.3.0
		 *
		 * @var   Charitable_Upgrade
		 */
		private static $instance = null;

		/**
		 * Whether the "upgrade in progress" notice has been shown.
		 *
		 * @since 1.6.14
		 *
		 * @var   boolean
		 */
		private $notice_shown_for_upgrade_in_progress = false;

		/**
		 * Current database version.
		 *
		 * @since 1.0.0
		 *
		 * @var   false|string
		 */
		protected $db_version;

		/**
		 * Edge version.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		protected $edge_version;

		/**
		 * Array of methods to perform when upgrading to specific versions.
		 *
		 * @since 1.0.0
		 *
		 * @var   array
		 */
		protected $upgrade_actions;

		/**
		 * Option key for upgrade log.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		protected $upgrade_log_key = 'charitable_upgrade_log';

		/**
		 * Option key for plugin version.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		protected $version_key = 'charitable_version';

		/**
		 * Create and return the class object.
		 *
		 * @since  1.3.0
		 *
		 * @return Charitable_Upgrade
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Manages the upgrade process.
		 *
		 * @since 1.0.0
		 *
		 * @param false|string $db_version   The version we are upgrading from.
		 * @param string       $edge_version The new version.
		 */
		protected function __construct( $db_version = '', $edge_version = '' ) {
			/**
			 * We are keeping this for the sake of backwards compatibility for
			 * extensions that extend Charitable_Upgrade.
			 */
			if ( strlen( $db_version ) && strlen( $edge_version ) ) {
				return $this->legacy_upgrade_mode( $db_version, $edge_version );
			}

			$this->upgrade_actions = array(
				'update_upgrade_system'                   => array(
					'version' => '1.3.0',
					'message' => __( 'Charitable needs to update the database.', 'charitable' ),
					'prompt'  => true,
				),
				'fix_donation_dates'                      => array(
					'version' => '1.3.0',
					'message' => __( 'Charitable needs to fix incorrect donation dates.', 'charitable' ),
					'prompt'  => true,
				),
				'trigger_cron'                            => array(
					'version'  => '1.3.4',
					'message'  => '',
					'prompt'   => false,
					'callback' => array( $this, 'trigger_cron' ),
				),
				'flush_permalinks_140'                    => array(
					'version'  => '1.4.0',
					'message'  => '',
					'prompt'   => false,
					'callback' => array( $this, 'flush_permalinks' ),
				),
				'remove_campaign_manager_cap'             => array(
					'version'  => '1.4.5',
					'message'  => '',
					'prompt'   => false,
					'callback' => array( $this, 'remove_campaign_manager_cap' ),
				),
				'fix_empty_campaign_end_date_meta'        => array(
					'version'  => '1.4.11',
					'message'  => '',
					'prompt'   => false,
					'callback' => array( $this, 'fix_empty_campaign_end_date_meta' ),
				),
				'clear_campaign_amount_donated_transient' => array(
					'version'  => '1.4.18',
					'message'  => '',
					'prompt'   => false,
					'callback' => array( $this, 'clear_campaign_amount_donated_transient' ),
				),
				'trim_upgrade_log'                        => array(
					'version'  => '1.4.18',
					'message'  => '',
					'prompt'   => false,
					'callback' => array( $this, 'trim_upgrade_log' ),
				),
				'remove_duplicate_donors'                 => array(
					'version' => '1.5.0',
					'message' => __( 'Charitable needs to remove duplicate donor records.', 'charitable' ),
					'prompt'  => true,
				),
				'flush_permalinks_150'                    => array(
					'version'  => '1.5.0',
					'message'  => '',
					'prompt'   => false,
					'callback' => array( $this, 'flush_permalinks' ),
				),
				'release_notes_150'                       => array(
					'version' => '1.5.0',
					'notice'  => 'release-150',
				),
				'update_tables_154'                       => array(
					'version'  => '1.5.4',
					'message'  => '',
					'prompt'   => false,
					'callback' => array( $this, 'update_tables' ),
				),
				'fix_donor_role_caps'                     => array(
					'version'  => '1.5.9',
					'message'  => '',
					'prompt'   => false,
					'callback' => array( $this, 'fix_donor_role_caps' ),
				),
				'upgrade_donor_tables'                    => array(
					'version' => '1.6.0',
					'message' => __( 'Charitable needs to upgrade its database tables.', 'charitable' ),
					'prompt'  => true,
				),
				'release_notes_160'                       => array(
					'version' => '1.6.0',
					'notice'  => 'release-160',
				),
				'fix_empty_donor_ids'                     => array(
					'version'         => '1.6.5',
					'message'         => __( 'Charitable needs to fix donations saved without a donor ID.', 'charitable' ),
					'prompt'          => true,
					'active_callback' => array( $this, 'has_empty_donor_ids' ),
				),
				'flush_permalinks_1614'                   => array(
					'version'  => '1.6.14',
					'message'  => '',
					'prompt'   => false,
					'callback' => array( $this, 'flush_permalinks' ),
				),
				'flush_permalinks_1621'                   => array(
					'version'  => '1.6.21',
					'message'  => '',
					'prompt'   => false,
					'callback' => array( $this, 'flush_permalinks' ),
				),
			);
		}

		/**
		 * Use the old upgrade mode.
		 *
		 * @since  1.5.0
		 *
		 * @param  false|string $db_version   Current pre-update version.
		 * @param  string       $edge_version The version we're upgrading to.
		 * @return void
		 */
		public function legacy_upgrade_mode( $db_version, $edge_version ) {
			$this->db_version   = $db_version;
			$this->edge_version = $edge_version;

			/* Perform version upgrades. */
			$this->do_upgrades();

			/* Log the upgrade and update the database version. */
			$this->save_upgrade_log();
			$this->update_db_version();
		}

		/**
		 * Populate the upgrade log when first installing the plugin.
		 *
		 * @since  1.3.0
		 *
		 * @return void
		 */
		public function populate_upgrade_log_on_install() {
			/**
			 * If the log already exists, don't change it.
			 */
			if ( get_option( $this->upgrade_log_key ) ) {
				return;
			}

			$log = array(
				'install' => array(
					'time'    => time(),
					'version' => Charitable::VERSION,
					'message' => __( 'Charitable was installed.', 'charitable' ),
				),
			);

			foreach ( $this->upgrade_actions as $key => $notes ) {
				$log[ $key ] = array(
					'time'    => time(),
					'version' => Charitable::VERSION,
					'install' => true,
				);
			}

			add_option( $this->upgrade_log_key, $log );
		}

		/**
		 * Check if there is an upgrade that needs to happen and if so, displays a notice to begin upgrading.
		 *
		 * @since  1.3.0
		 *
		 * @return void
		 */
		public function add_upgrade_notice() {
			if ( isset( $_GET['page'] ) && 'charitable-upgrades' == $_GET['page'] ) {
				return;
			}

			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				return;
			}

			/**
			 * If an upgrade is still in progress, continue it until it's done.
			 */
			if ( ! Charitable_Upgrade::get_instance()->notice_shown_for_upgrade_in_progress() ) {
				$upgrade_progress = $this->upgrade_is_in_progress();

				if ( false !== $upgrade_progress ) {
					return Charitable_Upgrade::get_instance()->show_upgrade_in_progress_notice( $upgrade_progress );
				}
			}

			$this->walk_upgrade_actions( array( $this, 'show_upgrade_notice' ) );
		}

		/**
		 * Show upgrade notice for a particular upgrade notice.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $action  The upgrade action key.
		 * @param  array  $upgrade Upgrade args.
		 * @return boolean
		 */
		public function show_upgrade_notice( $action, $upgrade ) {
			if ( $this->do_upgrade_immediately( $upgrade ) ) {
				return;
			}

			/* Check if we're just setting a transient to display a notice. */
			if ( array_key_exists( 'notice', $upgrade ) ) {
				return $this->set_update_notice_transient( $upgrade, $action );
			}
			?>
			<div class="updated">
				<p>
					<?php
					printf(
						'%s %s',
						$upgrade['message'],
						sprintf(
							/* translators: %s: upgrade link */
							__( 'Click <a href="%s">here</a> to start the upgrade.', 'charitable' ),
							esc_url( admin_url( 'index.php?page=charitable-upgrades&charitable-upgrade=' . $action ) )
						)
					);
					?>
				</p>
			</div>
<?php
		}

		/**
		 * Perform any immediate upgrades.
		 *
		 * @since  1.5.0
		 *
		 * @return void
		 */
		public function do_immediate_upgrades() {
			if ( is_admin() && isset( $_GET['page'] ) && 'charitable-upgrades' == $_GET['page'] ) {
				return;
			}

			$this->walk_upgrade_actions( array( $this, 'perform_immediate_upgrade' ) );
		}

		/**
		 * Perform an immediate upgrade and log the result.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $action  The upgrade's action key.
		 * @param  array  $upgrade Upgrade details.
		 * @return void
		 */
		public function perform_immediate_upgrade( $action, $upgrade ) {
			if ( $this->do_upgrade_immediately( $upgrade ) ) {
				$ret = call_user_func( $upgrade['callback'], $action );

				/* If the upgrade succeeded, update the log. */
				if ( $ret ) {
					$this->update_upgrade_log( $action );
				}
			}
		}

		/**
		 * Walk over the array of upgrade actions, performing the callback
		 * for any upgrades that have not been completed yet.
		 *
		 * @since  1.5.0
		 *
		 * @param  callback $callback Callback method to perform if the
		 *                            upgrade has not been completed.
		 * @return void
		 */
		protected function walk_upgrade_actions( $callback ) {
			if ( ! is_callable( $callback ) ) {
				return;
			}

			foreach ( $this->upgrade_actions as $action => $upgrade ) {

				if ( ! $this->upgrade_has_been_completed( $action ) ) {

					/* If the upgrade has an active_callback, check whether the upgrade is required. If not, mark it as done. */
					if ( array_key_exists( 'active_callback', $upgrade ) && ! call_user_func( $upgrade['active_callback'] ) ) {
						$this->update_upgrade_log( $action );
						continue;
					}

					call_user_func( $callback, $action, $upgrade );
				}
			}
		}

		/**
		 * Checks whether an upgrade has been completed.
		 *
		 * @since  1.3.0
		 * @since  1.6.0 Changed access to public.
		 *
		 * @param  string $action The upgrade action.
		 * @return boolean
		 */
		public function upgrade_has_been_completed( $action ) {
			$log = get_option( $this->upgrade_log_key );

			return is_array( $log ) && array_key_exists( $action, $log );
		}


		/**
		 * Check whether a notice has been shown to say that an upgrade is in progress.
		 *
		 * @since  1.6.14
		 *
		 * @return boolean
		 */
		public function notice_shown_for_upgrade_in_progress() {
			return $this->notice_shown_for_upgrade_in_progress;
		}

		/**
		 * Show the "upgrade in progress" notice.
		 *
		 * @since  1.6.14
		 *
		 * @param  array $upgrade_progress Array detailing the upgrade progress.
		 * @return void
		 */
		public function show_upgrade_in_progress_notice( $upgrade_progress ) {
			/* Fixes a bug that incorrectly set the page as charitable-upgrade */
			if ( isset( $upgrade_progress['page'] ) && 'charitable-upgrade' == $upgrade_progress['page'] ) {
				$upgrade_progress['page'] = 'charitable-upgrades';
			}
?>
			<div class="error">
				<p>
					<?php
						printf(
							/* translators: %s: upgrade link */
							__( 'Charitable needs to complete an upgrade that was started earlier. Click <a href="%s">here</a> to continue the upgrade.', 'charitable' ),
							esc_url( add_query_arg( $upgrade_progress, admin_url( 'index.php' ) ) )
						);
					?>
				</p>
			</div>
<?php
			$this->notice_shown_for_upgrade_in_progress = true;
		}

		/**
		 * Evaluates two version numbers and determines whether an upgrade is
		 * required for version A to get to version B.
		 *
		 * @since  1.0.0
		 *
		 * @param  false|string $version_a Current stored version.
		 * @param  string       $version_b Version we are upgrading to.
		 * @return bool
		 */
		public static function requires_upgrade( $version_a, $version_b ) {
			return false === $version_a || version_compare( $version_a, $version_b, '<' );
		}

		/**
		 * Set up cron.
		 *
		 * @since  1.4.18
		 *
		 * @param  string $action The upgrade action.
		 * @return boolean Whether the event was scheduled.
		 */
		public function trigger_cron( $action ) {
			return Charitable_Cron::schedule_events();
		}

		/**
		 * This just flushes the permalinks on the `init` hook.
		 *
		 * Called by 1.0.1 and 1.1.3 update scripts.
		 *
		 * @since  1.1.3
		 *
		 * @param  string $action The upgrade action.
		 * @return true Will always return true.
		 */
		public function flush_permalinks( $action = '' ) {
			return add_action( 'init', 'flush_rewrite_rules' );
		}

		/**
		 * Upgrade to version 1.1.0.
		 *
		 * This sets up the daily scheduled event.
		 *
		 * @since  1.1.0
		 *
		 * @return boolean Whether the event was scheduled.
		 */
		public function upgrade_1_1_0() {
			return Charitable_Cron::schedule_events();
		}

		/**
		 * Update the upgrade system.
		 *
		 * Also updates the campaign donations table to start storing amounts as DECIMAL, instead of FLOAT.
		 *
		 * This upgrade routine was added in 1.3.0.
		 *
		 * @see    https://github.com/Charitable/Charitable/issues/56
		 *
		 * @since  1.3.0
		 *
		 * @return void
		 */
		public function update_upgrade_system() {
			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_die( __( 'You do not have permission to do Charitable upgrades', 'charitable' ), __( 'Error', 'charitable' ), array( 'response' => 403 ) );
			}

			ignore_user_abort( true );

			if ( ! charitable_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
				@set_time_limit( 0 );
			}

			/**
			 * Update the campaign donations table to use DECIMAL for amounts.
			 *
			 * @see 	https://github.com/Charitable/Charitable/issues/56
			 */
			$table = new Charitable_Campaign_Donations_DB();
			$table->create_table();

			$this->upgrade_logs();

			$this->finish_upgrade( 'update_upgrade_system' );
		}

		/**
		 * Fix the donation dates.
		 *
		 * This upgrade routine was added in 1.3.0
		 *
		 * @see    https://github.com/Charitable/Charitable/issues/58
		 *
		 * @since  1.3.0
		 *
		 * @return void
		 */
		public function fix_donation_dates() {
			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_die( __( 'You do not have permission to do Charitable upgrades', 'charitable' ), __( 'Error', 'charitable' ), array( 'response' => 403 ) );
			}

			ignore_user_abort( true );

			if ( ! charitable_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
				@set_time_limit( 0 );
			}

			$step   = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
			$number = 20;

			$total  = Charitable_Donations::count_all();

			/**
			 * If there are no donations to update, go ahead and wrap it up right now.
			 */
			if ( ! $total ) {
				$this->finish_upgrade( 'fix_donation_dates' );
			}

			$donations = get_posts( array(
				'post_type' => Charitable::DONATION_POST_TYPE,
				'posts_per_page' => $number,
				'paged' => $step,
				'post_status' => array_keys( charitable_get_valid_donation_statuses() ),
			) );

			if ( count( $donations ) ) {

				/**
				 * Prevent donation receipt & admin notifications from getting resent.
				 */
				remove_action( 'save_post_' . Charitable::DONATION_POST_TYPE, array( 'Charitable_Email_Donation_Receipt', 'send_with_donation_id' ) );
				remove_action( 'save_post_' . Charitable::DONATION_POST_TYPE, array( 'Charitable_Email_New_Donation', 'send_with_donation_id' ) );

				foreach ( $donations as $donation ) {

					/**
					 * Thankfully, we store the timestamp of the donation in the log,
					 * so we can use that to correct any incorrect post_date/post_date_gmt
					 * values.
					 */
					$donation_log = get_post_meta( $donation->ID, '_donation_log', true );

					if ( empty( $donation_log ) ) {
						continue;
					}

					$time = $donation_log[0]['time'];

					$date_gmt = gmdate( 'Y-m-d H:i:s', $time );

					if ( $date_gmt == $donation->post_date_gmt ) {
						continue;
					}

					$date = get_date_from_gmt( $date_gmt );

					wp_update_post( array(
						'ID' => $donation->ID,
						'post_date' => $date,
						'post_date_gmt' => $date_gmt,
					) );
				}//end foreach

				$step++;

				$redirect = add_query_arg( array(
					'page' => 'charitable-upgrades',
					'charitable-upgrade' => 'fix_donation_dates',
					'step' => $step,
					'number' => $number,
					'total' => $total,
				), admin_url( 'index.php' ) );

				wp_redirect( $redirect );

				exit;
			}//end if

			$this->upgrade_logs();

			$this->finish_upgrade( 'fix_donation_dates' );
		}

		/**
		 * Remove duplicate donor records.
		 *
		 * This upgrade routine was added in 1.5.0
		 *
		 * @see    https://github.com/Charitable/Charitable/issues/409
		 *
		 * @since  1.5.0
		 *
		 * @global WPDB $wpdb
		 * @return void
		 */
		public function remove_duplicate_donors() {
			global $wpdb;

			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_die( __( 'You do not have permission to do Charitable upgrades', 'charitable' ), __( 'Error', 'charitable' ), array( 'response' => 403 ) );
			}

			ignore_user_abort( true );

			if ( ! charitable_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
				@set_time_limit( 0 );
			}

			$step     = array_key_exists( 'step', $_GET ) ? absint( $_GET['step'] ) : 1;
			$number   = 20;
			$subquery = "SELECT GROUP_CONCAT(donor_id, ':', user_id) AS donor, COUNT(*) AS count
				FROM {$wpdb->prefix}charitable_donors
				GROUP BY email";
			$total    = $wpdb->get_var( "SELECT COUNT( d.donor ) FROM ( {$subquery} ) AS d WHERE d.count > 1" );

			/**
			 * If there are no donors left to remove, go ahead and wrap it up right now.
			 */
			if ( ! $total ) {
				$this->finish_upgrade( 'remove_duplicate_donors' );
			}

			$donors = $wpdb->get_col( "SELECT d.donor FROM ( {$subquery} ) AS d WHERE d.count > 1" );

			if ( count( $donors ) ) {

				foreach ( $donors as $donor ) {

					$records    = explode( ',', $donor );
					$canonical  = false;
					$duplicates = array();

					foreach ( $records as $record ) {
						list( $donor_id, $user_id ) = explode( ':', $record );

						if ( 0 == $user_id && false === $canonical ) {
							$canonical = $donor_id;
						} else {
							$duplicates[] = $donor_id;
						}
					}

					/**
					 * It seems very unlikely that this would ever be the case, but
					 * it could theoretically happen if someone registers an account,
					 * donates while logged in, then registers a different account
					 * (using a different email address), and makes a donation while
					 * logged into that account but with the first email address. In
					 * that case, there could be two donor records, both with user IDs.
					 *
					 * To reconcile that, we just pick the first donor record and consider
					 * the remainder as duplicates.
					 */
					if ( false === $canonical ) {
						$canonical = array_shift( $duplicates );
					}

					/* Update donations from duplicate accounts and set canonical donor_id. */
					$donor_id_placeholders = charitable_get_query_placeholders( count( $duplicates ), '%d' );
					$sql                   = "UPDATE {$wpdb->prefix}charitable_campaign_donations
						SET donor_id = %d
						WHERE donor_id IN ( {$donor_id_placeholders} )";

					$parameters = array_merge( array( $canonical ), $duplicates );
					$updated    = $wpdb->query( $wpdb->prepare( $sql, array_merge( array( $canonical ), $duplicates ) ) );

					if ( false !== $updated ) {
						$sql     = "DELETE FROM {$wpdb->prefix}charitable_donors WHERE donor_id IN ( {$donor_id_placeholders} );";
						$deleted = $wpdb->query( $wpdb->prepare( $sql, $duplicates ) );
					}
				}//end foreach

				$step++;

				$redirect = add_query_arg( array(
					'charitable-upgrade' => 'remove_duplicate_donors',
					'page'               => 'charitable-upgrades',
					'step'               => $step,
					'number'             => $number,
					'total'              => $total,
				), admin_url( 'index.php' ) );

				wp_redirect( $redirect );

				exit;
			}//end if

			$this->finish_upgrade( 'remove_duplicate_donors' );
		}

		/**
		 * Upgrade the logs structure.
		 *
		 * This upgrade routine was added in 1.3.0.
		 *
		 * @see    Charitable_Upgrade::update_upgrade_system()
		 * @see    Charitable_upgrade::fix_donation_dates()
		 *
		 * @since  1.3.0
		 *
		 * @return void
		 */
		public function upgrade_logs() {
			/**
			 * Deal with old upgrades.
			 */
			$log = get_option( $this->upgrade_log_key, false );

			/**
			 * Both of the 1.3 upgrades call this, so we need to make sure it hasn't run yet.
			 */
			if ( is_array( $log ) && isset( $log['legacy_logs'] ) ) {
				return;
			}

			$last_log = ! is_array( $log ) ? false : end( $log );

			/**
			 * If we're upgrading from prior to 1.1.0, we'll schedule events and flush rewrite rules.
			 */
			if ( false === $last_log || version_compare( $last_log['to'], '1.1.0', '<' ) ) {
				Charitable_Cron::schedule_events(); // 1.1.0 upgrade
				flush_rewrite_rules(); // 1.2.0 upgrade
			} /**
			 * If we're upgrade from prior to 1.2.0, we'll just flush the rewrite rules.
			 */
			elseif ( version_compare( $last_log['to'], '1.2.0', '<' ) ) {
				flush_rewrite_rules(); // 1.2.0 upgrade
			}

			/**
			 * Update the upgrade log and save all old logs as 'legacy_logs'.
			 */
			if ( is_array( $log ) ) {
				$new_log = array(
					'legacy_logs' => array(
						'time'    => time(),
						'version' => charitable()->get_version(),
						'logs'    => $log,
					),
				);

				update_option( $this->upgrade_log_key, $new_log );
			}
		}

		/**
		 * Remove the 'manage_charitable_settings' cap from the Campaign Manager role.
		 *
		 * @since  1.4.5
		 *
		 * @global WP_Roles
		 * @param  string $action The upgrade action.
		 * @return true Will always return true.
		 */
		public function remove_campaign_manager_cap( $action ) {
			global $wp_roles;

			if ( class_exists( 'WP_Roles' ) ) {
				if ( ! isset( $wp_roles ) ) {
					$wp_roles = new WP_Roles();
				}
			}

			if ( is_object( $wp_roles ) ) {
				$wp_roles->remove_cap( 'campaign_manager', 'manage_charitable_settings' );
			}

			return true;
		}

		/**
		 * Convert the campaign end date meta to 0 for any campaigns where it is currently blank.
		 *
		 * @since  1.4.11
		 *
		 * @global WPDB $wpdb
		 * @param  string $action The upgrade action.
		 * @return boolean Whether the query was successfully executed.
		 */
		public function fix_empty_campaign_end_date_meta( $action ) {
			global $wpdb;

			$sql = "UPDATE $wpdb->postmeta
					INNER JOIN $wpdb->posts
					ON $wpdb->posts.ID = $wpdb->postmeta.post_id
					SET $wpdb->postmeta.meta_value = 0
					WHERE $wpdb->postmeta.meta_key = '_campaign_end_date'
					AND $wpdb->postmeta.meta_value = ''
					AND $wpdb->posts.post_type = 'campaign';";

			$ret = $wpdb->query( $sql );

			return false !== $ret;
		}

		/**
		 * Clear the campaign amount donated transients.
		 *
		 * @since  1.4.18
		 *
		 * @global WPDB $wpdb
		 * @param  string $action The upgrade action.
		 * @return boolean Whether the event was scheduled.
		 */
		public function clear_campaign_amount_donated_transient( $action ) {
			global $wpdb;

			$sql = "DELETE FROM $wpdb->options
					WHERE option_name LIKE '_transient_charitable_campaign_%_donation_amount'";

			$ret = $wpdb->query( $sql );

			return false !== $ret;
		}

		/**
		 * Prior to 1.4.18, when first installing Charitable all the upgrade details were
		 * stored in the upgrade log (including serialized instances of any objects used).
		 * As of 1.4.18, we only store the time, version when the upgrade took place and
		 * whether the upgrade was done at install time.
		 *
		 * @since  1.4.18
		 *
		 * @return boolean Whether the log was successfully updated.
		 */
		public function trim_upgrade_log() {
			$log     = get_option( $this->upgrade_log_key );
			$new_log = array();
			$keys    = array( 'time', 'version', 'install' );

			foreach ( $log as $action => $details ) {

				$action_log = array();

				if ( array_key_exists( 'time', $details ) ) {
					$action_log['time'] = $details['time'];
				}

				if ( array_key_exists( 'version', $details ) ) {
					$action_log['version'] = $details['version'];
				}

				if ( array_key_exists( 'install', $details ) && $details['install'] ) {
					$action_log['install'] = $details['install'];

					/* Use the version when the plugin was installed. */
					if ( array_key_exists( 'install', $log ) ) {
						$action_log['version'] = $log['install']['version'];
					}
				}

				$new_log[ $action ] = $action_log;

			}

			return update_option( $this->upgrade_log_key, $new_log );
		}

		/**
		 * Set a transient to display an update notice.
		 *
		 * @since  1.4.0
		 *
		 * @param  array  $upgrade The upgrade details.
		 * @param  string $action  The action key for the upgrade.
		 * @return void
		 */
		public function set_update_notice_transient( $upgrade, $action ) {
			set_transient( 'charitable_' . $upgrade['notice'] . '_notice', 1 );

			$this->update_upgrade_log( $action );
		}

		/**
		 * Checks whether an upgrade should be completed immediately, without a prompt.
		 *
		 * @since  1.3.4
		 *
		 * @param  array $upgrade The upgrade parameters.
		 * @return boolean
		 */
		protected function do_upgrade_immediately( $upgrade ) {
			/* If a prompt is required, return false. */
			if ( ! isset( $upgrade['prompt'] ) || $upgrade['prompt'] ) {
				return false;
			}

			/* If the callback is set and it's callable, return true. */
			return isset( $upgrade['callback'] ) && is_callable( $upgrade['callback'] );
		}

		/**
		 * Checks whether an upgrade is in progress.
		 *
		 * @since  1.3.0
		 *
		 * @return false|array False if the upgrade is not in progress.
		 */
		protected function upgrade_is_in_progress() {
			$doing_upgrade = get_option( 'charitable_doing_upgrade', false );

			if ( empty( $doing_upgrade ) ) {
				return false;
			}

			return $doing_upgrade;
		}

		/**
		 * Finish an upgrade. This clears the charitable_doing_upgrade setting and updates the log.
		 *
		 * @since  1.3.0
		 *
		 * @param  string $upgrade      The upgrade action.
		 * @param  string $redirect_url Optional URL to redirect to after the upgrade.
		 * @return void
		 */
		protected function finish_upgrade( $upgrade, $redirect_url = '' ) {
			delete_option( 'charitable_doing_upgrade' );

			$this->update_upgrade_log( $upgrade );

			if ( empty( $redirect_url ) ) {
				$redirect_url = admin_url( 'index.php' );
			}

			wp_redirect( $redirect_url );

			exit();
		}

		/**
		 * Add a completed upgrade to the upgrade log.
		 *
		 * @since  1.3.0
		 *
		 * @param  string $upgrade The upgrade action.
		 * @return boolean False if value was not updated and true if value was updated.
		 */
		protected function update_upgrade_log( $upgrade ) {
			$log = get_option( $this->upgrade_log_key );

			$log[ $upgrade ] = array(
				'time'    => time(),
				'version' => charitable()->get_version(),
			);

			return update_option( $this->upgrade_log_key, $log );
		}

		/**
		 * Update the campaign donations and donors tables.
		 *
		 * @since  1.5.4
		 *
		 * @return boolean Whether tables were successfully updated.
		 */
		protected function update_tables() {
			try {
				charitable_get_table( 'campaign_donations' )->create_table();
				charitable_get_table( 'donors' )->create_table();
				return true;
			} catch ( Exception $e ) {
				return false;
			}
		}

		/**
		 * Update the donors table and add the donormeta table.
		 *
		 * @since  1.6.0
		 *
		 * @return boolean Whether tables were successfully updated.
		 */
		public function upgrade_donor_tables() {
			try {
				charitable_get_table( 'donors' )->create_table();
				charitable_get_table( 'donormeta' )->create_table();

				$this->finish_upgrade( 'upgrade_donor_tables' );

				return true;
			} catch ( Exception $e ) {
				return false;
			}
		}

		/**
		 * Fix the donor role caps.
		 *
		 * @since  1.5.9
		 *
		 * @return true
		 */
		public function fix_donor_role_caps() {
			remove_role( 'donor' );

			add_role( 'donor', __( 'Donor', 'charitable' ), array( 'read' => true ) );

			return true;
		}

		/**
		 * Checks whether the site has donations missing a donor id.
		 *
		 * @since  1.6.5
		 *
		 * @return boolean
		 */
		protected function has_empty_donor_ids() {
			return 0 < $this->count_empty_donor_id_donations( get_option( 'charitable_skipped_donations_with_empty_donor_id', array() ) );
		}

		/**
		 * Count donations with empty donor ids.
		 *
		 * @since  1.6.6
		 *
		 * @param  array $skipped List of skipped donations.
		 * @return int
		 */
		protected function count_empty_donor_id_donations( $skipped = array() ) {
			if ( empty( $skipped ) ) {
				return charitable()->get_db_table( 'campaign_donations' )->count_donations_by_donor( 0, true );
			}

			global $wpdb;

			$placeholders = charitable_get_query_placeholders( count( $skipped ), '%d' );
			$sql          = "SELECT COUNT(*)
            				 FROM {$wpdb->prefix}charitable_campaign_donations
            				 WHERE donor_id = 0
            		       	 AND donation_id NOT IN ( $placeholders )";

			return $wpdb->get_var( $wpdb->prepare( $sql, $skipped ) );
		}

		/**
		 * Get donations with empty donor ids.
		 *
		 * @since  1.6.6
		 *
		 * @param  array $skipped List of skipped donations.
		 * @param  int   $number  The number of donations to retrieve.
		 * @return int
		 */
		protected function get_empty_donor_id_donations( $skipped = array(), $number = 20 ) {
			global $wpdb;

			if ( empty( $skipped ) ) {
				return $wpdb->get_col( "SELECT DISTINCT donation_id
					FROM {$wpdb->prefix}charitable_campaign_donations
					WHERE donor_id = 0
					LIMIT $number;" );
			}

			$placeholders = charitable_get_query_placeholders( count( $skipped ), '%d' );

			return $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT donation_id
				FROM {$wpdb->prefix}charitable_campaign_donations
				WHERE donor_id = 0
				AND donation_id NOT IN ( $placeholders )
				LIMIT $number;", $skipped ) );
		}

		/**
		 * Fix donations with missing donor ids.
		 *
		 * @since  1.6.5
		 *
		 * @return void
		 */
		public function fix_empty_donor_ids() {
			global $wpdb;

			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_die( __( 'You do not have permission to do Charitable upgrades', 'charitable' ), __( 'Error', 'charitable' ), array( 'response' => 403 ) );
			}

			ignore_user_abort( true );

			if ( ! charitable_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
				@set_time_limit( 0 );
			}

			$step    = array_key_exists( 'step', $_GET ) ? absint( $_GET['step'] ) : 1;
			$number  = 20;
			$skipped = get_option( 'charitable_skipped_donations_with_empty_donor_id', array() );
			$total   = $this->count_empty_donor_id_donations( $skipped );

			/**
			 * If there are no donors left to remove, go ahead and wrap it up right now.
			 */
			if ( ! $total ) {
				delete_option( 'charitable_skipped_donations_with_empty_donor_id' );

				$this->finish_upgrade( 'fix_empty_donor_ids' );
			}

			$donations_table = charitable()->get_db_table( 'campaign_donations' );
			$donors_table    = charitable()->get_db_table( 'donors' );
			$donations       = $this->get_empty_donor_id_donations( $skipped, $number );

			if ( count( $donations ) ) {

				foreach ( $donations as $donation_id ) {
					$data = get_post_meta( $donation_id, 'donor', true );

					if ( ! array_key_exists( 'email', $data ) || empty( $data['email'] ) ) {
						$skipped[] = $donation_id;
						continue;
					}

					$donor_id = $donors_table->get_donor_id_by_email( $data['email'] );

					if ( ! $donor_id ) {
						$donor_id = $donors_table->insert( array(
							'email'       => $data['email'],
							'first_name'  => array_key_exists( 'first_name', $data ) ? $data['first_name'] : '',
							'last_name'   => array_key_exists( 'last_name', $data ) ? $data['last_name'] : '',
							'date_joined' => get_post_field( 'post_date_gmt', $donation_id ),
						) );
					}

					if ( $donor_id ) {
						$donations_table->update( $donation_id, array(
							'donor_id' => $donor_id,
						), 'donation_id' );
					} else {
						$skipped[] = $donation_id;
					}
				}//end foreach

				update_option( 'charitable_skipped_donations_with_empty_donor_id', $skipped );

				$step++;

				$redirect = add_query_arg( array(
					'charitable-upgrade' => 'fix_empty_donor_ids',
					'page'               => 'charitable-upgrades',
					'step'               => $step,
					'number'             => $number,
					'total'              => $total,
				), admin_url( 'index.php' ) );

				wp_redirect( $redirect );

				exit;
			}//end if

			delete_option( 'charitable_skipped_donations_with_empty_donor_id' );

			$this->finish_upgrade( 'fix_empty_donor_ids' );
		}

		/**
		 * HERE BE DEPRECATED FUNCTIONS...
		 *
		 * We're keeping these functions since Charitable add-ons extend this
		 * class and don't know whether those have upgraded.
		 */

		/**
		 * Upgrade from the current version stored in the database to the live version.
		 *
		 * @since  1.0.0
		 *
		 * @param  false|string $db_version   The version stored in the database.
		 * @param  string       $edge_version The new version to upgrade to.
		 * @return void
		 */
		public static function upgrade_from( $db_version, $edge_version ) {
			if ( self::requires_upgrade( $db_version, $edge_version ) ) {
				new Charitable_Upgrade( $db_version, $edge_version );
			}
		}

		/**
		 * Perform version upgrades.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		protected function do_upgrades() {
			/**
			 * Before Charitable 1.3, upgrades were in a simple key=>value
			 * array format.
			 *
			 * $upgrade_actions = array(
			 * 	'1.0.1' => 'flush_permalinks',
			 * 	'1.1.0' => 'upgrade_1_1_0',
			 * 	'1.1.3' => 'flush_permalinks',
			 * 	'1.2.0' => 'flush_permalinks',
			 * );
			 */

			if ( empty( $this->upgrade_actions ) || ! is_array( $this->upgrade_actions ) ) {
				return;
			}

			foreach ( $this->upgrade_actions as $version => $method ) {

				/**
				 * The do_upgrades method was called, but the $upgrade_actions data structure has changed.
				 *
				 * This should never happen.
				 */
				if ( is_array( $method ) ) {
					return;
				}

				if ( self::requires_upgrade( $this->db_version, $version ) ) {

					call_user_func( array( $this, $method ) );

				}
			}
		}

		/**
		 * Saves a log of the version to version upgrades made.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		protected function save_upgrade_log() {
			$log = get_option( $this->upgrade_log_key );

			if ( false === $log || ! is_array( $log ) ) {
				$log = array();
			}

			$log[] = array(
				'timestamp'		=> time(),
				'from'			=> $this->db_version,
				'to'			=> $this->edge_version,
			);

			update_option( $this->upgrade_log_key, $log );
		}

		/**
		 * Upgrade complete. This saves the new version to the database.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		protected function update_db_version() {
			update_option( $this->version_key, $this->edge_version );
		}
	}

endif;
