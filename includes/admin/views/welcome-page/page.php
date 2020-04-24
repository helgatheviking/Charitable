<?php
/**
 * Display the Welcome page.
 *
 * @author  Studio 164a
 * @package Charitable/Admin View/Welcome Page
 * @since   1.0.0
 * @version 1.6.38
 */

wp_enqueue_style( 'charitable-admin-pages' );

require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );

$gateways        = Charitable_Gateways::get_instance()->get_active_gateways_names();
$campaigns       = wp_count_posts( 'campaign' );
$campaigns_count = $campaigns->publish + $campaigns->draft + $campaigns->future + $campaigns->pending + $campaigns->private;
$emails          = charitable_get_helper( 'emails' )->get_enabled_emails_names();
$install         = isset( $_GET['install'] ) && $_GET['install'];
$languages       = wp_get_available_translations();
$locale          = get_locale();
$language        = isset( $languages[ $locale ]['native_name'] ) ? $languages[ $locale ]['native_name'] : $locale;
$currency        = charitable_get_option( 'currency', 'AUD' );
$currencies      = charitable_get_currency_helper()->get_all_currencies();
$all_extensions  = array(
	'payfast'                        => __( 'Accept donations in South African Rand', 'charitable' ),
	'payu-money'                     => __( 'Accept donations in Indian Rupees with PayUmoney', 'charitable' ),
	'easy-digital-downloads-connect' => __( 'Collect donations with Easy Digital Downloads', 'charitable' ),
	'recurring-donations'            => __( 'Accept recurring donations', 'charitable' ),
	'fee-relief'                     => __( 'Let donors cover the gateway fees', 'charitable' ),
	'stripe'                         => __( 'Accept credit card donations with Stripe', 'charitable' ),
	'authorize-net'                  => __( 'Collect donations with Authorize.Net', 'charitable' ),
	'ambassadors'                    => __( 'Peer to peer fundraising or crowdfunding', 'charitable' ),
	'windcave'                       => sprintf(
		/* translators: %s: currency code */
		__( 'Collect donations in %s', 'charitable' ),
		$currencies[ $currency ]
	),
	'anonymous-donations'            => __( 'Let donors give anonymously', 'charitable' ),
	'user-avatar'                    => __( 'Let your donors upload their own profile photo', 'charitable' ),
);

if ( 'en_ZA' == $locale || 'ZAR' == $currency ) {
	$extensions = array_intersect_key( $all_extensions, array( 'payfast' => '', 'recurring-donations' => '', 'ambassadors' => '', 'fee-relief' => '' ) );
} elseif ( 'hi_IN' == $locale || 'INR' == $currency ) {
	$extensions = array_intersect_key( $all_extensions, array( 'payu-money' => '', 'ambassadors' => '', 'fee-relief' => '', 'windcave' => '' ) );
} elseif ( in_array( $locale, array( 'en_NZ', 'ms_MY', 'ja', 'zh_HK' ) ) || in_array( $currency, array( 'NZD', 'MYR', 'JPY', 'HKD' ) ) ) {
	$extensions = array_intersect_key( $all_extensions, array( 'recurring-donations' => '', 'stripe' => '', 'windcave' => '', 'fee-relief' => '' ) );
} elseif( in_array( $locale, array( 'th' ) ) || in_array( $currency, array( 'BND', 'FJD', 'KWD', 'PGK', 'SBD', 'THB', 'TOP', 'VUV', 'WST' ) ) ) {
	$extensions = array_intersect_key( $all_extensions, array( 'windcave' => '', 'fee-relief' => '', 'ambassadors' => '', 'anonymous-donations' => '' ) );
} elseif ( class_exists( 'EDD' ) ) {
	$extensions = array_intersect_key( $all_extensions, array( 'ambassadors' => '', 'easy-digital-downloads-connect' => '', 'anonymous-donations' => '', 'user-avatar' => '' ) );
} else {
	$extensions = array_intersect_key( $all_extensions, array( 'recurring-donations' => '', 'stripe' => '', 'authorize-net' => '', 'fee-relief' => '' ) );
}

