<?php
/**
 * Charitable Meta Box Helper
 *
 * @package   Charitable/Classes/Charitable_Meta_Box_Helper
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

if ( ! class_exists( 'Charitable_Meta_Box_Helper' ) ) :

	/**
	 * Charitable Meta Box Helper
	 *
	 * @since   1.0.0
	 */
	class Charitable_Meta_Box_Helper {

		/**
		 * Nonce action.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		protected $nonce_action;

		/**
		 * Nonce name.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		protected $nonce_name = '_charitable_nonce';

		/**
		 * Whether nonce has been added.
		 *
		 * @since 1.0.0
		 *
		 * @var   boolean
		 */
		protected $nonce_added = false;

		/**
		 * Create a helper instance.
		 *
		 * @since  1.0.0
		 *
		 * @global WP_Post $post
		 * @param  string $nonce_action Action for the nonce.
		 */
		public function __construct( $nonce_action = 'charitable' ) {
			$this->nonce_action = $nonce_action;
		}

		/**
		 * Metabox callback wrapper.
		 *
		 * Every meta box is registered with this method as its callback,
		 * and then delegates to the appropriate view.
		 *
		 * @since  1.0.0
		 *
		 * @param  WP_Post $post The post object.
		 * @param  array   $args The arguments passed to the meta box, including the view to render.
		 * @return void
		 */
		public function metabox_display( WP_Post $post, array $args ) {
			if ( ! isset( $args['args']['view'] ) ) {
				return;
			}

			$view_args = $args['args'];

			unset( $view_args['view'] );

			$this->display( $args['args']['view'], $view_args );
		}

		/**
		 * Display a metabox with the given view.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $view      The view to render.
		 * @param  array  $view_args Arguments to pass to the view.
		 * @return void
		 */
		public function display( $view, $view_args ) {
			/**
			 * Set the nonce.
			 */
			if ( false === $this->nonce_added ) {

				wp_nonce_field( $this->nonce_action, $this->nonce_name );

				$this->nonce_added = true;
			}

			do_action( 'charitable_metabox_before', $view, $view_args );

			charitable_admin_view( $view, $view_args );

			do_action( 'charitable_metabox_after', $view, $view_args );
		}

		/**
		 * Display the fields to show inside a metabox.
		 *
		 * The fields parameter should contain an array of fields,
		 * all of which are arrays with a 'priority' key and a 'view'
		 * key.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $fields The set of fields to be displayed.
		 * @return void
		 */
		public function display_fields( array $fields ) {
			$form = new Charitable_Admin_Form();
			$form->set_fields( $fields );
			$form->view()->render_fields();
		}

		/**
		 * Verifies that the user who is currently logged in has permission to save the data
		 * from the meta box to the database.
		 *
		 * Hat tip Tom McFarlin: http://tommcfarlin.com/wordpress-meta-boxes-each-component/
		 *
		 * @since  1.0.0
		 *
		 * @param  integer $post_id The current post being saved.
		 * @return boolean True if the user can save the information.
		 */
		public function user_can_save( $post_id ) {
			$is_autosave    = wp_is_post_autosave( $post_id );
			$is_revision    = wp_is_post_revision( $post_id );
			$is_valid_nonce = ( isset( $_POST[ $this->nonce_name ] ) && wp_verify_nonce( $_POST[ $this->nonce_name ], $this->nonce_action ) );

			return ! ( $is_autosave || $is_revision ) && $is_valid_nonce;
		}
	}

endif;
