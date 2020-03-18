<?php
/**
 * Contains the class that provides a utility functions relating to locales.
 *
 * @package   Charitable/Classes/Charitable_Locations
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.35
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Locations' ) ) :

	/**
	 * Charitable_Locations
	 *
	 * @since 1.0.0
	 */
	class Charitable_Locations {

		/**
		 * The single instance of this class.
		 *
		 * @since 1.0.0
		 *
		 * @var   Charitable_Locations|null
		 */
		private static $instance = null;

		/**
		 * List of countries in country_code=>name format.
		 *
		 * @since 1.0.0
		 *
		 * @var   string[]
		 */
		private $countries = array();

		/**
		 * List of states as a multidimensional array, grouped by country.
		 *
		 * @since 1.0.0
		 *
		 * @var   array[]
		 */
		private $states = array();

		/**
		 * List of different countries' address formats.
		 *
		 * @since 1.0.0
		 *
		 * @var   string[]
		 */
		private $address_formats;

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Locations
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Set up the class.
		 *
		 * @since 1.2.0
		 */
		private function __construct() {}

		/**
		 * Return an array with all the countries supported by Charitable.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function get_countries() {
			if ( empty( $this->countries ) ) {
				/**
				 * Filter the list of countries supported by Charitable.
				 *
				 * @since 1.0.0
				 *
				 * @param array $countries The list of countries.
				 */
				$this->countries = apply_filters( 'charitable_countries', include( charitable()->get_path( 'directory' ) . 'i18n/countries.php' ) );
			}

			return $this->countries;
		}

		/**
		 * Return the country codes of countries with states.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function get_countries_with_states() {
			return array(
				'AU',
				'BD',
				'BG',
				'BR',
				'CA',
				'CN',
				'ES',
				'GR',
				'HK',
				'HU',
				'ID',
				'IN',
				'IR',
				'IT',
				'JP',
				'MX',
				'MY',
				'NP',
				'NZ',
				'PE',
				'TH',
				'TR',
				'US',
				'ZA',
			);
		}

		/**
		 * Return an array with all the states supported by Charitable.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function get_all_states() {
			foreach ( $this->get_countries_with_states() as $country_code ) {
				$this->get_states_for_country( $country_code );
			}

			return $this->states;
		}

		/**
		 * Return the states in this country.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $country_code The three-character country code.
		 * @return string[]
		 */
		public function get_states_for_country( $country_code ) {
			if ( ! in_array( $country_code, $this->get_countries_with_states() ) ) {
				return array();
			}

			if ( ! isset( $this->states[ $country_code ] ) ) {
				/**
				 * Filter the list of states by country.
				 *
				 * @since 1.0.0
				 *
				 * @param array  $states       The list of states.
				 * @param string $country_code The country code.
				 */
				$this->states[ $country_code ] = apply_filters(
					'charitable_country_states',
					include( charitable()->get_path( 'directory' ) . '/i18n/states/' . $country_code . '.php' ),
					$country_code
				);
			}

			return $this->states[ $country_code ];
		}

		/**
		 * Get the base country for the website.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_base_country() {
			$country = esc_attr( get_option( 'charitable_default_country' ) );

			/**
			 * Filter the default country.
			 *
			 * @since 1.0.0
			 *
			 * @param string $country The base country.
			 */
			return apply_filters( 'charitable_countries_base_country', $country );
		}

		/**
		 * Get country address formats.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function get_address_formats() {
			if ( ! isset( $this->address_formats ) ) {

				/* Common formats. */
				$postcode_before_city = "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}";

				/**
				 * Filter address formats by country.
				 *
				 * @since 1.0.0
				 *
				 * @param array $formats
				 */
				$this->address_formats = apply_filters(
					'charitable_localisation_address_formats',
					array(
						'default' => "{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}",
						'AU' => "{name}\n{company}\n{address_1}\n{address_2}\n{city} {state} {postcode}\n{country}",
						'AT' => $postcode_before_city,
						'BE' => $postcode_before_city,
						'CA' => "{company}\n{name}\n{address_1}\n{address_2}\n{city} {state} {postcode}\n{country}",
						'CH' => $postcode_before_city,
						'CN' => "{country} {postcode}\n{state}, {city}, {address_2}, {address_1}\n{company}\n{name}",
						'CZ' => $postcode_before_city,
						'DE' => $postcode_before_city,
						'EE' => $postcode_before_city,
						'FI' => $postcode_before_city,
						'DK' => $postcode_before_city,
						'FR' => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city_upper}\n{country}",
						'HK' => "{company}\n{first_name} {last_name_upper}\n{address_1}\n{address_2}\n{city_upper}\n{state_upper}\n{country}",
						'HU' => "{name}\n{company}\n{city}\n{address_1}\n{address_2}\n{postcode}\n{country}",
						'IS' => $postcode_before_city,
						'IT' => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode}\n{city}\n{state_upper}\n{country}",
						'JP' => "{postcode}\n{state}{city}{address_1}\n{address_2}\n{company}\n{last_name} {first_name}\n {country}",
						'TW' => "{postcode}\n{city}{address_2}\n{address_1}\n{company}\n{last_name} {first_name}\n {country}",
						'LI' => $postcode_before_city,
						'NL' => $postcode_before_city,
						'NZ' => "{name}\n{company}\n{address_1}\n{address_2}\n{city} {postcode}\n{country}",
						'NO' => $postcode_before_city,
						'PL' => $postcode_before_city,
						'SK' => $postcode_before_city,
						'SI' => $postcode_before_city,
						'ES' => "{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city}\n{state}\n{country}",
						'SE' => $postcode_before_city,
						'TR' => "{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city} {state}\n{country}",
						'US' => "{name}\n{company}\n{address_1}\n{address_2}\n{city}, {state_code} {postcode}\n{country}",
						'VN' => "{name}\n{company}\n{address_1}\n{city}\n{country}",
					)
				);

			}//end if

			return $this->address_formats;
		}

		/**
		 * Return the address format to use for the given address.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $address_fields Address fields.
		 * @return string
		 */
		private function get_address_format( $address_fields ) {
			$formats = $this->get_address_formats();
			$country = $address_fields['country'];

			/**
			 * If the country is set and it has its own specific format, use that. Otherwise, use default format.
			 */
			$format = isset( $formats[ $country ] ) ? $formats[ $country ] : $formats['default'];

			/**
			 * By default, the country field is left out for local addresses.
			 *
			 * Use the `charitable_formatted_address_force_country_display` filter
			 * to force the country to always be printed.
			 *
			 * @since 1.0.0
			 *
			 * @param boolean $force_country Whether the country is always shown. False by default.
			 */
			if ( $country == $this->get_base_country() && false === apply_filters( 'charitable_formatted_address_force_country_display', false ) ) {
				$format = str_replace( '{country}', '', $format );
			}

			return $format;

		}

		/**
		 * Process an array of address fields, trimming whitespace and adding full country and full state names.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $address_fields Address fields.
		 * @return string[]
		 */
		private function sanitize_address_fields( $address_fields ) {
			if ( ! is_array( $address_fields ) ) {
				return $address_fields;
			}

			$address_fields = array_map( 'trim', array_filter( $address_fields, 'is_string' ) );

			/**
			 * Set up empty default fields and merge with address fields.
			 */
			$defaults = array(
				'first_name'   => '',
				'last_name'    => '',
				'company'      => '',
				'address'      => '',
				'address_2'    => '',
				'city'         => '',
				'state'        => '',
				'full_state'   => '',
				'postcode'     => '',
				'country'      => '',
				'full_country' => '',
			);

			$address_fields = array_merge( $defaults, $address_fields );
			$country        = $address_fields['country'];
			$state          = $address_fields['state'];

			/**
			 * If country is empty, return address fields as they are.
			 */
			if ( empty( $country ) ) {
				return $address_fields;
			}

			$countries = $this->get_countries();

			if ( array_key_exists( $country, $countries ) ) {
				$address_fields['full_country'] = $countries[ $country ];
			}

			/**
			 * Return address fields as they are if state is not set.
			 */
			if ( empty( $state ) ) {
				return $address_fields;
			}

			$states = $this->get_states_for_country( $country );

			if ( array_key_exists( $address_fields['state'], $states ) ) {
				$address_fields['full_state'] = $states[ $address_fields['state'] ];
			}

			return $address_fields;

		}

		/**
		 * Get formatted address based on country of address.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $address_fields Address fields.
		 * @return string
		 */
		public function get_formatted_address( $address_fields = array() ) {
			$address_fields    = $this->sanitize_address_fields( $address_fields );
			$formatted_address = '';

			/* If main address field is empty, then do not format. */
			if ( empty( $address_fields['address'] ) ) {
				return $formatted_address;
			}

			/**
			 * Filter formatted address replacements.
			 *
			 * @since 1.0.0
			 *
			 * @param array $replacements   The replacements as a key => value array.
			 * @param array $address_fields All the address fields.
			 */
			$replacements = apply_filters(
				'charitable_formatted_address_replacements',
				array(
					'{first_name}'       => $address_fields['first_name'],
					'{last_name}'        => $address_fields['last_name'],
					'{name}'             => $address_fields['first_name'] . ' ' . $address_fields['last_name'],
					'{company}'          => $address_fields['company'],
					'{address_1}'        => $address_fields['address'],
					'{address_2}'        => $address_fields['address_2'],
					'{city}'             => $address_fields['city'],
					'{state}'            => $address_fields['full_state'],
					'{postcode}'         => $address_fields['postcode'],
					'{country}'          => $address_fields['full_country'],
					'{first_name_upper}' => strtoupper( $address_fields['first_name'] ),
					'{last_name_upper}'  => strtoupper( $address_fields['last_name'] ),
					'{name_upper}'       => strtoupper( $address_fields['first_name'] . ' ' . $address_fields['last_name'] ),
					'{company_upper}'    => strtoupper( $address_fields['company'] ),
					'{address_1_upper}'  => strtoupper( $address_fields['address'] ),
					'{address_2_upper}'  => strtoupper( $address_fields['address_2'] ),
					'{city_upper}'       => strtoupper( $address_fields['city'] ),
					'{state_upper}'      => strtoupper( $address_fields['full_state'] ),
					'{state_code}'       => strtoupper( $address_fields['state'] ),
					'{postcode_upper}'   => strtoupper( $address_fields['postcode'] ),
					'{country_upper}'    => strtoupper( $address_fields['full_country'] ),
				),
				 $address_fields
			);

			/* Substitute address parts into the string */
			$replace = array_map( 'esc_html', $replacements );
			$format  = $this->get_address_format( $address_fields );
			$formatted_address = str_replace( array_keys( $replace ), $replace, $format );

			/* Clean up white space */
			$formatted_address = preg_replace( '/  +/', ' ', trim( $formatted_address ) );
			$formatted_address = preg_replace( '/\n\n+/', "\n", $formatted_address );

			/* Break newlines apart and remove empty lines/trim commas and white space */
			$formatted_address = explode( "\n", $formatted_address );
			$formatted_address = array_map( array( $this, 'trim_formatted_address_line' ), $formatted_address );
			$formatted_address = array_filter( $formatted_address );

			/* Add html breaks */
			$formatted_address = implode( '<br/>', $formatted_address );

			return $formatted_address;
		}

		/**
		 * Trim white space and commas off a line.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $line The line to be trimmed.
		 * @return string
		 */
		private function trim_formatted_address_line( $line ) {
			return trim( $line, ', ' );
		}
	}

endif;
