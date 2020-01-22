<?php
/**
 * Email Fields class.
 *
 * @package   Charitable/Classes/Charitable_Email_Fields
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

if ( ! class_exists( 'Charitable_Email_Fields' ) ) :

	/**
	 * Charitable_Email_Fields class.
	 *
	 * @since 1.5.0
	 */
	class Charitable_Email_Fields {

		/**
		 * Email object.
		 *
		 * @since 1.5.0
		 *
		 * @var   Charitable_Email
		 */
		private $email;

		/**
		 * Whether this is an email preview.
		 *
		 * @since 1.5.0
		 *
		 * @var   boolean
		 */
		private $preview;

		/**
		 * An array of registered fields that are available for this email.
		 *
		 * @since 1.5.0
		 *
		 * @var   array
		 */
		private $fields;

		/**
		 * Set up class instance.
		 *
		 * @since 1.5.0
		 *
		 * @param Charitable_Email $email   The email object.
		 * @param boolean          $preview Whether this is an email preview.
		 */
		public function __construct( Charitable_Email $email, $preview = false ) {
			$this->email   = $email;
			$this->preview = $preview;
			$this->fields  = $this->init_fields();
		}

		/**
		 * Returns whether this is an email preview.
		 *
		 * @since  1.5.0
		 *
		 * @return boolean
		 */
		public function is_preview() {
			return $this->preview;
		}

		/**
		 * Get the fields that apply to the current email.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function init_fields() {
			$site_name = get_option( 'blogname' );
			$home_url  = home_url();
			$fields    = array(
				'site_name' => array(
					'description' => __( 'Your website title', 'charitable' ),
					'value'       => $site_name,
					'preview'     => $site_name,
				),
				'site_url'  => array(
					'description' => __( 'Your website URL', 'charitable' ),
					'value'       => $home_url,
					'preview'     => $home_url,
				),
			);

			$object_types = $this->email->get_object_types();

			if ( ! empty( $object_types ) ) {
				$fields = array_merge( $fields, array_reduce( $object_types, array( $this, 'get_object_type_fields' ) ) );
			}

			$fields = array_merge( $fields, $this->email->email_fields() );

			/**
			 * For backwards compatibility reasons, we still set hook the email's
			 * deprecated methods for adding fields. Some extensions define their
			 * own child versions of these methods, and expect them to be hooked
			 * automatically.
			 *
			 * This will be removed in or after Charitable 1.8.
			 */
			add_filter( 'charitable_email_content_fields', array( $this->email, 'add_donation_content_fields' ), 10, 2 );
			add_filter( 'charitable_email_preview_content_fields', array( $this->email, 'add_preview_donation_content_fields' ), 10, 2 );
			add_filter( 'charitable_email_content_fields', array( $this->email, 'add_campaign_content_fields' ), 10, 2 );
			add_filter( 'charitable_email_preview_content_fields', array( $this->email, 'add_preview_campaign_content_fields' ), 10, 2 );

			/**
			 * Filter the email content fields.
			 *
			 * @since 1.0.0
			 *
			 * @param array            $fields Registered fields.
			 * @param Charitable_Email $email  Instance of `Charitable_Email` type.
			 */
			$fields = apply_filters( 'charitable_email_content_fields', $fields, $this->email );

			/**
			 * Finally, if we are previewing the email, parse any preview fields added
			 * through the `charitable_email_preview_content_fields` filter, which is
			 * deprecated as of 1.5.0.
			 */
			if ( $this->preview ) {
				$fields = $this->parse_preview_fields( $fields );
			}

			return $fields;
		}

		/**
		 * Return the fields array.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function get_fields() {
			return $this->fields;
		}

		/**
		 * Get the value for a particular email field.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $field The field.
		 * @param  array  $args  Mixed arguments.
		 * @return string
		 */
		public function get_field_value( $field, $args ) {
			$value = '';

			/* Get the field value if the field is registered and has a 'value' or 'callback' set. */
			if ( array_key_exists( $field, $this->fields ) ) {
				$value = $this->get_field_value_from_field_details( $this->fields[ $field ], $args );
			}

			/**
			 * Filter the returned value.
			 *
			 * @since 1.0.0
			 *
			 * @param string           $value The field value.
			 * @param array            $args  Mixed arguments.
			 * @param Charitable_Email $email The Email object.
			 */
			return apply_filters( 'charitable_email_content_field_value_' . $field, $value, $args, $this->email );
		}

		/**
		 * Return a value for the field from its details.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $field_details Email field definition.
		 * @param  array $args          Mixed shortcode arguments.
		 * @return string
		 */
		private function get_field_value_from_field_details( array $field_details, array $args ) {
			if ( array_key_exists( 'value', $field_details ) ) {
				return $field_details['value'];
			}

			if ( $this->preview ) {
				return array_key_exists( 'preview', $field_details ) ? $field_details['preview'] : '';
			}

			if ( array_key_exists( 'callback', $field_details ) ) {
				return call_user_func( $field_details['callback'], '', $args, $this->email );
			}

			/* If we still don't have a value, return an empty string. */
			return '';
		}

		/**
		 * Return the fields for a particular object type.
		 *
		 * @since  1.5.0
		 *
		 * @param  null|array $fields      Object type fields so far.
		 * @param  string     $object_type Object type to add.
		 * @return array
		 */
		private function get_object_type_fields( $fields, $object_type ) {
			if ( is_null( $fields ) ) {
				$fields = array();
			}

			$words = str_replace( '_', ' ', $object_type );
			$class = 'Charitable_Email_Fields_' . str_replace( ' ', '_', ucwords( $words ) );

			if ( ! class_exists( $class ) ) {
				return $fields;
			}

			$object = new $class( $this->email, $this->preview );

			if ( ! is_a( $object, 'Charitable_Email_Fields_Interface' ) ) {
				return $fields;
			}

			return array_merge( $fields, $object->get_fields() );
		}

		/**
		 * Parse preview fields registered through charitable_email_preview_content_fields hook.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $fields Registered email fields.
		 * @return array
		 */
		private function parse_preview_fields( $fields ) {
			/**
			 * Filter preview values for content fields. This filter is deprecated,
			 * as preview values can be simply defined when setting up the content
			 * field.
			 *
			 * @deprecated
			 *
			 * @since 1.0.0
			 *
			 * @param array            $fields Fields with their preview values. Single dimensional key => value array.
			 * @param Charitable_Email $email  The instance of `Charitable_Email`.
			 */
			$preview_values = apply_filters( 'charitable_email_preview_content_fields', array(), $this->email );

			foreach ( $preview_values as $key => $value ) {
				if ( ! array_key_exists( $key, $fields ) ) {
					continue;
				}

				$fields[ $key ]['preview'] = $value;
			}

			return $fields;
		}
	}

endif;
