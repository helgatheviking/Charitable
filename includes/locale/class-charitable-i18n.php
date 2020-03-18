<?php
/**
 * Sets up translations for Charitable.
 *
 * @package   Charitable/Classes/Charitable_i18n
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.1.2
 * @version   1.6.35
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_i18n' ) ) :

	/**
	 * Charitable_i18n
	 *
	 * @since   1.1.2
	 */
	class Charitable_i18n {

		/**
		 * The single instance of this class.
		 *
		 * @since 1.1.2
		 *
		 * @var   Charitable_i18n|null
		 */
		private static $instance = null;

		/**
		 * Plugin's textdomain.
		 *
		 * @since 1.1.2
		 *
		 * @var   string
		 */
		protected $textdomain = 'charitable';

		/**
		 * The path to the languages directory.
		 *
		 * @since 1.1.2
		 *
		 * @var   string
		 */
		protected $languages_directory;

		/**
		 * The site locale.
		 *
		 * @since 1.1.2
		 *
		 * @var   string
		 */
		protected $locale;

		/**
		 * The MO filename.
		 *
		 * @since 1.1.2
		 *
		 * @var   string
		 */
		protected $mofile;

		/**
		 * Whether decline months names is on.
		 *
		 * @since 1.6.35
		 *
		 * @var   boolean
		 */
		private $decline_months;

		/**
		 * Set up the class.
		 *
		 * @since 1.1.2
		 */
		private function __construct() {
			/**
			 * Customize the directory to use for translation files.
			 *
			 * @since 1.0.0
			 *
			 * @param string $directory The directory, relative to the WP_PLUGIN_DIR directory.
			 */
			$this->languages_directory = apply_filters( 'charitable_languages_directory', 'charitable/i18n/languages' );
			$this->locale              = apply_filters( 'plugin_locale', get_locale(), $this->textdomain );
			$this->mofile              = sprintf( '%1$s-%2$s.mo', $this->textdomain, $this->locale );

			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ), 20 );
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.2.0
		 *
		 * @return  Charitable_i18n
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Create class object.
		 *
		 * @since   1.1.2
		 *
		 * @return  void
		 */
		public function load_textdomain() {
			foreach ( array( 'global', 'local' ) as $source ) {
				$mofile_path = $this->get_mofile_path( $source );

				if ( ! file_exists( $mofile_path ) ) {
					continue;
				}

				load_textdomain( $this->textdomain, $mofile_path );
			}

			load_plugin_textdomain( $this->textdomain, false, $this->languages_directory );
		}

		/**
		 * Whether decline months is on or off.
		 *
		 * @since  1.6.35
		 *
		 * @return boolean
		 */
		public function decline_months() {
			if ( ! isset( $this->decline_months ) ) {
				$this->decline_months = 'on' === _x( 'off', 'decline months names: on or off' );
			}

			return $this->decline_months;
		}

		/**
		 * Return the date format to use for datepickers, based
		 * on whether decline months is on.
		 *
		 * @since  1.6.35
		 *
		 * @param  string $default The format to use when decline months is off.
		 * @return string
		 */
		public function get_datepicker_format( $default = 'F d, Y' ) {
			if ( ! $this->decline_months() ) {
				return $default;
			}

			/**
			 * Filter the date format used for datepickers
			 * when decline months is on.
			 *
			 * @since 1.6.35
			 *
			 * @param string $format  The date format to use.
			 * @param string $default The format that would be used if decline months is off.
			 */
			return apply_filters( 'charitable_datepicker_date_format_decline_months', 'Y-m-d', $default );
		}

		/**
		 * Return the Javascript date format to use for datepickers,
		 * based on whether decline months is on.
		 *
		 * @since  1.6.35
		 *
		 * @param  string $default The format to use when decline months is off.
		 * @return string
		 */
		public function get_js_datepicker_format( $default = 'MM d, yy' ) {
			if ( ! $this->decline_months() ) {
				return $default;
			}

			/**
			 * Filter the Javascript date format used for datepickers
			 * when decline months is on.
			 *
			 * @since 1.6.35
			 *
			 * @param string $format  The date format to use.
			 * @param string $default The format that would be used if decline months is off.
			 */
			return apply_filters( 'charitable_datepicker_date_format_decline_months', 'yy-mm-dd', $default );
		}

		/**
		 * Get the path to the MO file.
		 *
		 * @since   1.1.2
		 *
		 * @param   string $source Either 'local' or 'global'.
		 * @return  string
		 */
		private function get_mofile_path( $source = 'local' ) {
			if ( 'global' == $source ) {
				return WP_LANG_DIR . '/' . $this->textdomain . '/' . $this->mofile;
			}

			return trailingslashit( $this->languages_directory ) . $this->mofile;
		}
	}

endif;
