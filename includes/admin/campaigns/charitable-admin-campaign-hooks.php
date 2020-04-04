<?php
/**
 * Charitable Admin Campaign Hooks.
 *
 * Action/filter hooks used for setting up donations in the admin.
 *
 * @package   Charitable/Functions/Admin
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.24
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$registry = charitable()->registry();
$registry->register_object( Charitable_Campaign_Meta_Boxes::get_instance() );

/**
 * Sets the placeholder text of the campaign title field.
 *
 * @see Charitable_Campaign_Meta_Boxes::campaign_enter_title()
 */
add_filter( 'enter_title_here', array( $registry->get( 'campaign_meta_boxes' ), 'campaign_enter_title' ), 10, 2 );

/**
 * Display fields at the very top of the page.
 *
 * @see Charitable_Campaign_Meta_Boxes::campaign_form_top()
 */
add_action( 'edit_form_after_title', array( $registry->get( 'campaign_meta_boxes' ), 'campaign_form_top' ) );

/**
 * Prevent meta box sorting for campaigns.
 */
add_filter( 'get_user_option_meta-box-order_' . Charitable::CAMPAIGN_POST_TYPE, '__return_false' );

/**
 * Campaign Metaboxes.
 *
 * @see Charitable_Campaign_Meta_Boxes::add_meta_boxes()
 * @see Charitable_Campaign_Meta_Boxes::campaign_donation_options_metabox()
 * @see Charitable_Campaign_Meta_Boxes::wrap_editor()
 */
add_action( 'add_meta_boxes_' . Charitable::CAMPAIGN_POST_TYPE, array( $registry->get( 'campaign_meta_boxes' ), 'add_meta_boxes' ) );

/**
 * Save the campaign.
 *
 * @see Charitable_Campaign_Meta_Boxes::save_campaign()
 * @see Charitable_Campaign_Meta_Boxes::set_default_post_content()
 */
add_action( 'save_post_' . Charitable::CAMPAIGN_POST_TYPE, array( $registry->get( 'campaign_meta_boxes' ), 'save_campaign' ), 10, 2 );
add_filter( 'wp_insert_post_data', array( $registry->get( 'campaign_meta_boxes' ), 'set_default_post_content' ) );

/**
 * Set up admin messages & notifications displayed based on actions taken.
 *
 * @see Charitable_Campaign_Meta_Boxes::post_messages()
 * @see Charitable_Campaign_Meta_Boxes::bulk_messages()
 */
add_filter( 'post_updated_messages', array( $registry->get( 'campaign_meta_boxes' ), 'post_messages' ) );

/**
 * Set the table columns for campaigns.
 *
 * @see Charitable_Campaign_List_Table::dashboard_columns()
 */
add_filter( 'manage_edit-campaign_columns', array( Charitable_Campaign_List_Table::get_instance(), 'dashboard_columns' ), 11 );

/**
 * Set the content of each column item.
 *
 * @see Charitable_Campaign_List_Table::dashboard_column_item()
 */
add_filter( 'manage_campaign_posts_custom_column', array( Charitable_Campaign_List_Table::get_instance(), 'dashboard_column_item' ), 11, 2 );

/**
 * Set up admin messages & notifications for bulk actions.
 *
 * @see Charitable_Campaign_List_Table::bulk_messages()
 */
add_filter( 'bulk_post_updated_messages', array( Charitable_Campaign_List_Table::get_instance(), 'bulk_messages' ), 10, 2 );

/**
 * Add date-based filters above the campaigns table.
 *
 * @see Charitable_Campaign_List_Table::add_filters()
 */
add_action( 'restrict_manage_posts', array( Charitable_Campaign_List_Table::get_instance(), 'add_filters' ), 99 );

/**
 * Add the export button above the campaigns table.
 *
 * @see Charitable_Campaign_List_Table::add_export()
 */
add_action( 'manage_posts_extra_tablenav', array( Charitable_Campaign_List_Table::get_instance(), 'add_export' ) );

/**
 * Insert modal forms in the footer.
 *
 * @see Charitable_Campaign_List_Table::modal_forms()
 */
add_action( 'admin_footer', array( Charitable_Campaign_List_Table::get_instance(), 'modal_forms' ) );

/**
 * Load scripts to include on the campaigns table.
 *
 * @see Charitable_Campaign_List_Table::load_scripts()
 */
add_action( 'admin_enqueue_scripts', array( Charitable_Campaign_List_Table::get_instance(), 'load_scripts' ), 11 );

/**
 * Add custom filters to the query that returns the campaigns to be displayed.
 *
 * @see Charitable_Campaign_List_Table::filter_request_query()
 */
add_filter( 'request', array( Charitable_Campaign_List_Table::get_instance(), 'filter_request_query' ) );