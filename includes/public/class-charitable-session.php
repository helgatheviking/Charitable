<?php
/**
 * Responsible for managing user sessions.
 *
 * @package   Charitable/Classes/Charitable Session
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.42
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Session' ) ) :

	/**
	 * Charitable_Session
	 *
	 * @since 1.0.0
	 */
	class Charitable_Session {

		/**
		 * Holds our session data
		 *
		 * @since 1.0.0
		 *
		 * @var   WP_Session
		 */
		private $session;

		/**
		 * Whether to disable the cookie.
		 *
		 * @since 1.6.27
		 *
		 * @var   boolean
		 */
		private $disable_cookie;

		/**
		 * Instantiate session object.
		 *
		 * @since 1.0.0
		 * @since 1.5.4 Access changed to public.
		 */
		public function __construct() {
			/**
			 * Filter to disable the cookie.
			 *
			 * @since 1.6.27
			 *
			 * @param boolean $disable Whether to disable the cookie.
			 */
			$this->disable_cookie = apply_filters( 'charitable_disable_cookie', false );

			if ( ! $this->should_start_session() ) {
				return;
			}

			if ( ! defined( 'WP_SESSION_COOKIE' ) ) {
				define( 'WP_SESSION_COOKIE', 'charitable_session' );
			}

			if ( ! class_exists( 'Recursive_ArrayAccess' ) ) {
				require_once( charitable()->get_path( 'includes' ) . 'libraries/wp-session/class-recursive-arrayaccess.php' );
			}

			if ( ! class_exists( 'WP_Session_Utils' ) ) {
				require_once( charitable()->get_path( 'includes' ) . 'libraries/wp-session/class-wp-session-utils.php' );
			}

			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				require_once( charitable()->get_path( 'includes' ) . 'libraries/wp-session/wp-cli.php' );
			}

			if ( ! class_exists( 'WP_Session' ) ) {
				require_once( charitable()->get_path( 'includes' ) . 'libraries/wp-session/class-wp-session.php' );
				require_once( charitable()->get_path( 'includes' ) . 'libraries/wp-session/wp-session.php' );
			}

			/* Set the expiration length & variant of the session */
			add_filter( 'wp_session_expiration', array( $this, 'set_session_length' ), 99999 );
			add_filter( 'wp_session_expiration_variant', array( $this, 'set_session_expiration_variant_length' ), 99999 );

			$this->init();
		}

		/**
		 * Create the session.
		 *
		 * @since  1.4.17
		 *
		 * @return WP_Session
		 */
		public function init() {
			$this->session = WP_Session::get_instance();

			/* We're missing a Session ID, so we'll need to queue up our session scripts. */
			if ( ! $this->has_session_id() || $this->disable_cookie ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 1 );
			}

			return $this->session;
		}

		/**
		 * Set up scripts to create & manage cookies client-side.
		 *
		 * @since  1.5.0
		 *
		 * @return void
		 */
		public function enqueue_scripts() {
			if ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
				$suffix  = '';
				$version = '';
			} else {
				$suffix  = '.min';
				$version = charitable()->get_version();
			}

			$assets_dir = charitable()->get_path( 'assets', false );

			wp_register_script(
				'js-cookie',
				$assets_dir . 'js/libraries/js-cookie' . $suffix . '.js',
				array(),
				'2.1.4',
				false
			);

			wp_enqueue_script(
				'charitable-sessions',
				$assets_dir . 'js/charitable-session' . $suffix . '.js',
				array( 'js-cookie' ),
				$version,
				false
			);

			wp_localize_script(
				'charitable-sessions',
				'CHARITABLE_SESSION',
				array(
					'ajaxurl'            => admin_url( 'admin-ajax.php' ),
					'id'                 => $this->get_session_id(),
					'cookie_name'        => WP_SESSION_COOKIE,
					'expiration'         => $this->set_session_length(),
					'expiration_variant' => $this->set_session_expiration_variant_length(),
					'secure'             => (bool) apply_filters( 'wp_session_cookie_secure', false ),
					'cookie_path'        => COOKIEPATH,
					'cookie_domain'      => COOKIE_DOMAIN,
					'generated_id'       => WP_Session_Utils::generate_id(),
					'disable_cookie'     => $this->disable_cookie,
				)
			);
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Session
		 */
		public static function get_instance() {
			return charitable()->registry()->get( 'session' );
		}

		/**
		 * Checks whether the current request has a session ID.
		 *
		 * @since  1.5.0
		 *
		 * @return boolean
		 */
		public function has_session_id() {
			return ! empty( $this->session->session_id );
		}

		/**
		 * Return a session variable.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $key Session variable key.
		 * @return mixed Session variable
		 */
		public function get( $key ) {
			$key = sanitize_key( $key );
			return isset( $this->session[ $key ] ) ? maybe_unserialize( $this->session[ $key ] ) : false;
		}

		/**
		 * Set a session variable.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $key   Session variable key.
		 * @param  mixed  $value The value of the session variable.
		 * @return mixed The session variable value.
		 */
		public function set( $key, $value ) {
			$key = sanitize_key( $key );

			if ( is_array( $value ) ) {
				$this->session[ $key ] = serialize( $value );
			} else {
				$this->session[ $key ] = $value;
			}

			return $this->session[ $key ];
		}

		/**
		 * Remove a session variable.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $key Session variable key.
		 * @return mixed The session variable value.
		 */
		public function remove( $key ) {
			unset( $this->session[ $key ] );
		}

		/**
		 * Set the length of the cookie session to 24 hours.
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function set_session_length() {
			if ( ! defined( 'DAY_IN_SECONDS' ) ) {
				define( 'DAY_IN_SECONDS', 86400 );
			}

			/**
			 * Filter the length of the session.
			 *
			 * @since 1.0.0
			 *
			 * @param int $seconds Defaults to 86400 — one day.
			 */
			return apply_filters( 'charitable_session_length', DAY_IN_SECONDS );
		}

		/**
		 * Set the cookie expiration variant time to 23 hours.
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function set_session_expiration_variant_length() {
			if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
				define( 'HOUR_IN_SECONDS', 3600 );
			}

			/**
			 * Filter the variation length of the session.
			 *
			 * @since 1.0.0
			 *
			 * @param int $seconds Defaults to 82800 — 23 hours.
			 */
			return apply_filters( 'charitable_session_expiration_variant_length', HOUR_IN_SECONDS * 23 );
		}

		/**
		 * Add a donation to a campaign to the session.
		 *
		 * @since  1.0.0
		 * @since  1.6.25 Added $period parameter.
		 *
		 * @param  int    $campaign_id Campaign ID.
		 * @param  int    $amount      Donation amount.
		 * @param  string $period      Set the donation period. Defaults to once.
		 * @return void
		 */
		public function add_donation( $campaign_id, $amount, $period = 'once' ) {
			$donations = $this->get( 'donations' );

			$campaign_donation                    = isset( $donations[ $campaign_id ] ) ? $donations[ $campaign_id ] : array();
			$campaign_donation['amount']          = floatval( $amount );
			$campaign_donation['donation_period'] = $period;

			$donations[ $campaign_id ] = $campaign_donation;

			$this->set( 'donations', $donations );
		}

		/**
		 * Remove a donation from the session.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $campaign_id Campaign ID.
		 * @return void
		 */
		public function remove_donation( $campaign_id ) {
			$donations = $this->get( 'donations' );

			if ( isset( $donations[ $campaign_id ] ) ) {
				unset( $donations[ $campaign_id ] );
			}

			$this->set( 'donations', $donations );
		}

		/**
		 * Return the donation in session for a campaign.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $campaign_id The campaign ID.
		 * @return false|array
		 */
		public function get_donation_by_campaign( $campaign_id ) {
			$donations = $this->get( 'donations' );
			return isset( $donations[ $campaign_id ] ) ? $donations[ $campaign_id ] : false;
		}

		/**
		 * Store the donation key in the session, to ensure the user can access their receipt.
		 *
		 * @since  1.1.2
		 *
		 * @param  string $donation_key The transaction key for the donation.
		 * @return void
		 */
		public function add_donation_key( $donation_key ) {
			$keys = $this->get( 'donation-keys' );

			if ( ! $keys ) {
				$keys = array();
			}

			$keys[] = $donation_key;

			$this->set( 'donation-keys', $keys );
		}

		/**
		 * Checks whether the donation key is stored in the session.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $donation_key The transaction key for the donation.
		 * @return boolean
		 */
		public function has_donation_key( $donation_key ) {
			$keys = $this->get( 'donation-keys' );

			if ( ! $keys ) {
				return false;
			}

			return in_array( $donation_key, $keys );
		}

		/**
		 * Add the all notices to the session.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function add_notices() {
			$this->set( 'notices', charitable_get_notices()->get_notices() );
		}

		/**
		 * Return any notices set in the session.
		 *
		 * @since  1.4.0
		 *
		 * @return array Session variable
		 */
		public function get_notices() {
			$notices = $this->get( 'notices' );

			if ( $notices ) {
				return $notices;
			}

			return array(
				'error'   => array(),
				'warning' => array(),
				'success' => array(),
				'info'    => array(),
			);
		}

		/**
		 * Returns the session ID.
		 *
		 * @since  1.3.5
		 *
		 * @return string Session ID
		 */
		public function get_session_id() {
			return $this->session->session_id;
		}

		/**
		 * Determines if we should start sessions
		 *
		 * @since  1.4.17
		 *
		 * @return boolean
		 */
		public function should_start_session() {
			$start_session = true;

			if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
				$blocklist = $this->get_blocklist();
				$uri       = ltrim( $_SERVER['REQUEST_URI'], '/' );
				$uri       = untrailingslashit( $uri );

				if ( in_array( $uri, $blocklist ) ) {
					$start_session = false;
				}

				if ( false !== strpos( $uri, 'feed=' ) ) {
					$start_session = false;
				}
			}

			/**
			 * Filter whether the session should be started for the current request.
			 *
			 * @since 1.4.17
			 *
			 * @param boolean $start_session Whether to start the session.
			 */
			return apply_filters( 'charitable_start_session', $start_session );
		}

		/**
		 * Retrieve the URI blocklist.
		 *
		 * These are the URIs where we never start sessions.
		 *
		 * @since  1.4.17
		 *
		 * @return array
		 */
		public function get_blocklist() {
			/**
			 * Filter the blocklist of URIs where sessions should not be started.
			 *
			 * @since 1.4.17
			 *
			 * @param string[] $blocklist Array of URIs.
			 */
			$blocklist = apply_filters(
				'charitable_session_start_uri_blocklist',
				array(
					'feed',
					'feed/rss',
					'feed/rss2',
					'feed/rdf',
					'feed/atom',
					'comments/feed',
				)
			);

			/**
			 * Deprecated filter retained for backwards compatibility.
			 *
			 * @since 1.4.17
			 * @since 1.6.42 Deprecated in favour of charitable_session_start_uri_blocklist
			 *
			 * @param string[] $blocklist Array of URIs.
			 */
			$blocklist = apply_filters( 'charitable_session_start_uri_blacklist', $blocklist );

			/* Look to see if WordPress is in a sub folder or this is a network site that uses sub folders */
			$folder = str_replace( network_home_url(), '', get_site_url() );

			if ( ! empty( $folder ) ) {
				foreach ( $blocklist as $path ) {
					$blocklist[] = $folder . '/' . $path;
				}
			}

			return $blocklist;
		}
	}

endif;
