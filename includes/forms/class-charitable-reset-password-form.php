<?php
/**
 * Class that manages the display and processing of the reset password form.
 *
 * @package   Charitable/Classes/Charitable_Reset_Password_Form
 * @version   1.5.1
 * @author    Rafe Colton, Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Reset_Password_Form' ) ) :

	/**
	 * Charitable_Reset_Password_Form
	 *
	 * @since 1.4.0
	 */
	class Charitable_Reset_Password_Form extends Charitable_Form {

		/**
		 * @since 1.4.0
		 *
		 * @var   string
		 */
		protected $nonce_action = 'charitable_reset_password';

		/**
		 * @since 1.4.0
		 *
		 * @var   string
		 */
		protected $nonce_name = '_charitable_reset_password_nonce';

		/**
		 * Form action.
		 *
		 * @since 1.4.0
		 *
		 * @var   string
		 */
		protected $form_action = 'reset_password';

		/**
		 * Reset key.
		 *
		 * @since 1.4.0
		 *
		 * @var   string|null
		 */
		protected $key;

		/**
		 * Form action.
		 *
		 * @since 1.4.0
		 *
		 * @var   string|null
		 */
		protected $login;

		/**
		 * Whether the reset key is available.
		 *
		 * @since 1.5.0
		 *
		 * @var   boolean
		 */
		protected $has_key;

		/**
		 * Create class object.
		 *
		 * @since 1.4.0
		 *
		 * @param array $args User-defined shortcode attributes.
		 */
		public function __construct( $args = array() ) {
			$this->id      = uniqid();
			$this->has_key = $this->parse_reset_key();

			/* For backwards-compatibility */
			add_action( 'charitable_form_field', array( $this, 'render_field' ), 10, 6 );
		}

		/**
		 * Return whether the reset key is set correctly.
		 *
		 * If this returns false, the password reset will always fail so we avoid
		 * displaying the form at all.
		 *
		 * @since  1.5.0
		 *
		 * @return boolean
		 */
		public function has_key() {
			return $this->has_key;
		}

		/**
		 * Retrieve hidden fields.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function get_hidden_fields() {
			$fields          = parent::get_hidden_fields();
			$fields['key']   = $this->key;
			$fields['login'] = array(
				'value'        => $this->login,
				'autocomplete' => 'off',
			);
			return $fields;
		}

		/**
		 * Reset password fields to be displayed.
		 *
		 * @since  1.4.0
		 *
		 * @return array
		 */
		public function get_fields() {
			if ( ! $this->has_key ) {
				return array();
			}

			$fields = apply_filters( 'charitable_reset_password_fields', array(
				'pass1' => array(
					'label'    => __( 'New Password', 'charitable' ),
					'type'     => 'password',
					'required' => true,
					'priority' => 10,
					'attrs'    => array(
						'size'         => 20,
						'autocomplete' => 'off',
					),
				),
				'pass2' => array(
					'label'    => __( 'Repeat New Password', 'charitable' ),
					'type'     => 'password',
					'required' => true,
					'priority' => 11,
					'attrs'    => array(
						'size'         => 20,
						'autocomplete' => 'off',
					),
				),
			) );

			uasort( $fields, 'charitable_priority_sort' );

			return $fields;
		}

		/**
		 * Reset the password.
		 *
		 * @since  1.4.0
		 *
		 * @return bool|WP_Error True: when finish. WP_Error on error
		 */
		public static function reset_password() {
			$form = new Charitable_Reset_Password_Form();

			if ( ! $form->validate_nonce() || ! $form->validate_honeypot() ) {
				charitable_get_notices()->add_error( __( 'There was an error with processing your form submission. Please reload the page and try again.', 'charitable' ) );
				return;
			}

			/* The key and login must be set. */
			if ( ! isset( $_POST['key'] ) || ! isset( $_POST['login'] ) ) {
				charitable_get_notices()->add_error( '<strong>ERROR:</strong> Invalid reset key.', 'charitable' );
				return;
			}

			$user = check_password_reset_key( $_POST['key'], $_POST['login'] );

			if ( is_wp_error( $user ) ) {
				charitable_get_notices()->add_errors_from_wp_error( $user );
				return;
			}

			/* One of the passwords was not set. */
			if ( ! isset( $_POST['pass1'] ) || ! isset( $_POST['pass2'] ) ) {
				charitable_get_notices()->add_error( '<strong>ERROR:</strong> You must enter both passwords.', 'charitable' );
				return;
			}

			/* The passwords do not match. */
			if ( $_POST['pass1'] != $_POST['pass2'] ) {
				charitable_get_notices()->add_error( __( '<strong>ERROR:</strong> The two passwords you entered don\'t match.', 'charitable' ) );
				return;
			}

			/* Parameter checks OK, reset password */
			reset_password( $user, $_POST['pass1'] );

			charitable_get_notices()->add_success( __( 'Your password was successfully changed.', 'charitable' ) );

			charitable_get_session()->add_notices();

			wp_safe_redirect( charitable_get_permalink( 'login_page' ) );

			exit();
		}

		/**
		 * Get the reset key and login from the cookie.
		 *
		 * @since  1.4.0
		 * @since  1.5.0 Returns a boolean to indicate whether the key and login are available.
		 *
		 * @return boolean True if the reset key is found. False otherwise.
		 */
		protected function parse_reset_key() {
			$this->key   = null;
			$this->login = null;

			if ( ! isset( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ) ) {
				charitable_get_notices()->add_error( __( 'Missing password reset key.', 'charitable' ) );
				return false;
			}

			$cookie = $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ];

			if ( ! strpos( $cookie, ':' ) ) {
				charitable_get_notices()->add_error( __( 'Missing password reset key.', 'charitable' ) );
				return false;
			}

			$cookie_parts        = explode( ':', wp_unslash( $cookie ), 2 );
			list( $login, $key ) = array_map( 'sanitize_text_field', $cookie_parts );
			$user                = check_password_reset_key( $key, $login );

			if ( is_wp_error( $user ) ) {
				charitable_get_notices()->add_errors_from_wp_error( $user );
				Charitable_User_Management::get_instance()->set_reset_cookie();
				return false;
			}

			/* Reset key / login is correct, display reset password form with hidden key / login values */
			$this->key   = $key;
			$this->login = $login;

			return true;
		}
	}

endif;
