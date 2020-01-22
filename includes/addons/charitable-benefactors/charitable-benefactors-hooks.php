<?php
/**
 * Charitable Benefactors Hooks.
 *
 * Action/filter hooks used for Charitable Benefactors addon.
 *
 * @package     Charitable/Functions/Benefactors
 * @version     1.2.0
 * @author      Eric Daams
 * @copyright   Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the custom script.
 *
 * @see     Charitable_Benefactors::register_script()
 */
add_action( 'admin_enqueue_scripts', array( Charitable_Benefactors::get_instance(), 'register_script' ) );

/**
 * Register the custom benefactors table.
 *
 * @see     Charitable_Benefactors::register_table()
 */
add_filter( 'charitable_db_tables', array( Charitable_Benefactors::get_instance(), 'register_table' ) );

/**
 * Save benefactors when saving campaign.
 *
 * @see     Charitable_Benefactors::save_benefactors()
 */
add_filter( 'charitable_campaign_save', array( Charitable_Benefactors::get_instance(), 'save_benefactors' ) );

/**
 * AJAX hook to delete a benefactor.
 *
 * @see     Charitable_Benefactors::delete_benefactor()
 */
add_action( 'wp_ajax_charitable_delete_benefactor', array( Charitable_Benefactors::get_instance(), 'delete_benefactor' ) );

/**
 * AJAX hook to add a new benefactor.
 *
 * @see     Charitable_Benefactors::add_benefactor_form()
 */
add_action( 'wp_ajax_charitable_add_benefactor', array( Charitable_Benefactors::get_instance(), 'add_benefactor_form' ) );

/**
 * Add benefactor meta box and form.
 *
 * @see     Charitable_Benefactors::benefactor_meta_box()
 * @see     Charitable_Benefactors::benefactor_form()
 */
add_action( 'charitable_campaign_benefactor_meta_box', array( Charitable_Benefactors::get_instance(), 'benefactor_meta_box' ), 5, 2);
add_action( 'charitable_campaign_benefactor_meta_box', array( Charitable_Benefactors::get_instance(), 'benefactor_form' ), 10, 2 );

/**
 * Hook to execute when uninstalling Charitable.
 *
 * @see     Charitable_Benefactors::uninstall()
 */
add_action( 'charitable_uninstall', array( Charitable_Benefactors::get_instance(), 'uninstall' ) );