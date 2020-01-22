<?php
/**
 * Charitable Advanced Settings UI.
 *
 * @package   Charitable/Classes/Charitable_Advanced_Settings
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Advanced_Settings' ) ) :

	/**
	 * Charitable_Advanced_Settings
	 *
	 * @final
	 * @since   1.0.0
	 */
	final class Charitable_Advanced_Settings {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Advanced_Settings|null
		 */
		private static $instance = null;

		/**
		 * Create object instance.
		 *
		 * @since   1.0.0
		 */
		private function __construct() {
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.2.0
		 *
		 * @return  Charitable_Advanced_Settings
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Add the advanced tab settings fields.
		 *
		 * @since   1.0.0
		 *
		 * @return  array<string,array>
		 */
		public function add_advanced_fields() {
			if ( ! charitable_is_settings_view( 'advanced' ) ) {
				return array();
			}

			return array(
				'section'                       => array(
					'title'    => '',
					'type'     => 'hidden',
					'priority' => 10000,
					'value'    => 'advanced',
				),
				'section_dangerous'             => array(
					'title'    => __( 'Dangerous Settings', 'charitable' ),
					'type'     => 'heading',
					'priority' => 100,
				),
				'delete_data_on_uninstall'      => array(
					'label_for' => __( 'Reset Data', 'charitable' ),
					'type'      => 'checkbox',
					'help'      => __( 'DELETE ALL DATA when uninstalling the plugin.', 'charitable' ),
					'priority'  => 105,
				),
			);
		}
	}

endif;