?>
<div class="wrap about-wrap charitable-wrap">
	<h1>
		<strong>Charitable</strong>
		<sup class="version"><?php echo charitable()->get_version(); ?></sup>
	</h1>
	<div class="badge">
		<a href="https://www.wpcharitable.com/?utm_source=welcome-page&amp;utm_medium=wordpress-dashboard&amp;utm_campaign=home&amp;utm_content=icon" target="_blank"><i class="charitable-icon charitable-icon-charitable"></i></a>
	</div>
	<div class="intro">
		<?php
		if ( $install ) :
			_e( 'Thank you for installing Charitable!', 'charitable' );
		else :
			printf(
				__( 'Enjoying Charitable? Why not <a href="%s" target="_blank">leave a %s review</a> on WordPress.org? We\'d really appreciate it.', 'charitable' ),
				'https://wordpress.org/support/view/plugin-reviews/charitable?rate=5#postform',
				'<span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span>'
			);
		endif;
		?>
	</div>
	<hr />
	<div class="column-left">
		<div class="column-inside">
			<h2><?php _e( 'The WordPress Fundraising Toolkit', 'charitable' ); ?></h2>
			<p><?php _e( 'Charitable is everything you need to start accepting donations today. PayPal and offline donations work right out of the box, and when your organization is ready to grow, our extensions give you the tools you need to move forward.', 'charitable' ); ?></p>
			<?php if ( current_user_can( 'manage_charitable_settings' ) ) : ?>
				<hr />
				<h3><?php _e( 'Getting Started', 'charitable' ); ?></h3>
				<ul class="checklist">
					<?php if ( count( $gateways ) > 0 ) : ?>
						<li class="done"><?php
							printf(
								_x( 'You have activated %s. <a href="%s">Change settings</a>', 'You have activated x and y. Change gateway settings.', 'charitable' ),
								charitable_list_to_sentence_part( $gateways ),
								admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
							); ?>
						</li>
					<?php else : ?>
						<li class="not-done"><a href="<?php echo admin_url( 'admin.php?page=charitable-settings&tab=gateways' ); ?>"><?php _e( 'You need to enable a payment gateway', 'charitable' ); ?></a></li>
					<?php endif ?>
					<?php if ( $campaigns_count > 0 ) : ?>
						<li class="done"><?php
							printf(
								__( 'You have created your first campaign. <a href="%s">Create another one.</a>', 'charitable' ),
								admin_url( 'post-new.php?post_type=campaign' )
							); ?>
						</li>
					<?php else : ?>
						<li class="not-done"><a href="<?php echo admin_url( 'post-new.php?post_type=campaign' ); ?>"><?php _e( 'Create your first campaign', 'charitable' ); ?></a></li>
					<?php endif ?>
					<?php if ( count( $emails ) > 0 ) : ?>
						<li class="done"><?php
							printf(
								_x( 'You have turned on the %s. <a href="%s">Change settings</a>', 'You have activated x and y. Change email settings.', 'charitable' ),
								charitable_list_to_sentence_part( $emails ),
								admin_url( 'admin.php?page=charitable-settings&tab=emails' )
							); ?>
						</li>
					<?php else : ?>
						<li class="not-done"><a href="<?php echo admin_url( 'admin.php?page=charitable-settings&tab=emails' ); ?>"><?php _e( 'Turn on email notifications', 'charitable' ); ?></a></li>
					<?php endif ?>
				</ul>
				<p style="margin-bottom: 0;"><?php
					printf(
						__( 'Need a hand with anything? You might find the answer in <a href="%s">our documentation</a>, or you can always get in touch with us via <a href="%s">our support page</a>.', 'charitable' ),
						'https://www.wpcharitable.com/documentation/?utm_source=welcome-page&utm_medium=wordpress-dashboard&utm_campaign=documentation',
						'https://www.wpcharitable.com/support/?utm_source=welcome-page&utm_medium=wordpress-dashboard&utm_campaign=support'
					); ?>
				</p>
			<?php endif ?>
			<hr />
			<?php if ( strpos( $locale, 'en' ) !== 0 ) : ?>
				<h3><?php printf( _x( 'Translate Charitable into %s', 'translate Charitable into language', 'charitable' ), $language ); ?></h3>
				<p><?php printf( __( 'You can help us translate Charitable into %s by <a href="https://translate.wordpress.org/projects/wp-plugins/charitable">contributing to the translation project</a>.', 'charitable' ),
					$language
				); ?></p>
				<hr />
			<?php endif ?>
			<h3><?php _e( 'Developer? Contribute to Charitable', 'charitable' ); ?></h3>
			<p><?php printf(
				__( 'Found a bug? Want to contribute a patch or create a new feature? <a href="%s">GitHub is the place to go!</a>', 'charitable' ),
				'https://github.com/Charitable/Charitable'
			); ?></p>
			<hr />
			<h3><?php _e( 'Try Reach, a free theme designed for fundraising', 'charitable' ); ?></h3>
			<img src="<?php echo charitable()->get_path( 'assets', false ); ?>images/reach-mockup.png" alt="<?php _e( 'Screenshot of Reach, a WordPress fundraising theme designed to complement Charitable', 'charitable' ); ?>" style="margin-bottom: 21px; float: right; margin-left: 20px;" width="336" height="166" />
			<p><?php _e( 'We built Reach to help non-profits &amp; social entrepreneurs run beautiful online fundraising campaigns. Whether you’re creating a website for your organization’s peer-to-peer fundraising event or building an online crowdfunding platform, Reach is the perfect starting point.', 'charitable' ); ?></p>
			<p><a href="https://www.wpcharitable.com/download-reach/?utm_source=welcome-page&amp;utm_medium=wordpress-dashboard&amp;utm_campaign=reach" class="button-primary" style="margin-right: 8px;" target="_blank"><?php _e( 'Download it free', 'charitable' ); ?></a><a href="http://demo.wpcharitable.com/reach/?utm_source=welcome-page&amp;utm_medium=wordpress-dashboard&amp;utm_campaign=reach" class="button-secondary" target="_blank"><?php _e( 'View demo', 'charitable' ); ?></a></p>
		</div>
	</div>
	<div class="column-right">
		<div class="bundle-promo">
			<h2><?php _e( 'Bundle & Save', 'charitable' ); ?></h2>
			<p><?php _e( 'Choose one of our popular extension packages and <strong>save over 50%</strong>.', 'charitable' ); ?></p>
			<p style="text-align: center;"><a href="https://www.wpcharitable.com/pricing/?utm_source=welcome-page&amp;utm_medium=wordpress-dashboard&amp;utm_campaign=pwyw-packages" class="button-primary"><?php _e( 'View Pricing', 'charitable' ); ?></a></p>
		</div>
		<div class="column-inside">
			<h3><?php _e( 'Recommended Extensions', 'charitable' ); ?></h3>
			<ul class="extensions">
				<?php foreach ( $extensions as $extension => $description ) : ?>
					<li class="<?php echo $extension; ?>">
						<a href="https://www.wpcharitable.com/extensions/charitable-<?php echo $extension; ?>/?utm_source=welcome-page&amp;utm_medium=wordpress-dashboard&amp;utm_campaign=<?php echo $extension ?>"><img src="<?php echo charitable()->get_path( 'assets', false ); ?>images/extensions/<?php echo $extension ?>.png" width="640" height="300" alt="<?php echo esc_attr( sprintf( _x( '%s banner', 'extension banner', 'charitable' ), $extension ) ); ?>" /><?php echo $description; ?></a>
					</li>
				<?php endforeach ?>
			</ul>
		</div>
	</div>
</div>
