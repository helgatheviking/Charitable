<?php
/**
 * The endpoint registry class, providing a clean way to access details about individual endpoints.
 *
 * @package   Charitable/Classes/Charitable_Endpoints
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.29
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Endpoints' ) ) :

	/**
	 * Charitable_Endpoints
	 *
	 * @since  1.5.0
	 */
	class Charitable_Endpoints {

		/**
		 * Registered endpoints.
		 *
		 * @since 1.5.0
		 *
		 * @var   Charitable_Endpoint[]
		 */
		protected $endpoints;

		/**
		 * Current endpoint.
		 *
		 * @since 1.5.0
		 *
		 * @var   string
		 */
		protected $current_endpoint;

		/**
		 * Create class object.
		 *
		 * @since 1.5.0
		 */
		public function __construct() {
			$this->endpoints = array();

			add_action( 'wp', array( $this, 'disable_endpoint_cache' ) );
			add_action( 'init', array( $this, 'setup_rewrite_rules' ) );
			add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
			add_action( 'template_redirect', array( $this, 'maybe_redirect' ) );
			add_filter( 'template_include', array( $this, 'template_loader' ), 12 );
			add_filter( 'the_content', array( $this, 'get_content' ) );
			add_filter( 'body_class', array( $this, 'add_body_classes' ) );
			add_filter( 'nav_menu_meta_box_object', array( $this, 'add_endpoints_menu_meta_box' ) );
			add_filter( 'customize_nav_menu_available_item_types', array( $this, 'add_endpoints_menu_meta_box_to_customizer' ) );
			add_filter( 'customize_nav_menu_available_items', array( $this, 'add_endpoints_menu_meta_box_items_to_customizer' ), 10, 4 );

			/* Avoid Polylang rewriting the rewrite rules. */
			add_filter( 'pll_modify_rewrite_rule', array( $this, 'prevent_polylang_rewrite_modification' ), 10, 2 );
		}

		/**
		 * Register an endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @param  Charitable_Endpoint $endpoint The endpoint object.
		 * @return boolean True if the endpoint was registered. False if it was already registered.
		 */
		public function register( Charitable_Endpoint $endpoint ) {
			$endpoint_id = $endpoint->get_endpoint_id();

			if ( $this->endpoint_exists( $endpoint_id ) ) {
				charitable_get_deprecated()->doing_it_wrong(
					__METHOD__,
					sprintf( __( 'Endpoint %s has already been registered.', 'charitable' ), $endpoint_id ),
					'1.5.0'
				);

				return false;
			}

			$this->endpoints[ $endpoint_id ] = $endpoint;

			return true;
		}

		/**
		 * Get the permalink/URL of a particular endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $endpoint The endpoint id.
		 * @param  array  $args     Optional array of arguments.
		 * @return string|false
		 */
		public function get_page_url( $endpoint, $args = array() ) {
			$endpoint = $this->sanitize_endpoint( $endpoint );
			$default  = '';

			if ( $this->endpoint_exists( $endpoint ) ) {
				$default = $this->endpoints[ $endpoint ]->get_page_url( $args );
			}

			/**
			 * Filter the URL of a particular endpoint.
			 *
			 * The hook takes the format of charitable_permalink_{endpoint}_page. For example,
			 * for the campaign_donation endpoint, the hook is:
			 *
			 * charitable_permalink_campaign_donation_page
			 *
			 * @since 1.0.0
			 *
			 * @param string $default The endpoint's URL.
			 * @param array  $args    Mixed set of arguments.
			 */
			return apply_filters( 'charitable_permalink_' . $endpoint . '_page', $default, $args );
		}

		/**
		 * Checks if we're currently viewing a particular endpoint/page.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $endpoint The endpoint id.
		 * @param  array  $args     Optional array of arguments.
		 * @return boolean
		 */
		public function is_page( $endpoint, $args = array() ) {
			$endpoint = $this->sanitize_endpoint( $endpoint );
			$default  = '';

			if ( $this->endpoint_exists( $endpoint ) ) {
				$default = $this->endpoints[ $endpoint ]->is_page( $args );
			}

			/**
			 * Return whether we are currently viewing a particular endpoint.
			 *
			 * The hook takes the format of charitable_is_page_{endpoint}_page. For example,
			 * for the campaign_donation endpoint, the hook is:
			 *
			 * charitable_is_page_campaign_donation_page
			 *
			 * @since 1.0.0
			 *
			 * @param boolean $default Whether we are currently on the endpoint.
			 * @param array   $args    Mixed set of arguments.
			 */
			return apply_filters( 'charitable_is_page_' . $endpoint . '_page', $default, $args );
		}

		/**
		 * Set up the template for an endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $endpoint         The endpoint id.
		 * @param  string $default_template The default template to be used if the endpoint doesn't return its own.
		 * @return string $template
		 */
		public function get_endpoint_template( $endpoint, $default_template ) {
			$endpoint = $this->sanitize_endpoint( $endpoint );

			if ( ! $this->endpoint_exists( $endpoint ) ) {
				charitable_get_deprecated()->doing_it_wrong(
					__METHOD__,
					sprintf(
						/* translators: %s: endpoint id */
						__( 'Endpoint %s has not been registered.', 'charitable' ), $endpoint
					),
					'1.5.0'
				);

				return $default_template;
			}

			return $this->endpoints[ $endpoint ]->get_template( $default_template );
		}

		/**
		 * Disable page cache on non-cacheable endpoints using the DONOTCACHEPAGE constant.
		 *
		 * @since  1.6.14
		 *
		 * @return void
		 */
		public function disable_endpoint_cache() {
			if ( defined( 'DONOTCACHEPAGE' ) ) {
				return;
			}

			$endpoint_id = $this->get_current_endpoint();

			if ( ! $endpoint_id ) {
				return;
			}

			if ( $this->get_endpoint( $endpoint_id )->is_cacheable() ) {
				return;
			}

			define( 'DONOTCACHEPAGE', true );

			/**
			 * Fire action to note that the current page should not be cached.
			 *
			 * @since 1.6.14
			 */
			do_action( 'charitable_do_not_cache' );
		}

		/**
		 * Prevent Polylang from changing some rewrite rules.
		 *
		 * @since  1.6.21
		 *
		 * @param  boolean $modify Whether to modify or not the rule, defaults to true.
		 * @param  array   $rule   Original rewrite rule.
		 * @return boolean
		 */
		public function prevent_polylang_rewrite_modification( $modify, $rule ) {
			/**
			 * Filter the list of endpoint URLs that Polylang won't touch.
			 *
			 * @since 1.6.21
			 *
			 * @param array $protected_rules The protected rules.
			 */
			$protected_rules = apply_filters(
				'charitable_polylang_protected_rewrite_rules',
				array(
					'charitable-listener(/(.*))?/?$',
				)
			);

			return $modify && ! in_array( key( $rule ), $protected_rules );
		}

		/**
		 * Set up the rewrite rules for the site.
		 *
		 * @since  1.5.0
		 *
		 * @return void
		 */
		public function setup_rewrite_rules() {
			foreach ( $this->endpoints as $endpoint ) {
				$endpoint->setup_rewrite_rules();
			}

			/* Set up any common rewrite tags */
			add_rewrite_tag( '%donation_id%', '([0-9]+)' );
		}

		/**
		 * Add custom query vars.
		 *
		 * @since  1.5.0
		 *
		 * @param  string[] $vars The query vars.
		 * @return string[]
		 */
		public function add_query_vars( $vars ) {
			foreach ( $this->endpoints as $endpoint ) {
				$vars = $endpoint->add_query_vars( $vars );
			}

			return array_merge( $vars, array( 'donation_id', 'cancel' ) );
		}

		/**
		 * Check the current endpoint to see if we should redirect the user to a different page.
		 *
		 * @since  1.6.26
		 *
		 * @return void
		 */
		public function maybe_redirect() {
			$current_endpoint = $this->get_current_endpoint();

			if ( ! $current_endpoint ) {
				return;
			}

			$url = $this->endpoints[ $current_endpoint ]->get_redirect();

			if ( ! $url ) {
				return;
			}

			wp_safe_redirect( $url );

			exit;
		}

		/**
		 * Load templates for our endpoints.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $template The default template.
		 * @return string
		 */
		public function template_loader( $template ) {
			$current_endpoint = $this->get_current_endpoint();

			if ( ! $current_endpoint ) {
				return $template;
			}

			$template_options = $this->endpoints[ $current_endpoint ]->get_template( $template );

			if ( $template_options == $template ) {
				return $template_options;
			}

			$template_options = apply_filters( 'charitable_' . $current_endpoint . '_page_template', $template_options );

			return charitable_get_template_path( $template_options, $template );
		}

		/**
		 * Get the content to display for the endpoint we're viewing.
		 *
		 * @since  1.5.0
		 *
		 * @param  string       $content  The default content.
		 * @param  false|string $endpoint Fetch the content for a specific endpoint.
		 * @return string
		 */
		public function get_content( $content, $endpoint = false ) {
			if ( ! $endpoint ) {
				$endpoint = $this->get_current_endpoint();
			}

			if ( ! $endpoint ) {
				return $content;
			}

			return $this->endpoints[ $endpoint ]->get_content( $content );
		}

		/**
		 * Add any custom body classes defined for the endpoint we're viewing.
		 *
		 * @since  1.5.0
		 *
		 * @param  string[] $classes The list of body classes.
		 * @return string[]
		 */
		public function add_body_classes( $classes ) {
			$endpoint = $this->get_current_endpoint();

			if ( ! $endpoint ) {
				return $classes;
			}

			$classes[] = $this->endpoints[ $endpoint ]->get_body_class();

			return $classes;
		}

		/**
		 * Add a "Charitable" menus meta box.
		 *
		 * @since  1.6.29
		 *
		 * @param  object $object The meta box object
		 * @return object
		 */
		public function add_endpoints_menu_meta_box( $object ) {
			add_meta_box(
				'add-charitable-endpoints',
				__( 'Charitable', 'charitable' ),
				[ $this, 'endpoints_menu_meta_box' ],
				'nav-menus',
				'side',
				'low'
			);

			return $object;
		}

		/**
		 * The content of the endpoints menu meta box.
		 *
		 * @since  1.6.29
		 *
		 * @global int|string $nav_menu_selected_id (id, name or slug) of the currently-selected menu.
		 * @return void
		 */
		public function endpoints_menu_meta_box() {
			global $nav_menu_selected_id;

			$walker = new Charitable_Walker_Nav_Menu_Checklist();

			$current_tab = 'all';
			$endpoints   = $this->get_endpoints_for_nav_menu();

			$removed_args = array( 'action', 'customlink-tab', 'edit-menu-item', 'menu-item', 'page-tab', '_wpnonce' );
			?>
			<div id="charitable" class="categorydiv">
				<ul id="charitable-tabs" class="charitable-tabs add-menu-item-tabs">
					<li <?php echo ( 'all' == $current_tab ? ' class="tabs"' : '' ); ?>>
						<a class="nav-tab-link" data-type="tabs-panel-charitable-all" href="<?php if ( $nav_menu_selected_id ) echo esc_url( add_query_arg( 'charitable-tab', 'all', remove_query_arg( $removed_args ) ) ); ?>#tabs-panel-charitable-all">
							<?php _e( 'View All', 'charitable' ); ?>
						</a>
					</li><!-- /.tabs -->
				</ul>
				<div id="tabs-panel-charitable-all" class="tabs-panel tabs-panel-view-all <?php echo ( 'all' == $current_tab ? 'tabs-panel-active' : 'tabs-panel-inactive' ); ?>">
					<ul id="charitable-checklist-all" class="categorychecklist form-no-clear">
					<?php
						echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $endpoints ), 0, (object) array( 'walker' => $walker ) );
					?>
					</ul>
				</div><!-- /.tabs-panel -->
				<p class="button-controls wp-clearfix">
					<span class="list-controls">
						<a href="<?php echo esc_url( add_query_arg( array( 'charitable-tab' => 'all', 'selectall' => 1, ), remove_query_arg( $removed_args ) ) ); ?>#charitable" class="select-all"><?php _e( 'Select All', 'charitable' ); ?></a>
					</span>
					<span class="add-to-menu">
						<input type="submit"<?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu', 'charitable' ); ?>" name="add-charitable-menu-item" id="submit-charitable" />
						<span class="spinner"></span>
					</span>
				</p>
			</div><!-- /.categorydiv -->
			<?php
		}

		/**
		 * Add Charitable menu meta box to the Customizer.
		 *
		 * @since  1.6.29
		 *
		 * @param  array $item_types An associative array structured for the customizer.
		 * @return array
		 */
		public function add_endpoints_menu_meta_box_to_customizer( $item_types ) {
			return array_merge(
				$item_types,
				array(
					'charitable_nav' => array(
						'title'  => _x( 'Charitable', 'customizer menu section title', 'charitable' ),
						'type'   => 'charitable_nav',
						'object' => 'charitable_nav',
					),
				)
			);
		}

		/**
		 * Add meta box items to the Customizer.
		 *
		 * @since  1.6.29
		 *
		 * @param  array   $items  The array of menu items.
		 * @param  string  $type   The requested type.
		 * @param  string  $object The requested object name.
		 * @param  integer $page   The page num being requested.
		 * @return array The paginated Charitable user nav items.
		 */
		public function add_endpoints_menu_meta_box_items_to_customizer( $items = array(), $type = '', $object = '', $page = 0 ) {
			if ( 'charitable_nav' !== $object ) {
				return $items;
			}

			foreach ( $this->get_endpoints_for_nav_menu() as $item ) {
				$item               = (array) $item;
				$item['id']         = 'charitable-' . $item['object_id'];
				$item['classes']    = implode( ' ', $item['classes'] );
				$item['type_label'] = _x( 'Custom Link', 'customizer menu type label', 'charitable' );

				$items[] = $item;
			}

			return array_slice( $items, 10 * $page, 10 );
		}

		/**
		 * Return all endpoints that can be added to navigation menus.
		 *
		 * @since  1.6.29
		 *
		 * @return object[]
		 */
		public function get_endpoints_for_nav_menu() {
			$endpoints = [];

			foreach ( $this->endpoints as $endpoint_id => $endpoint ) {
				$menu_object = $endpoint->nav_menu_object();
				if ( ! is_null( $menu_object ) ) {
					$endpoints[] = $menu_object;
				}
			}

			return $endpoints;
		}

		/**
		 * Return the current endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @return string|false String if we're on one of our endpoints. False otherwise.
		 */
		public function get_current_endpoint() {
			if ( ! isset( $this->current_endpoint ) ) {

				foreach ( $this->endpoints as $endpoint_id => $endpoint ) {
					if ( $this->is_page( $endpoint_id, array( 'strict' => true ) ) ) {
						$this->current_endpoint = $endpoint_id;

						return $this->current_endpoint;
					}
				}

				$this->current_endpoint = false;
			}

			return $this->current_endpoint;
		}

		/**
		 * Return a list of all endpoints that should not be cached.
		 *
		 * @since  1.5.4
		 *
		 * @return array
		 */
		public function get_non_cacheable_endpoints() {
			$endpoints = array();

			foreach ( $this->endpoints as $endpoint_id => $endpoint ) {
				if ( ! $endpoint->is_cacheable() ) {
					$endpoints[] = $endpoint_id;
				}
			}

			return $endpoints;
		}

		/**
		 * Checks whether a particular endpoint exists.
		 *
		 * @since  1.5.9
		 *
		 * @param  string $endpoint The endpoint ID.
		 * @return boolean
		 */
		public function endpoint_exists( $endpoint ) {
			return array_key_exists( $endpoint, $this->endpoints );
		}

		/**
		 * Returns an endpoint.
		 *
		 * @since  1.6.14
		 *
		 * @param  string $endpoint The endpoint ID.
		 * @return Charitable_Endpoint|false False if no endpoint exists, or the object.
		 */
		public function get_endpoint( $endpoint ) {
			return $this->endpoint_exists( $endpoint ) ? $this->endpoints[ $endpoint ] : false;
		}

		/**
		 * Remove _page from the endpoint (required for backwards compatibility)
		 * and make sure donation_cancel is changed to donation_cancellation.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $endpoint The endpoint id.
		 * @return string
		 */
		protected function sanitize_endpoint( $endpoint ) {
			$endpoint = str_replace( '_page', '', $endpoint );

			if ( 'donation_cancel' == $endpoint ) {
				$endpoint = 'donation_cancellation';
			}

			return $endpoint;
		}
	}

endif;
