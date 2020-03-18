<?php
/**
 * Main class for setting up the Charitable Benefactors Addon, which is programatically activated by child themes.
 *
 * @package   Charitable/Classes/Charitable_Benefactors
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.35
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Benefactors' ) ) :

	/**
	 * Charitable_Benefactors
	 *
	 * @since   1.0.0
	 */
	class Charitable_Benefactors implements Charitable_Addon_Interface {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Benefactors|null
		 */
		private static $instance = null;

		/**
		 * Create class object. A private constructor, so this is used in a singleton context.
		 *
		 * @since   1.0.0
		 *
		 * @return  void
		 */
		private function __construct() {
			require_once( 'class-charitable-benefactor.php' );
			require_once( 'class-charitable-benefactors-db.php' );
			require_once( 'charitable-benefactors-hooks.php' );
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.0.0
		 *
		 * @return  Charitable_Benefactors
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Responsible for creating class instances.
		 *
		 * @since   1.0.0
		 *
		 * @return  void
		 */
		public static function load() {
			do_action( 'charitable_benefactors_addon_loaded', Charitable_Benefactors::get_instance() );
		}

		/**
		 * Enqueue script.
		 *
		 * @since   1.2.0
		 *
		 * @return  void
		 */
		public function register_script() {
			$screen = get_current_screen();

			if ( 'campaign' == $screen->id ) {
				$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
				wp_register_script( 'charitable-benefactors-js', charitable()->get_path( 'assets', false ) . 'js/charitable-admin-benefactors' . $suffix . '.js', array( 'charitable-admin' ), charitable()->get_version(), false );
				wp_enqueue_script( 'charitable-benefactors-js' );
			}
		}

		/**
		 * Register table.
		 *
		 * @since   1.0.0
		 *
		 * @param   array $tables Registered tables.
		 * @return  array
		 */
		public function register_table( $tables ) {
			$tables['benefactors'] = 'Charitable_Benefactors_DB';
			return $tables;
		}

		/**
		 * Display a benefactor relationship block inside of a meta box on campaign pages.
		 *
		 * @since   1.0.0
		 *
		 * @param   Charitable_Benefactor $benefactor Benefactor object.
		 * @param   string                $extension  Extension this benefactor object is created by.
		 * @return  void
		 */
		public function benefactor_meta_box( $benefactor, $extension ) {
			charitable_admin_view( 'metaboxes/campaign-benefactors/summary', array(
				'benefactor' => $benefactor,
				'extension' => $extension,
			) );
		}

		/**
		 * Display benefactor relationship form.
		 *
		 * @since   1.0.0
		 *
		 * @param   Charitable_Benefactor $benefactor Benefactor object.
		 * @param   string                $extension  Extension this benefactor object is created by.
		 * @return  void
		 */
		public function benefactor_form( $benefactor, $extension ) {
			charitable_admin_view( 'metaboxes/campaign-benefactors/form', array(
				'benefactor' => $benefactor,
				'extension' => $extension,
			) );
		}

		/**
		 * Save benefactors when saving campaign.
		 *
		 * @since   1.0.0
		 *
		 * @param   WP_Post $post Post object.
		 * @return  void
		 */
		public function save_benefactors( WP_Post $post ) {
			if ( ! isset( $_POST['_campaign_benefactor'] ) ) {
				return;
			}

			$currency_helper = charitable_get_currency_helper();
			$benefactors = $_POST['_campaign_benefactor'];

			foreach ( $benefactors as $campaign_benefactor_id => $data ) {

				/* If the contribution amount was not set, we won't create a benefactor object. */
				if ( empty( $data['contribution_amount'] ) ) {
					continue;
				}

				$data['campaign_id'] = $post->ID;
				$data['contribution_amount_is_percentage'] = intval( false !== strpos( $data['contribution_amount'], '%' ) );
				$data['contribution_amount'] = $currency_helper->sanitize_monetary_amount( $data['contribution_amount'] );

				/* If the contribution amount was set to 0, we won't create a benefactor object. */
				if ( 0 == $data['contribution_amount'] ) {
					continue;
				}

				$sanitize_date = ! charitable()->registry()->get( 'i18n' )->decline_months();

				if ( isset( $data['date_created'] ) && strlen( $data['date_created'] ) ) {
					if ( $sanitize_date ) {
						$data['date_created'] = charitable_sanitize_date( $data['date_created'], 'Y-m-d 00:00:00' );
					} else {
						$data['date_created'] = date( 'Y-m-d 00:00:00', strtotime( $data['date_created'] ) );
					}

				}

				/**
				 * Sanitize end date of benefactor relationship. If the campaign has
				 * an end date, then the benefactor relationship should end then or
				 * before then (not after).
				 */
				$campaign_end_date = get_post_meta( $post->ID, '_campaign_end_date', true );

				if ( isset( $data['date_deactivated'] ) && strlen( $data['date_deactivated'] ) ) {
					if ( $sanitize_date ) {
						$date_deactivated = charitable_sanitize_date( $data['date_deactivated'], 'Y-m-d 00:00:00' );
					} else {
						$date_deactivated = date( 'Y-m-d 00:00:00', strtotime( $data['date_deactivated'] ) );
					}

					$data['date_deactivated'] = ( $campaign_end_date && $campaign_end_date < $date_deactivated ) ? $campaign_end_date : $date_deactivated;
				} elseif ( 0 != $campaign_end_date ) {
					$data['date_deactivated'] = $campaign_end_date;
				}

				/* Insert or update benefactor record */
				if ( 0 == $campaign_benefactor_id ) {
					charitable_get_table( 'benefactors' )->insert( $data );
				} else {
					charitable_get_table( 'benefactors' )->update( $campaign_benefactor_id, $data );
				}
			}//end foreach
		}

		/**
		 * Add a new benefactor block with AJAX.
		 *
		 * @since   1.2.0
		 *
		 * @return  void
		 */
		public function add_benefactor_form() {
			$idx = isset( $_POST['idx'] ) ? $_POST['idx'] : 0;

			if ( ! isset( $_POST['extension'] ) ) {
				wp_die( '-1' );
			}

			ob_start();

			charitable_admin_view( 'metaboxes/campaign-benefactors/form', array(
				'benefactor' => null,
				'extension' => $_POST['extension'],
				'index' => "_{$idx}",
			) );

			echo ob_get_clean();

			wp_die();
		}

		/**
		 * Deactivate a benefactor.
		 *
		 * @since   1.0.0
		 *
		 * @return  void
		 */
		public function delete_benefactor() {
			/* Run a security check first to ensure we initiated this action. */
			check_ajax_referer( 'charitable-deactivate-benefactor', 'nonce' );

			$benefactor_id = isset( $_POST['benefactor_id'] ) ? $_POST['benefactor_id'] : 0;

			if ( ! $benefactor_id ) {
				$return = array( 'error' => __( 'No benefactor ID provided.', 'charitable' ) );
			} else {
				$deleted = charitable_get_table( 'benefactors' )->delete( $benefactor_id );
				$return = array( 'deleted' => $deleted );
			}

			echo json_encode( $return );

			wp_die();
		}

		/**
		 * Called when Charitable is uninstalled and data removal is set to true.
		 *
		 * @since   1.0.0
		 *
		 * @return  void
		 */
		public function uninstall() {
			if ( 'charitable_uninstall' != current_filter() ) {
				return;
			}

			global $wpdb;

			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'charitable_benefactors' );

			delete_option( $wpdb->prefix . 'charitable_benefactors_db_version' );
		}

		/**
		 * Activate the addon.
		 *
		 * @since   1.0.0
		 *
		 * @return  boolean Whether the addon is activated.
		 */
		public static function activate() {
			if ( 'charitable_activate_addon' !== current_filter() ) {
				return false;
			}

			/* Load extension */
			self::load();

			/* Create table */
			$table = new Charitable_Benefactors_DB();
			$table->create_table();

			return true;
		}
	}

endif;
