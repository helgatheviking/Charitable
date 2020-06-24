<?php
/**
 * Main class for setting up the Charitable User Dashboard.
 *
 * @package   Charitable/Classes/Charitable_User_Dashboard
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.42
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_User_Dashboard' ) ) :

	/**
	 * Charitable_User_Dashboard
	 *
	 * @since 1.0.0
	 */
	class Charitable_User_Dashboard {

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_User_Dashboard|null
		 */
		private static $instance = null;

		/**
		 * Stores whether the current request is in the user dashboard.
		 *
		 * @since 1.6.42
		 *
		 * @var   boolean
		 */
		private $on_user_dashboard_page;

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_User_Dashboard
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Create class instance.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			add_action( 'after_setup_theme', array( $this, 'register_menu' ), 100 );
			add_action( 'template_include', array( $this, 'load_user_dashboard_template' ) );
			add_action( 'wp_update_nav_menu', array( $this, 'flush_menu_object_cache' ) );
			add_action( 'wp_update_nav_menu_item', array( $this, 'flush_menu_object_cache' ) );
			add_filter( 'body_class', array( $this, 'add_body_class' ) );

			do_action( 'charitable_user_dashboard_start', $this );
		}

		/**
		 * Register navigation menu for frontend dashboard.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function register_menu() {
			register_nav_menu( 'charitable-dashboard', __( 'User Dashboard', 'charitable' ) );
		}

		/**
		 * Returns the user dashboard navigation menu.
		 *
		 * @uses   wp_nav_menu
		 * @since  1.0.0
		 *
		 * @param  array $args Additional arguments to pass to wp_nav_menu.
		 * @return string|false|void Menu output if $echo is false, false if there are no items or no menu was found.
		 */
		public function nav( $args ) {
			$defaults = array(
				'theme_location' => 'charitable-dashboard',
				'fallback_cb'    => false,
			);

			$args = wp_parse_args( $args, $defaults );

			return wp_nav_menu( $args );
		}

		/**
		 * Return the menu ID based on the theme location.
		 *
		 * @since  1.0.0
		 *
		 * @return int 0 if no menu found. Menu ID otherwise.
		 */
		public function get_nav_id() {
			$locations = get_nav_menu_locations();

			if ( ! isset( $locations['charitable-dashboard'] ) ) {
				return 0;
			}

			return wp_get_nav_menu_object( $locations['charitable-dashboard'] );
		}

		/**
		 * Returns all objects in the user dashboard navigation.
		 *
		 * @uses   wp_get_nav_menu_items
		 * @since  1.0.0
		 *
		 * @return WP_Post[]
		 */
		public function nav_objects() {
			$objects = get_transient( 'charitable_user_dashboard_objects' );

			if ( false === $objects ) {
				$objects        = array();
				$nav_menu_items = wp_get_nav_menu_items( $this->get_nav_id() );

				if ( is_array( $nav_menu_items ) ) {
					foreach ( $nav_menu_items as $nav_menu_item ) {
						switch ( $nav_menu_item->type ) {
							case 'custom':
								$identifier = trailingslashit( $nav_menu_item->url );
								break;

							default:
								$identifier = apply_filters( 'charitable_nav_menu_object_identifier', $nav_menu_item->object_id, $nav_menu_item );
						}

						$objects[] = $identifier;
					}
				}

				set_transient( 'charitable_user_dashboard_objects', $objects );
			}

			return $objects;
		}

		/**
		 * Flushes the menu object cache after updating a menu or menu item.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $menu_id The menu id.
		 * @return void
		 */
		public function flush_menu_object_cache( $menu_id ) {
			$dashboard_menu = $this->get_nav_id();

			if ( ! $dashboard_menu ) {
				delete_transient( 'charitable_user_dashboard_objects' );
				return;
			}

			$nav_menu = wp_get_nav_menu_object( $dashboard_menu );

			/* If $nav_menu is not set that means the location 'charitable-dashboard' is not checked. */
			if ( $menu_id == $nav_menu->term_id ) {
				delete_transient( 'charitable_user_dashboard_objects' );
			}
		}

		/**
		 * Checks whether the current requested page is in the user dashboard nav.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function in_nav() {
			global $wp;

			if ( ! isset( $this->on_user_dashboard_page ) ) {
				$current_url = trailingslashit( charitable_get_current_url() );

				$this->on_user_dashboard_page = in_array( get_queried_object_id(), $this->nav_objects() ) || in_array( $current_url, $this->nav_objects() );

				/**
				 * Set whether we're in the user dashboard.
				 *
				 * @param  boolean $on_user_dashboard_page Whether we're in the user dashboard.
				 * @param  array   $nav_objects            The navigation menu objects for the user dashboard.
				 */
				$this->on_user_dashboard_page = apply_filters( 'charitable_is_in_user_dashboard', $this->on_user_dashboard_page, $this->nav_objects() );
			}

			return $this->on_user_dashboard_page;
		}

		/**
		 * Loads the user dashboard template.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $template The template to use for the user dashboard.
		 * @return string
		 */
		public function load_user_dashboard_template( $template ) {
			/**
			 * The user dashboard template is not loaded by default; this has to be enabled.
			 */
			if ( false === apply_filters( 'charitable_force_user_dashboard_template', false ) ) {
				return $template;
			}

			/**
			 * The current object isn't in the nav, so return the template.
			 */
			if ( ! $this->in_nav() ) {
				return $template;
			}

			do_action( 'charitable_is_user_dashboard' );

			$new_template = apply_filters( 'charitable_user_dashboard_template', 'user-dashboard.php' );
			$template     = charitable_get_template_path( $new_template, $template );

			return $template;
		}

		/**
		 * Add the user-dashboard class to the body if we're looking at it.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $classes Body classes.
		 * @return array
		 */
		public function add_body_class( $classes ) {
			if ( $this->in_nav() ) {
				$classes[] = 'user-dashboard';
			}

			return $classes;
		}
	}

endif;
