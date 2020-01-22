<?php
/**
 * Sets up the donation meta boxes.
 *
 * @package   Charitable/Classes/Charitable_Donation_Meta_Boxes
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donation_Meta_Boxes' ) ) :

	/**
	 * Charitable_Donation_Meta_Boxes class.
	 *
	 * @final
	 * @since 1.5.0
	 */
	final class Charitable_Donation_Meta_Boxes {

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_Donation_Meta_Boxes|null
		 */
		private static $instance = null;

		/**
		 * @var Charitable_Meta_Box_Helper $meta_box_helper
		 */
		private $meta_box_helper;

		/**
		 * Create object instance.
		 *
		 * @since 1.5.0
		 *
		 * @param Charitable_Meta_Box_Helper $helper The meta box helper class.
		 */
		public function __construct( Charitable_Meta_Box_Helper $helper ) {
			$this->meta_box_helper = $helper;
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.5.0
		 *
		 * @return Charitable_Donation_Meta_Boxes
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self(
					new Charitable_Meta_Box_Helper( 'charitable-donation' )
				);
			}

			return self::$instance;
		}

		/**
		 * Sets up the meta boxes to display on the donation admin page.
		 *
		 * @since  1.5.0
		 *
		 * @return void
		 */
		public function add_meta_boxes() {
			foreach ( $this->get_meta_boxes() as $meta_box_id => $meta_box ) {
				add_meta_box(
					$meta_box_id,
					$meta_box['title'],
					array( $this->meta_box_helper, 'metabox_display' ),
					Charitable::DONATION_POST_TYPE,
					$meta_box['context'],
					$meta_box['priority'],
					$meta_box
				);
			}
		}

		/**
		 * Remove default meta boxes.
		 *
		 * @since  1.5.0
		 *
		 * @global array $wp_meta_boxes Registered meta boxes in WP.
		 * @return void
		 */
		public function remove_meta_boxes() {
			global $wp_meta_boxes;

			$charitable_meta_boxes = $this->get_meta_boxes();

			foreach ( $wp_meta_boxes[ Charitable::DONATION_POST_TYPE ] as $context => $priorities ) {
				foreach ( $priorities as $priority => $meta_boxes ) {
					foreach ( $meta_boxes as $meta_box_id => $meta_box ) {
						if ( ! isset( $charitable_meta_boxes[ $meta_box_id ] ) ) {
							remove_meta_box( $meta_box_id, Charitable::DONATION_POST_TYPE, $context );
						}
					}
				}
			}
		}

		/**
		 * Returns an array of all meta boxes added to the donation post type screen.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		private function get_meta_boxes() {
			$screen = get_current_screen();

			if ( 'donation' == $screen->post_type && ( 'add' == $screen->action || isset( $_GET['show_form'] ) ) ) {
				$meta_boxes = $this->get_form_meta_box();
			} else {
				$meta_boxes = $this->get_view_meta_boxes();
			}

			/**
			 * Filter the meta boxes to be displayed on a donation overview page.
			 *
			 * @since 1.0.0
			 *
			 * @param array $meta_boxes The array of meta boxes and their details.
			 */
			return apply_filters( 'charitable_donation_meta_boxes', $meta_boxes );
		}

		/**
		 * Return the form meta box.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function get_form_meta_box() {
			global $post;

			$form       = new Charitable_Admin_Donation_Form( charitable_get_donation( $post->ID ) );
			$meta_boxes = array(
				'donation-form' => array(
					'title'    => __( 'Donation Form', 'charitable' ),
					'context'  => 'normal',
					'priority' => 'high',
					'view'     => 'metaboxes/donation/donation-form',
					'form'     => $form,
				),
				'donation-form-meta' => array(
					'title'    => __( 'Additional Details', 'charitable' ),
					'context'  => 'side',
					'priority' => 'high',
					'view'     => 'metaboxes/donation/donation-form-meta',
					'form'     => $form,
				),
			);

			/**
			 * Filter the meta boxes to be displayed on a donation add/edit page.
			 *
			 * @since 1.0.0
			 *
			 * @param array $meta_boxes The array of meta boxes and their details.
			 */
			return apply_filters( 'charitable_donation_form_meta_boxes', $meta_boxes );
		}

		/**
		 * Return the view meta boxes.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function get_view_meta_boxes() {
			global $post;

			$meta_boxes = array(
				'donation-overview' => array(
					'title'    => __( 'Donation Overview', 'charitable' ),
					'context'  => 'normal',
					'priority' => 'high',
					'view'     => 'metaboxes/donation/donation-overview',
				),
				'donation-actions'  => array(
					'title'    => __( 'Donation Actions', 'charitable' ),
					'context'  => 'side',
					'priority' => 'high',
					'view'     => 'metaboxes/actions',
					'actions'  => charitable_get_donation_actions(),
				),
				'donation-details'  => array(
					'title'    => __( 'Donation Details', 'charitable' ),
					'context'  => 'side',
					'priority' => 'high',
					'view'     => 'metaboxes/donation/donation-details',
				),
				'donation-log'      => array(
					'title'    => __( 'Donation Log', 'charitable' ),
					'context'  => 'normal',
					'priority' => 'low',
					'view'     => 'metaboxes/donation/donation-log',
				),
			);

			/* Get rid of the donation actions meta box if it doesn't apply to this donation. */
			if ( ! charitable_get_donation_actions()->has_available_actions( $post->ID ) ) {
				unset( $meta_boxes['donation-actions'] );
			}

			/**
			 * Filter the meta boxes to be displayed on a donation overview page.
			 *
			 * @since 1.5.0
			 *
			 * @param array $meta_boxes The array of meta boxes and their details.
			 */
			return apply_filters( 'charitable_donation_view_meta_boxes', $meta_boxes );
		}

		/**
		 * Create donation actions instance, with some initial defaults.
		 *
		 * @since  1.6.0
		 *
		 * @return void
		 */
		public function register_donation_actions() {
			$donation_actions = charitable_get_donation_actions();

			foreach ( charitable_get_valid_donation_statuses() as $status => $label ) {
				$donation_actions->register( 'change_status_to_' . $status, array(
					'label'           => $label,
					'callback'        => array( $this, 'change_donation_status' ),
					'button_text'     => __( 'Update Status', 'charitable' ),
					'active_callback' => array( $this, 'can_change_donation_status' ),
					'success_message' => 13,
					'failed_message'  => 14,
					'fields'          => array( $this, 'get_donation_status_change_fields' ),
				), __( 'Change Status', 'charitable' ) );
			}
		}

		/**
		 * Check whether a particular status change works for the given donation.
		 *
		 * @since  1.6.0
		 *
		 * @param  int   $object_id The donation's ID.
		 * @param  array $args      Mixed action arguments.
		 * @return boolean
		 */
		public function can_change_donation_status( $object_id, $args = array() ) {
			$donation = charitable_get_donation( $object_id );

			return $donation && $args['action_args']['label'] !== $donation->get_status_label();
		}

		/**
		 * Change a donation's status.
		 *
		 * @since  1.6.0
		 *
		 * @param  boolean $success   Whether the action has been run.
		 * @param  int     $object_id The donation's ID.
		 * @param  array   $args      Mixed action arguments.
		 * @param  string  $action    The action id.
		 * @return boolean Whether the status was changed.
		 */
		public function change_donation_status( $success, $object_id, $args = array(), $action ) {
			$donation = charitable_get_donation( $object_id );

			if ( ! $donation ) {
				return false;
			}

			$status  = str_replace( 'change_status_to_', '', $action );
			$success = $donation->update_status( $status );

			if ( array_key_exists( 'gateway_refund', $_POST ) && $_POST['gateway_refund'] ) {

				$gateway = $donation->get_gateway();

				/**
				 * Perform a refund in the donation's gateway.
				 *
				 * @since 1.6.0
				 *
				 * @param int $donation_id The donation's ID.
				 */
				do_action( 'charitable_process_refund_' . $gateway, $object_id );
			}

			return 0 !== $success;
		}

		/**
		 * Additional fields to display for a status change.
		 *
		 * @since  1.6.0
		 *
		 * @param  int   $object_id The donation's ID.
		 * @param  array $action    Mixed action arguments.
		 * @return void
		 */
		public function get_donation_status_change_fields( $object_id, $action ) {
			$statuses = charitable_get_valid_donation_statuses();

			if ( $statuses['charitable-refunded'] != $action['label'] ) {
				return;
			}

			$donation = charitable_get_donation( $object_id );

			if ( ! $donation || ! $donation->is_refundable_in_gateway() ) {
				return;
			}

			/* translators: %s: gateway name */
			printf( '%s' . __( 'Refund in %s automatically.', 'charitable' ),
				'<input type="checkbox" name="gateway_refund" value="1" />',
				$donation->get_gateway_object()->get_name()
			);
		}

		/**
		 * Save meta for the donation.
		 *
		 * @since  1.5.0
		 *
		 * @param  int     $donation_id
		 * @param  WP_Post $post
		 * @return void
		 */
		public function save_donation( $donation_id, WP_Post $post ) {
			if ( ! $this->meta_box_helper->user_can_save( $donation_id ) ) {
				return;
			}

			$this->maybe_save_form_submission( $donation_id );

			/* Handle any fired actions */
			if ( ! empty( $_POST['charitable_donation_action'] ) ) {
				charitable_get_donation_actions()->do_action( sanitize_text_field( $_POST['charitable_donation_action'] ), $donation_id );
			}

			/**
			 * Hook for plugins to do something else with the posted data.
			 *
			 * @since 1.0.0
			 *
			 * @param int     $donation_id The donation ID.
			 * @param WP_Post $post        Instance of `WP_Post`.
			 */
			do_action( 'charitable_donation_save', $donation_id, $post );
		}

		/**
		 * Save a donation after the admin donation form has been submitted.
		 *
		 * @since  1.5.0
		 *
		 * @param  int $donation_id The donation ID.
		 * @return boolean True if this was a form submission. False otherwise.
		 */
		public function maybe_save_form_submission( $donation_id ) {
			if ( ! $this->is_admin_donation_save() || did_action( 'charitable_before_save_donation' ) ) {
				return false;
			}

			$form = new Charitable_Admin_Donation_Form( charitable_get_donation( $donation_id ) );

			if ( ! $form->validate_submission() ) {
				wp_safe_redirect( admin_url( 'post-new.php?post_type=donation&show_form=1' ) );
				exit();
			}

			charitable_create_donation( $form->get_donation_values() );

			update_post_meta( $donation_id, '_donation_manually_edited', true );

			return true;
		}

		/**
		 * Change messages when a post type is updated.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $messages The post messages.
		 * @return array
		 */
		public function post_messages( $messages ) {
			global $post, $post_ID;

			$messages[ Charitable::DONATION_POST_TYPE ] = array(
				0  => '', // Unused. Messages start at index 1.
				1  => sprintf(
					/* translators: %s: link */
					__( 'Donation updated. <a href="%s">View Donation</a>', 'charitable' ),
					esc_url( get_permalink( $post_ID ) )
				),
				2  => __( 'Custom field updated.', 'charitable' ),
				3  => __( 'Custom field deleted.', 'charitable' ),
				4  => __( 'Donation updated.', 'charitable' ),
				5  => isset( $_GET['revision'] )
					? sprintf(
						/* translators: %s: revision title */
						__( 'Donation restored to revision from %s', 'charitable' ),
						wp_post_revision_title( (int) $_GET['revision'], false )
					)
					: false,
				6  => sprintf(
					/* translators: %s: link */
					__( 'Donation published. <a href="%s">View Donation</a>', 'charitable' ),
					esc_url( get_permalink( $post_ID ) )
				),
				7  => __( 'Donation saved.', 'charitable' ),
				8  => sprintf(
					/* translators: %s: link */
					__( 'Donation submitted. <a target="_blank" href="%s">Preview Donation</a>', 'charitable' ),
					esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) )
				),
				9  => sprintf(
					/* translators: %1$s: date and time; %2$s: link */
					__( 'Donation scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Donation</a>', 'charitable' ),
					date_i18n( __( 'M j, Y @ G:i', 'charitable' ), strtotime( $post->post_date ) ),
					esc_url( get_permalink( $post_ID ) )
				),
				10 => sprintf(
					/* translators: %s: link */
					__( 'Donation draft updated. <a target="_blank" href="%s">Preview Donation</a>', 'charitable' ),
					esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) )
				),
				11 => __( 'Email resent.', 'charitable' ),
				12 => __( 'Email could not be resent.', 'charitable' ),
				13 => __( 'Donation status changed.', 'charitable' ),
				14 => __( 'Donation status could not be changed.', 'charitable' ),
			);

			return $messages;
		}

		/**
		 * Prevent email from sending if we are saving the donation via the admin.
		 *
		 * @since  1.6.15
		 *
		 * @param  boolean $send_email Whether to send the email.
		 * @return boolean
		 */
		public function maybe_block_new_donation_email( $send_email ) {
			if ( ! $send_email ) {
				return $send_email;
			}

			return ! $this->is_admin_donation_save() && ! $this->is_donation_action();
		}

		/**
		 * Prevent email from sending if we are saving the donation via the admin.
		 *
		 * @since  1.6.15
		 *
		 * @param  boolean $send_email Whether to send the email.
		 * @return boolean
		 */
		public function maybe_block_donation_receipt_email( $send_email ) {
			if ( ! $send_email ) {
				return $send_email;
			}

			if ( $this->is_donation_action() ) {
				return false;
			}

			if ( $this->is_admin_donation_save() ) {
				$send_email = array_key_exists( 'send_donation_receipt', $_POST ) && $_POST['send_donation_receipt'];
			}

			return $send_email;
		}

		/**
		 * Checks whether we are saving the admin donation form.
		 *
		 * @since  1.6.15
		 *
		 * @return boolean
		 */
		public function is_admin_donation_save() {
			return array_key_exists( 'charitable_action', $_POST );
		}

		/**
		 * Checks whether we are doing a donation action.
		 *
		 * @since  1.6.15
		 *
		 * @return boolean
		 */
		public function is_donation_action() {
			return array_key_exists( 'charitable_donation_action', $_POST );
		}
	}

endif;
