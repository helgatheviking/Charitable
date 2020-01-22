<?php
/**
 * Loops over the meta boxes inside the advanced settings area of the Campaign post type.
 *
 * @author 		Studio 164a
 * @package     Charitable/Admin Views/Metaboxes
 * @copyright 	Copyright (c) 2020, Studio 164a
 * @since 		1.0.0
 */

global $post;

if ( ! isset( $view_args['meta_boxes'] ) || empty( $view_args['meta_boxes'] ) ) {
	return;
}
?>
<div id="charitable-campaign-advanced-metabox" class="charitable-metabox">
	<ul class="charitable-tabs">
		<?php foreach ( $view_args['meta_boxes'] as $meta_box ) : ?>
			<li><a href="<?php printf( '#%s', $meta_box['id'] ); ?>"><?php echo $meta_box['title']; ?></a></li>
		<?php endforeach ?>
	</ul>
	<?php foreach ( $view_args['meta_boxes'] as $meta_box ) : ?>
		<div id="<?php echo $meta_box['id']; ?>" class="postbox <?php echo postbox_classes( $meta_box['id'], 'campaign' ); ?>">
			<div class="inside">
				<?php call_user_func( $meta_box['callback'], $post, $meta_box ); ?>
			</div>
		</div>
	<?php
	endforeach;
	?>
</div>