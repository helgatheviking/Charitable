<?php
/**
 * Email Fields Donation class.
 *
 * @package   Charitable/Classes/Charitable_Email_Fields_Donation
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

if ( ! class_exists( 'Charitable_Email_Fields_Donation' ) ) :

	/**
	 * Charitable_Email_Fields class.
	 *
	 * @since 1.5.0
	 */
	class Charitable_Email_Fields_Donation implements Charitable_Email_Fields_Interface {

		/**
		 * The Charitable_Donation object.
		 *
		 * @since 1.5.0
		 *
		 * @var   Charitable_Donation
		 */
		private $donation;

		/**
		 * Set up class instance.
		 *
		 * @since 1.5.0
		 *
		 * @param Charitable_Email $email   The email object.
		 * @param boolean          $preview Whether this is an email preview.
		 */
		public function __construct( Charitable_Email $email, $preview ) {
			$this->email    = $email;
			$this->preview  = $preview;
			$this->donation = $email->get_donation();
			$this->fields   = $this->init_fields();
		}

		/**
		 * Get the fields that apply to the current email.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function init_fields() {
			$fields = charitable()->donation_fields()->get_email_tag_fields();
			$fields = array_map( array( $this, 'parse_donation_field' ), $fields );
			$fields = array_combine( wp_list_pluck( $fields, 'tag' ), $fields );

			/**
			 * Filter the donation email fields.
			 *
			 * @since 1.5.0
			 *
			 * @param array                    $fields   The default set of fields.
			 * @param Charitable_Donation|null $donation When an email is being sent, this will be an instance of `Charitable_Donation`. Otherwise it's null.
			 * @param Charitable_Email         $email    Instance of `Charitable_Email`.
			 */
			return apply_filters( 'charitable_email_donation_fields', $fields, $this->donation, $this->email );
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
		 * Checks whether the email has a valid donation object set.
		 *
		 * @since  1.5.0
		 *
		 * @return boolean
		 */
		public function has_valid_donation() {
			return ! is_null( $this->donation ) && is_a( $this->donation, 'Charitable_Donation' );
		}

		/**
		 * Return the campaigns donated to.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $value The content to display in place of the shortcode.
		 * @param  array  $args  Optional set of arguments.
		 * @return string
		 */
		public function get_campaigns( $value, $args ) {
			$linked = array_key_exists( 'with_links', $args ) ? $args['with_links'] : false;
			return $this->donation->get_campaigns_donated_to( $linked );
		}

		/**
		 * Return the value for a particular field that is registered as a Charitable_Donation_Field.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $value The content to display in place of the shortcode.
		 * @param  array  $args  Optional set of arguments.
		 * @return string
		 */
		public function get_value_from_donation_field( $value, $args ) {
			if ( ! array_key_exists( $args['show'], $this->fields ) || ! array_key_exists( 'field', $this->fields[ $args['show'] ] ) ) {
				return '';
			}

			return $this->donation->get( $this->fields[ $args['show'] ]['field'] );
		}

		/**
		 * Parse a donation field, returning just the email tag parameters.
		 *
		 * @since  1.5.0
		 *
		 * @param  Charitable_Donation_Field $field A Charitable_Donation_Field instance.
		 * @return array
		 */
		private function parse_donation_field( Charitable_Donation_Field $field ) {
			$tag_settings          = $field->email_tag;
			$tag_settings['field'] = $field->field;

			if ( method_exists( $this, 'get_' . $field->field ) ) {
				$tag_settings['callback'] = array( $this, 'get_' . $field->field );
			} else {
				$tag_settings['callback'] = array( $this, 'get_value_from_donation_field' );
			}

			return $tag_settings;
		}
	}

endif;
