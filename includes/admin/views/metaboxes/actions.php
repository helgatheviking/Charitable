<?php
/**
 * Renders the donation details meta box for the Donation post type.
 *
 * @author    Eric Daams
 * @package   Charitable/Admin View/Donations Page
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.0
 */

global $post;

$helper  = array_key_exists( 'actions', $view_args ) ? $view_args['actions'] : charitable_get_donation_actions();
$actions = $helper->get_available_actions( $post->ID );
$groups  = $helper->get_available_groups( $post->ID );
$type    = esc_attr( $helper->get_type() );

if ( empty( $actions ) ) {
	return;
}
?>
<div id="charitable-<?php echo $type; ?>-actions-metabox-wrapper" class="charitable-metabox charitable-actions-form-wrapper">

	<div id="charitable-<?php echo $type; ?>-actions-form" class="charitable-actions-form">
		<?php
		/**
		 * Do something at the top of the actions form.
		 *
		 * @since 1.6.0
		 *
		 * @param int $post_id The current post ID.
		 */
		do_action( 'charitable_' . $type . '_actions_start', $post->ID );
		?>
		<select id="charitable_<?php echo $type; ?>_actions" name="charitable_<?php echo $type; ?>_action" class="charitable-action-select">
			<option value=""><?php _e( 'Select an action', 'charitable' ); ?></option>
			<?php
			foreach ( $groups as $label => $group_actions ) :

				if ( ! empty( $label ) && 'default' != $label ) :
				?>
					<optgroup label="<?php echo esc_attr( $label ); ?>">
				<?php
				endif;
				foreach ( $group_actions as $action ) :
					if ( array_key_exists( $action, $actions ) ) :
					?>
						<option value="<?php echo esc_attr( $action ); ?>" data-button-text="<?php echo esc_attr( $actions[ $action ]['button_text'] ); ?>"><?php echo esc_html( $actions[ $action ]['label'] ); ?></option>
					<?php
					endif;
				endforeach;
				if ( ! empty( $label ) ) :
				?>
					</optgroup>
				<?php
				endif;
			endforeach;
		?>
		</select>
		<?php
		foreach ( $groups as $group_actions ) :
			foreach ( $group_actions as $action ) :
				$helper->add_action_fields( $post->ID, $action );
			endforeach;
		endforeach;
		/**
		 * Do something at the end of the actions form.
		 *
		 * @since 1.6.0
		 *
		 * @param int $post_id The current post ID.
		 */
		do_action( 'charitable_' . $type . '_actions_end', $post->ID );
		?>
	</div><!-- #charitable-<?php echo $type; ?>-actions-form -->
	<div id="charitable-<?php echo $type; ?>-actions-submit" class="charitable-actions-submit">
		<button type="submit" class="button-primary" title="<?php esc_attr_e( 'Submit', 'charitable' ); ?>"><?php _e( 'Submit', 'charitable' ); ?></button>
		<div class="clear"></div>
	</div><!-- #charitble-<?php echo $type; ?>-actions-submit -->
</div><!-- #charitable-<?php echo $type; ?>-actions-metabox-wrapper -->
