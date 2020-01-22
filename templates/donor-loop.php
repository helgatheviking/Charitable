<?php
/**
 * Display a list of donors, either for a specific campaign or sitewide.
 *
 * Override this template by copying it to yourtheme/charitable/donor-loop.php
 *
 * @package Charitable/Templates/Donor
 * @author  Studio 164a
 * @since   1.5.0
 * @version 1.6.31
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Donors have to be included in the view args. */
if ( ! array_key_exists( 'donors', $view_args ) ) {
	return;
}

$donors            = $view_args['donors'];
$args              = $view_args;
$campaign_id       = $view_args['campaign'];
$hide_if_no_donors = array_key_exists( 'hide_if_no_donors', $view_args ) && $view_args['hide_if_no_donors'];

if ( ! charitable_is_campaign_page() && 'current' === $campaign_id ) {
	return;
}

if ( ! $donors->count() && $hide_if_no_donors ) {
	return;
}

if ( 'all' == $campaign_id ) {
	$args['campaign'] = false;
} elseif ( 'current' == $campaign_id ) {
	$args['campaign'] = get_the_ID();
}

$orientation = array_key_exists( 'orientation', $view_args ) ? $view_args['orientation'] : 'vertical';
$style       = '';

if ( 'horizontal' == $orientation ) {
	$width = array_key_exists( 'width', $view_args ) ? $view_args['width'] : get_option( 'thumbnail_size_w', 100 );
	if ( 100 != $width ) {
		$style = '<style>.donors-list.donors-list-horizontal .donor{ width:' . $width . 'px; }</style>';
	}
}

if ( $donors->count() ) :
	echo $style;
	?>
	<ol class="donors-list donors-list-<?php echo $orientation; ?>">
		<?php
		foreach ( $donors as $donor ) :

			$args['donor'] = $donor;

			charitable_template( 'donor-loop/donor.php', $args );

		endforeach;
		?>
	</ol>
<?php else : ?>
	<p><?php _e( 'No donors yet. Be the first!', 'charitable' ); ?></p>
	<?php
endif;
