<?php
/**
 * Class that models the Email Verification email.
 *
 * @package   Charitable/Classes/Charitable_Email_Email_Verification
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.37
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Email_Email_Verification' ) ) :

	/**
	 * Password Reset Email
	 *
	 * @since   1.5.0
	 */
	class Charitable_Email_Email_Verification extends Charitable_Email {

		/** Email ID */
		const ID = 'email_verification';

		/**
		 * Whether the email allows you to define the email recipients.
		 *
		 * @since 1.5.0
		 *
		 * @var   boolean
		 */
		protected $has_recipient_field = false;

		/**
		 * The Email Verification email is required.
		 *
		 * @since 1.5.0
		 *
		 * @var   boolean
		 */
		protected $required = true;

		/**
		 * The user data.
		 *
		 * @since 1.5.0
		 *
		 * @var   WP_User
		 */
		protected $user;

		/**
		 * Array of supported object types (campaigns, donations, donors, etc).
		 *
		 * @since 1.5.0
		 *
		 * @var   string[]
		 */
		protected $object_types = array( 'user' );

		/**
		 * The confirmation link.
		 *
		 * @since 1.5.0
		 *
		 * @var   string|WP_Error
		 */
		protected $confirmation_url;

		/**
		 * The URL to redirect to after verification.
		 *
		 * @since 1.5.0
		 *
		 * @var   string|WP_Error
		 */
		protected $redirect_url;

		/**
		 * Instantiate the email class, defining its key values.
		 *
		 * @since 1.5.0
		 *
		 * @param mixed[] $objects Array containing a user object, or nothing if this is a preview.
		 */
		public function __construct( $objects = array() ) {
			parent::__construct( $objects );

			add_filter( 'charitable_settings_fields_emails_email_' . self::ID, array( $this, 'add_email_settings' ), 1 );

			/**
			 * Filter the default email name.
			 *
			 * @since 1.5.0
			 *
			 * @param string $name The email name.
			 */
			$this->name = apply_filters( 'charitable_email_email_verification_name', __( 'User: Email Verification', 'charitable' ) );

			$this->user = array_key_exists( 'user', $objects ) ? $objects['user'] : false;
		}

		/**
		 * Returns the current email's ID.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		public static function get_email_id() {
			return self::ID;
		}

		/**
		 * Return the recipient for the email.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		public function get_recipient() {
			return $this->has_valid_user() ? $this->user->user_email : '';
		}

		/**
		 * Add extra email settings.
		 *
		 * @since  1.5.7
		 *
		 * @param  array $settings Email settings.
		 * @return array
		 */
		public function add_email_settings( $settings ) {
			return array_merge(
				$settings,
				array(
					'send_after_registration' => array(
						'type'     => 'radio',
						'title'    => __( 'Send after Registration', 'charitable' ),
						'help'     => __( 'Whether to send an email verification message immediately after registration. If this is not set, users are only prompted to verify their email when they try to access their donation history.', 'charitable' ),
						'priority' => 22,
						'class'    => 'wide',
						'default'  => 1,
						'options'  => array(
							'1' => __( 'Yes', 'charitable' ),
							'0' => __( 'No', 'charitable' ),
						),
					),
				)
			);
		}

		/**
		 * Return the custom email fields for this email.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function email_fields() {
			$fields = array(
				'confirmation_url' => array(
					'description' => __( 'The link the user needs to verify their email address.', 'charitable' ),
					'preview'     => add_query_arg(
						array(
							'key'   => '123123123',
							'login' => 'adam123',
						),
						charitable_get_permalink( 'email_verification' )
					),
				),
			);

			if ( $this->has_valid_user() ) {
				$fields = array_merge_recursive(
					$fields,
					array(
						'confirmation_url' => array( 'callback' => array( $this, 'get_confirmation_url' ) ),
					)
				);
			}

			return $fields;
		}

		/**
		 * Return the reset link.
		 *
		 * @since  1.5.0
		 *
		 * @return string|WP_Error If the reset key could not be generated, an error is returned.
		 */
		public function get_confirmation_url() {
			if ( ! isset( $this->confirmation_url ) ) {
				$key = get_password_reset_key( $this->user );

				if ( is_wp_error( $key ) ) {
					return $key;
				}

				$args = array(
					'key'        => $key,
					'login'      => rawurlencode( $this->user->user_login ),
					'charitable' => true,
				);

				$redirect_url = $this->get_redirect_url();

				if ( $redirect_url ) {
					$args['redirect_to'] = $redirect_url;
				}

				$this->confirmation_url = esc_url_raw( add_query_arg( $args, charitable_get_permalink( 'email_verification' ) ) );
			}

			return $this->confirmation_url;
		}

		/**
		 * Return the redirect URL.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		public function get_redirect_url() {
			if ( ! isset( $this->redirect_url ) ) {
				$profile            = charitable_get_option( 'profile_page', false );
				$this->redirect_url = $profile ? get_permalink( $profile ) : false;
			}

			return $this->redirect_url;
		}

		/**
		 * Set the redirect URL.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $redirect_url The URL.
		 * @return void
		 */
		public function set_redirect_url( $redirect_url ) {
			$this->redirect_url = $redirect_url;
		}

		/**
		 * Send the email, then record the time that it was sent.
		 *
		 * @since  1.6.32
		 *
		 * @return boolean
		 */
		public function send() {
			$sent = parent::send();

			if ( $sent ) {
				update_user_meta( $this->user->ID, '_charitable_email_verification_email_send_time', time() );
			}

			return $sent;
		}

		/**
		 * Checks whether the email was sent recently.
		 *
		 * @since  1.6.32
		 *
		 * @return boolean
		 */
		public function sent_recently() {
			$send_time = get_user_meta( $this->user->ID, '_charitable_email_verification_email_send_time', true );

			if ( ! $send_time ) {
				return false;
			}

			/**
			 * Change the amount of minutes to wait before resending
			 * the email (unless it's a force-resend).
			 *
			 * By default, this is set to wait 30 minutes.
			 *
			 * @since 1.6.32
			 *
			 * @param boolean $seconds The number of seconds to wait.
			 */
			$threshold = apply_filters( 'charitable_email_verification_resend_threshold_time', 1800 );

			return time() - $send_time < $threshold;
		}

		/**
		 * Return the default subject line for the email.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		protected function get_default_subject() {
			return __( 'Verify your email for [charitable_email show=site_name]', 'charitable' );
		}

		/**
		 * Return the default headline for the email.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		protected function get_default_headline() {
			/**
			 * Filter the default email headline.
			 *
			 * @since 1.5.0
			 *
			 * @param string                              $headline The default headline.
			 * @param Charitable_Email_Email_Verification $email    This instance of `Charitable_Email_Email_Verification`.
			 */
			return apply_filters( 'charitable_email_email_verification_default_headline', __( 'Verify your email address', 'charitable' ), $this );
		}

		/**
		 * Return the default body for the email.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		protected function get_default_body() {
			ob_start();
			?>
<p><?php _e( 'Welcome to [charitable_email show=site_name]', 'charitable' ); ?></p>
<p><?php _e( 'To complete your registration, please confirm your email address by clicking the link below:', 'charitable' ); ?></p>
<p><a href="[charitable_email show=confirmation_url]">[charitable_email show=confirmation_url]</a></p>
			<?php
			/**
			 * Filter the default email body.
			 *
			 * @since 1.5.0
			 *
			 * @param string                              $body  The default body.
			 * @param Charitable_Email_Email_Verification $email This instance of `Charitable_Email_Email_Verification`.
			 */
			return apply_filters( 'charitable_email_email_verification_default_body', ob_get_clean(), $this );
		}

		/**
		 * Check whether a user is set for the object instance.
		 *
		 * @since  1.5.0
		 *
		 * @return boolean
		 */
		protected function has_valid_user() {
			return isset( $this->user ) && is_a( $this->user, 'WP_User' );
		}
	}

endif;
