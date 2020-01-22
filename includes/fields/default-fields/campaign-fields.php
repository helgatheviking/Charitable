<?php
/**
 * Returns an array of all the default campaign fields.
 *
 * @package   Charitable/Campaign Fields
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.0
 * @version   1.6.19
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter the set of default campaign fields.
 *
 * This filter is provided primarily for internal use by Charitable
 * extensions, as it allows us to add to the registered campaign fields
 * as soon as possible.
 *
 * @since 1.6.0
 *
 * @param array $fields The multi-dimensional array of keys in $key => $args format.
 */
return apply_filters(
	'charitable_default_campaign_fields',
	array(
		'ID'                       => array(
			'label'          => __( 'Campaign ID', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => 'charitable_get_campaign_post_field',
			'admin_form'     => false,
			'email_tag'      => false,
			'show_in_export' => true,
		),
		'description'              => array(
			'label'          => __( 'Description', 'charitable' ),
			'data_type'      => 'meta',
			'admin_form'     => array(
				'section'  => 'campaign-top',
				'type'     => 'textarea',
				'view'     => 'metaboxes/campaign-description',
				'priority' => 4,
			),
			'email_tag'      => false,
			'show_in_export' => true,
		),
		'goal'                     => array(
			'label'          => __( 'Goal', 'charitable' ),
			'data_type'      => 'meta',
			'admin_form'     => array(
				'section'     => 'campaign-top',
				'type'        => 'number',
				'view'        => 'metaboxes/campaign-goal',
				'priority'    => 6,
				'description' => __( 'Leave empty for campaigns without a fundraising goal.', 'charitable' ),
			),
			'email_tag'      => false,
			'show_in_export' => true,
		),
		'monetary_goal'            => array(
			'label'          => __( 'Goal ($)', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false,
			'admin_form'     => false,
			'email_tag'      => array(
				'tag'         => 'campaign_goal',
				'description' => __( 'Display the campaign\'s fundraising goal', 'charitable' ),
				'preview'     => '$15,000',
			),
			'show_in_export' => false,
		),
		'end_date'                 => array(
			'label'          => __( 'End Date', 'charitable' ),
			'data_type'      => 'meta',
			'admin_form'     => array(
				'section'     => 'campaign-top',
				'type'        => 'date',
				'view'        => 'metaboxes/campaign-end-date',
				'priority'    => 8,
				'description' => __( 'Leave empty for ongoing campaigns.', 'charitable' ),
			),
			'email_tag'      => array(
				'tag'         => 'campaign_end_date',
				'description' => __( 'The end date of the campaign', 'charitable' ),
				'preview'     => date( get_option( 'date_format', 'd/m/Y' ) ),
			),
			'show_in_export' => true,
		),
		'suggested_donations'      => array(
			'label'          => __( 'Suggested Donation Amounts', 'charitable' ),
			'data_type'      => 'meta',
			'admin_form'     => array(
				'section'  => 'campaign-donation-options',
				'type'     => 'array',
				'view'     => 'metaboxes/campaign-donation-options/suggested-amounts',
				'priority' => 4,
			),
			'email_tag'      => false,
			'show_in_export' => false,
		),
		'allow_custom_donations'   => array(
			'label'          => __( 'Allow Custom Donations', 'charitable' ),
			'data_type'      => 'meta',
			'admin_form'     => array(
				'section'  => 'campaign-donation-options',
				'type'     => 'checkbox',
				'priority' => 6,
			),
			'email_tag'      => false,
			'show_in_export' => false,
		),
		'post_title'               => array(
			'label'          => __( 'Title', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => 'charitable_get_campaign_post_field',
			'admin_form'     => false,
			'email_tag'      => array(
				'tag'         => 'campaign_title',
				'description' => __( 'The title of the campaign', 'charitable' ),
				'preview'     => __( 'Fake Campaign', 'charitable' ),
			),
			'show_in_export' => true,
		),
		'post_date'                => array(
			'label'          => __( 'Date Created', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => 'charitable_get_campaign_post_field',
			'admin_form'     => false,
			'email_tag'      => false,
			'show_in_export' => true,
		),
		'post_author'              => array(
			'label'          => __( 'Campaign Creator ID', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => 'charitable_get_campaign_post_field',
			'admin_form'     => array(
				'section'  => 'campaign-creator',
				'type'     => 'text',
				'view'     => 'metaboxes/campaign-creator',
				'priority' => 2,
			),
			'email_tag'      => false,
			'show_in_export' => true,
		),
		'post_content'             => array(
			'label'          => __( 'Extended Description', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => 'charitable_get_campaign_post_field',
			'admin_form'     => array(
				'section'  => 'campaign-extended-description',
				'type'     => 'text',
				'view'     => 'metaboxes/campaign-extended-description',
				'priority' => 2,
			),
			'email_tag'      => false,
			'show_in_export' => false,
		),
		'image'                    => array(
			'label'          => __( 'Featured Image', 'charitable' ),
			'data_type'      => 'meta',
			'value_callback' => 'charitable_get_campaign_featured_image',
			'admin_form'     => false,
			'email_tag'      => false,
			'show_in_export' => false,
		),
		'campaign_creator_name'    => array(
			'label'          => __( 'Campaign Creator', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false,
			'admin_form'     => false,
			'email_tag'      => array(
				'tag'         => 'campaign_creator',
				'description' => __( 'The name of the campaign creator', 'charitable' ),
				'preview'     => 'Harry Ferguson',
			),
			'show_in_export' => true,
		),
		'campaign_creator_email'   => array(
			'label'          => __( 'Campaign Creator Email', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false,
			'admin_form'     => false,
			'email_tag'      => array(
				'description' => __( 'The email address of the campaign creator', 'charitable' ),
				'preview'     => 'harry@example.com',
			),
			'show_in_export' => true,
		),
		'goal_achieved_message'    => array(
			'label'          => __( 'Achieved Goal?', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false,
			'admin_form'     => false,
			'email_tag'      => array(
				'tag'         => 'campaign_achieved_goal',
				'description' => __( 'Display whether the campaign reached its goal. Add a `success` parameter as the message when the campaign was successful, and a `failure` parameter as the message when the campaign is not successful', 'charitable' ),
				'preview'     => __( 'The campaign achieved its fundraising goal.', 'charitable' ),
			),
			'show_in_export' => false,
		),
		'donated_amount'           => array(
			'label'          => __( 'Amount Donated', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false,
			'admin_form'     => false,
			'email_tag'      => false,
			'show_in_export' => true,
		),
		'donated_amount_formatted' => array(
			'label'          => __( 'Amount Donated', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false,
			'admin_form'     => false,
			'email_tag'      => array(
				'tag'         => 'campaign_donated_amount',
				'description' => __( 'Display the total amount donated to the campaign', 'charitable' ),
				'preview'     => '$16,523',
			),
			'show_in_export' => false,
		),
		'percent_donated'          => array(
			'label'          => __( 'Percent Donated', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false,
			'admin_form'     => false,
			'email_tag'      => array(
				'tag'         => 'campaign_percent_donated',
				'description' => __( 'Display the percentage donated to the campaign', 'charitable' ),
				'preview'     => '34%',
			),
			'show_in_export' => false,
		),
		'percent_donated_raw'      => array(
			'label'          => __( 'Percent Donated', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false,
			'admin_form'     => false,
			'email_tag'      => false,
			'show_in_export' => true,
		),
		'donor_count'              => array(
			'label'          => __( 'Number of Donors', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false,
			'admin_form'     => false,
			'email_tag'      => array(
				'tag'         => 'campaign_donor_count',
				'description' => __( 'Display the number of campaign donors', 'charitable' ),
				'preview'     => 23,
			),
			'show_in_export' => true,
		),
		'status'                   => array(
			'label'          => __( 'Campaign Status', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false,
			'admin_form'     => false,
			'email_tag'      => false,
			'show_in_export' => true,
		),
		'permalink'                => array(
			'label'          => __( 'Campaign Permalink', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false,
			'admin_form'     => false,
			'email_tag'      => array(
				'tag'         => 'campaign_url',
				'description' => __( 'Display the campaign\'s URL', 'charitable' ),
				'preview'     => 'http://www.example.com/campaigns/fake-campaign',
			),
			'show_in_export' => true,
		),
		'admin_edit_link'          => array(
			'label'          => __( 'Campaign Edit Link', 'charitable' ),
			'data_type'      => 'core',
			'value_callback' => false,
			'admin_form'     => false,
			'email_tag'      => array(
				'tag'         => 'campaign_dashboard_url',
				'description' => __( 'Display a link to the campaign in the dashboard', 'charitable' ),
				'preview'     => get_edit_post_link( 1 ),
			),
			'show_in_export' => false,
		),
		'categories'               => array(
			'label'          => __( 'Categories', 'charitable' ),
			'data_type'      => 'taxonomy',
			'value_callback' => 'charitable_get_campaign_taxonomy_terms_list',
			'admin_form'     => false,
			'email_tag'      => array(
				'description' => __( 'Display a comma-separated list of campaign categories', 'charitable' ),
				'preview'     => 'Category 1, Category 2',
			),
			'show_in_export' => true,
		),
		'tags'                     => array(
			'label'          => __( 'Tags', 'charitable' ),
			'data_type'      => 'taxonomy',
			'value_callback' => 'charitable_get_campaign_taxonomy_terms_list',
			'admin_form'     => false,
			'email_tag'      => array(
				'description' => __( 'Display a comma-separated list of campaign tags', 'charitable' ),
				'preview'     => 'Tag 1, Tag 2, Tag 3',
			),
			'show_in_export' => true,
		),
	)
);
