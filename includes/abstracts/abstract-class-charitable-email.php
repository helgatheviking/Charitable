<?php
/**
 * Email model.
 *
 * @package   Charitable/Classes/Charitable_Email
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Email' ) ) :

	/**
	 * Charitable_Email
	 *
	 * @since 1.0.0
	 */
	abstract class Charitable_Email implements Charitable_Email_Interface {

		/** Email ID */
		const ID = '';

		/**
		 * Descriptive name of the email.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		protected $name;

		/**
		 * Array of supported object types (campaigns, donations, donors, etc).
		 *
		 * @since 1.0.0
		 *
		 * @var   string[]
		 */
		protected $object_types = array();

		/**
		 * Whether the email allows you to define the email recipients.
		 *
		 * @since 1.0.0
		 *
		 * @var   boolean
		 */
		protected $has_recipient_field = false;

		/**
		 * Whether the email is required.
		 *
		 * @since 1.4.0
		 *
		 * @var   boolean
		 */
		protected $required = false;

		/**
		 * The Donation object, if relevant.
		 *
		 * @since 1.0.0
		 *
		 * @var   Charitable_Donation
		 */
		protected $donation;

		/**
		 * The Campaign object, if relevant.
		 *
		 * @since 1.0.0
		 *
		 * @var   Charitable_Campaign
		 */
		protected $campaign;

		/**
		 * Email recipient.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		protected $recipients;

		/**
		 * Email headers.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		protected $headers;

		/**
		 * Create a class instance.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed[] $objects Objects for the email.
		 */
		public function __construct( $objects = array() ) {
			$this->donation = isset( $objects['donation'] ) ? $objects['donation'] : null;
			$this->campaign = isset( $objects['campaign'] ) ? $objects['campaign'] : null;
		}

		/**
		 * Return an instance property.
		 *
		 * @since  1.5.0
		 *
		 * @param  key $property The property to return.
		 * @return mixed
		 */
		public function get( $property ) {
			if ( property_exists( $this, $property ) ) {
				return $this->$property;
			}

			return '';
		}

		/**
		 * Return the email name.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_name() {
			return $this->name;
		}

		/**
		 * Return whether the email is required.
		 *
		 * If an email is required, it cannot be disabled/enabled, but it can still be edited.
		 *
		 * @since  1.4.0
		 *
		 * @return boolean
		 */
		public function is_required() {
			return $this->required;
		}

		/**
		 * Return the types of objects.
		 *
		 * @since  1.3.0
		 *
		 * @return string[]
		 */
		public function get_object_types() {
			return $this->object_types;
		}

		/**
		 * Return the donation object.
		 *
		 * @since  1.3.0
		 *
		 * @return null|Charitable_Donation
		 */
		public function get_donation() {
			return $this->donation;
		}

		/**
		 * Return the campaign object.
		 *
		 * @since  1.3.0
		 *
		 * @return null|Charitable_Campaign
		 */
		public function get_campaign() {
			return $this->campaign;
		}

		/**
		 * Get from name for email.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_from_name() {
			return wp_specialchars_decode( charitable_get_option( 'email_from_name', get_option( 'blogname' ) ) );
		}

		/**
		 * Get from address for email.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_from_address() {
			return charitable_get_option( 'email_from_email', get_option( 'admin_email' ) );
		}

		/**
		 * Return the email recipients.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_recipient() {
			return $this->get_option( 'recipient', $this->get_default_recipient() );
		}

		/**
		 * Return the email subject line.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_subject() {
			return $this->get_option( 'subject', $this->get_default_subject() );
		}

		/**
		 * Get the email content type
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_content_type() {
			/**
			 * Filter the content type for the email.
			 *
			 * @since 1.0.0
			 *
			 * @param string           $content_type The content type. Defaults to 'text/html'.
			 * @param Charitable_Email $email        This instance of `Charitable_Email`.
			 */
			return apply_filters( 'charitable_email_content_type', 'text/html', $this );
		}

		/**
		 * Get the email headers.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_headers() {
			if ( ! isset( $this->headers ) ) {
				$this->headers  = "From: {$this->get_from_name()} <{$this->get_from_address()}>\r\n";
				$this->headers .= "Reply-To: {$this->get_from_address()}\r\n";
				$this->headers .= "Content-Type: {$this->get_content_type()}; charset=utf-8\r\n";
			}

			/**
			 * Filter the email headers.
			 *
			 * @since 1.0.0
			 *
			 * @param string           $headers The default email headers.
			 * @param Charitable_Email $email   The email object.
			 */
			return apply_filters( 'charitable_email_headers', $this->headers, $this );
		}

		/**
		 * Checks whether we are currently previewing the email.
		 *
		 * @since  1.3.5
		 *
		 * @return boolean
		 */
		public function is_preview() {
			return isset( $_GET['charitable_action'] ) && 'preview_email' == $_GET['charitable_action'];
		}

		/**
		 * Register email settings.
		 *
		 * @since  1.0.0
		 * @since  1.5.0 $settings argument is now deprecated.
		 * @since  1.5.7 $settings argument removed from function definition.
		 *
		 * @return array
		 */
		public function email_settings() {
			if ( func_num_args() ) {
				charitable_get_deprecated()->deprecated_argument(
					__METHOD__,
					'1.5.0',
					__( 'The `$settings` parameter is no longer used.', 'charitable' )
				);
			}

			$email_settings = array(
				'section_email' => array(
					'type'     => 'heading',
					'title'    => $this->get_name(),
					'priority' => 2,
				),
				'subject'       => array(
					'type'     => 'text',
					'title'    => __( 'Email Subject Line', 'charitable' ),
					'help'     => __( 'The email subject line when it is delivered to recipients.', 'charitable' ),
					'priority' => 6,
					'class'    => 'wide',
					'default'  => $this->get_default_subject(),
				),
				'headline'      => array(
					'type'     => 'text',
					'title'    => __( 'Email Headline', 'charitable' ),
					'help'     => __( 'The headline displayed at the top of the email.', 'charitable' ),
					'priority' => 10,
					'class'    => 'wide',
					'default'  => $this->get_default_headline(),
				),
				'body'          => array(
					'type'     => 'editor',
					'title'    => __( 'Email Body', 'charitable' ),
					'help'     => sprintf(
						'%s <div class="charitable-shortcode-options">%s</div>',
						__( 'The content of the email that will be delivered to recipients. HTML is accepted.', 'charitable' ),
						$this->get_shortcode_options()
					),
					'priority' => 14,
					'default'  => $this->get_default_body(),
				),
				'preview'       => array(
					'type'     => 'content',
					'title'    => __( 'Preview', 'charitable' ),
					'content'  => sprintf(
						'<a href="%s" target="_blank" class="button">%s</a>',
						esc_url(
							add_query_arg(
								array(
									'charitable_action' => 'preview_email',
									'email_id'          => $this->get_email_id(),
								),
								home_url()
							)
						),
						__( 'Preview email', 'charitable' )
					),
					'priority' => 18,
					'save'     => false,
				),
			);

			/* Add the recipients field if applicable to this email. */
			$email_settings = $this->add_recipients_field( $email_settings );

			/**
			 * Filter the settings available for this email.
			 *
			 * This filter is primarily useful for adding settings to specific email types.
			 * If you only want to add fields to all email types, use this hook instead:
			 *
			 * charitable_settings_fields_emails_email
			 *
			 * @see   Charitable_Email_Settings::add_individual_email_fields
			 *
			 * @since 1.0.0
			 *
			 * @param array $email_settings Email settings.
			 */
			return apply_filters( 'charitable_settings_fields_emails_email_' . $this->get_email_id(), $email_settings );
		}

		/**
		 * Add recipient field
		 *
		 * @since  1.0.0
		 *
		 * @param  array $settings Existing array of email settings.
		 * @return array
		 */
		public function add_recipients_field( $settings = array() ) {
			if ( ! $this->has_recipient_field ) {
				return $settings;
			}

			$settings['recipient'] = array(
				'type'     => 'text',
				'title'    => __( 'Recipients', 'charitable' ),
				'help'     => __( 'A comma-separated list of email address that will receive this email.', 'charitable' ),
				'priority' => 4,
				'class'    => 'wide',
				'default'  => $this->get_default_recipient(),
			);

			return $settings;
		}

		/**
		 * Sends the email.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function send() {
			do_action( 'charitable_before_send_email', $this );

			$sent = wp_mail(
				$this->get_recipient(),
				do_shortcode( $this->get_subject() ),
				$this->build_email(),
				$this->get_headers()
			);

			do_action( 'charitable_after_send_email', $this, $sent );

			return $sent;
		}

		/**
		 * Resend an email.
		 *
		 * @since  1.5.0
		 *
		 * @param  int   $object_id An object ID.
		 * @param  array $args      Mixed set of arguments.
		 * @return boolean
		 */
		public static function resend( $object_id, $args = array() ) {
			charitable_get_deprecated()->doing_it_wrong(
				__METHOD__,
				__( 'A `resend` method has not been defined for this class.', 'charitable' ),
				'1.5.0'
			);

			return false;
		}

		/**
		 * Checks whether an email can be resent.
		 *
		 * @since  1.5.0
		 *
		 * @param  int   $object_id An object ID.
		 * @param  array $args      Mixed set of arguments.
		 * @return boolean
		 */
		public static function can_be_resent( $object_id, $args = array() ) {
			charitable_get_deprecated()->doing_it_wrong(
				__METHOD__,
				__( 'A `can_be_resent` method has not been defined for this class.', 'charitable' ),
				'1.5.0'
			);

			return false;
		}

		/**
		 * Checks whether the email has already been sent.
		 *
		 * @since  1.3.2
		 * @since  1.5.2 Added the $data_type parameter.
		 *
		 * @param  int    $object_id The ID of the object related to this email. May be a campaign ID, a donation ID or a user ID.
		 * @param  string $data_type Optional. The type of meta we are saving. Defaults to 'post'.
		 * @return boolean
		 */
		public function is_sent_already( $object_id, $data_type = 'post' ) {
			$log = get_metadata( $data_type, $object_id, $this->get_log_key(), true );

			if ( is_array( $log ) ) {
				foreach ( $log as $time => $sent ) {
					if ( $sent ) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Log that the email was sent.
		 *
		 * @since  1.3.2
		 * @since  1.5.2 Added the $data_type parameter.
		 *
		 * @param  int     $object_id The ID of the object related to this email. May be a campaign ID, a donation ID, or a user ID.
		 * @param  boolean $sent      Whether the email was sent.
		 * @param  string  $data_type Optional. The type of meta we are saving. Defaults to 'post'.
		 * @return void
		 */
		public function log( $object_id, $sent, $data_type = 'post' ) {
			$log = get_metadata( $data_type, $object_id, $this->get_log_key(), true );

			if ( ! $log ) {
				$log = array();
			}

			$log[ time() ] = $sent;

			update_metadata( $data_type, $object_id, $this->get_log_key(), $log );
		}

		/**
		 * Preview the email. This will display a sample email within the browser.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function preview() {
			/**
			 * Do something before building the preview output.
			 *
			 * @since 1.0.0
			 *
			 * @param Charitable_Email $this Email object.
			 */
			do_action( 'charitable_before_preview_email', $this );

			$output = $this->build_email();

			/**
			 * Do something after building the preview output.
			 *
			 * @since 1.5.0
			 *
			 * @param Charitable_Email $this Email object.
			 */
			do_action( 'charitable_after_preview_email', $this );

			return $output;
		}

		/**
		 * Returns the body content of the email, formatted as HTML.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_body() {
			$body = $this->get_option( 'body', $this->get_default_body() );
			$body = do_shortcode( $body );
			$body = wpautop( $body );

			/**
			 * Filter the email body before it is sent.
			 *
			 * @since 1.0.0
			 *
			 * @param string           $body  Body content.
			 * @param Charitable_Email $email Instance of `Charitable_Email`.
			 */
			return apply_filters( 'charitable_email_body', $body, $this );
		}

		/**
		 * Returns the email headline.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_headline() {
			$headline = $this->get_option( 'headline', $this->get_default_headline() );
			$headline = do_shortcode( $headline );

			/**
			 * Filter the email headline.
			 *
			 * @since 1.0.0
			 *
			 * @param string           $headline Headline.
			 * @param Charitable_Email $email    Instance of `Charitable_Email`.
			 */
			return apply_filters( 'charitable_email_headline', $headline, $this );
		}

		/**
		 * Return an array of email fields that are specifically available
		 * for this email.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function email_fields() {
			return array();
		}

		/**
		 * Checks whether the email has a valid donation object set.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function has_valid_donation() {
			if ( is_null( $this->donation ) || ! is_a( $this->donation, 'Charitable_Donation' ) ) {
				charitable_get_deprecated()->doing_it_wrong(
					__METHOD__,
					__( 'You cannot send this email without a donation!', 'charitable' ),
					'1.0.0'
				);

				return false;
			}

			return true;
		}
		/**
		 * Checks whether the email has a valid campaign object set.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function has_valid_campaign() {
			if ( is_null( $this->campaign ) || ! is_a( $this->campaign, 'Charitable_Campaign' ) ) {
				charitable_get_deprecated()->doing_it_wrong(
					__METHOD__,
					__( 'You cannot send this email without a campaign!', 'charitable' ),
					'1.0.0'
				);

				return false;
			}

			return true;
		}

		/**
		 * Build the email.
		 *
		 * @since   1.0.0
		 *
		 * @return  string
		 */
		protected function build_email() {
			ob_start();

			charitable_template( 'emails/header.php', array( 'email' => $this ) );

			charitable_template( 'emails/body.php', array( 'email' => $this ) );

			charitable_template( 'emails/footer.php', array( 'email' => $this ) );

			$message = ob_get_clean();

			/**
			 * Filter the email message before it is sent.
			 *
			 * @since 1.0.0
			 *
			 * @param string           $message The full email message output (header, body and footer).
			 * @param Charitable_Email $email   Instance of `Charitable_Email`.
			 */
			return apply_filters( 'charitable_email_message', $message, $this );
		}

		/**
		 * Return the meta key used for the log.
		 *
		 * @since  1.3.2
		 *
		 * @return string
		 */
		protected function get_log_key() {
			return '_email_' . $this->get_email_id() . '_log';
		}

		/**
		 * Return the value of an option specific to this email.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $key     Settings option key.
		 * @param  mixed  $default Default value to return in case setting is not set.
		 * @return mixed
		 */
		protected function get_option( $key, $default ) {
			return charitable_get_option( array( 'emails_' . $this->get_email_id(), $key ), $default );
		}

		/**
		 * Return the default recipient for the email.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		protected function get_default_recipient() {
			return '';
		}

		/**
		 * Return the default subject line for the email.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		protected function get_default_subject() {
			return '';
		}

		/**
		 * Return the default headline for the email.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		protected function get_default_headline() {
			return '';
		}

		/**
		 * Return the default body for the email.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		protected function get_default_body() {
			return '';
		}

		/**
		 * Return HTML formatted list of shortcode options that can be used within the body, headline and subject line.
		 *
		 * @since  version
		 *
		 * @return string
		 */
		protected function get_shortcode_options() {
			$fields = new Charitable_Email_Fields( $this );
			ob_start();
?>
			<p><?php _e( 'The following options are available with the <code>[charitable_email]</code> shortcode:', 'charitable' ) ?></p>
			<ul>
			<?php foreach ( $fields->get_fields() as $key => $field ) : ?>
				<li><strong><?php echo $field['description'] ?></strong>: [charitable_email show=<?php echo $key ?>]</li>
			<?php endforeach ?>
			</ul>

<?php
			$html = ob_get_clean();

			/**
			 * Filter the shortcode options block.
			 *
			 * @since 1.0.0
			 *
			 * @param string           $html  The content.
			 * @param Charitable_Email $email This instance of `Charitable_Email`.
			 */
			return apply_filters( 'charitable_email_shortcode_options_text', $html, $this );
		}

		/**
		 * This function is deprecated as of 1.5.0. Checks whether the passed email is the
		 * same as the current email object.
		 *
		 * @deprecated 1.8.0
		 *
		 * @since  1.3.2
		 * @since  1.5.0 Deprecated. No notice added to allow extensions to be updated first.
		 *
		 * @param  Charitable_Email $email  Email object.
		 * @return boolean
		 */
		protected function is_current_email( Charitable_Email $email ) {
			return $email->get_email_id() == $this->get_email_id();
		}

		/**
		 * Add donation content fields.
		 *
		 * @deprecated 1.8.0
		 *
		 * @since  1.0.0
		 * @since  1.5.0 Deprecated.
		 *
		 * @param  array            $fields Shortcode fields.
		 * @param  Charitable_Email $email  Instance of `Charitable_Email`.
		 * @return array[]
		 */
		public function add_donation_content_fields( $fields, Charitable_Email $email ) {
			return $fields;
		}

		/**
		 * Add donation preview fields.
		 *
		 * @deprecated 1.8.0
		 *
		 * @since  1.0.0
		 * @since  1.5.0 Deprecated.
		 *
		 * @param  array            $fields Shortcode fields.
		 * @param  Charitable_Email $email  Instance of `Charitable_Email`.
		 * @return array[]
		 */
		public function add_preview_donation_content_fields( $fields, Charitable_Email $email ) {
			return $fields;
		}

		/**
		 * Add campaign content fields.
		 *
		 * @deprecated 1.8.0
		 *
		 * @since  1.0.0
		 * @since  1.5.0 Deprecated.
		 *
		 * @param  array            $fields Shortcode fields.
		 * @param  Charitable_Email $email  Instance of `Charitable_Email`.
		 * @return array[]
		 */
		public function add_campaign_content_fields( $fields, Charitable_Email $email ) {
			return $fields;
		}

		/**
		 * Add campaign preview fields.
		 *
		 * @deprecated 1.8.0
		 *
		 * @since  1.0.0
		 * @since  1.5.0 Deprecated.
		 *
		 * @param  array            $fields Shortcode fields.
		 * @param  Charitable_Email $email  Instance of `Charitable_Email`.
		 * @return array[]
		 */
		public function add_preview_campaign_content_fields( $fields, Charitable_Email $email ) {
			return $fields;
		}
	}

endif;
