<?php
/**
 * Charitable Dashboard Widgets Hooks.
 *
 * Action/filter hooks used for Charitable Dashboard Widgets.
 *
 * @package     Charitable/Functions/Admin
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
 * Register dashboard widgets.
 *
 * @see Charitable_Donations_Dashboard_Widget::register()
 */
add_action( 'wp_dashboard_setup', array( 'Charitable_Donations_Dashboard_Widget', 'register' ) );

/**
 * Get the content for the donations widget.
 *
 * @see Charitable_Donations_Dashboard_Widget::get_content()
 */
add_action( 'wp_ajax_charitable_load_dashboard_donations_widget', array( 'Charitable_Donations_Dashboard_Widget', 'get_content' ) );
