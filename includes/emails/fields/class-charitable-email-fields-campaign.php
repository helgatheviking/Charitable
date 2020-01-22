<?php
/**
 * Email Fields Campaign class.
 *
 * @package   Charitable/Classes/Charitable_Email_Fields_Campaign
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Email_Fields_Campaign' ) ) :

	/**
	 * Charitable_Email_Fields class.
	 *
	 * @since 1.5.0
	 */
	class Charitable_Email_Fields_Campaign implements Charitable_Email_Fields_Interface {

		/**
		 * The Charitable_Campaign object.
		 *
		 * @since 1.5.0
		 *
		 * @var   Charitable_Campaign
		 */
		private $campaign;

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
			$this->campaign = $email->get_campaign();
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
			$fields = charitable()->campaign_fields()->get_email_tag_fields();
			$fields = array_map( array( $this, 'parse_campaign_field' ), $fields );
			$fields = array_combine( wp_list_pluck( $fields, 'tag' ), $fields );

			/**
			 * Filter the campaign email fields.
			 *
			 * @since 1.5.0
			 *
			 * @param array               $fields   The default set of fields.
			 * @param Charitable_Campaign $campaign Instance of `Charitable_Campaign`.
			 * @param Charitable_Email    $email    Instance of `Charitable_Email`.
			 */
			return apply_filters( 'charitable_email_campaign_fields', $fields, $this->campaign, $this->email );
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
		 * Checks whether the email has a valid Campaign object set.
		 *
		 * @since  1.5.0
		 *
		 * @return boolean
		 */
		public function has_valid_campaign() {
			return ! is_null( $this->campaign ) && is_a( $this->campaign, 'Charitable_Campaign' );
		}

		/**
		 * Display whether the campaign achieved its goal.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $value The content to display in place of the shortcode.
		 * @param  array  $args  Optional set of arguments.
		 * @return string
		 */
		public function get_goal_achieved_message( $value, $args ) {
			$args = wp_parse_args( $args, array(
				'success' => '',
				'failure' => '',
			) );

			return $this->campaign->get_goal_achieved_message( $args['success'], $args['failure'] );
		}

		/**
		 * Return the value for a particular field that is registered as a Charitable_Campaign_Field.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $value The content to display in place of the shortcode.
		 * @param  array  $args  Optional set of arguments.
		 * @return string
		 */
		public function get_value_from_campaign_field( $value, $args ) {
			if ( ! array_key_exists( $args['show'], $this->fields ) || ! array_key_exists( 'field', $this->fields[ $args['show'] ] ) ) {
				return '';
			}

			return $this->campaign->get( $this->fields[ $args['show'] ]['field'] );
		}

		/**
		 * Parse a campaign field, returning just the email tag parameters.
		 *
		 * @since  1.6.0
		 *
		 * @param  Charitable_Campaign_Field $field A Charitable_Campaign_Field instance.
		 * @return array
		 */
		private function parse_campaign_field( Charitable_Campaign_Field $field ) {
			$tag_settings          = $field->email_tag;
			$tag_settings['field'] = $field->field;

			if ( method_exists( $this, 'get_' . $field->field ) ) {
				$tag_settings['callback'] = array( $this, 'get_' . $field->field );
			} else {
				$tag_settings['callback'] = array( $this, 'get_value_from_campaign_field' );
			}

			return $tag_settings;
		}
	}

endif;
