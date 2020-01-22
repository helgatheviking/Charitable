<?php
/**
 * Email Fields Donation class.
 *
 * @package   Charitable/Classes/Charitable_Email_Fields_User
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Email_Fields_User' ) ) :

	/**
	 * Charitable_Email_Fields class.
	 *
	 * @since 1.5.0
	 */
	class Charitable_Email_Fields_User implements Charitable_Email_Fields_Interface {

		/**
		 * The WP_User object.
		 *
		 * @since 1.5.0
		 *
		 * @var   WP_User
		 */
		private $user;

		/**
		 * Set up class instance.
		 *
		 * @since 1.5.0
		 *
		 * @param Charitable_Email $email   The email object.
		 * @param boolean          $preview Whether this is an email preview.
		 */
		public function __construct( Charitable_Email $email, $preview ) {
			$this->email   = $email;
			$this->preview = $preview;
			$this->user    = $email->get( 'user' );
			$this->fields  = $this->init_fields();
		}

		/**
		 * Get the fields that apply to the current email.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function init_fields() {
			$fields = array(
				'user_login' => array(
					'description' => __( 'The user login', 'charitable' ),
					'preview'     => 'adam123',
				),
				'first_name' => array(
					'description' => __( 'The user\'s first name', 'charitable' ),
					'preview'     => 'Adam',
				),
				'last_name' => array(
					'description' => __( 'The user\'s last name', 'charitable' ),
					'preview'     => 'Jones',
				),
				'display_name' => array(
					'description' => __( 'The user\'s display name', 'charitable' ),
					'preview'     => 'Adam Jones',
				),
			);

			if ( $this->has_valid_user() ) {
				$fields = array_merge_recursive( $fields, array(
					'user_login'   => array( 'value' => $this->user->user_login ),
					'first_name'   => array( 'value' => $this->user->first_name ),
					'last_name'    => array( 'value' => $this->user->last_name ),
					'display_name' => array( 'value' => $this->user->display_name ),
				) );
			}

			/**
			 * Filter the user email fields.
			 *
			 * @since 1.5.0
			 *
			 * @param array            $fields The default set of fields.
			 * @param Charitable_User  $user   Instance of `WP_User`.
			 * @param Charitable_Email $email  Instance of `Charitable_Email`.
			 */
			return apply_filters( 'charitable_email_user_fields', $fields, $this->user, $this->email );
		}

		/**
		 * Return fields.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function get_fields() {
			return $this->fields;
		}

		/**
		 * Checks whether the email has a valid `WP_User` object set.
		 *
		 * @since  1.5.0
		 *
		 * @return boolean
		 */
		public function has_valid_user() {
			return is_a( $this->user, 'WP_User' );
		}
	}

endif;
