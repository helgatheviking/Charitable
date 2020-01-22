<?php
/**
 * Renders the Campaign Creator metabox.
 *
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @package   Charitable/Admin Views/Metaboxes
 * @since     1.2.0
 * @version   1.2.0
 */

global $post;

$creator  = new Charitable_User( $post->post_author );
$campaign = new Charitable_Campaign( $post );

?>
<div id="charitable-campaign-creator-metabox-wrap" class="charitable-metabox-wrap">
	<div id="campaign-creator" class="charitable-media-block">
		<div class="creator-avatar charitable-media-image">
			<?php echo $creator->get_avatar(); ?>
		</div><!--.creator-avatar-->
		<div class="creator-facts charitable-media-body">
			<h3 class="creator-name"><a href="<?php echo admin_url( 'user-edit.php?user_id=' . $creator->ID ); ?>"><?php printf( '%s (%s %d)', $creator->display_name, __( 'User ID', 'charitable-ambassadors' ), $creator->ID ); ?></a></h3>
			<p><?php printf( '%s %s', _x( 'Joined on', 'joined on date', 'charitable-ambassadors' ), date_i18n( 'F Y', strtotime( $creator->user_registered ) ) ); ?></p>
			<ul>
				<li><a href="<?php echo get_author_posts_url( $creator->ID ); ?>"><?php _e( 'Public Profile', 'charitable-ambassadors' ); ?></a></li>
				<li><a href="<?php echo admin_url( 'user-edit.php?user_id=' . $creator->ID ); ?>"><?php _e( 'Edit Profile', 'charitable' ); ?></a></li>
			</ul>
		</div><!--.creator-facts-->
	</div><!--#campaign-creator-->
	<div id="charitable-post-author-wrap" class="charitable-metabox charitable-select-wrap">
		<label for="post_author"><?php _e( 'Change the campaign creator' ); ?></label>
		<?php
		wp_dropdown_users( array(
			'name'     => 'post_author',
			'selected' => $post->post_author,
		) );
		?>
	</div>
</div><!--#charitable-campaign-description-metabox-wrap-->
