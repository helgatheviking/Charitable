<?php
/**
 * Renders a benefactors addon metabox. Used by any plugin that utilizes the Benefactors Addon.
 *
 * @since       1.0.0
 * @author      Eric Daams
 * @package     Charitable/Admin Views/Metaboxes
 * @copyright   Copyright (c) 2020, Studio 164a
 */

global $post;

if ( ! isset( $view_args['extension'] ) ) {
	charitable_get_deprecated()->doing_it_wrong(
		'charitable_campaign_meta_boxes',
		'Campaign benefactors metabox requires an extension argument.',
		'1.0.0'
	);
	return;
}

$extension   = $view_args['extension'];
$benefactors = charitable_get_table( 'benefactors' )->get_campaign_benefactors_by_extension( $post->ID, $extension );
$ended       = charitable_get_campaign( $post->ID )->has_ended();

?>
<div class="charitable-metabox charitable-metabox-wrap">
	<?php
	if ( empty( $benefactors ) ) :
		if ( $ended ) :
		?>
			<p><?php _e( 'You did not add any contribution rules.', 'charitable' ); ?></p>
		<?php else : ?>
			<p><?php _e( 'You have not added any contribution rules yet.', 'charitable' ); ?></p>
		<?php
		endif;
	else :
		foreach ( $benefactors as $benefactor ) :
			$benefactor_object = Charitable_Benefactor::get_object( $benefactor, $extension );

			if ( $benefactor_object->is_active() ) {
				$active_class = 'charitable-benefactor-active';
			} elseif ( $benefactor_object->is_expired() ) {
				$active_class = 'charitable-benefactor-expired';
			} else {
				$active_class = 'charitable-benefactor-inactive';
			}
			?>
			<div class="charitable-metabox-block charitable-benefactor <?php echo $active_class; ?>">
				<?php do_action( 'charitable_campaign_benefactor_meta_box', $benefactor_object, $extension ); ?>
			</div>
			<?php

		endforeach;
	endif;

	charitable_admin_view( 'metaboxes/campaign-benefactors/form', array(
		'benefactor' => null,
		'extension'  => $extension,
	) );

	if ( ! $ended ) :
	?>
		<p><a href="#" class="button" data-charitable-toggle="campaign_benefactor__0"><?php _e( '+ Add New Contribution Rule', 'charitable' ); ?></a></p>
	<?php
	endif;
	?>
</div>
