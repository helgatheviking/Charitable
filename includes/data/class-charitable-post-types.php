<?php
/**
 * The class that defines Charitable's custom post types, taxonomies and post statuses.
 *
 * @package   Charitable/Classes/Charitable_Post_Types
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.39
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Post_Types' ) ) :

	/**
	 * Charitable_Post_Types
	 *
	 * @since 1.0.0
	 */
	final class Charitable_Post_Types {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Post_Types|null
		 */
		private static $instance = null;

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.2.0
		 *
		 * @return  Charitable_Post_Types
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Set up the class.
		 *
		 * Note that the only way to instantiate an object is with the on_start method,
		 * which can only be called during the start phase. In other words, don't try
		 * to instantiate this object.
		 *
		 * @since   1.0.0
		 */
		private function __construct() {
			add_action( 'init', array( $this, 'register_post_types' ), 5 );
			add_action( 'init', array( $this, 'register_post_statuses' ), 5 );
			add_action( 'init', array( $this, 'register_taxonomies' ), 6 );
		}

		/**
		 * Register plugin post types.
		 *
		 * @hook    init
		 * @since   1.0.0
		 *
		 * @return  void
		 */
		public function register_post_types() {
			/**
			 * Filter the campaign post type definition.
			 *
			 * To change any of the arguments used for the post type, other than the name
			 * of the post type itself, use the 'charitable_campaign_post_type' filter.
			 *
			 * @since 1.0.0
			 *
			 * @param array $args Post type arguments.
			 */
			$args = apply_filters(
				'charitable_campaign_post_type',
				array(
					'labels'              => array(
						'name'               => __( 'Campaigns', 'charitable' ),
						'singular_name'      => __( 'Campaign', 'charitable' ),
						'menu_name'          => _x( 'Campaigns', 'Admin menu name', 'charitable' ),
						'add_new'            => __( 'Add Campaign', 'charitable' ),
						'add_new_item'       => __( 'Add New Campaign', 'charitable' ),
						'edit'               => __( 'Edit', 'charitable' ),
						'edit_item'          => __( 'Edit Campaign', 'charitable' ),
						'new_item'           => __( 'New Campaign', 'charitable' ),
						'view'               => __( 'View Campaign', 'charitable' ),
						'view_item'          => __( 'View Campaign', 'charitable' ),
						'search_items'       => __( 'Search Campaigns', 'charitable' ),
						'not_found'          => __( 'No Campaigns found', 'charitable' ),
						'not_found_in_trash' => __( 'No Campaigns found in trash', 'charitable' ),
						'parent'             => __( 'Parent Campaign', 'charitable' ),
					),
					'description'         => __( 'This is where you can create new campaigns for people to support.', 'charitable' ),
					'public'              => true,
					'show_ui'             => true,
					'capability_type'     => 'campaign',
					'menu_icon'           => '',
					'map_meta_cap'        => true,
					'publicly_queryable'  => true,
					'exclude_from_search' => false,
					'hierarchical'        => false,
					'rewrite'             => array(
						'slug'       => 'campaigns',
						'with_front' => true,
					),
					'query_var'           => true,
					'supports'            => array( 'title', 'thumbnail', 'comments' ),
					'has_archive'         => false,
					'show_in_nav_menus'   => true,
					'show_in_menu'        => false,
					'show_in_admin_bar'   => true,
				)
			);

			register_post_type( 'campaign', $args );

			/**
			 * Filter the donation post type definition.
			 *
			 * To change any of the arguments used for the post type, other than the name
			 * of the post type itself, use the 'charitable_donation_post_type' filter.
			 *
			 * @since 1.0.0
			 *
			 * @param array $args Post type arguments.
			 */
			$args = apply_filters(
				'charitable_donation_post_type',
				array(
					'labels'              => array(
						'name'               => __( 'Donations', 'charitable' ),
						'singular_name'      => __( 'Donation', 'charitable' ),
						'menu_name'          => _x( 'Donations', 'Admin menu name', 'charitable' ),
						'add_new'            => __( 'Add Donation', 'charitable' ),
						'add_new_item'       => __( 'Add New Donation', 'charitable' ),
						'edit'               => __( 'Edit', 'charitable' ),
						'edit_item'          => __( 'Donation Details', 'charitable' ),
						'new_item'           => __( 'New Donation', 'charitable' ),
						'view'               => __( 'View Donation', 'charitable' ),
						'view_item'          => __( 'View Donation', 'charitable' ),
						'search_items'       => __( 'Search Donations', 'charitable' ),
						'not_found'          => __( 'No Donations found', 'charitable' ),
						'not_found_in_trash' => __( 'No Donations found in trash', 'charitable' ),
						'parent'             => __( 'Parent Donation', 'charitable' ),
					),
					'public'              => false,
					'show_ui'             => true,
					'capability_type'     => 'donation',
					'menu_icon'           => '',
					'map_meta_cap'        => true,
					'publicly_queryable'  => false,
					'exclude_from_search' => false,
					'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
					'rewrite'             => false,
					'query_var'           => false,
					'supports'            => array( '' ),
					'has_archive'         => false,
					'show_in_nav_menus'   => false,
					'show_in_menu'        => false,
				)
			);

			register_post_type( 'donation', $args );
		}

		/**
		 * Register custom post statuses.
		 *
		 * @since   1.0.0
		 *
		 * @return  void
		 */
		public function register_post_statuses() {
			register_post_status(
				'charitable-pending',
				array(
					'label'                     => _x( 'Pending', 'Pending Donation Status', 'charitable' ),
					/* translators: %s: count */
					'label_count'               => _n_noop( 'Pending (%s)', 'Pending (%s)', 'charitable' ),
					'public'                    => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'exclude_from_search'       => true,
				)
			);

			register_post_status(
				'charitable-completed',
				array(
					'label'                     => _x( 'Paid', 'Paid Donation Status', 'charitable' ),
					/* translators: %s: count */
					'label_count'               => _n_noop( 'Paid (%s)', 'Paid (%s)', 'charitable' ),
					'public'                    => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'exclude_from_search'       => true,
				)
			);

			register_post_status(
				'charitable-failed',
				array(
					'label'                     => _x( 'Failed', 'Failed Donation Status', 'charitable' ),
					/* translators: %s: count */
					'label_count'               => _n_noop( 'Failed (%s)', 'Failed (%s)', 'charitable' ),
					'public'                    => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'exclude_from_search'       => true,
				)
			);

			register_post_status(
				'charitable-cancelled',
				array(
					'label'                     => _x( 'Canceled', 'Canceled Donation Status', 'charitable' ),
					/* translators: %s: count */
					'label_count'               => _n_noop( 'Canceled (%s)', 'Canceled (%s)', 'charitable' ),
					'public'                    => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'exclude_from_search'       => true,
				)
			);

			register_post_status(
				'charitable-refunded',
				array(
					'label'                     => _x( 'Refunded', 'Refunded Donation Status', 'charitable' ),
					/* translators: %s: count */
					'label_count'               => _n_noop( 'Refunded (%s)', 'Refunded (%s)', 'charitable' ),
					'public'                    => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'exclude_from_search'       => true,
				)
			);

			register_post_status(
				'charitable-preapproved',
				array(
					'label'                     => _x( 'Pre Approved', 'Pre Approved Donation Status', 'charitable' ),
					/* translators: %s: count */
					'label_count'               => _n_noop( 'Pre Approved (%s)', 'Pre Approved (%s)', 'charitable' ),
					'public'                    => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'exclude_from_search'       => true,
				)
			);
		}

		/**
		 * Register the campaign category taxonomy.
		 *
		 * @since   1.0.0
		 *
		 * @return  void
		 */
		public function register_taxonomies() {
			$labels = array(
				'name'                       => _x( 'Campaign Categories', 'Taxonomy General Name', 'charitable' ),
				'singular_name'              => _x( 'Campaign Category', 'Taxonomy Singular Name', 'charitable' ),
				'menu_name'                  => __( 'Categories', 'charitable' ),
				'all_items'                  => __( 'All Campaign Categories', 'charitable' ),
				'parent_item'                => __( 'Parent Campaign Category', 'charitable' ),
				'parent_item_colon'          => __( 'Parent Campaign Category:', 'charitable' ),
				'new_item_name'              => __( 'New Campaign Category Name', 'charitable' ),
				'add_new_item'               => __( 'Add New Campaign Category', 'charitable' ),
				'edit_item'                  => __( 'Edit Campaign Category', 'charitable' ),
				'update_item'                => __( 'Update Campaign Category', 'charitable' ),
				'view_item'                  => __( 'View Campaign Category', 'charitable' ),
				'separate_items_with_commas' => __( 'Separate campaign categories with commas', 'charitable' ),
				'add_or_remove_items'        => __( 'Add or remove campaign categories', 'charitable' ),
				'choose_from_most_used'      => __( 'Choose from the most used', 'charitable' ),
				'popular_items'              => __( 'Popular Campaign Categories', 'charitable' ),
				'search_items'               => __( 'Search Campaign Categories', 'charitable' ),
				'not_found'                  => __( 'Not Found', 'charitable' ),
			);

			$args = array(
				'labels'            => $labels,
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => true,
				'show_tagcloud'     => true,
				'capabilities'      => array(
					'manage_terms' => 'manage_campaign_terms',
					'edit_terms'   => 'edit_campaign_terms',
					'delete_terms' => 'delete_campaign_terms',
					'assign_terms' => 'assign_campaign_terms',
				),
			);

			register_taxonomy( 'campaign_category', array( 'campaign' ), $args );

			$labels = array(
				'name'                       => _x( 'Campaign Tags', 'Taxonomy General Name', 'charitable' ),
				'singular_name'              => _x( 'Campaign Tag', 'Taxonomy Singular Name', 'charitable' ),
				'menu_name'                  => __( 'Tags', 'charitable' ),
				'all_items'                  => __( 'All Campaign Tags', 'charitable' ),
				'parent_item'                => __( 'Parent Campaign Tag', 'charitable' ),
				'parent_item_colon'          => __( 'Parent Campaign Tag:', 'charitable' ),
				'new_item_name'              => __( 'New Campaign Tag Name', 'charitable' ),
				'add_new_item'               => __( 'Add New Campaign Tag', 'charitable' ),
				'edit_item'                  => __( 'Edit Campaign Tag', 'charitable' ),
				'update_item'                => __( 'Update Campaign Tag', 'charitable' ),
				'view_item'                  => __( 'View Campaign Tag', 'charitable' ),
				'separate_items_with_commas' => __( 'Separate campaign tags with commas', 'charitable' ),
				'add_or_remove_items'        => __( 'Add or remove campaign tags', 'charitable' ),
				'choose_from_most_used'      => __( 'Choose from the most used', 'charitable' ),
				'popular_items'              => __( 'Popular Campaign Tags', 'charitable' ),
				'search_items'               => __( 'Search Campaign Tags', 'charitable' ),
				'not_found'                  => __( 'Not Found', 'charitable' ),
			);

			$args = array(
				'labels'            => $labels,
				'hierarchical'      => false,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => true,
				'show_tagcloud'     => true,
				'capabilities'      => array(
					'manage_terms' => 'manage_campaign_terms',
					'edit_terms'   => 'edit_campaign_terms',
					'delete_terms' => 'delete_campaign_terms',
					'assign_terms' => 'assign_campaign_terms',
				),
			);

			register_taxonomy( 'campaign_tag', array( 'campaign' ), $args );

			register_taxonomy_for_object_type( 'campaign_category', 'campaign' );
			register_taxonomy_for_object_type( 'campaign_tag', 'campaign' );
		}
	}

endif;
