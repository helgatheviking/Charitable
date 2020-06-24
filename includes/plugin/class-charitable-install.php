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
 * @since     1.0.0
 * @version   1.6.42
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
		 * Includes directory path.
		 *
		 * @since 1.6.42
		 *
		 * @var   string
		 */
		private $includes_path;

		/**
		 * Install the plugin.
		 *
		 * @since 1.0.0
		 *
		 * @param string $includes_path Path to the includes directory.
		 */
		public function __construct( $includes_path ) {
			$this->includes_path = $includes_path;

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
			require_once( $this->includes_path . '/users/class-charitable-roles.php' );
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
			require_once( $this->includes_path . 'abstracts/abstract-class-charitable-db.php' );

			$tables = array(
				$this->includes_path . 'data/class-charitable-donors-db.php'             => 'Charitable_Donors_DB',
				$this->includes_path . 'data/class-charitable-donormeta-db.php'          => 'Charitable_Donormeta_DB',
				$this->includes_path . 'data/class-charitable-campaign-donations-db.php' => 'Charitable_Campaign_Donations_DB',
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
			require_once( $this->includes_path . '/admin/upgrades/class-charitable-upgrade.php' );
			Charitable_Upgrade::get_instance()->populate_upgrade_log_on_install();
		}
	}

endif;
