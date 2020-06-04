<?php
/**
 * Class that is responsible for creating campaigns.
 *
 * @package   Charitable/Classes/Charitable_Campaign_Processor
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.9
 * @version   1.6.41
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Campaign_Processor' ) ) :

	/**
	 * Charitable_Campaign_Processor
	 *
	 * @since 1.5.9
	 */
	class Charitable_Campaign_Processor {

		/**
		 * Raw input args.
		 *
		 * @since 1.5.9
		 *
		 * @var   array
		 */
		protected $input;

		/**
		 * Campaign values.
		 *
		 * @since 1.5.9
		 *
		 * @var   array
		 */
		protected $args;

		/**
		 * Core post keys.
		 *
		 * @since 1.5.9
		 *
		 * @var   array
		 */
		protected $core_keys;

		/**
		 * Campaign meta keys.
		 *
		 * @since 1.5.9
		 *
		 * @var   array
		 */
		protected $meta_keys;

		/**
		 * Create class object.
		 *
		 * @since 1.5.9
		 *
		 * @param array $args      Values for the campaign.
		 * @param array $meta_keys Meta keys to be saved as campaign meta.
		 */
		public function __construct( array $args = array(), $meta_keys = null ) {
			$this->input     = $args;
			$this->core_keys = array(
				'ID'           => 'ID',
				'post_title'   => 'title',
				'post_content' => 'content',
				'post_author'  => 'creator',
				'post_status'  => 'status',
				'post_parent'  => 'parent',
			);

			$this->meta_keys = is_array( $meta_keys ) ? $meta_keys : array(
				'goal',
				'end_date',
				'suggested_donations',
				'allow_custom_donations',
				'description',
			);

			$this->parse_args();
		}

		/**
		 * Return the query argument value for the given key.
		 *
		 * @since  1.5.9
		 *
		 * @param  string $key      The key of the argument.
		 * @param  mixed  $fallback Default value to fall back to.
		 * @return mixed|false Returns fallback if the argument is not found.
		 */
		public function get( $key, $fallback = false ) {
			return isset( $this->args[ $key ] ) ? $this->args[ $key ] : $fallback;
		}

		/**
		 * Set the query argument for the given key.
		 *
		 * @since  1.5.9
		 *
		 * @param  string $key   Key of argument to set.
		 * @param  mixed  $value Value to be set.
		 * @return void
		 */
		public function set( $key, $value ) {
			$this->input[ $key ] = $value;
			$this->args[ $key ]  = $this->sanitize( $key, $value );
		}

		/**
		 * Sanitize arguments.
		 *
		 * @since  1.5.9
		 *
		 * @param  string $key   Key of argument to set.
		 * @param  mixed  $value Value to be set.
		 * @return mixed
		 */
		public function sanitize( $key, $value ) {
			$key = $this->truncated_key( $key );

			/**
			 * If a class sanitization method exists for this key, call it.
			 */
			if ( $this->use_sanitizer( $key ) ) {
				$value = call_user_func( array( $this, 'sanitize_' . $key ), $value );
			}

			/**
			 * Filter a campaign argument when it is set.
			 *
			 * @since 1.5.9
			 *
			 * @param mixed                                     $value     The value.
			 * @param Charitable_Campaign_Processor $processor This processor object.
			 */
			return apply_filters( 'charitable_campaign_processor_sanitize_' . $key, $value, $this );
		}

		/**
		 * Save the campaign to the database.
		 *
		 * @since  1.5.9
		 *
		 * @return int The campaign ID, or 0 if the campaign was not created.
		 */
		public function save() {
			$this->campaign_id = $this->save_core();

			$this->save_meta();
			$this->save_taxonomies();

			return $this->campaign_id;
		}

		/**
		 * Save the core data.
		 *
		 * @since  1.5.9
		 *
		 * @return int
		 */
		public function save_core() {
			$data              = array_reduce( $this->core_keys, array( $this, 'get_core_data_value' ) );
			$data['post_type'] = Charitable::CAMPAIGN_POST_TYPE;

			/**
			 * Filter the core campaign data.
			 *
			 * @since 1.5.9
			 *
			 * @param array                                     $data      Core data.
			 * @param Charitable_Campaign_Processor $processor This processor object.
			 */
			$data = apply_filters( 'charitable_campaign_processor_core_data', $data, $this );

			/**
			 * Update or insert the campaign.
			 */
			if ( $data['ID'] ) {
				return wp_update_post( $data );
			}

			return wp_insert_post( $data );
		}

		/**
		 * Save the campaign meta data.
		 *
		 * @since  1.5.9
		 *
		 * @return boolean
		 */
		public function save_meta() {
			/**
			 * Filter the campaign meta.
			 *
			 * @since 1.5.9
			 *
			 * @param array                                     $meta      Meta data.
			 * @param Charitable_Campaign_Processor $processor This processor object.
			 */
			$meta = apply_filters( 'charitable_campaign_processor_meta_data', charitable_array_subset( $this->args, $this->meta_keys ), $this );

			return array_walk( $meta, array( $this, 'save_meta_field' ) );
		}

		/**
		 * Returns a truncated key.
		 *
		 * @since  1.5.9
		 *
		 * @param  string $key The key to be truncated.
		 * @return string
		 */
		public function truncated_key( $key ) {
			return strpos( $key, '_campaign_' ) === 0 ? str_replace( '_campaign_', '', $key ) : $key;
		}

		/**
		 * Save the campaign meta data.
		 *
		 * @since  1.5.9
		 *
		 * @return void
		 */
		public function save_taxonomies() {
			/**
			 * Filter the campaign taxonomy data.
			 *
			 * @since 1.5.9
			 *
			 * @param array                                     $data      Taxonomy data.
			 * @param Charitable_Campaign_Processor $processor This processor object.
			 */
			$data = apply_filters(
				'charitable_campaign_processor_taxonomy_data',
				array(
					'campaign_category' => $this->get( 'categories' ),
					'campaign_tag'      => $this->get( 'tags' ),
				),
				$this
			);

			foreach ( $data as $taxonomy => $terms ) {
				if ( false !== $terms ) {
					wp_set_object_terms( $this->campaign_id, $terms, $taxonomy, false );
				}
			}
		}

		/**
		 * Checks whether this is a new campaign being created,
		 * or an update to an existing campaign.
		 *
		 * @since  1.5.9
		 *
		 * @return boolean
		 */
		public function is_new_campaign() {
			return ! array_key_exists( 'ID', $this->input ) || 0 == $this->input['ID'];
		}

		/**
		 * Return the default arguments.
		 *
		 * @since  1.5.9
		 *
		 * @return boolean
		 */
		protected function get_default_args() {
			/**
			 * Set the default campaign arguments.
			 *
			 * @since 1.5.9
			 *
			 * @param array $args The list of default arguments.
			 */
			return apply_filters(
				'charitable_campaign_processor_default_args',
				array(
					'ID'                     => 0,
					'title'                  => '',
					'content'                => '',
					'creator'                => get_current_user_id(),
					'status'                 => 'publish',
					'parent'                 => 0,
					'image'                  => 0,
					'description'            => '',
					'goal'                   => 0,
					'end_date'               => 0,
					'suggested_donations'    => array(),
					'allow_custom_donations' => 1,
					'categories'             => array(),
					'tags'                   => array(),
				)
			);
		}

		/**
		 * Parse submitted values with the default values.
		 *
		 * @since  1.5.9
		 *
		 * @return array
		 */
		protected function parse_args() {
			if ( $this->is_new_campaign() ) {
				$this->args = $this->get_default_args();
			}

			/**
			 * Set each passed argument.
			 */
			array_walk( $this->input, array( $this, 'set_initial_arg' ) );

			/**
			 * Set the campaign ID.
			 */
			$this->campaign_id = $this->args['ID'];

			return $this->args;
		}

		/**
		 * Set an arg that was passed when instantiating the object.
		 *
		 * @since  1.5.9
		 *
		 * @param  mixed  $value The arg value.
		 * @param  string $key   The arg key.
		 * @return void
		 */
		protected function set_initial_arg( $value, $key ) {
			$this->set( $key, $value );
		}

		/**
		 * Sanitize the campaign status.
		 *
		 * @since  1.5.9
		 *
		 * @param  mixed $value The submitted end date.
		 * @return string
		 */
		protected function sanitize_status( $value ) {
			if ( ! in_array( $value, get_post_stati() ) ) {
				$value = 'publish';
			}

			return $value;
		}

		/**
		 * Sanitize the campaign creator.
		 *
		 * This checks to ensure that a user actually exists for the given ID.
		 *
		 * @since  1.5.9
		 *
		 * @param  mixed $value The submitted creator ID.
		 * @return string
		 */
		protected function sanitize_creator( $value ) {
			$user = get_user_by( 'id', $value );

			if ( false === $user ) {
				$value = 0;
			}

			return $value;
		}

		/**
		 * Sanitize the goal.
		 *
		 * @since  1.5.9
		 *
		 * @param  string $value Current value of goal.
		 * @return float
		 */
		protected function sanitize_goal( $value ) {
			if ( $this->should_skip_sanitizer_for_key( 'goal', array( 'Charitable_Campaign', 'sanitize_campaign_goal' ) ) ) {
				return $value;
			}

			return Charitable_Campaign::sanitize_campaign_goal( (string) $value );
		}

		/**
		 * Sanitize the suggested donations.
		 *
		 * @since  1.5.9
		 *
		 * @param  arrat $value Current value of suggested_donations.
		 * @return array
		 */
		protected function sanitize_suggested_donations( $value ) {
			if ( $this->should_skip_sanitizer_for_key( 'suggested_donations', array( 'Charitable_Campaign', 'sanitize_campaign_suggested_donations' ) ) ) {
				return $value;
			}

			return Charitable_Campaign::sanitize_campaign_suggested_donations( $value );
		}

		/**
		 * Sanitize the custom donations checkbox.
		 *
		 * @since  1.5.9
		 *
		 * @param  int $value Current value of custom donations.
		 * @return int
		 */
		protected function sanitize_allow_custom_donations( $value ) {
			if ( $this->should_skip_sanitizer_for_key( 'allow_custom_donations', array( 'Charitable_Campaign', 'sanitize_custom_donations' ) ) ) {
				return $value;
			}

			return Charitable_Campaign::sanitize_custom_donations( $value, $this->input );
		}

		/**
		 * Return the input value of the suggested donations, or the existing suggested
		 * donations for the campaign if this is an update.
		 *
		 * @since  1.5.9
		 *
		 * @return array
		 */
		public function get_suggested_donations_value() {
			if ( array_key_exists( 'suggested_donations', $this->input ) ) {
				return $this->input['suggested_donations'];
			}

			if ( $this->is_new_campaign() ) {
				return array();
			}

			return charitable_get_campaign( $this->args['ID'] )->get_suggested_donations();
		}

		/**
		 * Sanitize the description.
		 *
		 * @since  1.5.9
		 *
		 * @param  arrat $value Current value of description.
		 * @return array
		 */
		protected function sanitize_description( $value ) {
			if ( $this->should_skip_sanitizer_for_key( 'description', array( 'Charitable_Campaign', 'sanitize_campaign_description' ) ) ) {
				return $value;
			}

			return Charitable_Campaign::sanitize_campaign_description( $value );
		}

		/**
		 * Sanitize the end date.
		 *
		 * @since  1.5.9
		 *
		 * @param  mixed $value The submitted end date.
		 * @return string
		 */
		protected function sanitize_end_date( $value ) {
			if ( 0 == $value ) {
				return 0;
			}

			if ( ! $this->is_mysql_date_format( $value ) ) {
				charitable_get_deprecated()->doing_it_wrong(
					__METHOD__,
					sprintf( __( 'Campaign processor requires date in YYYY-MM-DD format. Date passed as %s. This message was added in Charitable version 1.5.9.', 'charitable' ), $value ),
					null
				);

				return 0;
			}

			if ( false === strpos( $value, ' ' ) ) {
				$value .= ' 23:59:59';
			}

			return $value;
		}

		/**
		 * Checks whether the passed date is in MySQL date format.
		 *
		 * @since  1.5.9
		 *
		 * @param  string $date The date.
		 * @return boolean
		 */
		protected function is_mysql_date_format( $date ) {
			return preg_match( '/^\d{4}-\d{2}-\d{2}/', $date );
		}

		/**
		 * After saving the thumbnail ID, maybe attach its attachment post
		 * to the current campaign.
		 *
		 * @since  1.5.9
		 *
		 * @param  int|string $value The thumbnail ID.
		 * @return void
		 */
		protected function post_process_image( $value ) {
			if ( empty( $value ) ) {
				return;
			}

			$parent = get_post_field( 'post_parent', $value );

			/* If the image is unattached to any post, attach it to the campaign. */
			if ( 0 === $parent ) {
				wp_update_post(
					array(
						'ID'          => $value,
						'post_parent' => $this->campaign_id,
					)
				);
			}
		}

		/**
		 * Given a key, checks whether the key is set and if it is,
		 * gets the correct core key (i.e. post_content vs content)
		 * and the value.
		 *
		 * @since  1.5.9
		 *
		 * @return mixed
		 */
		public function get_core_data_value( $ret, $key ) {
			if ( is_null( $ret ) ) {
				$ret = array();
			}

			if ( array_key_exists( $key, $this->args ) ) {
				$ret[ array_search( $key, $this->core_keys ) ] = $this->args[ $key ];
			}

			return $ret;
		}

		/**
		 * Save a meta field.
		 *
		 * @since  1.5.9
		 *
		 * @param  mixed  $value The value of the meta field.
		 * @param  string $key   The key of the meta field.
		 * @return void
		 */
		protected function save_meta_field( $value, $key ) {
			update_post_meta( $this->campaign_id, $this->get_meta_key( $key ), $value );

			if ( method_exists( $this, 'post_process_' . $key ) ) {
				call_user_func( array( $this, 'post_process_' . $key ), $value );
			}
		}

		/**
		 * Returns a sanitized meta	key.
		 *
		 * @since  1.5.9
		 *
		 * @param  string $key The key to be sanitized.
		 * @return string
		 */
		protected function get_meta_key( $key ) {
			if ( 'image' == $key ) {
				return '_thumbnail_id';
			}

			$sanitized = strpos( $key, '_campaign_' ) === 0 ? $key : '_campaign_' . $key;

			/**
			 * Filter the meta key.
			 *
			 * @since 1.0.0
			 *
			 * @param string $key         The meta key.
			 * @param string $original    The original passed key.
			 * @param int    $campaign_id The campaign ID.
			 */
			return apply_filters( 'charitable_campaign_meta_key', $sanitized, $key, $this->campaign_id );
		}

		/**
		 * Checks whether the original callback function which was hooked for a
		 * particular meta field is still hooked.
		 *
		 * We do this for backwards compatibility reasons. If the function is
		 * still hooked, we do nothing. If it is _not_ hooked, that indicates
		 * that the user used remove_filter() to disable the sanitizer, in
		 * which case we should prevent further sanitization. In this case,
		 * we also fire a notice to let users know that this approach is
		 * deprecated, along with a way to fix it.
		 *
		 * @since  1.5.9
		 *
		 * @param  string   $key      The field key.
		 * @param  callback $callback The callback function.
		 * @return boolean
		 */
		protected function should_skip_sanitizer_for_key( $key, $callback ) {
			$tag = 'charitable_sanitize_campaign_meta_campaign_' . $key;

			/* We already used the sanitizer. Remove this in Charitable 1.6/1.7. */
			if ( did_action( $tag ) ) {
				return true;
			}

			/* It is still hooked, we do not want to skip the sanitizer. */
			if ( has_filter( $tag, $callback ) ) {
				return false;
			}

			/**
			 * If we get here, the user has disabled the field's default
			 * sanitizer, so we will skip the sanitizer, while firing a
			 * notice to advise that this method of unhooking the sanitizer
			 * is now deprecated.
			 */
			$method = is_array( $callback ) ? implode( '::', $callback ) : $method;

			charitable_get_deprecated()->doing_it_wrong(
				$tag,
				sprintf( __( '%s is deprecated as a sanitizer on the %s hook. To prevent sanitization, use add_filter( \'charitable_campaign_processor_disable_sanitizer_%s\', \'__return_true\' ) instead.', 'charitable' ),
					$method,
					$tag,
					$key
				),
				'1.5.9'
			);

			return true;
		}

		/**
		 * Checks whether an internal sanitizer exists for a particular
		 * field, and whether it has been disabled.
		 *
		 * @since  1.5.9
		 *
		 * @param  string $key The field key.
		 * @return boolean
		 */
		protected function use_sanitizer( $key ) {
			/* If there is no sanitizer, return false. */
			if ( ! method_exists( $this, 'sanitize_' . $key ) ) {
				return false;
			}

			/**
			 * Disable the internal sanitizer for a particular key.
			 *
			 * @since  1.5.9
			 *
			 * @return boolean
			 */
			return ! apply_filters( 'charitable_campaign_processor_disable_sanitizer_' . $key, false );
		}
	}

endif;
