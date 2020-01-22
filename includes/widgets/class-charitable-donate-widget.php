<?php
/**
 * Class responsible for setting up the donate widget.
 *
 * @package     Charitable/Classes/Charitable_Donate_Widget
 * @version     1.0.0
 * @author      Eric Daams
 * @copyright   Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donate_Widget' ) ) :

	/**
	 * A widget to display the downloads that are connected to the campaign.
	 *
	 * @see     WP_Widget
	 * @since   1.0.0
	 */
	class Charitable_Donate_Widget extends WP_Widget {

		/**
		 * Register the widget.
		 *
		 * @since   1.0.0
		 */
		public function __construct() {

			parent::__construct(
				'charitable_donate_widget',
				__( 'Campaign Donation', 'charitable' ),
				array(
					'description' => __( 'Display a donation widget.', 'charitable' ),
					'customize_selective_refresh' => true,
				)
			);

		}

		/**
		 * Displays the widget on the frontend.
		 *
		 * @since   1.0.0
		 *
		 * @param   array $args 	Widget args.
		 * @param   array $instance Widget instance.
		 * @return  void
		 */
		public function widget( $args, $instance ) {
			if ( ! array_key_exists( 'campaign_id', $instance ) || '' == $instance['campaign_id'] ) {
				return;
			}

			charitable_template( 'widgets/donate.php', array_merge( $args, $instance ) );
		}

		/**
		 * Displays the widget form.
		 *
		 * @since   1.0.0
		 *
		 * @param   array $instance Widget instance.
		 * @return  void
		 */
		public function form( $instance ) {

			$defaults = array(
				'title'       => '',
				'campaign_id' => '',
			);

			$instance    = wp_parse_args( (array) $instance, $defaults );
			$title       = $instance['title'];
			$campaign_id = $instance['campaign_id'];
			$campaigns   = Charitable_Campaigns::query( array( 'posts_per_page' => -1 ) );
			?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'charitable' ) ?>
					<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
				</label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'campaign_id' ); ?>"><?php _e( 'Campaign:', 'charitable' ) ?>
					<select name="<?php echo $this->get_field_name( 'campaign_id' ) ?>">
						<option value="current"><?php _e( 'Campaign currently viewed', 'charitable' ) ?></option>
						<optgroup label="<?php _e( 'Specific campaigns', 'charitable' ) ?>">
							<?php foreach ( $campaigns->posts as $campaign ) : ?>
								<option value="<?php echo $campaign->ID ?>" <?php selected( $campaign->ID, $campaign_id ) ?>><?php echo $campaign->post_title ?></option>
							<?php endforeach ?>
						</optgroup>
					</select>
				</label>
			</p>
			<?php
		}

		/**
		 * Update the widget settings.
		 *
		 * @since   1.0.0
		 *
		 * @param   array $new_instance New widget instance arguments.
		 * @param   array $old_instance Old widget instance arguments.
		 * @return  array
		 */
		public function update( $new_instance, $old_instance ) {
			$instance                = $old_instance;
			$instance['title']       = $new_instance['title'];
			$instance['campaign_id'] = $new_instance['campaign_id'];
			return $instance;
		}
	}

endif;
