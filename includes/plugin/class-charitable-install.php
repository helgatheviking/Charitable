<?php
/**
 * Charitable Install class.
 *
 * The responsibility of this class is to manage the events that need to happen
 * when the plugin is activated.
 *
 * @package   Charitable/Class/Charitable Install
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since    1.0.0
 * @version   1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Install' ) ) :

	/**
	 * Charitable_Install
	 *
	 * @since  1.0.0
	 */
	class Charitable_Install {

		/**
		 * Install the plugin.
		 *
		 * @since  1.0.0
		 */
		public function __construct() {
			$this->setup_roles();
			$this->create_tables();
			$this->setup_upgrade_log();

			set_transient( 'charitable_install', 1, 0 );
		}

		/**
		 * Finish the plugin installation.
		 *
		 * @since  1.3.4
		 *
		 * @return void
		 */
		public static function finish_installing() {
			Charitable_Cron::schedule_events();

			add_action( 'init', 'flush_rewrite_rules' );
		}

		/**
		 * Create wp roles and assign capabilities
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		protected function setup_roles() {
			require_once( 'users/class-charitable-roles.php' );
			$roles = new Charitable_Roles();
			$roles->add_roles();
			$roles->add_caps();
		}

		/**
		 * Create database tables.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		protected function create_tables() {
			require_once( 'abstracts/abstract-class-charitable-db.php' );

			$tables = array(
				'db/class-charitable-donors-db.php'    => 'Charitable_Donors_DB',
				'db/class-charitable-donormeta-db.php' => 'Charitable_Donormeta_DB',
				'db/class-charitable-campaign-donations-db.php' => 'Charitable_Campaign_Donations_DB',
			);

			foreach ( $tables as $file => $class ) {
				require_once( $file );
				$table = new $class;
				$table->create_table();
			}
		}

		/**
		 * Set up the upgrade log.
		 *
		 * @since  1.3.0
		 *
		 * @return void
		 */
		protected function setup_upgrade_log() {
			require_once( 'admin/upgrades/class-charitable-upgrade.php' );
			Charitable_Upgrade::get_instance()->populate_upgrade_log_on_install();
		}
	}

endif;
