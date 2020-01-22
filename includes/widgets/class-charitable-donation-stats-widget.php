<?php
/**
 * Donation stats widget class.
 *
 * @package     Charitable/Classes/Charitable_Donation_Stats_Widget
 * @version     1.0.0
 * @author      Eric Daams
 * @copyright   Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donation_Stats_Widget' ) ) :

	/**
	 * Charitable_Donation_Stats_Widget class.
	 *
	 * @since   1.0.0
	 */
	class Charitable_Donation_Stats_Widget extends WP_Widget {

		/**
		 * Instantiate the widget and set up basic configuration.
		 *
		 * @since   1.0.0
		 */
		public function __construct() {
			parent::__construct(
				'charitable_donation_stats_widget',
				__( 'Donation Stats', 'charitable' ),
				array(
					'description' => __( 'Show off your donation statistics.', 'charitable' ),
					'customize_selective_refresh' => true,
				)
			);
		}

		/**
		 * Display the widget contents on the front-end.
		 *
		 * @since   1.0.0
		 *
		 * @param 	array $args     Display arguments including 'before_title', 'after_title',
		 *                          'before_widget', and 'after_widget'.
		 * @param 	array $instance The settings for the particular instance of the widget.
		 * @return 	void
		 */
		public function widget( $args, $instance ) {
			$view_args = array_merge( $args, $instance );

			if ( ! array_key_exists( 'title', $view_args ) ) {
				$view_args['title'] = __( 'Donation Statistics', 'charitable' );
			}

			charitable_template( 'widgets/donation-stats.php', $view_args );
		}

		/**
		 * Display the widget form in the admin.
		 *
		 * @since   1.0.0
		 *
		 * @param   array $instance The current settings for the widget options.
		 * @return  void
		 */
		public function form( $instance ) {
			$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
			?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'charitable' ); ?></label>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ) ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			</p>
			<?php
			/**
			 * Add extra settings for the widget.
			 *
			 * @since 	1.5.0
			 *
			 * @param 	array                            $args Widget instance arguments.
			 * @param 	Charitable_Donation_Stats_Widget $this This widget instance.
			 */
			do_action( 'charitable_donation_stats_widget_settings_bottom', $instance, $this );
		}

		/**
		 * Update the widget settings in the admin.
		 *
		 * @since   1.0.0
		 *
		 * @param   array $new_instance The updated settings.
		 * @param   array $old_instance The old settings.
		 * @return  array
		 */
		public function update( $new_instance, $old_instance ) {
			$instance = array(
				'title' => strip_tags( $new_instance['title'] ),
			);

			/**
			 * Filter the instance arguments.
			 *
			 * @since   1.5.0
			 *
			 * @param   array $instance     The parsed instance settings.
			 * @param   array $new_instance The updated settings.
		 	 * @param   array $old_instance The old settings.
		 	 * @return  array
		 	 */
			return apply_filters( 'charitable_donation_stats_widget_update_instance', $instance, $new_instance, $old_instance );
		}
	}

endif;
