<?php
/**
 * Display a widget with donors, either for a specific campaign or sitewide.
 *
 * Override this template by copying it to yourtheme/charitable/widgets/donors.php
 *
 * @package Charitable/Templates/Widgets
 * @author  Studio 164a
 * @since   1.0.0
 * @version 1.5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! charitable_is_campaign_page() && 'current' == $view_args['campaign_id'] ) {
	return;
}

$widget_title = apply_filters( 'widget_title', $view_args['title'] );

/* If there are no donors and the widget is configured to hide when empty, return now. */
if ( ! $view_args['donors']->count() && $view_args['hide_if_no_donors'] ) {
	return;
}

echo $view_args['before_widget'];

if ( ! empty( $widget_title ) ) :
	echo $view_args['before_title'] . $widget_title . $view_args['after_title'];
endif;

charitable_template( 'donor-loop.php', $view_args );

echo $view_args['after_widget'];
