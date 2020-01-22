<?php
/**
 * Charitable Privacy Settings UI.
 *
 * @package   Charitable/Classes/Charitable_Privacy_Settings
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.0
 * @version   1.6.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Privacy_Settings' ) ) :

	/**
	 * Charitable_Privacy_Settings
	 *
	 * @since 1.6.0
	 */
	final class Charitable_Privacy_Settings {

		/**
		 * The single instance of this class.
		 *
		 * @since  1.6.0
		 *
		 * @var    Charitable_Privacy_Settings|null
		 */
		private static $instance = null;

		/**
		 * Create object instance.
		 *
		 * @since   1.6.0
		 */
		private function __construct() {
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.6.0
		 *
		 * @return  Charitable_Privacy_Settings
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Add the privacy tab settings fields.
		 *
		 * @since   1.6.0
		 *
		 * @return  array<string,array>
		 */
		public function add_privacy_fields() {
			if ( ! charitable_is_settings_view( 'privacy' ) ) {
				return array();
			}

			$data_fields = $this->get_user_donation_field_options();

			return array(
				'section'                       => array(
					'title'    => '',
					'type'     => 'hidden',
					'priority' => 10000,
					'value'    => 'privacy',
				),
				'section_privacy_description'   => array(
					'type'     => 'content',
					'priority' => 20,
					'content'  => '<div class="charitable-settings-notice">'
								. '<p>' . __( 'Charitable stores personal data such as donors\' names, email addresses, addresses and phone numbers in your database. Donors may request to have their personal data erased, but you may be legally required to retain some personal data for donations made within a certain time. Below you can control how long personal data is retained for at a minimum, as well as which data fields must be retained.' ) . '</p>'
								. '<p><a href="https://www.wpcharitable.com/documentation/charitable-user-privacy/?utm_source=privacy-page&utm_medium=wordpress-dashboard&utm_campaign=documentation">' . __( 'Read more about Charitable & user privacy', 'charitable' ) . '</a></p>'
								. '</div>',
				),
				'minimum_data_retention_period' => array(
					'label_for' => __( 'Minimum Data Retention Period', 'charitable' ),
					'type'      => 'select',
					'help'      => sprintf(
						/* translators: %1$s: HTML strong tag. %2$s: HTML closing strong tag. %1$s: HTML break tag. */
						__( 'Prevent personal data from being erased for donations made within a certain amount of time.%3$sChoose %1$sNone%2$s to allow the personal data of any donation to be erased.%3$sChoose %1$sForever%2$s to prevent any personal data from being erased from donations, regardless of how long ago they were made.' ),
						'<strong>',
						'</strong>',
						'<br />'
					),
					'priority'  => 25,
					'default'   => 2,
					'options'   => array(
						0         => __( 'None', 'charitable' ),
						1         => __( 'One year', 'charitable' ),
						2         => __( 'Two years', 'charitable' ),
						3         => __( 'Three years', 'charitable' ),
						4         => __( 'Four years', 'charitable' ),
						5         => __( 'Five years', 'charitable' ),
						6         => __( 'Six years', 'charitable' ),
						7         => __( 'Seven years', 'charitable' ),
						8         => __( 'Eight years', 'charitable' ),
						9         => __( 'Nine years', 'charitable' ),
						10        => __( 'Ten years', 'charitable' ),
						'endless' => __( 'Forever', 'charitable' ),
					),
				),
				'data_retention_fields'         => array(
					'label_for' => __( 'Retained Data', 'charitable' ),
					'type'      => 'multi-checkbox',
					'priority'  => 30,
					'default'   => array_keys( $data_fields ),
					'options'   => $data_fields,
					'help'      => __( 'The checked fields will not be erased fields when personal data is erased for a donation made within the Minimum Data Retention Period.', 'charitable' ),
					'attrs'     => array(
						'data-trigger-key'   => '#charitable_settings_minimum_data_retention_period',
						'data-trigger-value' => '!0',
					),
				),
			);
		}

		/**
		 * Return the list of user donation field options.
		 *
		 * @since  1.6.0
		 *
		 * @return string[]
		 */
		protected function get_user_donation_field_options() {
			$fields = charitable()->donation_fields()->get_data_type_fields( 'user' );

			return array_combine(
				array_keys( $fields ),
				wp_list_pluck( $fields, 'label' )
			);
		}
	}

endif;
