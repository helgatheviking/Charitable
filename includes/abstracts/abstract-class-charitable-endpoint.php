<?php
/**
 * Endpoint abstract model.
 *
 * @package   Charitable/Classes/Charitable_Endpoint
 * @author    Eric Daams
 * @copyright Copyright (c) 2019, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

if ( ! class_exists( 'Charitable_Endpoint' ) ) :

	/**
	 * Charitable_Endpoint
	 *
	 * @abstract
	 * @since  1.5.0
	 */
	abstract class Charitable_Endpoint implements Charitable_Endpoint_Interface {

		/* @var string The endpoint's unique identifier. */
		const ID = '';

		/**
		 * Whether the endpoint can be cached.
		 *
		 * @since 1.5.4
		 *
		 * @var   boolean
		 */
		protected $cacheable = true;

		/**
		 * Add rewrite rules for the endpoint.
		 *
		 * Unless the child class defines this, this won't do anything for an endpoint.
		 *
		 * @since 1.5.0
		 */
		public function setup_rewrite_rules() {
			/* Do nothing by default. */
		}

		/**
		 * Add new query vars for the endpoint.
		 *
		 * Unless the child class defines this, this won't do anything for an endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $vars The query vars.
		 * @return array
		 */
		public function add_query_vars( array $vars ) {
			/* Return vars unchanged by default. */
			return $vars;
		}

		/**
		 * Set up the endpoint template.
		 *
		 * By default, we will return the default template that WordPress already selected.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $template The default template.
		 * @return string
		 */
		public function get_template( $template ) {
			return $template;
		}

		/**
		 * Get the content to display for the endpoint.
		 *
		 * By default, we will return the default content that is passed by WordPress.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $content The default content.
		 * @return string
		 */
		public function get_content( $content ) {
			return $content;
		}

		/**
		 * Return the body class to add for the endpoint.
		 *
		 * By default, this will be the endpoint ID with underscores replaced by hyphens.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		public function get_body_class() {
			return str_replace( '_', '-', $this->get_endpoint_id() );
		}

		/**
		 * Whether an endpoint can be cached.
		 *
		 * @since  1.5.4
		 *
		 * @return boolean
		 */
		public function is_cacheable() {
			return $this->cacheable;
		}

		/**
		 * Return the "Post Title" for the endpoint.
		 *
		 * By default, this will be blank.
		 *
		 * @since  1.7.0
		 *
		 * @return string
		 */
		public function get_title() {
		}

		/**
         * Creates a guid based on the current view.
         *
         * @since   1.7.0
         *
         * @return  string
         */
        public function get_guid() {
        }
	}

endif;
