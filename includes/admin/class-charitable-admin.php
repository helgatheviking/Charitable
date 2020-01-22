<?php
/**
 * Class that sets up the Charitable Admin functionality.
 *
 * @package   Charitable/Classes/Charitable_Admin
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Admin' ) ) :

	/**
	 * Charitable_Admin
	 *
	 * @final
	 * @since 1.0.0
	 */
	final class Charitable_Admin {

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_Admin|null
		 */
		private static $instance = null;

		/**
		 * Donation actions class.
		 *
		 * @var Charitable_Donation_Admin_Actions
		 */
		private $donation_actions;

		/**
		 * Set up the class.
		 *
		 * Note that the only way to instantiate an object is with the charitable_start method,
		 * which can only be called during the start phase. In other words, don't try
		 * to instantiate this object.
		 *
		 * @since  1.0.0
		 */
		protected function __construct() {
			$this->load_dependencies();

			$this->donation_actions = new Charitable_Donation_Admin_Actions;

			do_action( 'charitable_admin_loaded' );
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Admin
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Include admin-only files.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		private function load_dependencies() {
			$admin_dir = charitable()->get_path( 'includes' ) . 'admin/';

			require_once( $admin_dir . 'charitable-core-admin-functions.php' );
			require_once( $admin_dir . 'campaigns/charitable-admin-campaign-hooks.php' );
			require_once( $admin_dir . 'dashboard-widgets/charitable-dashboard-widgets-hooks.php' );
			require_once( $admin_dir . 'donations/charitable-admin-donation-hooks.php' );
			require_once( $admin_dir . 'settings/charitable-settings-admin-hooks.php' );
		}

		/**
		 * Get Charitable_Donation_Admin_Actions class.
		 *
		 * @since  1.5.0
		 *
		 * @return Charitable_Donation_Admin_Actions
		 */
		public function get_donation_actions() {
			return $this->donation_actions;
		}

		/**
		 * Do an admin action.
		 *
		 * @since  1.5.0
		 *
		 * @return boolean|WP_Error WP_Error in case of error. Mixed results if the action was performed.
		 */
		public function maybe_do_admin_action() {
			if ( ! array_key_exists( 'charitable_admin_action', $_GET ) ) {
				return false;
			}

			if ( count( array_diff( array( 'action_type', '_nonce', 'object_id' ), array_keys( $_GET ) ) ) ) {
				return new WP_Error( __( 'Action could not be executed.', 'charitable' ) );
			}

			if ( ! wp_verify_nonce( $_GET['_nonce'], 'donation_action' ) ) {
				return new WP_Error( __( 'Action could not be executed. Nonce check failed.', 'charitable' ) );
			}

			if ( 'donation' != $_GET['action_type'] ) {
				return new WP_Error( __( 'Action from an unknown action type executed.', 'charitable' ) );
			}

			return $this->donation_actions->do_action( $_GET['charitable_admin_action'], $_GET['object_id'] );
		}

		/**
		 * Loads admin-only scripts and stylesheets.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function admin_enqueue_scripts() {
			if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
				$suffix  = '';
				$version = '';
			} else {
				$suffix  = '.min';
				$version = charitable()->get_version();
			}

			$assets_dir = charitable()->get_path( 'assets', false );

			/* Menu styles are loaded everywhere in the WordPress dashboard. */
			wp_register_style(
				'charitable-admin-menu',
				$assets_dir . 'css/charitable-admin-menu' . $suffix . '.css',
				array(),
				$version
			);

			wp_enqueue_style( 'charitable-admin-menu' );

			/* Admin page styles are registered but only enqueued when necessary. */
			wp_register_style(
				'charitable-admin-pages',
				$assets_dir . 'css/charitable-admin-pages' . $suffix . '.css',
				array(),
				$version
			);

			/* The following styles are only loaded on Charitable screens. */
			$screen = get_current_screen();

			if ( ! is_null( $screen ) && in_array( $screen->id, $this->get_charitable_screens() ) ) {

				wp_register_style(
					'charitable-admin',
					$assets_dir . 'css/charitable-admin' . $suffix . '.css',
					array(),
					$version
				);

				wp_enqueue_style( 'charitable-admin' );

				$dependencies   = array( 'jquery-ui-datepicker', 'jquery-ui-tabs', 'jquery-ui-sortable' );
				$localized_vars = array(
					'suggested_amount_placeholder' => __( 'Amount', 'charitable' ),
					'suggested_amount_description_placeholder' => __( 'Optional Description', 'charitable' ),
				);

				if ( 'donation' == $screen->id ) {
					wp_register_script(
						'accounting',
						$assets_dir . 'js/libraries/accounting'. $suffix . '.js',
						array( 'jquery-core' ),
						$version,
						true
					);

					$dependencies[] = 'accounting';
					$localized_vars = array_merge(
						$localized_vars,
						array(
							'currency_format_num_decimals' => esc_attr( charitable_get_currency_helper()->get_decimals() ),
							'currency_format_decimal_sep'  => esc_attr( charitable_get_currency_helper()->get_decimal_separator() ),
							'currency_format_thousand_sep' => esc_attr( charitable_get_currency_helper()->get_thousands_separator() ),
							'currency_format'              => esc_attr( charitable_get_currency_helper()->get_accounting_js_format() ),
						)
					);
				}

				wp_register_script(
					'charitable-admin',
					$assets_dir . 'js/charitable-admin' . $suffix . '.js',
					$dependencies,
					$version,
					false
				);

				wp_enqueue_script( 'charitable-admin' );

				/**
				 * Filter the admin Javascript vars.
				 *
				 * @since 1.0.0
				 *
				 * @param array $localized_vars The vars.
				 */
				$localized_vars = apply_filters( 'charitable_localized_javascript_vars', $localized_vars );

				wp_localize_script( 'charitable-admin', 'CHARITABLE', $localized_vars );

			}//end if

			wp_register_script(
				'charitable-admin-notice',
				$assets_dir . 'js/charitable-admin-notice' . $suffix . '.js',
				array( 'jquery-core' ),
				$version,
				false
			);

			wp_register_script(
				'charitable-admin-media',
				$assets_dir . 'js/charitable-admin-media' . $suffix . '.js',
				array( 'jquery-core' ),
				$version,
				false
			);

			wp_register_script(
				'lean-modal',
				$assets_dir . 'js/libraries/leanModal' . $suffix . '.js',
				array( 'jquery-core' ),
				$version,
				true
			);

			wp_register_style(
				'lean-modal-css',
				$assets_dir . 'css/modal' . $suffix . '.css',
				array(),
				$version
			);

			wp_register_script(
				'charitable-admin-tables',
				$assets_dir . 'js/charitable-admin-tables' . $suffix . '.js',
				array( 'jquery-core', 'lean-modal' ),
				$version,
				true
			);

			wp_register_script(
				'select2',
				$assets_dir . 'js/libraries/select2' . $suffix . '.js',
				array( 'jquery-core' ),
				'4.0.12',
				true
			);

			wp_register_style(
				'select2-css',
				$assets_dir . 'css/libraries/select2' . $suffix . '.css',
				array(),
				'4.0.12'
			);
		}

		/**
		 * Set admin body classes.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $classes Existing list of classes.
		 * @return string
		 */
		public function set_body_class( $classes ) {
			$screen = get_current_screen();

			if ( 'donation' == $screen->post_type && ( 'add' == $screen->action || isset( $_GET['show_form'] ) ) ) {
				$classes .= ' charitable-admin-donation-form';
			}

			return $classes;
		}

		/**
		 * Add notices to the dashboard.
		 *
		 * @since  1.4.0
		 *
		 * @return void
		 */
		public function add_notices() {
			/* Get any version update notices first. */
			$this->add_version_update_notices();

			/* Render notices. */
			charitable_get_admin_notices()->render();
		}

		/**
		 * Add version update notices to the dashboard.
		 *
		 * @since  1.4.6
		 *
		 * @return void
		 */
		public function add_version_update_notices() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$notices = array(
				/* translators: %s: link */
				'release-150' => sprintf( __( "Charitable 1.5 is packed with new features and improvements. <a href='%s' target='_blank'>Find out what's new</a>.", 'charitable' ),
					'https://www.wpcharitable.com/charitable-1-5-release-notes/?utm_source=notice&utm_medium=wordpress-dashboard&utm_campaign=release-notes&utm_content=release-150'
				),
				/* translators: %s: link */
				'release-160' => sprintf( __( 'Charitable 1.6 introduces important new user privacy features and other improvements. <a href="%s" target="_blank">Find out what\'s new</a>.', 'charitable' ),
					'https://www.wpcharitable.com/charitable-1-6-user-privacy-gdpr-better-refunds-and-a-new-shortcode/?utm_source=notice&utm_medium=wordpress-dashboard&utm_campaign=release-notes&utm_content=release-160'
				),
			);

			$helper = charitable_get_admin_notices();

			foreach ( $notices as $notice => $message ) {
				if ( ! get_transient( 'charitable_' . $notice . '_notice' ) ) {
					continue;
				}

				$helper->add_version_update( $message, $notice );
			}
		}

		/**
		 * Dismiss a notice.
		 *
		 * @since  1.4.0
		 *
		 * @return void
		 */
		public function dismiss_notice() {
			if ( ! isset( $_POST['notice'] ) ) {
				wp_send_json_error();
			}

			$ret = delete_transient( 'charitable_' . $_POST['notice'] . '_notice', true );

			if ( ! $ret ) {
				wp_send_json_error( $ret );
			}

			wp_send_json_success();
		}

		/**
		 * Adds one or more classes to the body tag in the dashboard.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $classes Current body classes.
		 * @return string Altered body classes.
		 */
		public function add_admin_body_class( $classes ) {
			$screen = get_current_screen();

			if ( in_array( $screen->post_type, array( Charitable::DONATION_POST_TYPE, Charitable::CAMPAIGN_POST_TYPE ) ) ) {
				$classes .= ' post-type-charitable';
			}

			return $classes;
		}

		/**
		 * Add custom links to the plugin actions.
		 *
		 * @since  1.0.0
		 *
		 * @param  string[] $links Plugin action links.
		 * @return string[]
		 */
		public function add_plugin_action_links( $links ) {
			$links[] = '<a href="' . admin_url( 'admin.php?page=charitable-settings' ) . '">' . __( 'Settings', 'charitable' ) . '</a>';
			return $links;
		}

		/**
		 * Add Extensions link to the plugin row meta.
		 *
		 * @since  1.2.0
		 *
		 * @param  string[] $links Plugin action links.
		 * @param  string   $file  The plugin file.
		 * @return string[] $links
		 */
		public function add_plugin_row_meta( $links, $file ) {
			if ( plugin_basename( charitable()->get_path() ) != $file ) {
				return $links;
			}

			$extensions_link = esc_url(
				add_query_arg(
					array(
						'utm_source'   => 'plugins-page',
						'utm_medium'   => 'plugin-row',
						'utm_campaign' => 'admin',
					),
					'https://wpcharitable.com/extensions/'
				)
			);

			$links[] = '<a href="' . $extensions_link . '">' . __( 'Extensions', 'charitable' ) . '</a>';

			return $links;
		}

		/**
		 * Remove the jQuery UI styles added by Ninja Forms.
		 *
		 * @since  1.2.0
		 *
		 * @param  string $context Media buttons context.
		 * @return string
		 */
		public function remove_jquery_ui_styles_nf( $context ) {
			wp_dequeue_style( 'jquery-smoothness' );
			return $context;
		}

		/**
		 * Export donations.
		 *
		 * @since  1.3.0
		 *
		 * @return false|void Returns false if the export failed. Exits otherwise.
		 */
		public function export_donations() {
			if ( ! wp_verify_nonce( $_GET['_charitable_export_nonce'], 'charitable_export_donations' ) ) {
				return false;
			}

			/**
			 * Filter the donation export arguments.
			 *
			 * @since 1.3.0
			 *
			 * @param array $args Export arguments.
			 */
			$export_args = apply_filters( 'charitable_donations_export_args', array(
				'start_date'  => $_GET['start_date'],
				'end_date'    => $_GET['end_date'],
				'status'      => $_GET['post_status'],
				'campaign_id' => $_GET['campaign_id'],
				'report_type' => $_GET['report_type'],
			) );

			/**
			 * Filter the export class name.
			 *
			 * @since 1.3.0
			 *
			 * @param string $report_type The type of report.
			 * @param array  $args        Export arguments.
			 */
			$export_class = apply_filters( 'charitable_donations_export_class', 'Charitable_Export_Donations', $_GET['report_type'], $export_args );

			new $export_class( $export_args );

			die();
		}

		/**
		 * Export campaigns.
		 *
		 * @since  1.6.0
		 *
		 * @return false|void Returns false if the export failed. Exits otherwise.
		 */
		public function export_campaigns() {
			if ( ! wp_verify_nonce( $_GET['_charitable_export_nonce'], 'charitable_export_campaigns' ) ) {
				return false;
			}

			/**
			 * Filter the donation export arguments.
			 *
			 * @since 1.6.0
			 *
			 * @param array $args Export arguments.
			 */
			$export_args = apply_filters( 'charitable_campaigns_export_args', array(
				'start_date'  => $_GET['start_date'],
				'end_date'    => $_GET['end_date'],
				'status'      => $_GET['status'],
				'report_type' => $_GET['report_type'],
			) );

			/**
			 * Filter the export class name.
			 *
			 * @since 1.6.0
			 *
			 * @param string $report_type The type of report.
			 * @param array  $args        Export arguments.
			 */
			$export_class = apply_filters( 'charitable_campaigns_export_class', 'Charitable_Export_Campaigns', $_GET['report_type'], $export_args );

			new $export_class( $export_args );

			die();
		}


		/**
		 * Returns an array of screen IDs where the Charitable scripts should be loaded.
		 *
		 * @uses   charitable_admin_screens
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_charitable_screens() {
			/**
			 * Filter admin screens where Charitable styles & scripts should be loaded.
			 *
			 * @since 1.0.0
			 *
			 * @param string[] $screens List of screen ids.
			 */
			return apply_filters( 'charitable_admin_screens', array(
				'campaign',
				'donation',
				'charitable_page_charitable-settings',
				'edit-campaign',
				'edit-donation',
				'dashboard',
			) );
		}
	}

endif;
