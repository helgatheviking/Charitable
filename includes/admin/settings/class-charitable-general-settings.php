<?php
/**
 * Charitable General Settings UI.
 *
 * @package   Charitable/Classes/Charitable_General_Settings
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.38
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_General_Settings' ) ) :

	/**
	 * Charitable_General_Settings
	 *
	 * @final
	 * @since   1.0.0
	 */
	final class Charitable_General_Settings {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_General_Settings|null
		 */
		private static $instance = null;

		/**
		 * Create object instance.
		 *
		 * @since   1.0.0
		 */
		private function __construct() {
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.2.0
		 *
		 * @return  Charitable_General_Settings
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Add the general tab settings fields.
		 *
		 * @since   1.0.0
		 *
		 * @param   array[] $fields
		 * @return  array
		 */
		public function add_general_fields( $fields = array() ) {
			if ( ! charitable_is_settings_view( 'general' ) ) {
				return array();
			}

			$currency_helper = charitable_get_currency_helper();

			$general_fields = array(
				'section'               => array(
					'title'             => '',
					'type'              => 'hidden',
					'priority'          => 10000,
					'value'             => 'general',
				),
				'section_locale'        => array(
					'title'             => __( 'Currency & Location', 'charitable' ),
					'type'              => 'heading',
					'priority'          => 2,
				),
				'country'               => array(
					'title'             => __( 'Base Country', 'charitable' ),
					'type'              => 'select',
					'priority'          => 4,
					'default'           => 'AU',
					'options'           => charitable_get_location_helper()->get_countries(),
				),
				'currency'              => array(
					'title'             => __( 'Currency', 'charitable' ),
					'type'              => 'select',
					'priority'          => 10,
					'default'           => 'AUD',
					'options'           => charitable_get_currency_helper()->get_all_currencies(),
				),
				'currency_format'       => array(
					'title'             => __( 'Currency Format', 'charitable' ),
					'type'              => 'select',
					'priority'          => 12,
					'default'           => 'left',
					'options'           => array(
						'left'              => $currency_helper->get_monetary_amount( '23', false, false, 'left' ),
						'right'             => $currency_helper->get_monetary_amount( '23', false, false, 'right' ),
						'left-with-space'   => $currency_helper->get_monetary_amount( '23', false, false, 'left-with-space' ),
						'right-with-space'  => $currency_helper->get_monetary_amount( '23', false, false, 'right-with-space' ),
					),
				),
				'decimal_separator'     => array(
					'title'             => __( 'Decimal Separator', 'charitable' ),
					'type'              => 'select',
					'priority'          => 14,
					'default'           => '.',
					'options'           => array(
						'.' => 'Period (12.50)',
						',' => 'Comma (12,50)',
					),
				),
				'thousands_separator'   => array(
					'title'             => __( 'Thousands Separator', 'charitable' ),
					'type'              => 'select',
					'priority'          => 16,
					'default'           => ',',
					'options'           => array(
						',' => __( 'Comma (10,000)', 'charitable' ),
						'.' => __( 'Period (10.000)', 'charitable' ),
						' ' => __( 'Space (10 000)', 'charitable' ),
						'none'  => __( 'None', 'charitable' ),
					),
				),
				'decimal_count'         => array(
					'title'             => __( 'Number of Decimals', 'charitable' ),
					'type'              => 'number',
					'priority'          => 18,
					'default'           => 2,
					'class'             => 'short',
				),
				'section_donation_form' => array(
					'title'             => __( 'Donation Form', 'charitable' ),
					'type'              => 'heading',
					'priority'          => 20,
				),
				'donation_form_display' => array(
					'title'             => __( 'Display Options', 'charitable' ),
					'type'              => 'select',
					'priority'          => 22,
					'default'           => 'separate_page',
					'options'           => array(
						'separate_page' => __( 'Show on a Separate Page', 'charitable' ),
						'same_page'     => __( 'Show on the Same Page', 'charitable' ),
						'modal'         => __( 'Reveal in a Modal', 'charitable' ),
					),
					'help'              => __( 'Choose how you want a campaign\'s donation form to show.', 'charitable' ),
				),
				'section_pages'         => array(
					'title'             => __( 'Pages', 'charitable' ),
					'type'              => 'heading',
					'priority'          => 30,
				),
				'login_page'            => array(
					'title'             => __( 'Login Page', 'charitable' ),
					'type'              => 'select',
					'priority'          => 32,
					'default'           => 'wp',
					'options'           => array(
						'wp'            => __( 'Use WordPress Login', 'charitable' ),
						'pages'         => array(
							'options'   => charitable_get_admin_settings()->get_pages(),
							'label'     => __( 'Choose a Static Page', 'charitable' ),
						),
					),
					'help'              => __( 'Allow users to login via the normal WordPress login page or via a static page. The static page should contain the <code>[charitable_login]</code> shortcode.', 'charitable' ),
				),
				'registration_page' => array(
					'title'             => __( 'Registration Page', 'charitable' ),
					'type'              => 'select',
					'priority'          => 34,
					'default'           => 'wp',
					'options'           => array(
						'wp'            => __( 'Use WordPress Registration Page', 'charitable' ),
						'pages'         => array(
							'options'   => charitable_get_admin_settings()->get_pages(),
							'label'     => __( 'Choose a Static Page', 'charitable' ),
						),
					),
					'help'              => __( 'Allow users to register via the default WordPress login or via a static page. The static page should contain the <code>[charitable_registration]</code> shortcode.', 'charitable' ),
				),
				'profile_page'          => array(
					'title'             => __( 'Profile Page', 'charitable' ),
					'type'              => 'select',
					'priority'          => 36,
					'options'           => charitable_get_admin_settings()->get_pages(),
					'help'              => __( 'The static page should contain the <code>[charitable_profile]</code> shortcode.', 'charitable' ),
				),
				'donation_receipt_page' => array(
					'title'             => __( 'Donation Receipt Page', 'charitable' ),
					'type'              => 'select',
					'priority'          => 38,
					'default'           => 'auto',
					'options'           => array(
						'auto'          => __( 'Automatic', 'charitable' ),
						'pages'         => array(
							'options'   => charitable_get_admin_settings()->get_pages(),
							'label'     => __( 'Choose a Static Page', 'charitable' ),
						),
					),
					'help'              => __( 'Choose the page that users will be redirected to after donating. Leave it set to automatic to use the built-in Charitable receipt. If you choose a static page, it should contain the <code>[donation_receipt]</code> shortcode.', 'charitable' ),
				),
			);

			/* If we're using a zero-decimal currency, get rid of the decimal separator and decimal number fields */
			if ( $currency_helper->is_zero_decimal_currency() ) {
				unset(
					$general_fields['decimal_separator'],
					$general_fields['decimal_count']
				);
			}

			$fields = array_merge( $fields, $general_fields );

			return $fields;
		}
	}

endif;
