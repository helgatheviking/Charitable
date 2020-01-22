<?php
/**
 * Campaigns widget class.
 *
 * @class 		Charitable_Campaigns_Widget
 * @version		1.0.0
 * @package		Charitable/Widgets/Campaigns Widget
 * @category	Class
 * @author 		Eric Daams
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Campaigns_Widget' ) ) :

	/**
	 * Charitable_Campaigns_Widget class.
	 *
	 * @since   1.0.0
	 */
	class Charitable_Campaigns_Widget extends WP_Widget {

		/**
		 * Instantiate the widget and set up basic configuration.
		 *
		 * @since   1.0.0
		 */
		public function __construct() {
			parent::__construct(
				'charitable_campaigns_widget',
				__( 'Campaigns', 'charitable' ),
				array(
					'description' => __( 'Displays your Charitable campaigns.', 'charitable' ),
					'customize_selective_refresh' => true,
				)
			);
		}

		/**
		 * Display the widget contents on the front-end.
		 *
		 * @since   1.0.0
		 *
		 * @param 	array $args
		 * @param 	array $instance
		 */
		public function widget( $args, $instance ) {
			$view_args              = array_merge( $args, $instance );
	        $view_args['campaigns'] = $this->get_widget_campaigns( $instance );

	        charitable_template( 'widgets/campaigns.php', $view_args );
		}

		/**
		 * Display the widget form in the admin.
		 *
		 * @since   1.0.0
		 *
		 * @param 	array $instance         The current settings for the widget options.
		 * @return 	void
		 */
		public function form( $instance ) {
			$defaults = array(
	            'title'          => '',
	            'number'         => 10,
	            'order'          => 'recent',
	            'show_thumbnail' => false,
	        );

	        $args = wp_parse_args( $instance, $defaults );
			?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ) ?>"><?php _e( 'Title:', 'charitable' ) ?></label>
				<input type="text" name="<?php echo $this->get_field_name( 'title' ) ?>" id="<?php echo $this->get_field_id( 'title' ) ?>" value="<?php echo $args['title'] ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'number' ) ?>"><?php _e( 'Number of campaigns to display:', 'charitable' ) ?></label>
				<input type="text" name="<?php echo $this->get_field_name( 'number' ) ?>" id="<?php echo $this->get_field_id( 'number' ) ?>" value="<?php echo $args['number'] ?>" />
			</p>
			<p>
	            <input id="<?php echo esc_attr( $this->get_field_id( 'show_thumbnail' ) ) ?>" type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_thumbnail' ) ); ?>" <?php checked( $args['show_thumbnail'] ) ?>>
	            <label for="<?php echo esc_attr( $this->get_field_id( 'show_thumbnail' ) ) ?>"><?php _e( 'Show thumbnail', 'charitable' ) ?></label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'order' ) ?>"><?php _e( 'Order:', 'charitable' ) ?></label>
				<select name="<?php echo $this->get_field_name( 'order' ) ?>" id="<?php echo $this->get_field_id( 'order' ) ?>">
					<option value="recent" <?php selected( 'recent', $args['order'] ) ?>><?php _e( 'Date published', 'charitable' ) ?></option>
					<option value="ending" <?php selected( 'ending', $args['order'] ) ?>><?php _e( 'Ending soonest', 'charitable' ) ?></option>
				</select>
			</p>
			<?php
		}

		/**
		 * Update the widget settings in the admin.
		 *
		 * @since   1.0.0
		 *
		 * @param 	array   $new_instance   The updated settings.
		 * @param 	array   $new_instance   The old settings.
		 * @return 	void
		 */
		public function update( $new_instance, $old_instance ) {

			$instance                   = array();
			$instance['title']          = isset( $new_instance['title'] ) ? $new_instance['title'] : $old_instance['title'];
			$instance['number']         = isset( $new_instance['number'] ) ? $new_instance['number'] : $old_instance['number'];
			$instance['show_thumbnail'] = isset( $new_instance['show_thumbnail'] ) && $new_instance['show_thumbnail'];
			$instance['order']          = isset( $new_instance['order'] ) ? $new_instance['order'] : $old_instance['order'];
			return $instance;
		}

		/**
		 * Return campaigns to display in the widget.
		 *
		 * @since   1.0.0
		 *
		 * @param 	array 	$instance
		 * @return  WP_Query
		 */
		protected function get_widget_campaigns( $instance ) {

			$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
			$args   = array(
				'posts_per_page' => $number,
			);

			if ( isset( $instance['order'] ) && 'recent' == $instance['order'] ) {
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				return Charitable_Campaigns::query( $args );
			}

			return Charitable_Campaigns::ordered_by_ending_soon( $args );
		}
	}

endif;
