<?php
/**
 * Class that manages the display and processing of the forgot password form.
 *
 * @package   Charitable/Classes/Charitable_Forgot_Password_Form
 * @author    Rafe Colton, Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.4.0
 * @version   1.5.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Forgot_Password_Form' ) ) :

	/**
	 * Charitable_Forgot_Password_Form
	 *
	 * @since 1.4.0
	 */
	class Charitable_Forgot_Password_Form extends Charitable_Form {

		/**
		 * Nonce action.
		 *
		 * @since 1.4.0
		 *
		 * @var   string
		 */
		protected $nonce_action = 'charitable_forgot_password';

		/**
		 * Nonce name.
		 *
		 * @since 1.4.0
		 *
		 * @var   string
		 */
		protected $nonce_name = '_charitable_forgot_password_nonce';

		/**
		 * Form action.
		 *
		 * @since 1.4.0
		 *
		 * @var   string
		 */
		protected $form_action = 'retrieve_password';

		/**
		 * Create class object.
		 *
		 * @since 1.4.0
		 *
		 * @param array $args User-defined shortcode attributes.
		 */
		public function __construct() {
			$this->id = uniqid();

			/* For backwards-compatibility */
			add_action( 'charitable_form_field', array( $this, 'render_field' ), 10, 6 );
		}

		/**
		 * Forgot password fields to be displayed.
		 *
		 * @since  1.4.0
		 *
		 * @return array
		 */
		public function get_fields() {
			$fields = apply_filters( 'charitable_forgot_password_fields', array(
				'user_login' => array(
					'label'    => __( 'Email Address', 'charitable' ),
					'type'     => 'email',
					'required' => true,
					'priority' => 10,
				),
			) );

			uasort( $fields, 'charitable_priority_sort' );

			return $fields;
		}

		/**
		 * Send the password reset email.
		 *
		 * @since  1.4.0
		 *
		 * @return bool|WP_Error True: when finish. WP_Error on error
		 */
		public static function retrieve_password() {
			$form = new Charitable_Forgot_Password_Form();

			if ( ! $form->validate_nonce() || ! $form->validate_honeypot() ) {
				charitable_get_notices()->add_error( __( 'There was an error with processing your form submission. Please reload the page and try again.', 'charitable' ) );
				return;
			}

			if ( empty( $_POST['user_login'] ) ) {
				charitable_get_notices()->add_error( __( '<strong>ERROR</strong>: Enter a username or email address.', 'charitable' ) );
				return;
			} elseif ( strpos( $_POST['user_login'], '@' ) ) {
				$user = get_user_by( 'email', trim( $_POST['user_login'] ) );
			} else {
				$login = trim( $_POST['user_login'] );
				$user = get_user_by( 'login', $login );
			}

			do_action( 'lostpassword_post' );

			/* If we are missing user data, proceed no further. */
			if ( ! $user ) {
				charitable_get_notices()->add_error( __( '<strong>ERROR</strong>: Invalid username or email.', 'charitable' ) );
				return;
			}

			/* Prepare the email. */
			$email = new Charitable_Email_Password_Reset( array( 'user' => $user ) );

			/* Make sure that the reset link was generated correctly. */
			if ( is_wp_error( $email->get_reset_link() ) ) {
				charitable_get_notices()->add_errors_from_wp_error( $email->get_reset_link() );
				return;
			}

			$sent = $email->send();

			if ( ! $sent ) {
				charitable_get_notices()->add_error( __( 'We were unable to send your password reset email.', 'charitable' ) );
				return;
			}

			charitable_get_notices()->add_success( __( 'Your password reset request has been received. Please check your email for a link to reset your password.', 'charitable' ) );

			charitable_get_session()->add_notices();

			$redirect_url = esc_url_raw( charitable_get_permalink( 'login_page' ) );

			wp_safe_redirect( $redirect_url );

			exit();
		}
	}

endif;
