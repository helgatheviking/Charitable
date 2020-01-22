<?php
/**
 * Charitable template.
 *
 * @package   Charitable/Classes/Charitable_Template
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Template' ) ) :

	/**
	 * Charitable_Template
	 *
	 * @since  1.0.0
	 */
	class Charitable_Template {

		/**
		 * Theme template path.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		protected $theme_template_path;

		/**
		 * Template names to be loaded.
		 *
		 * @since 1.0.0
		 *
		 * @var   string[]
		 */
		protected $template_names;

		/**
		 * Whether to load template file if it is found.
		 *
		 * @since 1.0.0
		 *
		 * @var   boolean
		 */
		protected $load;

		/**
		 * Whether to use require_once or require.
		 *
		 * @since 1.0.0
		 *
		 * @var   boolean
		 */
		protected $require_once;

		/**
		 * The arguments available in the view.
		 *
		 * @since 1.0.0
		 *
		 * @var   array
		 */
		protected $view_args;

		/**
		 * Class constructor.
		 *
		 * @since 1.0.0
		 *
		 * @param string|array $template_name A single template name or an ordered array of template.
		 * @param boolean      $load          If true the template file will be loaded if it is found.
		 * @param boolean      $require_once  Whether to require_once or require. Default true. Has no effect if $load is false.
		 */
		public function __construct( $template_name, $load = true, $require_once = true ) {
			$this->load                = $load;
			$this->theme_template_path = $this->get_theme_template_path();
			$this->base_template_path  = $this->get_base_template_path();

			/**
			 * Filter the template name.
			 *
			 * @since 1.0.0
			 *
			 * @param array $template_names Set of template names to check for.
			 */
			$this->template_names = apply_filters( 'charitable_template_name', (array) $template_name );
			$this->view_args      = array();

			if ( $this->load ) {
				$this->render( $require_once );
			}
		}

		/**
		 * Set theme template path.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_theme_template_path() {
			/**
			 * Customize the directory to use for template files in themes/child themes.
			 *
			 * @since 1.0.0
			 *
			 * @param string $directory The directory, relative to the theme or child theme's root directory.
			 */
			return trailingslashit( apply_filters( 'charitable_theme_template_path', 'charitable' ) );
		}

		/**
		 * Return the base template path.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_base_template_path() {
			return charitable()->get_path( 'templates' );
		}

		/**
		 * Adds an array of view arguments.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $args Arguments to include in the view.
		 * @return void
		 */
		public function set_view_args( $args ) {
			$this->view_args = array_merge( $this->view_args, $args );
		}

		/**
		 * Adds an argument to be accessed within the view.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $key   The key of the argument.
		 * @param  mixed  $value The vaalue of the argument.
		 * @return void
		 */
		public function set_view_arg( $key, $value ) {
			$this->view_args[ $key ] = $value;
		}

		/**
		 * Renders the template.
		 *
		 * @since  1.0.0
		 *
		 * @param  boolean $require_once Whether the template should be loaded with include_once instead of include.
		 * @return false|string False if the template does not exist. Template file otherwise.
		 */
		public function render( $require_once = true ) {
			/* Make sure that the template file exists. */
			$template = $this->locate_template();

			if ( ! $this->template_file_exists( $template ) ) {
				charitable_get_deprecated()->doing_it_wrong(
					__FUNCTION__,
					sprintf( '<code>%s</code> does not exist.', $template ),
					'1.0.0'
				);
				return false;
			}

			if ( $template ) {

				$view_args = $this->view_args;

				if ( $this->require_once ) {
					include_once( $template );
				} else {
					include( $template );
				}
			}

			return $template;
		}

		/**
		 * Locate the template file of the highest priority.
		 *
		 * @uses   locate_template()
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function locate_template() {
			/* Template options are first checked in the theme/child theme using locate_template. */
			$template = locate_template( $this->get_theme_template_options(), false );

			/* No templates found in the theme/child theme, so use the plugin's default template. */
			if ( ! $template ) {
				$template = $this->base_template_path . $this->template_names[0];
			}

			/**
			 * Filter the template path before rendering it.
			 *
			 * @since 1.0.0
			 *
			 * @param string $template_path  The template path to render.
			 * @param array  $template_names Set of template names to check for.
			 */
			return apply_filters( 'charitable_locate_template', $template, $this->template_names );
		}

		/**
		 * Checks whether the template file exists.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $template The template file to check for.
		 * @return boolean
		 */
		public function template_file_exists( $template = '' ) {
			if ( empty( $template ) ) {
				$template = $this->locate_template();
			}

			return file_exists( $template );
		}

		/**
		 * Return the theme template options for a specific template.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		protected function get_theme_template_options() {
			$options = array();

			foreach ( $this->template_names as $template_name ) {
				$options[] = $this->theme_template_path . $template_name;
				$options[] = $template_name;
			}

			return $options;
		}
	}

endif;
