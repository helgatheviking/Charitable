<?php
/**
 * Class to load addon functionality.
 *
 * @package   Charitable/Classes/Charitable_Addons
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Addons' ) ) :

	/**
	 * Charitable_Addons
	 *
	 * @since 1.0.0
	 */
	class Charitable_Addons {

		/**
		 * Load addons. This is executed before the charitable_start hook
		 * to allow addons to hook into that.
		 *
		 * @since  1.0.0
		 *
		 * @param  Charitable $charitable
		 * @return void
		 */
		public static function load( Charitable $charitable ) {
			if ( $charitable->started() ) {
				return;
			}

			new Charitable_Addons();
		}

		/**
		 * Create class object.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			add_action( 'charitable_activate_addon', array( $this, 'activate_addon' ) );
			add_action( 'after_setup_theme', array( $this, 'load_addons' ) );

			do_action( 'charitable_addons_start', $this );
		}

		/**
		 * Activate an addon.
		 *
		 * This is programatically called on the charitable_activate_addon hook,
		 * triggered by a plugin.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function activate_addon( $addon ) {
			/* This method should only be called on the charitable_activate_addon hook */
			if ( 'charitable_activate_addon' !== current_filter() ) {
				return;
			}

			$filepath = $this->get_validated_addon_filepath( $addon );

			/* If we cannot read the file, bounce back with an error. */
			if ( false === $filepath ) {
				charitable_get_deprecated()->doing_it_wrong(
					__METHOD__,
					sprintf( 'File %s does not exist or is not readable', $filepath ),
					'1.0.0'
				);
				return;
			}

			$this->load_addon_dependencies();

			require_once( $filepath );

			$class = $this->get_addon_class( $addon );

			/* Call the Addon's activate method */
			call_user_func( array( $class, 'activate' ) );
		}

		/**
		 * Load activated addons.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function load_addons() {
			$active_addons = apply_filters( 'charitable_active_addons', array() );

			if ( empty( $active_addons ) ) {
				return;
			}

			$this->load_addon_dependencies();

			foreach ( $active_addons as $addon ) {
				$filepath = $this->get_validated_addon_filepath( $addon );

				/* If we cannot read the file, bounce back with an error. */
				if ( false === $filepath ) {
					charitable_get_deprecated()->doing_it_wrong(
						__METHOD__,
						sprintf( 'File %s does not exist or is not readable', $filepath ),
						'1.0.0'
					);
					continue;
				}

				require_once( $filepath );

				/* Call the Addon's load method */
				call_user_func( array( $this->get_addon_class( $addon ), 'load' ) );
			}

			do_action( 'charitable_addons_loaded', $active_addons );
		}

		/**
		 * Load interface and abstract classes that addons use.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		private function load_addon_dependencies() {
			require_once( charitable()->get_path( 'includes' ) . 'interfaces/interface-charitable-addon.php' );
		}

		/**
		 * Return the validated filepath, or false if the file path could
		 * not be validated.
		 *
		 * @since  1.5.4
		 *
		 * @param  string $addon The addon slug.
		 * @return string|false
		 */
		private function get_validated_addon_filepath( $addon ) {
			$filepath = charitable()->get_path( 'includes' ) . "addons/{$addon}/class-{$addon}.php";
			$valid    = file_exists( $filepath ) && is_readable( $filepath );
			return $valid ? $filepath : false;
		}

		/**
		 * Get class name of addon.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $addon The addon slug.
		 * @return string
		 */
		private function get_addon_class( $addon ) {
			$class = str_replace( '-', ' ', $addon );
			$class = ucfirst( $class );
			return str_replace( ' ', '_', $class );
		}
	}

endif;
