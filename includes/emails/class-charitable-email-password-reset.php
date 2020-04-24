<?php
/**
 * Class that models the Password Reset email.
 *
 * @package   Charitable/Classes/Charitable_Email_Password_Reset
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.4.0
 * @version   1.6.37
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Email_Password_Reset' ) ) :

	/**
	 * Password Reset Email
	 *
	 * @since   1.4.0
	 */
	class Charitable_Email_Password_Reset extends Charitable_Email {

		/** Email ID */
		const ID = 'password_reset';

		/**
		 * Whether the email allows you to define the email recipients.
		 *
		 * @since 1.4.0
		 *
		 * @var   boolean
		 */
		protected $has_recipient_field = false;

		/**
		 * The Password Reset email is required.
		 *
		 * @since 1.4.0
		 *
		 * @var   boolean
		 */
		protected $required = true;

		/**
		 * The user data.
		 *
		 * @since 1.4.0
		 *
		 * @var   WP_User
		 */
		protected $user;

		/**
		 * Array of supported object types (campaigns, donations, donors, etc).
		 *
		 * @since 1.4.0
		 *
		 * @var   string[]
		 */
		protected $object_types = array( 'user' );

		/**
		 * The reset link.
		 *
		 * @since 1.4.0
		 *
		 * @var   string|WP_Error
		 */
		protected $reset_link;

		/**
		 * Instantiate the email class, defining its key values.
		 *
		 * @since 1.4.0
		 *
		 * @param mixed[] $objects Array containing a user object,
		 *                         or nothing if this is a preview.
		 */
		public function __construct( $objects = array() ) {
			parent::__construct( $objects );

			/**
			 * Filter the default email name.
			 *
			 * @since 1.4.0
			 *
			 * @param string $name The email name.
			 */
			$this->name = apply_filters( 'charitable_email_password_reset_name', __( 'User: Password Reset', 'charitable' ) );

			$this->user = array_key_exists( 'user', $objects ) ? $objects['user'] : false;
		}

		/**
		 * Returns the current email's ID.
		 *
		 * @since   1.4.0
		 *
		 * @return  string
		 */
		public static function get_email_id() {
			return self::ID;
		}

		/**
		* Return the recipient for the email.
		*
		* @since  1.4.0
		*
		* @return string
		*/
		public function get_recipient() {
			return $this->has_valid_user() ? $this->user->user_email : '';
		}

		/**
		 * Return the custom email fields for this email.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function email_fields() {
			/* If we are using the default WordPress login process, return an empty array. */
			if ( wp_login_url() == charitable_get_permalink( 'login_page' ) ) {
				return array();
			}

			$fields = array(
				'reset_link' => array(
					'description' => __( 'The link the user needs to click to reset their password', 'charitable' ),
					'preview'     => add_query_arg( array(
						'key'   => '123123123',
						'login' => 'adam123',
					), charitable_get_permalink( 'reset_password_page' ) ),
				),
			);

			if ( $this->has_valid_user() ) {
				$fields = array_merge_recursive( $fields, array(
					'reset_link' => array( 'callback' => array( $this, 'get_reset_link' ) ),
				) );
			}

			return $fields;
		}

		/**
		 * Return the reset link.
		 *
		 * @since  1.5.0
		 *
		 * @return string|WP_Error|false If the reset key could not be generated, an error is returned.
		 */
		public function get_reset_link() {
			if ( ! isset( $this->reset_link ) ) {
				$base_url = charitable_get_permalink( 'reset_password_page' );
				$key      = get_password_reset_key( $this->user );

				if ( is_wp_error( $key ) ) {
					return $key;
				}

				$this->reset_link = esc_url_raw(
					add_query_arg(
						array(
							'key'        => $key,
							'login'      => rawurlencode( $this->user->user_login ),
							'charitable' => true,
						),
						$base_url
					)
				);
			}

			return $this->reset_link;
		}

		/**
		 * Return the default subject line for the email.
		 *
		 * @since  1.4.0
		 *
		 * @return string
		 */
		protected function get_default_subject() {
			return __( 'Password Reset for [charitable_email show=site_name]', 'charitable' );
		}

		/**
		 * Return the default headline for the email.
		 *
		 * @since  1.4.0
		 *
		 * @return string
		 */
		protected function get_default_headline() {
			/**
			 * Filter the default email headline.
			 *
			 * @since 1.4.0
			 *
			 * @param string                          $headline The default headline.
			 * @param Charitable_Email_Password_Reset $email    This instance of `Charitable_Email_Password_Reset`.
			 */
			return apply_filters( 'charitable_email_password_reset_default_headline', __( 'Reset your password', 'charitable' ), $this );
		}

		/**
		 * Return the default body for the email.
		 *
		 * @since  1.4.0
		 *
		 * @return string
		 */
		protected function get_default_body() {
			ob_start();
?>
<p><?php _e( 'Someone requested that the password be reset for the following account:', 'charitable' ) ?></p>
<p><?php _e( 'Username: [charitable_email show=user_login]', 'charitable' ) ?></p>
<p><?php _e( 'If this was a mistake, just ignore this email and nothing will happen.', 'charitable' ) ?></p>
<p><?php _e( 'To reset your password, visit the following address:', 'charitable' ) ?></p>
<p><a href="[charitable_email show=reset_link]">[charitable_email show=reset_link]</a></p>
<?php
			/**
			 * Filter the default email body.
			 *
			 * @since 1.4.0
			 *
			 * @param string                          $body  The default body text.
			 * @param Charitable_Email_Password_Reset $email This instance of `Charitable_Email_Password_Reset`.
			 */
			return apply_filters( 'charitable_email_password_reset_default_body', ob_get_clean(), $this );
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
