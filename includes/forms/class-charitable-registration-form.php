<?php
/**
 * Class that manages the display and processing of the registration form.
 *
 * @package     Charitable/Classes/Charitable_Registration_Form
 * @version     1.5.1
 * @author      Eric Daams
 * @copyright   Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Registration_Form' ) ) :

	/**
	 * Charitable_Registration_Form
	 *
	 * @since 1.0.0
	 */
	class Charitable_Registration_Form extends Charitable_Form {

		/**
		 * Shortcode parameters.
		 *
		 * @since 1.0.0
		 *
		 * @var   array
		 */
		protected $shortcode_args;

		/**
		 * Nonce action.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		protected $nonce_action = 'charitable_user_registration';

		/**
		 * Nonce name.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		protected $nonce_name = '_charitable_user_registration_nonce';

		/**
		 * Action to be executed upon form submission.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		protected $form_action = 'save_registration';

		/**
		 * The current donor.
		 *
		 * @since 1.0.0
		 *
		 * @var   Charitable_Donor
		 */
		protected $donor;

		/**
		 * Create class object.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args User-defined shortcode attributes.
		 */
		public function __construct( $args = array() ) {
			$this->id             = uniqid();
			$this->shortcode_args = $args;

			/* For backwards-compatibility */
			add_action( 'charitable_form_field', array( $this, 'render_field' ), 10, 6 );
		}

		/**
		 * Return the arguments passed to the shortcode.
		 *
		 * @since  1.4.0
		 *
		 * @return mixed[]
		 */
		public function get_shortcode_args() {
			return $this->shortcode_args;
		}

		/**
		 * Profile fields to be displayed.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_fields() {
			$fields = array(
				'user_email' => array(
					'label'    => __( 'Email', 'charitable' ),
					'type'     => 'email',
					'required' => true,
					'priority' => 4,
					'value'    => isset( $_POST['user_email'] ) ? $_POST['user_email'] : '',
				),
				'user_login' => array(
					'label'    => __( 'Username', 'charitable' ),
					'type'     => 'text',
					'priority' => 6,
					'required' => true,
					'value'    => isset( $_POST['user_login'] ) ? $_POST['user_login'] : '',
				),
				'user_pass'  => array(
					'label'    => __( 'Password', 'charitable' ),
					'type'     => 'password',
					'priority' => 8,
					'required' => true,
					'value'    => isset( $_POST['user_pass'] ) ? $_POST['user_pass'] : '',
				),
			);

			$fields = $this->maybe_add_terms_conditions_fields( $fields );

			/**
			 * Filter the user registration fields.
			 *
			 * @since 1.0.0
			 *
			 * @param array $fields The registered fields.
			 */
			$fields = apply_filters( 'charitable_user_registration_fields', $fields );

			uasort( $fields, 'charitable_priority_sort' );

			return $fields;
		}

		/**
		 * Maybe add terms and conditions fields to the form.
		 *
		 * @since  1.6.2
		 *
		 * @param  array $fields The registered form fields.
		 * @return array
		 */
		public function maybe_add_terms_conditions_fields( $fields ) {
			if ( charitable_is_privacy_policy_activated() ) {
				$fields['privacy_policy_text'] = array(
					'type'     => 'content',
					'content'  => '<p class="charitable-privacy-policy-text">' . charitable_get_privacy_policy_field_text() . '</p>',
					'priority' => 20,
				);
			}

			if ( charitable_is_contact_consent_activated() ) {
				$fields['contact_consent'] = array(
					'type'     => 'checkbox',
					'label'    => charitable_get_option( 'contact_consent_label', __( 'Yes, I am happy for you to contact me via email or phone.', 'charitable' ) ),
					'priority' => 22,
					'required' => false,
					'checked'  => array_key_exists( 'contact_consent', $_POST ) && $_POST['contact_consent'],
				);
			}

			if ( charitable_is_terms_and_conditions_activated() ) {
				$fields['terms_text'] = array(
					'type'     => 'content',
					'content'  => '<div class="charitable-terms-text">' . charitable_get_terms_and_conditions() . '</div>',
					'priority' => 24,
				);

				$fields['accept_terms'] = array(
					'type'      => 'checkbox',
					'label'     => charitable_get_terms_and_conditions_field_label(),
					'priority'  => 28,
					'required'  => true,
					'data_type' => 'meta',
				);
			}

			return $fields;
		}

		/**
		 * Return the hidden fields for this form.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function get_hidden_fields() {
			$fields   = parent::get_hidden_fields();
			$redirect = $this->get_redirect_url();

			if ( false !== $redirect ) {
				$fields['redirect_to'] = $redirect;
			}

			return $fields;
		}

		/**
		 * Update registration after form submission.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public static function save_registration() {
			$form = new Charitable_Registration_Form();

			if ( ! $form->validate_nonce() || ! $form->validate_honeypot() ) {
				charitable_get_notices()->add_error( __( 'There was an error with processing your form submission. Please reload the page and try again.', 'charitable' ) );
				return;
			}

			$fields = $form->get_fields();
			$valid  = $form->check_required_fields( $fields );

			if ( ! $valid ) {
				return;
			}

			$submitted = apply_filters( 'charitable_registration_values', $_POST, $fields, $form );

			if ( ! isset( $submitted['user_email'] ) || ! is_email( $submitted['user_email'] ) ) {
				charitable_get_notices()->add_error(
					sprintf(
						/* translators: %s: submitted email address */
						__( '%s is not a valid email address.', 'charitable' ),
						$submitted['user_email']
					)
				);

				return false;
			}

			$user    = new Charitable_User();
			$user_id = $user->update_profile( $submitted, array_keys( $fields ) );

			/**
			 * If the user was successfully created, redirect to the login redirect URL.
			 * If there was a problem, this simply falls through and keeps the user on the
			 * registration page.
			 */
			if ( $user_id ) {

				/* Maybe send an email verification email. */
				if ( charitable_get_option( array( 'emails_email_verification', 'send_after_registration' ), 1 ) ) {

					/* If the confirmation link is generated correctly and the email is sent, set a notice. */
					if ( Charitable_User_Management::get_instance()->send_verification_email( $user ) ) {
						$message = sprintf(
							/* translators: %s: email verification link */
							__( 'We have sent you an email to confirm your email address. Haven\'t received the email? <a href="%s">Click here to send it again.</a>', 'charitable' ),
							esc_url_raw( charitable_get_email_verification_link( $user, false, true ) )
						);

						charitable_get_notices()->add_success( $message );
						charitable_get_session()->add_notices();
					}
				}

				wp_safe_redirect( charitable_get_login_redirect_url() );
				exit();
			}
		}

		/**
		 * Return the link to the login page, or false if we are not going to display it.
		 *
		 * @since  1.4.2
		 *
		 * @return false|string
		 */
		public function get_login_link() {
			if ( false == $this->shortcode_args['login_link_text'] || 'false' == $this->shortcode_args['login_link_text'] ) {
				return false;
			}

			$login_link = charitable_get_permalink( 'login_page' );

			if ( charitable_get_permalink( 'registration_page' ) === $login_link ) {
				return false;
			}

			if ( isset( $_GET['redirect_to'] ) ) {
				$login_link = add_query_arg( 'redirect_to', $_GET['redirect_to'], $login_link );
			}

			return sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $login_link ),
				$this->shortcode_args['login_link_text']
			);
		}

		/**
		 * Get the redirect URL.
		 *
		 * @since  1.5.0
		 *
		 * @return string|false
		 */
		protected function get_redirect_url() {
			$redirect = false;

			if ( isset( $_GET['redirect_to'] ) && strlen( $_GET['redirect_to'] ) ) {
				$redirect = $_GET['redirect_to'];
			} elseif ( isset( $this->shortcode_args['redirect'] ) && strlen( $this->shortcode_args['redirect'] ) ) {
				$redirect = $this->shortcode_args['redirect'];
			}

			return $redirect;
		}
	}

endif;
