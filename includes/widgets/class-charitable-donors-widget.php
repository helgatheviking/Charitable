<?php
/**
 * Campaign donors widget class.
 *
 * @version     1.0.0
 * @package     Charitable/Widgets/Donors
 * @author      Eric Daams
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donors_Widget' ) ) :

	/**
	 * Charitable_Donors_Widget class.
	 *
	 * @since 1.0.0
	 */
	class Charitable_Donors_Widget extends WP_Widget {

		/**
		 * Instantiate the widget and set up basic configuration.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			parent::__construct(
				'charitable_donors_widget',
				__( 'Donors', 'charitable' ),
				array(
					'description' => __( 'Display a list of donors.', 'charitable' ),
					'customize_selective_refresh' => true,
				)
			);
		}

		/**
		 * Display the widget contents on the front-end.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $args     Display arguments including 'before_title', 'after_title', 'before_widget' and 'after_widget'.
		 * @param  array $instance The settings for the particular instance of the widget.
		 * @return void
		 */
		public function widget( $args, $instance ) {
			$instance              = $this->get_parsed_args( $instance );
			$view_args             = array_merge( $args, $instance );
			$instance['campaign']  = $view_args['campaign_id'];
			$view_args['campaign'] = $view_args['campaign_id'];
			$view_args['orderby']  = $view_args['order'];
			$view_args['order']    = 'DESC';
			$view_args['donors']   = $this->get_widget_donors( $instance );

			charitable_template( 'widgets/donors.php', $view_args );
		}

		/**
		 * Display the widget form in the admin.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $instance The current settings for the widget options.
		 * @return void
		 */
		public function form( $instance ) {
			$args      = $this->get_parsed_args( $instance );
			$campaigns = Charitable_Campaigns::query( array( 'posts_per_page' => -1 ) );
			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title', 'charitable' ); ?>:</label>
				<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" value="<?php echo esc_attr( $args['title'] ); ?>" class="widefat" />
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php _e( 'Number of donors to display', 'charitable' ); ?>:</label>
				<input type="number" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" value="<?php echo intval( $args['number'] ); ?>" min="1" size="3" />
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>"><?php _e( 'Order by', 'charitable' ); ?>:</label>
				<select name="<?php echo esc_attr( $this->get_field_name( 'order' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>">
					<option value="recent" <?php selected( 'recent', $args['order'] ); ?>><?php _e( 'Most recent', 'charitable' ); ?></option>
					<option value="amount" <?php selected( 'amount', $args['order'] ); ?>><?php _e( 'Amount donated', 'charitable' ); ?></option>
				</select>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'campaign_id' ) ); ?>"><?php _e( 'Show donors by campaign', 'charitable' ); ?>:</label>
				<select name="<?php echo esc_attr( $this->get_field_name( 'campaign_id' ) ); ?>">
					<option value="all" <?php selected( 'all', $args['campaign_id'] ); ?>><?php _e( 'Include all campaigns' ); ?></option>
					<option value="current" <?php selected( 'current', $args['campaign_id'] ); ?>><?php _e( 'Campaign currently viewed', 'charitable' ); ?></option>
					<optgroup label="<?php _e( 'Specific campaign', 'charitable' ); ?>">
						<?php foreach ( $campaigns->posts as $campaign ) : ?>
							<option value="<?php echo intval( $campaign->ID ); ?>" <?php selected( $campaign->ID, $args['campaign_id'] ); ?>><?php echo $campaign->post_title; ?></option>
						<?php endforeach ?>
					</optgroup>
				</select>
			</p>
			<p>
				<input id="<?php echo esc_attr( $this->get_field_id( 'show_distinct' ) ); ?>" type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_distinct' ) ); ?>" <?php checked( $args['show_distinct'] ); ?>>
				<label for="<?php echo esc_attr( $this->get_field_id( 'show_distinct' ) ); ?>"><?php _e( 'Group donations by the same person', 'charitable' ); ?></label>
			</p>
			<p>
				<input id="<?php echo esc_attr( $this->get_field_id( 'show_avatar' ) ); ?>" type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_avatar' ) ); ?>" <?php checked( $args['show_avatar'] ); ?>>
				<label for="<?php echo esc_attr( $this->get_field_id( 'show_avatar' ) ); ?>"><?php _e( 'Show donor\'s avatar', 'charitable' ); ?></label>
			</p>
			<p>
				<input id="<?php echo esc_attr( $this->get_field_id( 'show_name' ) ); ?>" type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_name' ) ); ?>" <?php checked( $args['show_name'] ); ?>>
				<label for="<?php echo esc_attr( $this->get_field_id( 'show_name' ) ); ?>"><?php _e( 'Show donor\'s name', 'charitable' ); ?></label>
			</p>
			<p>
				<input id="<?php echo esc_attr( $this->get_field_id( 'show_amount' ) ); ?>" type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_amount' ) ); ?>" <?php checked( $args['show_amount'] ); ?>>
				<label for="<?php echo esc_attr( $this->get_field_id( 'show_amount' ) ); ?>"><?php _e( 'Show donor\'s donation amount', 'charitable' ); ?></label>
			</p>
			<p>
				<input id="<?php echo esc_attr( $this->get_field_id( 'show_location' ) ); ?>" type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_location' ) ); ?>" <?php checked( $args['show_location'] ); ?>>
				<label for="<?php echo esc_attr( $this->get_field_id( 'show_location' ) ); ?>"><?php _e( 'Show donor\'s location', 'charitable' ); ?></label>
			</p>
			<p>
				<input id="<?php echo esc_attr( $this->get_field_id( 'hide_if_no_donors' ) ); ?>" type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'hide_if_no_donors' ) ); ?>" <?php checked( $args['hide_if_no_donors'] ); ?>>
				<label for="<?php echo esc_attr( $this->get_field_id( 'hide_if_no_donors' ) ); ?>"><?php _e( 'Hide if there are no donors', 'charitable' ); ?></label>
			</p>
			<?php
			/**
			 * Add extra settings for the widget.
			 *
			 * @param array                    $args Widget instance arguments.
			 * @param Charitable_Donors_Widget $this This widget instance.
			 */
			do_action( 'charitable_donor_widget_settings_bottom', $args, $this );
		}

		/**
		 * Update the widget settings in the admin.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $new_instance The updated settings.
		 * @param  array $old_instance The old settings.
		 * @return array
		 */
		public function update( $new_instance, $old_instance ) {
			$instance = $new_instance;

			foreach ( array( 'show_distinct', 'show_avatar', 'show_location', 'show_amount', 'show_name', 'hide_if_no_donors' ) as $key ) {
					$instance[ $key ] = array_key_exists( $key, $instance )
						? charitable_sanitize_checkbox( $instance[ $key ] )
						: 0;
			}

			/**
			 * Filter the instance arguments.
			 *
			 * @since 1.0.0
			 *
			 * @param array $instance     The parsed instance settings.
			 * @param array $new_instance The updated settings.
			 * @param array $old_instance The old settings.
			 */
			return apply_filters( 'charitable_donors_widget_update_instance', $instance, $new_instance, $old_instance );
		}

		/**
		 * Return parsed array of arguments.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $instance The settings for the particular instance of the widget.
		 * @return mixed[]
		 */
		protected function get_parsed_args( $instance ) {
			/**
			 * Filter the default widget arguments.
			 *
			 * @since 1.0.0
			 *
			 * @param array $args     The default arguments.
			 * @param array $instance The widget instance settings.
		 	 */
			$defaults = apply_filters(
				'charitable_donors_widget_default_args',
				array(
					'title'             => '',
					'number'            => 10,
					'order'             => 'recent',
					'campaign_id'       => 'all',
					'show_distinct'     => 1,
					'show_avatar'       => 1,
					'show_location'     => 0,
					'show_amount'       => 0,
					'show_name'         => 0,
					'hide_if_no_donors' => 0,
				),
				$instance
			);

			return wp_parse_args( $instance, $defaults );
		}

		/**
		 * Return the donors to display in the widget.
		 *
		 * @since  1.0.0
		 *
		 * @param  mixed[] $instance The widget instance.
		 * @return Charitable_Donor_Query
		 */
		protected function get_widget_donors( $instance ) {
			$query_args = charitable_array_subset( $instance, array( 'number', 'campaign' ) );

			if ( 'amount' == $instance['order'] ) {
				$query_args['orderby'] = 'amount';
			}

			$query_args['distinct_donors'] = $instance['show_distinct'];

			/**
			 * Filter the arguments passed to Charitable_Donor_Query.
			 *
			 * @since 1.0.0
			 *
			 * @param array $query_args The arguments to be passed to Charitable_Donor_Query::__construct.
			 * @param array $args       All the parsed arguments.
			 */
			$query_args = apply_filters( 'charitable_donors_widget_donor_query_args', $query_args, $instance );

			return new Charitable_Donor_Query( $query_args );
		}
	}

endif;
