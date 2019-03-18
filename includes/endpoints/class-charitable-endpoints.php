<?php
/**
 * The endpoint registry class, providing a clean way to access details about individual endpoints.
 *
 * @package   Charitable/Classes/Charitable_Endpoints
 * @author    Eric Daams
 * @copyright Copyright (c) 2019, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.14
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

			add_action( 'init', array( $this, 'setup_rewrite_rules' ) );
			add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
			add_filter( 'posts_pre_query', array( $this, 'setup_query_object' ), 1, 2 );
			add_filter( 'template_include', array( $this, 'template_loader' ), 12 );
			add_filter( 'the_content', array( $this, 'get_content' ) );
			add_filter( 'body_class', array( $this, 'add_body_classes' ) );
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
         * Setup WP Query Object for Endpoint.
         *
         * @since   1.7.0
         *
         * @return  array
         */
        public function setup_query_object( $posts, $q ) {

        	$endpoint = $this->get_current_endpoint();

			if ( ! $endpoint ) {
				return $posts;
			}
				
            $q->posts_per_page = 1;
            $q->nopaging = true;
            $q->post_count = 1;
            $q->found_posts = 1;
            $q->is_single = false; //false -- so comments_template() doesn't add comments
            $q->is_preview = false;
            $q->is_page = true; //false -- so comments_template() doesn't add comments
            $q->is_archive = false;
            $q->is_date = false;
            $q->is_year = false;
            $q->is_month = false;
            $q->is_day = false;
            $q->is_time = false;
            $q->is_author = false;
            $q->is_category = false;
            $q->is_tag = false;
            $q->is_tax = false;
            $q->is_search = false;
            $q->is_feed = false;
            $q->is_comment_feed = false;
            $q->is_trackback = false;
            $q->is_home = false;
            $q->is_404 = false;
            $q->is_comments_popup = false;
            $q->is_paged = false;
            $q->is_admin = false;
            $q->is_attachment = false;
            $q->is_singular = true;
            $q->is_posts_page = false;
            $q->is_post_type_archive = false;

            $_post = new WP_Post( new stdClass() );

            $_post->ID = 0;
            $_post->post_date = current_time( 'mysql' );
            $_post->post_date_gmt = current_time( 'mysql', 1 );
            $_post->post_content = $this->endpoints[ $endpoint ]->get_content(); //$this->args[ 'content' ];
            $_post->post_title = $this->endpoints[ $endpoint ]->get_title();
            $_post->post_excerpt = '';
            $_post->post_status = 'publish';
            $_post->comment_status = false;
            $_post->ping_status = false;
            $_post->post_password = '';
            $_post->post_name = 'charitable-ghost-' . $endpoint;
            $_post->to_ping = '';
            $_post->pinged = '';
            $_post->post_modified = $_post->post_date;
            $_post->post_modified_gmt = $_post->post_date_gmt;
            $_post->post_content_filtered = '';
            $_post->post_parent = 0;
            $_post->guid = get_home_url() . '/' . $this->endpoints[ $endpoint ]->get_guid();
            $_post->menu_order = 0;
            $_post->post_type = 'page';
            $_post->post_mime_type = '';
            $_post->comment_count = 0;
            $_post->filter = 'raw';

            return array( $_post );
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
