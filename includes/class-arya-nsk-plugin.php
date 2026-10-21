<?php
/**
 * WordPress integration for the Arya NSK calculator.
 *
 * @package Arya_NSK_Calculator
 */

defined( 'ABSPATH' ) || exit;

class Arya_NSK_Plugin {

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_settings' ) );
		add_action( 'init', array( __CLASS__, 'register_shortcode' ), 20 );
		add_action( 'admin_menu', array( __CLASS__, 'register_settings_page' ) );
		add_filter( 'render_block_core/shortcode', array( __CLASS__, 'render_shortcode_block_fallback' ), 20, 2 );
	}

	/**
	 * Register the public shortcode after WordPress has initialized.
	 *
	 * @return void
	 */
	public static function register_shortcode() {
		add_shortcode( 'arya_calculator', array( __CLASS__, 'render_shortcode' ) );
	}

	/**
	 * Render the calculator when a Gutenberg Shortcode block was left unprocessed.
	 *
	 * This is a compatibility fallback for editors or filters that leave the
	 * literal shortcode text in the rendered block.
	 *
	 * @param string               $block_content Rendered block HTML.
	 * @param array<string, mixed> $block         Parsed block data.
	 * @return string
	 */
	public static function render_shortcode_block_fallback( $block_content, $block ) {
		$shortcode = isset( $block['attrs']['text'] ) ? trim( (string) $block['attrs']['text'] ) : '';

		if ( '[arya_calculator]' !== $shortcode || false !== strpos( $block_content, 'arya_nsk_calculator' ) ) {
			return $block_content;
		}

		return self::render_shortcode();
	}

	/**
	 * Set a sensible initial notification recipient.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( false === get_option( 'arya_nsk_notification_email', false ) ) {
			update_option( 'arya_nsk_notification_email', get_option( 'admin_email', '' ) );
		}
	}

	/**
	 * Register the notification email setting.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'arya_nsk_settings',
			'arya_nsk_notification_email',
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_notification_email' ),
				'type'              => 'string',
			)
		);
	}

	/**
	 * Sanitize an administrator notification address.
	 *
	 * @param mixed $value Submitted setting value.
	 * @return string
	 */
	public static function sanitize_notification_email( $value ) {
		$email = sanitize_email( (string) $value );

		if ( ! is_email( $email ) ) {
			add_settings_error( 'arya_nsk_notification_email', 'arya_nsk_invalid_email', 'Veuillez renseigner une adresse e-mail valide.', 'error' );

			return (string) get_option( 'arya_nsk_notification_email', '' );
		}

		return $email;
	}

	/**
	 * Add the plugin settings page below Settings.
	 *
	 * @return void
	 */
	public static function register_settings_page() {
		add_options_page(
			'Calculateur Arya',
			'Calculateur Arya',
			'manage_options',
			'arya-nsk-calculator',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Render the WordPress settings screen.
	 *
	 * @return void
	 */
	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1>Calculateur Arya</h1>
			<form action="options.php" method="post">
				<?php settings_fields( 'arya_nsk_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="arya_nsk_notification_email">Adresse e-mail de notification</label></th>
						<td>
							<input class="regular-text" id="arya_nsk_notification_email" name="arya_nsk_notification_email" type="email" value="<?php echo esc_attr( (string) get_option( 'arya_nsk_notification_email', '' ) ); ?>" required />
							<p class="description">Cette adresse reçoit les demandes calculées par le formulaire.</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the front-end calculator shortcode.
	 *
	 * @return string
	 */
	public static function render_shortcode() {
		wp_enqueue_style( 'arya-nsk-calculator', ARYA_NSK_PLUGIN_URL . 'assets/css/arya-nsk-calculator.css', array(), ARYA_NSK_VERSION );

		$submission = self::handle_submission();
		$values     = $submission['values'];

		ob_start();
		?>
		<div class="arya_nsk_calculator">
			<?php self::render_errors( $submission['errors'] ); ?>
			<form class="arya_nsk_calculator__form" action="" method="post">
				<?php wp_nonce_field( 'arya_nsk_calculate', 'arya_nsk_nonce' ); ?>
				<input name="arya_nsk_form" type="hidden" value="1" />
				<div class="arya_nsk_calculator__honeypot" aria-hidden="true">
					<label for="arya_nsk_website">Site web</label>
					<input id="arya_nsk_website" name="arya_nsk_website" type="text" tabindex="-1" autocomplete="off" />
				</div>

				<p class="arya_nsk_calculator__field">
					<label for="arya_nsk_first_name">Prénom <span aria-hidden="true">*</span></label>
					<input id="arya_nsk_first_name" name="arya_nsk_first_name" type="text" value="<?php echo esc_attr( $values['first_name'] ); ?>" autocomplete="given-name" required />
				</p>
				<p class="arya_nsk_calculator__field">
					<label for="arya_nsk_email">E-mail <span aria-hidden="true">*</span></label>
					<input id="arya_nsk_email" name="arya_nsk_email" type="email" value="<?php echo esc_attr( $values['email'] ); ?>" autocomplete="email" required />
				</p>
				<p class="arya_nsk_calculator__field">
					<label for="arya_nsk_birth_date">Date de naissance <span aria-hidden="true">*</span></label>
					<input id="arya_nsk_birth_date" name="arya_nsk_birth_date" type="date" value="<?php echo esc_attr( $values['birth_date'] ); ?>" max="<?php echo esc_attr( wp_date( 'Y-m-d', current_time( 'timestamp' ) - DAY_IN_SECONDS ) ); ?>" required />
				</p>
				<p class="arya_nsk_calculator__consent">
					<input id="arya_nsk_consent" name="arya_nsk_consent" type="checkbox" value="1" <?php checked( $values['consent'], '1' ); ?> required />
					<label for="arya_nsk_consent"><?php self::render_consent_label(); ?></label>
				</p>
				<p class="arya_nsk_calculator__required">Les champs marqués d’un astérisque sont obligatoires.</p>
				<p><button class="arya_nsk_calculator__submit" type="submit">Découvrir mon profil</button></p>
			</form>
			<?php self::render_result( $submission['result'] ); ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Validate and process a submitted calculator form.
	 *
	 * @return array{errors: array<int, string>, result: array<string, int|string>|null, values: array<string, string>}
	 */
	private static function handle_submission() {
		$values = array(
			'first_name' => '',
			'email'      => '',
			'birth_date' => '',
			'consent'    => '',
		);
		$output = array(
			'errors' => array(),
			'result' => null,
			'values' => $values,
		);

		if ( 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) || empty( $_POST['arya_nsk_form'] ) ) {
			return $output;
		}

		$input = wp_unslash( $_POST );

		if ( empty( $input['arya_nsk_nonce'] ) || ! wp_verify_nonce( (string) $input['arya_nsk_nonce'], 'arya_nsk_calculate' ) ) {
			$output['errors'][] = 'Votre session a expiré. Rechargez la page puis réessayez.';

			return $output;
		}

		$values['first_name'] = isset( $input['arya_nsk_first_name'] ) ? sanitize_text_field( (string) $input['arya_nsk_first_name'] ) : '';
		$values['email']      = isset( $input['arya_nsk_email'] ) ? sanitize_email( (string) $input['arya_nsk_email'] ) : '';
		$values['birth_date'] = isset( $input['arya_nsk_birth_date'] ) ? sanitize_text_field( (string) $input['arya_nsk_birth_date'] ) : '';
		$values['consent']    = isset( $input['arya_nsk_consent'] ) && '1' === (string) $input['arya_nsk_consent'] ? '1' : '';
		$output['values']     = $values;

		if ( '' === $values['first_name'] ) {
			$output['errors'][] = 'Veuillez renseigner votre prénom.';
		}

		if ( ! is_email( $values['email'] ) ) {
			$output['errors'][] = 'Veuillez renseigner une adresse e-mail valide.';
		}

		if ( '' === $values['consent'] ) {
			$output['errors'][] = 'Votre consentement est nécessaire pour continuer.';
		}

		try {
			$result = Arya_NSK_Calculator::calculate( $values['birth_date'] );
		} catch ( InvalidArgumentException $exception ) {
			$result             = null;
			$output['errors'][] = 'Veuillez renseigner une date de naissance valide.';
		}

		if ( null !== $result && $values['birth_date'] >= wp_date( 'Y-m-d' ) ) {
			$output['errors'][] = 'La date de naissance doit être antérieure à aujourd’hui.';
		}

		if ( ! empty( $output['errors'] ) || null === $result ) {
			return $output;
		}

		$output['result'] = $result;
		$honeypot          = isset( $input['arya_nsk_website'] ) ? sanitize_text_field( (string) $input['arya_nsk_website'] ) : '';

		if ( '' === $honeypot ) {
			self::send_notification( $values, $result );
		}

		return $output;
	}

	/**
	 * Send the administrator-only notification. Its outcome is intentionally not exposed to visitors.
	 *
	 * @param array<string, string>     $values Form values.
	 * @param array<string, int|string> $result Calculation result.
	 * @return void
	 */
	private static function send_notification( $values, $result ) {
		$recipient = (string) get_option( 'arya_nsk_notification_email', '' );

		if ( ! is_email( $recipient ) ) {
			return;
		}

		$subject = sprintf( 'Nouvelle demande calculateur — %s', $values['first_name'] );
		$message = implode(
			"\n",
			array(
				'Nouvelle demande depuis le calculateur Arya.',
				'',
				'Prénom : ' . $values['first_name'],
				'E-mail : ' . $values['email'],
				'Date de naissance : ' . $values['birth_date'],
				'',
				'Chiffre adulte : ' . $result['adult_number'] . ' — ' . $result['adult_element'],
				'Chiffre enfant : ' . $result['child_number'] . ' — ' . $result['child_element'],
				'',
				'Demande reçue le : ' . wp_date( 'd/m/Y H:i' ),
			)
		);

		wp_mail( $recipient, $subject, $message );
	}

	/**
	 * Output the privacy-consent label.
	 *
	 * @return void
	 */
	private static function render_consent_label() {
		$privacy_url = get_privacy_policy_url();

		if ( $privacy_url ) {
			echo wp_kses(
				sprintf(
					'J’accepte d’être recontacté(e) conformément à la <a href="%s">politique de confidentialité</a>. <span aria-hidden="true">*</span>',
					esc_url( $privacy_url )
				),
				array(
					'a'    => array( 'href' => array() ),
					'span' => array( 'aria-hidden' => array() ),
				)
			);

			return;
		}

		echo 'J’accepte d’être recontacté(e) conformément à la politique de confidentialité. <span aria-hidden="true">*</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static string.
	}

	/**
	 * Render validation errors.
	 *
	 * @param array<int, string> $errors Error messages.
	 * @return void
	 */
	private static function render_errors( $errors ) {
		if ( empty( $errors ) ) {
			return;
		}
		?>
		<div class="arya_nsk_calculator__errors" role="alert">
			<p>Votre demande comporte des erreurs :</p>
			<ul>
				<?php foreach ( $errors as $error ) : ?>
					<li><?php echo esc_html( $error ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Render a successful calculation.
	 *
	 * @param array<string, int|string>|null $result Calculation result.
	 * @return void
	 */
	private static function render_result( $result ) {
		if ( null === $result ) {
			return;
		}
		?>
		<section class="arya_nsk_calculator__result" aria-live="polite">
			<h2>Votre profil</h2>
			<div class="arya_nsk_calculator__result-grid">
				<div class="arya_nsk_calculator__result-card">
					<h3>Chiffre adulte</h3>
					<p class="arya_nsk_calculator__number"><?php echo esc_html( (string) $result['adult_number'] ); ?></p>
					<p><?php echo esc_html( (string) $result['adult_element'] ); ?></p>
				</div>
				<div class="arya_nsk_calculator__result-card">
					<h3>Chiffre enfant</h3>
					<p class="arya_nsk_calculator__number"><?php echo esc_html( (string) $result['child_number'] ); ?></p>
					<p><?php echo esc_html( (string) $result['child_element'] ); ?></p>
				</div>
			</div>
		</section>
		<?php
	}
}
