<?php
/**
 * Returns an array of all the default donation fields.
 *
 * @package   Charitable/Donation Fields
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.29
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter the set of default donation fields.
 *
 * This filter is provided primarily for internal use by Charitable
 * extensions, as it allows us to add to the registered donation fields
 * as soon as possible.
 *
 * @since 1.5.0
 *
 * @param array $fields The multi-dimensional array of keys in $key => $args format.
 */
return apply_filters(
	'charitable_default_donation_fields',
	array(
		'donation_id'              => array(
			'label'          => __( 'Donation ID', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false, // Charitable_Donation::get_donation_id().
			'donation_form'  => false,
			'admin_form'     => false,
			'show_in_meta'   => false,
			'email_tag'      => array(
				'description' => __( 'The donation ID', 'charitable' ),
				'preview'     => 164,
			),
		),
		'first_name'               => array(
			'label'          => __( 'First Name', 'charitable' ),
			'data_type'      => 'user',
			'value_callback' => 'charitable_get_donor_meta_value',
			'donation_form'  => array(
				'label'    => __( 'First name', 'charitable' ),
				'priority' => 4,
				'required' => true,
			),
			'admin_form'     => array(
				'required' => false,
			),
			'show_in_meta'   => false,
			'show_in_export' => true,
			'email_tag'      => array(
				'tag'         => 'donor_first_name',
				'description' => __( 'The first name of the donor', 'charitable' ),
				'preview'     => 'John',
			),
		),
		'last_name'                => array(
			'label'          => __( 'Last Name', 'charitable' ),
			'data_type'      => 'user',
			'value_callback' => 'charitable_get_donor_meta_value',
			'donation_form'  => array(
				'label'    => __( 'Last name', 'charitable' ),
				'priority' => 6,
				'required' => true,
			),
			'admin_form'     => array(
				'required' => false,
			),
			'show_in_meta'   => false,
			'show_in_export' => true,
			'email_tag'      => array(
				'tag'         => 'donor_last_name',
				'description' => __( 'The last name of the donor', 'charitable' ),
				'preview'     => 'Smith',
			),
		),
		'donor'                    => array(
			'label'          => __( 'Donor', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false,
			'donation_form'  => false,
			'admin_form'     => false,
			'show_in_meta'   => true,
			'show_in_export' => false,
			'email_tag'      => array(
				'description' => __( 'The full name of the donor', 'charitable' ),
				'preview'     => 'John Deere',
			),
		),
		'email'                    => array(
			'label'          => __( 'Email', 'charitable' ),
			'data_type'      => 'user',
			'value_callback' => 'charitable_get_donor_meta_value',
			'donation_form'  => array(
				'type'     => 'email',
				'label'    => __( 'Email', 'charitable' ),
				'priority' => 8,
				'required' => true,
			),
			'admin_form'     => array(
				'required' => false,
			),
			'email_tag'      => array(
				'tag'         => 'donor_email',
				'description' => __( 'The email address of the donor', 'charitable' ),
				'preview'     => 'john@example.com',
			),
		),
		'donor_address'            => array(
			'label'          => __( 'Address', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false, // Will use Charitable_Donation::get_donor_address().
			'donation_form'  => false,
			'admin_form'     => false,
			'email_tag'      => array(
				'description' => __( 'The donor\'s address', 'charitable' ),
				'preview'     => charitable_get_location_helper()->get_formatted_address(
					array(
						'first_name' => 'John',
						'last_name'  => 'Deere',
						'company'    => 'Deere & Company',
						'address'    => 'One John Deere Place',
						'city'       => 'Moline',
						'state'      => 'Illinois',
						'postcode'   => '61265',
						'country'    => 'US',
					)
				),
			),
			'show_in_meta'   => true,
			'show_in_export' => true,
		),
		'address'                  => array(
			'label'          => __( 'Address', 'charitable' ),
			'data_type'      => 'user',
			'value_callback' => 'charitable_get_donor_meta_value',
			'donation_form'  => array(
				'priority' => 10,
				'required' => false,
			),
			'admin_form'     => true,
			'email_tag'      => false,
			'show_in_meta'   => false,
			'show_in_export' => true,
		),
		'address_2'                => array(
			'label'          => __( 'Address 2', 'charitable' ),
			'data_type'      => 'user',
			'value_callback' => 'charitable_get_donor_meta_value',
			'donation_form'  => array(
				'priority' => 12,
				'required' => false,
			),
			'admin_form'     => true,
			'email_tag'      => false,
			'show_in_meta'   => false,
			'show_in_export' => true,
		),
		'city'                     => array(
			'label'          => __( 'City', 'charitable' ),
			'data_type'      => 'user',
			'value_callback' => 'charitable_get_donor_meta_value',
			'donation_form'  => array(
				'priority' => 14,
				'required' => false,
			),
			'admin_form'     => true,
			'email_tag'      => false,
			'show_in_meta'   => false,
			'show_in_export' => true,
		),
		'state'                    => array(
			'label'          => __( 'State', 'charitable' ),
			'data_type'      => 'user',
			'value_callback' => 'charitable_get_donor_meta_value',
			'donation_form'  => array(
				'priority' => 16,
				'required' => false,
			),
			'admin_form'     => true,
			'email_tag'      => false,
			'show_in_meta'   => false,
			'show_in_export' => true,
		),
		'postcode'                 => array(
			'label'          => __( 'Postcode', 'charitable' ),
			'data_type'      => 'user',
			'value_callback' => 'charitable_get_donor_meta_value',
			'donation_form'  => array(
				'priority' => 18,
				'required' => false,
			),
			'admin_form'     => true,
			'email_tag'      => false,
			'show_in_meta'   => false,
			'show_in_export' => true,
		),
		'country'                  => array(
			'label'          => __( 'Country', 'charitable' ),
			'data_type'      => 'user',
			'value_callback' => 'charitable_get_donor_meta_value',
			'donation_form'  => array(
				'priority' => 20,
				'required' => false,
				'type'     => 'select',
				'options'  => charitable_get_location_helper()->get_countries(),
				'default'  => charitable_get_option( 'country' ),
			),
			'admin_form'     => true,
			'email_tag'      => false,
			'show_in_meta'   => false,
			'show_in_export' => true,
		),
		'phone'                    => array(
			'label'          => __( 'Phone Number', 'charitable' ),
			'data_type'      => 'user',
			'value_callback' => 'charitable_get_donor_meta_value',
			'donation_form'  => array(
				'priority' => 22,
				'required' => false,
			),
			'admin_form'     => true,
			'email_tag'      => array(
				'tag'         => 'donor_phone',
				'description' => __( 'The donor\'s phone number', 'charitable' ),
				'preview'     => '1300 000 000',
			),
			'show_in_meta'   => true,
			'show_in_export' => true,
		),
		'campaigns'                => array(
			'label'          => __( 'Campaigns', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false, // Will use Charitable_Donation::get_campaigns().
			'donation_form'  => false,
			'admin_form'     => false,
			'email_tag'      => array(
				'description' => __( 'The campaigns that were donated to', 'charitable' ),
				'preview'     => __( 'Fake Campaign', 'charitable' ),
			),
			'show_in_meta'   => false,
			'show_in_export' => false,
		),
		'campaign_categories_list' => array(
			'label'          => __( 'Campaign Categories', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false, // Will use Charitable_Donation::get_campaign_categories_list().
			'donation_form'  => false,
			'admin_form'     => false,
			'email_tag'      => array(
				'tag'         => 'campaign_categories',
				'description' => __( 'The categories of the campaigns that were donated to', 'charitable' ),
				'preview'     => __( 'Fake Category', 'charitable' ),
			),
			'show_in_meta'   => false,
			'show_in_export' => true,
		),
		'amount_formatted'         => array(
			'label'          => __( 'Donation Amount', 'charitable' ),
			'data_type'      => 'core',
			'donation_form'  => false,
			'admin_form'     => false,
			'email_tag'      => array(
				'tag'         => 'donation_amount',
				'description' => __( 'The total amount donated', 'charitable' ),
				'preview'     => '$50.00',
			),
			'show_in_meta'   => false,
			'show_in_export' => false,
		),
		'date'                     => array(
			'label'          => __( 'Date of Donation', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false, // Will use Charitable_Donation::get_date().
			'donation_form'  => false,
			'admin_form'     => array(
				'type'           => 'datepicker',
				'priority'       => 4,
				'required'       => true,
				'section'        => 'meta',
				'default'        => date_i18n( 'F d, Y' ),
				'value_callback' => 'charitable_get_donation_date_for_form_value',
			),
			'email_tag'      => array(
				'tag'         => 'donation_date',
				'description' => __( 'The date the donation was made', 'charitable' ),
				'preview'     => date_i18n( get_option( 'date_format' ) ),
			),
			'show_in_meta'   => false,
			'show_in_export' => true,
		),
		'time'                     => array(
			'label'          => __( 'Time of Donation', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false, // Will use Charitable_Donation::get_time().
			'donation_form'  => false,
			'admin_form'     => false,
			'email_tag'      => false,
			'show_in_meta'   => false,
			'show_in_export' => true,
		),
		'status'                   => array(
			'label'          => __( 'Donation Status', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false, // Will use Charitable_Donation::get_status().
			'donation_form'  => false,
			'admin_form'     => array(
				'type'     => 'select',
				'priority' => 8,
				'required' => true,
				'section'  => 'meta',
				'options'  => charitable_get_valid_donation_statuses(),
			),
			'email_tag'      => false,
			'show_in_meta'   => false,
			'show_in_export' => false,
		),
		'status_label'             => array(
			'label'          => __( 'Donation Status', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false, // Will use Charitable_Donation::get_status_label().
			'donation_form'  => false,
			'admin_form'     => false,
			'email_tag'      => array(
				'tag'         => 'donation_status',
				'description' => __( 'The status of the donation (pending, paid, etc.)', 'charitable' ),
				'preview'     => __( 'Paid', 'charitable' ),
			),
			'show_in_meta'   => false,
			'show_in_export' => true,
		),
		'donation_gateway'         => array(
			'label'          => __( 'Payment Method', 'charitable' ),
			'data_type'      => 'meta',
			'value_callback' => 'charitable_get_donation_meta_value',
			'donation_form'  => false,
			'admin_form'     => array(
				'type'    => 'hidden',
				'section' => 'meta',
			),
			'email_tag'      => false,
			'show_in_meta'   => false,
			'show_in_export' => false,
		),
		'gateway_label'            => array(
			'label'          => __( 'Payment Method', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false, // Will use Charitable_Donation::get_gateway_label().
			'donation_form'  => false,
			'admin_form'     => false,
			'email_tag'      => false,
			'show_in_meta'   => true,
			'show_in_export' => true,
		),
		'donation_key'             => array(
			'label'          => __( 'Donation Key', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false, // Will use Charitable_Donation::get_donation_key().
			'donation_form'  => false,
			'admin_form'     => false,
			'email_tag'      => false,
			'show_in_meta'   => true,
			'show_in_export' => false,
		),
		'gateway_transaction_id'   => array(
			'label'          => __( 'Gateway Transaction ID', 'charitable' ),
			'data_type'      => 'meta',
			'value_callback' => false,
			'donation_form'  => false,
			'admin_form'     => false,
			'email_tag'      => array(
				'description' => __( 'The payment gateway\'s transaction ID for the donation', 'charitable' ),
				'preview'     => 'XYZ-2132323',
			),
			'show_in_meta'   => true,
			'show_in_export' => true,
		),
		'test_mode'                => array(
			'label'          => __( 'Donation made in test mode?', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false, // Will use Charitable_Donation::get_test_mode_text().
			'donation_form'  => false,
			'admin_form'     => false,
			'email_tag'      => false,
			'show_in_meta'   => true,
			'show_in_export' => true,
		),
		'donation_summary'         => array(
			'label'          => __( 'Summary', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false, // Will use Charitable_Donation::get_donation_summary().
			'donation_form'  => false,
			'admin_form'     => false,
			'email_tag'      => array(
				'description' => __( 'A summary of the donation', 'charitable' ),
				'preview'     => __( 'Fake Campaign: $50.00', 'charitable' ) . PHP_EOL,
			),
			'show_in_meta'   => false,
			'show_in_export' => false,
		),
		'contact_consent'          => array(
			'label'          => __( 'Contact Consent', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false,
			'donation_form'  => false,
			'admin_form'     => false,
			'email_tag'      => array(
				'description' => __( 'Whether the donor gave consent to be contacted', 'charitable' ),
				'preview'     => __( 'Given', 'charitable' ),
			),
			'show_in_meta'   => false,
			'show_in_export' => true,
		),
	)
);
