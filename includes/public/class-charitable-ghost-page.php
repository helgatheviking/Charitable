<?php
/**
 * This file contains the class in charge of our ghost posts.
 *
 * This is based on the IT_Exchange_Casper class in iThemes Exchange.
 *
 * @version     1.0.0
 * @package     Charitable/Classes/Charitable_Ghost_Page
 * @author      Eric Daams
 * @copyright   Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Ghost_Page' ) ) :

	/**
	 * Charitable_Ghost_Page class.
	 *
	 * It is used when viewing a frontend Charitable view other than a campaign.
	 *
	 * @since   1.0.0
	 */
	class Charitable_Ghost_Page {

		/**
		 * @var     string  $current_view   The current Charitable frontend view. Should never be campaign.
		 * @since   1.0.0
		 */
		private $current_view;

		/**
		 * @var     array   $args
		 * @since   1.0.0
		 */
		private $args;

		/**
		 * @var     WP_Query $wp_query
		 * @since   1.0.0
		 */
		private $wp_query;

		/**
		 * Constructor. Sets $current_view and $wp_query properties.
		 *
		 * @since 1.0.0
		 *
		 * @param string $current_view
		 * @param array  $args
		 */
		public function __construct( $current_view, $args = array() ) {
			if ( 'campaign' == $current_view ) {
				throw new Exception( 'Charitable_Ghost_Page should not be constructed when $current_view is "campaign".' );
			}

			$this->current_view = $current_view;

			$defaults = array(
				'title'   => '',
				'content' => ''
			);

			$this->args = wp_parse_args( $args, $defaults );
			$this->modify_wp_query();

			/* Remove wpautop filter on Ghost pages */
			remove_filter( 'the_content', 'wpautop' );
		}

		/**
		 * Modifies the WP Query.
		 *
		 * @since   1.0.0
		 *
		 * @return  void
		 */
		private function modify_wp_query() {
			$wp_query = $GLOBALS['wp_query'];

			$wp_query->posts_per_page = 1;
			$wp_query->nopaging       = true;
			$wp_query->post_count     = 1;

			// If we don't have a post, load an empty one
			if ( empty( $wp_query->post ) ) {
				$wp_query->post = new WP_Post( new stdClass() );
			}

			$wp_query->post->ID                    = 0;
			$wp_query->post->post_date             = current_time( 'mysql' );
			$wp_query->post->post_date_gmt         = current_time( 'mysql', 1 );
			$wp_query->post->post_content          = $this->args['content'];
			$wp_query->post->post_title            = $this->args['title'];
			$wp_query->post->post_excerpt          = '';
			$wp_query->post->post_status           = 'publish';
			$wp_query->post->comment_status        = false;
			$wp_query->post->ping_status           = false;
			$wp_query->post->post_password         = '';
			$wp_query->post->post_name             = 'charitable-ghost-' . $this->current_view;
			$wp_query->post->to_ping               = '';
			$wp_query->post->pinged                = '';
			$wp_query->post->post_modified         = $wp_query->post->post_date;
			$wp_query->post->post_modified_gmt     = $wp_query->post->post_date_gmt;
			$wp_query->post->post_content_filtered = '';
			$wp_query->post->post_parent           = 0;
			$wp_query->post->guid                  = get_home_url() . '/' . $this->get_guid();
			$wp_query->post->menu_order            = 0;
			$wp_query->post->post_type             = 'page';
			$wp_query->post->post_mime_type        = '';
			$wp_query->post->comment_count         = 0;
			$wp_query->post->filter                = 'raw';

			$wp_query->posts                = array( $wp_query->post );
			$wp_query->found_posts          = 1;
			$wp_query->is_single            = false; //false -- so comments_template() doesn't add comments
			$wp_query->is_preview           = false;
			$wp_query->is_page              = true; //false -- so comments_template() doesn't add comments
			$wp_query->is_archive           = false;
			$wp_query->is_date              = false;
			$wp_query->is_year              = false;
			$wp_query->is_month             = false;
			$wp_query->is_day               = false;
			$wp_query->is_time              = false;
			$wp_query->is_author            = false;
			$wp_query->is_category          = false;
			$wp_query->is_tag               = false;
			$wp_query->is_tax               = false;
			$wp_query->is_search            = false;
			$wp_query->is_feed              = false;
			$wp_query->is_comment_feed      = false;
			$wp_query->is_trackback         = false;
			$wp_query->is_home              = false;
			$wp_query->is_404               = false;
			$wp_query->is_comments_popup    = false;
			$wp_query->is_paged             = false;
			$wp_query->is_admin             = false;
			$wp_query->is_attachment        = false;
			$wp_query->is_singular          = true;
			$wp_query->is_posts_page        = false;
			$wp_query->is_post_type_archive = false;

			$GLOBALS['wp_query'] = $wp_query;
		}

		/**
		 * Creates a guid based on the current view.
		 *
		 * @since   1.0.0
		 *
		 * @return  string
		 */
		private function get_guid() {
		}
	}

endif;