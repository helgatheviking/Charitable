<?php
/**
 * Charitable Campaign Hooks.
 *
 * @package     Charitable/Functions/Campaigns
 * @version     1.3.0
 * @author      Eric Daams
 * @copyright   Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize individual campaign meta fields.
 *
 * @see     Charitable_Campaign::sanitize_campaign_goal()
 * @see     Charitable_Campaign::sanitize_campaign_end_date()
 * @see     Charitable_Campaign::sanitize_campaign_suggested_donations()
 * @see     Charitable_Campaign::sanitize_custom_donations()
 * @see     Charitable_Campaign::sanitize_campaign_description()
 */
add_filter( 'charitable_sanitize_campaign_meta_campaign_goal', array( 'Charitable_Campaign', 'sanitize_campaign_goal' ) );
add_filter( 'charitable_sanitize_campaign_meta_campaign_end_date', array( 'Charitable_Campaign', 'sanitize_campaign_end_date' ), 10, 2 );
add_filter( 'charitable_sanitize_campaign_meta_campaign_suggested_donations', array( 'Charitable_Campaign', 'sanitize_campaign_suggested_donations' ) );
add_filter( 'charitable_sanitize_campaign_meta_campaign_allow_custom_donations', array( 'Charitable_Campaign', 'sanitize_custom_donations' ), 10, 2 );
add_filter( 'charitable_sanitize_campaign_meta_campaign_description', array( 'Charitable_Campaign', 'sanitize_campaign_description' ) );
