<?php
/**
 * Renders the end date field for the Campaign post type.
 *
 * @author  Studio 164a
 * @package Charitable/Admin Views/Metaboxes
 * @since   1.0.0
 * @version 1.6.35
 */

global $post;

$i18n       = charitable()->registry()->get( 'i18n' );
$php_format = $i18n->get_datepicker_format( 'F d, Y' );
$js_format  = $i18n->get_js_datepicker_format( 'MM d, yy' );

$end_date           = get_post_meta( $post->ID, '_campaign_end_date', true );
$end_time           = strtotime( $end_date );
$end_date_formatted = 0 == $end_date ? '' : date_i18n( $php_format, $end_time );
$title              = array_key_exists( 'title', $view_args ) ? $view_args['title'] : '';
$description        = array_key_exists( 'description', $view_args ) ? $view_args['description'] : '';

?>
<div id="charitable-campaign-end-date-metabox-wrap" class="charitable-metabox-wrap">
	<label for="campaign_end_date"><?php echo $title; ?></label>
	<input type="text" id="campaign_end_date" name="_campaign_end_date" placeholder="&#8734;" class="charitable-datepicker" data-date="<?php echo $end_date_formatted; ?>" data-format="<?php echo $js_format; ?>" />
	<?php if ( $end_date ) : ?>
		<span class="charitable-end-time"><?php echo date_i18n( '@ G:i A', $end_time ); ?></span>
		<input type="hidden" id="campaign_end_time" name="_campaign_end_time" value="<?php echo esc_attr( date_i18n( 'H:i:s', $end_time ) ); ?>" />
	<?php else : ?>
		<span class="charitable-end-time" style="display: none;">=</span>
		<input type="hidden" id="campaign_end_time" name="_campaign_end_time" value="0" />
	<?php endif ?>
	<span class="charitable-helper"><?php echo $description; ?></span>
</div>
