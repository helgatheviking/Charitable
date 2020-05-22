<?php
/**
 * Charitable Currency helper.
 *
 * @package   Charitable/Classes/Charitable_Currency
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.40
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Currency' ) ) :

	/**
	 * Charitable_Currency
	 *
	 * @since 1.0.0
	 */
	final class Charitable_Currency {

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_Currency|null
		 */
		private static $instance = null;

		/**
		 * Every currency available.
		 *
		 * @var string[]
		 */
		private $currencies = array();

		/**
		 * Create class object. A private constructor, so this is used in a singleton context.
		 *
		 * @since 1.2.3
		 */
		private function __construct() {
			add_filter( 'charitable_option_decimal_count', array( $this, 'maybe_force_zero_decimals' ) );
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.3
		 *
		 * @return Charitable_Currency
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Return an amount as a monetary string.
		 *
		 * 50.00 -> $50.00
		 *
		 * @since  1.0.0
		 *
		 * @param  string    $amount          The amount to convert.
		 * @param  int|false $decimal_count   Optional. If not set, default decimal count will be used.
		 * @param  boolean   $db_format       Optional. Whether the amount is in db format (i.e. using decimals
		 *                                    for cents, regardless of site settings).
		 * @param  string    $symbol_position Optional. If specified, will use this symbol position setting instead
		 *                                    of the site's currency format.
		 * @return string|WP_Error
		 */
		public function get_monetary_amount( $amount, $decimal_count = false, $db_format = false, $symbol_position = '' ) {
			if ( false === $decimal_count ) {
				$decimal_count = charitable_get_option( 'decimal_count', 2 );
			}

			$amount = $this->sanitize_monetary_amount( strval( $amount ), $db_format );

			$amount = number_format(
				$amount,
				(int) $decimal_count,
				$this->get_decimal_separator(),
				$this->get_thousands_separator()
			);

			$formatted = sprintf( $this->get_currency_format( $symbol_position ), $this->get_currency_symbol(), $amount );

			/**
			 * Filter the amount.
			 *
			 * @since 1.0.0
			 *
			 * @param string $formatted The formatted amount.
			 * @param string $amount    The original amount before formatting.
			 */
			return apply_filters( 'charitable_monetary_amount', $formatted, $amount );
		}

		/**
		 * Receives unfiltered monetary amount and sanitizes it, returning it as a float.
		 *
		 * $50.00 -> 50.00
		 *
		 * @since  1.0.0
		 *
		 * @param  string  $amount    The amount to sanitize.
		 * @param  boolean $db_format Optional. Whether the amount is in db format (i.e. using decimals for cents, regardless of site settings).
		 * @return float|WP_Error
		 */
		public function sanitize_monetary_amount( $amount, $db_format = false ) {
			/* Sending anything other than a string can cause unexpected returns, so we require strings. */
			if ( ! is_string( $amount ) ) {

				charitable_get_deprecated()->doing_it_wrong(
					__METHOD__,
					__( 'Amount must be passed as a string.', 'charitable' ),
					'1.0.0'
				);

				return new WP_Error( 'invalid_parameter_type', 'Amount must be passed as a string.' );
			}

			/**
			 * If we're using commas for decimals, we need to turn any commas into points, and
			 * we need to replace existing points with blank spaces. Example:
			 *
			 * 12.500,50 -> 12500.50
			 */
			if ( ! $db_format && $this->is_comma_decimal() ) {
				/* Convert to 12.500_50 */
				$amount = str_replace( ',', '_', $amount );
				/* Convert to 12500_50 */
				$amount = str_replace( '.', '', $amount );
				/* Convert to 12500.50 */
				$amount = str_replace( '_', '.', $amount );
			}

			$amount = str_replace( $this->get_currency_symbol(), '', $amount );

			return floatval( filter_var( $amount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ) );
		}

		/**
		 * Returns an amount as a localized monetary string, without a currency symbol.
		 *
		 * @since  1.6.11
		 *
		 * @param  string    $amount        The amount to convert.
		 * @param  int|false $decimal_count Optional. If not set, default decimal count will be used.
		 * @return string
		 */
		public function get_sanitized_and_localized_amount( $amount, $decimal_count = false ) {
			if ( false === $decimal_count ) {
				$decimal_count = charitable_get_option( 'decimal_count', 2 );
			}

			$amount = $this->sanitize_monetary_amount( $amount, false );

			return number_format(
				$amount,
				(int) $decimal_count,
				$this->get_decimal_separator(),
				$this->get_thousands_separator()
			);
		}

		/**
		 * Turns a database amount into an amount formatted for the currency that the site is in.
		 *
		 * @since  1.3.0
		 *
		 * @param  string $amount The amount to be sanitized.
		 * @return string
		 */
		public function sanitize_database_amount( $amount ) {
			if ( $this->is_comma_decimal() ) {
				$amount = str_replace( '.', ',', $amount );
			}

			return $amount;
		}

		/**
		 * Force a string amount into decimal based format, regardless of the site currency.
		 *
		 * This effectively reverses the effect of Charitable_Currency::sanitize_database_amount.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $amount The amount to be cast to decimal format.
		 * @return string
		 */
		public function cast_to_decimal_format( $amount ) {
			if ( $this->is_comma_decimal() ) {
				$amount = str_replace( ',', '.', $amount );
			} else {
				$amount = str_replace( ',', '', $amount );
			}

			return $amount;
		}

		/**
		 * Checks whether the comma is being used as the separator.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function is_comma_decimal() {
			return ( ',' == $this->get_decimal_separator() );
		}

		/**
		 * Return the currency format based on the position of the currency symbol.
		 *
		 * @since  1.0.0
		 * @since  1.6.38 Added optional $symbol_position argument.
		 *
		 * @param  string $symbol_position The symbol position. If not set, the site's
		 *                                 currency_format setting will be used.
		 * @return string
		 */
		public function get_currency_format( $symbol_position = '' ) {
			if ( empty( $symbol_position ) ) {
				$symbol_position = charitable_get_option( 'currency_format', 'left' );
			}

			switch ( $symbol_position ) {
				case 'left':
					$format = '%1$s%2$s';
					break;
				case 'right':
					$format = '%2$s%1$s';
					break;
				case 'left-with-space':
					$format = '%1$s&nbsp;%2$s';
					break;
				case 'right-with-space':
					$format = '%2$s&nbsp;%1$s';
					break;
				default:
					/**
					 * Filter the fallback currency format.
					 *
					 * @since 1.0.0
					 *
					 * @param string $format          The currency format.
					 * @param string $symbol_position Where the symbol is positioned in relation to the amount.
					 */
					$format = apply_filters( 'charitable_currency_format', '%1$s%2$s', $symbol_position );
			}

			return $format;
		}

		/**
		 * Get the currency format for accounting.js
		 *
		 * @since  1.3.0
		 *
		 * @return string
		 */
		public function get_accounting_js_format() {
			switch ( charitable_get_option( 'currency_format', 'left' ) ) {
				case 'right':
					$format = '%v%s';
					break;
				case 'left-with-space':
					$format = '%s %v';
					break;
				case 'right-with-space':
					$format = '%v %s';
					break;
				default:
					$format = '%s%v';
			}

			/**
			 * Filter the currency format to use with accounting.js.
			 *
			 * @since 1.3.0
			 *
			 * @param string $format The currency format.
			 */
			return apply_filters( 'charitable_accounting_js_currency_format', $format );
		}

		/**
		 * Return every currency symbol used with the
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function get_all_currencies() {
			if ( empty( $this->currencies ) ) {
				/**
				 * Filter the currencies available in Charitable.
				 *
				 * @since 1.0.0
				 *
				 * @param array $currencies All currencies as a key=>index array, with
				 *                          the currency code as the key and the currency
				 *                          name including symbol as value.
				 */
				$this->currencies = apply_filters(
					'charitable_currencies',
					array(
						'ARS' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Argentine Peso (%s)', 'charitable' ),
							$this->get_currency_symbol( 'ARS' )
						),
						'AUD' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Australian Dollars (%s)', 'charitable' ),
							$this->get_currency_symbol( 'AUD' )
						),
						'BDT' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Bangladeshi Taka (%s)', 'charitable' ),
							$this->get_currency_symbol( 'BDT' )
						),
						'BOB' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Bolivian Bolíviano (%s)', 'charitable' ),
							$this->get_currency_symbol( 'BOB' )
						),
						'BRL' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Brazilian Real (%s)', 'charitable' ),
							$this->get_currency_symbol( 'BRL' )
						),
						'BND' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Brunei Dollar (%s)', 'charitable' ),
							$this->get_currency_symbol( 'BND' )
						),
						'BGN' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Bulgarian Lev (%s)', 'charitable' ),
							$this->get_currency_symbol( 'BGN' )
						),
						'CAD' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Canadian Dollar (%s)', 'charitable' ),
							$this->get_currency_symbol( 'CAD' )
						),
						'CHF' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Swiss Franc (%s)', 'charitable' ),
							$this->get_currency_symbol( 'CHF' )
						),
						'CLP' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Chilean Peso (%s)', 'charitable' ),
							$this->get_currency_symbol( 'CLP' )
						),
						'CNY' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Chinese Yuan Renminbi (%s)', 'charitable' ),
							$this->get_currency_symbol( 'CNY' )
						),
						'COP' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Colombian Peso (%s', 'charitable' ),
							$this->get_currency_symbol( 'COP' )
						),
						'CZK' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Czech Koruna (%s)', 'charitable' ),
							$this->get_currency_symbol( 'CZK' )
						),
						'DKK' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Danish Krone (%s)', 'charitable' ),
							$this->get_currency_symbol( 'DKK' )
						),
						'EGP' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Egyptian Pound (%s)', 'charitable' ),
							$this->get_currency_symbol( 'EGP' )
						),
						'AED' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Emirati Dirham (%s)', 'charitable' ),
							$this->get_currency_symbol( 'AED' )
						),
						'EUR' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Euro (%s)', 'charitable' ),
							$this->get_currency_symbol( 'EUR' )
						),
						'FJD' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Fijian Dollar (%s)', 'charitable' ),
							$this->get_currency_symbol( 'FJD' )
						),
						'GBP' => sprintf(
							/* translators: %s: currency symbol */
							__( 'British Pound (%s)', 'charitable' ),
							$this->get_currency_symbol( 'GBP' )
						),
						'GHS' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Ghanaian Cedi (%s)', 'charitable' ),
							$this->get_currency_symbol( 'GHS' )
						),
						'HKD' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Hong Kong Dollar (%s)', 'charitable' ),
							$this->get_currency_symbol( 'HKD' )
						),
						'HRK' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Croatian Kuna (%s)', 'charitable' ),
							$this->get_currency_symbol( 'HRK' )
						),
						'HUF' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Hungarian Forint (%s)', 'charitable' ),
							$this->get_currency_symbol( 'HUF' )
						),
						'IDR' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Indonesian Rupiah (%s)', 'charitable' ),
							$this->get_currency_symbol( 'IDR' )
						),
						'ILS' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Israeli Shekel (%s)', 'charitable' ),
							$this->get_currency_symbol( 'ILS' )
						),
						'INR' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Indian Rupee (%s)', 'charitable' ),
							$this->get_currency_symbol( 'INR' )
						),
						'ISK' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Icelandic Krona (%s)', 'charitable' ),
							$this->get_currency_symbol( 'ISK' )
						),
						'JPY' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Japanese Yen (%s)', 'charitable' ),
							$this->get_currency_symbol( 'JPY' )
						),
						'KWD' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Kuwaiti Dinar (%s)', 'charitable' ),
							$this->get_currency_symbol( 'KWD' )
						),
						'MYR' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Malaysian Ringgit (%s)', 'charitable' ),
							$this->get_currency_symbol( 'MYR' )
						),
						'MXN' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Mexican Peso (%s)', 'charitable' ),
							$this->get_currency_symbol( 'MXN' )
						),
						'NZD' => sprintf(
							/* translators: %s: currency symbol */
							__( 'New Zealand Dollar (%s)', 'charitable' ),
							$this->get_currency_symbol( 'NZD' )
						),
						'NGN' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Nigerian Naira (%s)', 'charitable' ),
							$this->get_currency_symbol( 'NGN' )
						),
						'NOK' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Norwegian Krone (%s)', 'charitable' ),
							$this->get_currency_symbol( 'NOK' )
						),
						'PGK' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Papua New Guinean Kina (%s)', 'charitable' ),
							$this->get_currency_symbol( 'PGK' )
						),
						'PHP' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Philippine Peso (%s)', 'charitable' ),
							$this->get_currency_symbol( 'PHP' )
						),
						'PLN' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Polish Zloty (%s)', 'charitable' ),
							$this->get_currency_symbol( 'PLN' )
						),
						'RON' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Romanian New Leu (%s)', 'charitable' ),
							$this->get_currency_symbol( 'RON' )
						),
						'RUB' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Russian Ruble (%s)', 'charitable' ),
							$this->get_currency_symbol( 'RUB' )
						),
						'WST' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Samoan Tālā (%s)', 'charitable' ),
							$this->get_currency_symbol( 'SGD' )
						),
						'SGD' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Singapore Dollar (%s)', 'charitable' ),
							$this->get_currency_symbol( 'SGD' )
						),
						'SBD' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Solomon Islands Dollar (%s)', 'charitable' ),
							$this->get_currency_symbol( 'SBD' )
						),
						'ZAR' => sprintf(
							/* translators: %s: currency symbol */
							__( 'South African Rand (%s)', 'charitable' ),
							$this->get_currency_symbol( 'ZAR' )
						),
						'KRW' => sprintf(
							/* translators: %s: currency symbol */
							__( 'South Korean Won (%s)', 'charitable' ),
							$this->get_currency_symbol( 'KRW' )
						),
						'SEK' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Swedish Krona (%s)', 'charitable' ),
							$this->get_currency_symbol( 'SEK' )
						),
						'TWD' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Taiwan New Dollar (%s)', 'charitable' ),
							$this->get_currency_symbol( 'TWD' )
						),
						'THB' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Thai Baht (%s)', 'charitable' ),
							$this->get_currency_symbol( 'THB' )
						),
						'TOP' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Tongan Pa&lsquo;anga (%s)', 'charitable' ),
							$this->get_currency_symbol( 'TOP' )
						),
						'TRY' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Turkish Lira (%s)', 'charitable' ),
							$this->get_currency_symbol( 'TRY' )
						),
						'USD' => sprintf(
							/* translators: %s: currency symbol */
							__( 'US Dollar (%s)', 'charitable' ),
							$this->get_currency_symbol( 'USD' )
						),
						'VUV' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Vanuatu Vatu (%s)', 'charitable' ),
							$this->get_currency_symbol( 'VUV' )
						),
						'VND' => sprintf(
							/* translators: %s: currency symbol */
							__( 'Vietnamese Dong (%s)', 'charitable' ),
							$this->get_currency_symbol( 'VND' )
						),
					)
				);
			}//end if

			return $this->currencies;
		}

		/**
		 * Return the currency symbol for a given currency.
		 *
		 * This function was changed to a public method in 1.3.7.
		 *
		 * Credit: This is based on the WooCommerce implemenation.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $currency Optional. If not set, currency is based on currently selected currency.
		 * @return string
		 */
		public function get_currency_symbol( $currency = '' ) {
			if ( ! strlen( $currency ) ) {
				$currency = charitable_get_option( 'currency', 'AUD' );
			}

			switch ( $currency ) {
				case 'AUD':
				case 'ARS':
				case 'BND':
				case 'CAD':
				case 'CLP':
				case 'COP':
				case 'FJD':
				case 'MXN':
				case 'NZD':
				case 'HKD':
				case 'SGD':
				case 'USD':
					$currency_symbol = '&#36;';
					break;

				case 'CNY':
				case 'RMB':
				case 'JPY':
					$currency_symbol = '&yen;';
					break;
				case 'AED':
					$currency_symbol = 'د.إ';
					break;
				case 'BDT':
					$currency_symbol = '&#2547;';
					break;
				case 'BGN':
					$currency_symbol = '&#1083;&#1074;.';
					break;
				case 'BOB':
					$currency_symbol = '&#66;&#115;&#46;';
					break;
				case 'BRL':
					$currency_symbol = '&#82;&#36;';
					break;
				case 'CHF':
					$currency_symbol = '&#67;&#72;&#70;';
					break;
				case 'CZK':
					$currency_symbol = '&#75;&#269;';
					break;
				case 'DKK':
					$currency_symbol = 'kr.';
					break;
				case 'EGP':
					$currency_symbol = 'E&pound;';
					break;
				case 'EUR':
					$currency_symbol = '&euro;';
					break;
				case 'GBP':
					$currency_symbol = '&pound;';
					break;
				case 'GHS':
					$currency_symbol = 'GH&#8373;';
					break;
				case 'HRK':
					$currency_symbol = 'Kn';
					break;
				case 'HUF':
					$currency_symbol = '&#70;&#116;';
					break;
				case 'IDR':
					$currency_symbol = 'Rp';
					break;
				case 'ILS':
					$currency_symbol = '&#8362;';
					break;
				case 'INR':
					$currency_symbol = 'Rs.';
					break;
				case 'ISK':
					$currency_symbol = 'Kr.';
					break;
				case 'KWD':
					$currency_symbol = 'KD';
					break;
				case 'KRW':
					$currency_symbol = '&#8361;';
					break;
				case 'MYR':
					$currency_symbol = '&#82;&#77;';
					break;
				case 'NGN':
					$currency_symbol = '&#8358;';
					break;
				case 'NOK':
					$currency_symbol = '&#107;&#114;';
					break;
				case 'PGK':
					$currency_symbol = 'K';
					break;
				case 'PHP':
					$currency_symbol = '&#8369;';
					break;
				case 'PLN':
					$currency_symbol = '&#122;&#322;';
					break;
				case 'RON':
					$currency_symbol = 'lei';
					break;
				case 'RUB':
					$currency_symbol = '&#1088;&#1091;&#1073;.';
					break;
				case 'SBD':
					$currency_symbol = 'SI&#36;';
					break;
				case 'SEK':
					$currency_symbol = '&#107;&#114;';
					break;
				case 'THB':
					$currency_symbol = '&#3647;';
					break;
				case 'TOP':
					$currency_symbol = 'T&#36;';
					break;
				case 'TRY':
					$currency_symbol = '&#8378;';
					break;
				case 'TWD':
					$currency_symbol = '&#78;&#84;&#36;';
					break;
				case 'VND':
					$currency_symbol = '&#8363;';
					break;
				case 'VUV':
					$currency_symbol = 'VT';
					break;
				case 'WST':
					$currency_symbol = 'WS&#36;';
					break;
				case 'ZAR':
					$currency_symbol = '&#82;';
					break;
				default:
					$currency_symbol = '';
					break;
			}//end switch

			return apply_filters( 'charitable_currency_symbol', $currency_symbol, $currency );
		}

		/**
		 * Return the thousands separator.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		public function get_thousands_separator() {
			$separator = charitable_get_option( 'thousands_separator', ',' );

			if ( 'none' == $separator ) {
				$separator = '';
			}

			return $separator;
		}

		/**
		 * Return the decimal separator.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		public function get_decimal_separator() {
			return charitable_get_option( 'decimal_separator', '.' );
		}

		/**
		 * Return the number of decimals to use.
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function get_decimals() {
			$default = $this->is_zero_decimal_currency() ? 0 : 2;
			return charitable_get_option( 'decimal_count', $default );
		}

		/**
		 * Returns a list of currencies that do not use decimals.
		 *
		 * @since  1.6.38
		 *
		 * @return array
		 */
		public function get_zero_decimal_currencies() {
			/**
			 * Filter the list of zero decimal currencies.
			 *
			 * @since 1.6.38
			 *
			 * @param array $currencies All the zero-decimal currencies.
			 */
			return apply_filters(
				'charitable_zero_decimal_currencies',
				array(
					'BIF',
					'CLP',
					'DJF',
					'GNF',
					'JPY',
					'KMF',
					'KRW',
					'MGA',
					'PYG',
					'RWF',
					'UGX',
					'VND',
					'VUV',
					'XAF',
					'XOF',
					'XPF',
				)
			);
		}

		/**
		 * Checks whether a given currency is a zero-decimal currency.
		 *
		 * @since  1.6.38
		 *
		 * @param  string $currency The currency code to check.
		 * @return boolean
		 */
		public function is_zero_decimal_currency( $currency = '' ) {
			if ( empty( $currency ) ) {
				$currency = charitable_get_currency();
			}

			return in_array( $currency, $this->get_zero_decimal_currencies() );
		}

		/**
		 * If we're using a zero-decimal currency, automatically set the
		 * number of decimals to equal 0.
		 *
		 * @since  1.6.38
		 *
		 * @param  int $decimals The decimal count.
		 * @return int
		 */
		public function maybe_force_zero_decimals( $decimals ) {
			if ( $this->is_zero_decimal_currency() ) {
				$decimals = 0;
			}

			return $decimals;
		}
	}

endif;
