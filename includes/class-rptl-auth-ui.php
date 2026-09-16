<?php
/**
 * Markup for the login / signup interface.
 *
 * @package SbytesRestrictPreviewForTutorLMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds the locked lesson card, the popup and the shared tabbed form.
 */
class RPTL_Auth_UI {

	/**
	 * Counter used to keep element IDs unique when the form renders twice.
	 *
	 * @var int
	 */
	private static $instance = 0;

	/**
	 * Whether the Sign Up tab can be shown.
	 *
	 * Tutor's registration handler bails out when WordPress registration
	 * is closed, so offering the tab in that case would be a dead end.
	 *
	 * @return bool
	 */
	public static function registration_enabled() {
		return (bool) get_option( 'users_can_register', false ) && RPTL_Settings::get( 'show_register_tab' );
	}

	/**
	 * The locked lesson card shown in place of preview content.
	 *
	 * @param int $lesson_id Lesson post ID.
	 * @return string
	 */
	public static function locked_lesson( $lesson_id ) {
		$lesson_url = get_permalink( $lesson_id );

		ob_start();
		?>
		<div class="rptl-card" role="alert">
			<span class="rptl-eyebrow"><?php echo esc_html( RPTL_Settings::get( 'eyebrow_text' ) ); ?></span>
			<h3 class="rptl-card-title"><?php echo esc_html( get_the_title( $lesson_id ) ); ?></h3>
			<p class="rptl-card-text"><?php echo esc_html( RPTL_Settings::get( 'lesson_message' ) ); ?></p>
			<?php echo self::auth_tabs( $lesson_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in auth_tabs(). ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * The popup shown when a guest clicks a preview lesson link.
	 *
	 * Not rendered on a locked lesson page: the inline card already shows
	 * the same form there, and printing Tutor's forms twice on one page
	 * duplicates their element IDs and breaks both copies.
	 *
	 * @param RPTL_Access $access Access handler.
	 * @return void
	 */
	public static function render_modal( RPTL_Access $access ) {
		// Skip unless the stylesheet and script are loading here too,
		// otherwise the forms would render unstyled and inert.
		if ( ! RPTL_Lessons::is_relevant_context() ) {
			return;
		}
		if ( $access->user_may_view() || $access->is_locked_request() ) {
			return;
		}
		?>
		<div class="rptl-overlay" id="rptl-overlay" aria-hidden="true">
			<div class="rptl-modal" role="dialog" aria-modal="true" aria-labelledby="rptl-modal-title">
				<button type="button" class="rptl-close" id="rptl-close" aria-label="<?php esc_attr_e( 'Close', 'sbytes-restrict-preview-for-tutor-lms' ); ?>">
					<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">
						<path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
					</svg>
				</button>
				<span class="rptl-eyebrow"><?php echo esc_html( RPTL_Settings::get( 'eyebrow_text' ) ); ?></span>
				<h3 id="rptl-modal-title"><?php echo esc_html( RPTL_Settings::get( 'modal_heading' ) ); ?></h3>
				<p><?php echo esc_html( RPTL_Settings::get( 'modal_message' ) ); ?></p>
				<?php echo self::auth_tabs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in auth_tabs(). ?>
			</div>
		</div>
		<?php
	}

	/**
	 * The shared tabbed Log In / Sign Up form.
	 *
	 * Embeds Tutor LMS's own shortcodes so validation, password handling
	 * and account creation all stay in Tutor rather than being
	 * reimplemented here:
	 *   [tutor_login]                     login
	 *   [tutor_student_registration_form] student signup
	 *
	 * The redirect target is written into a hidden field on both forms by
	 * the front-end script. Tutor supports this natively: its login
	 * handler passes the posted redirect_to value to wp_safe_redirect(),
	 * and its student registration handler reads the same field from the
	 * request. Both are ordinary POST submissions, not AJAX.
	 *
	 * @param string $redirect_to Where to send the visitor after signing in.
	 * @return string
	 */
	public static function auth_tabs( $redirect_to = '' ) {
		self::$instance++;
		$uid          = 'rptl-auth-' . self::$instance;
		$can_register = self::registration_enabled();

		ob_start();
		?>
		<div class="rptl-auth" data-rptl-auth>
			<?php if ( $can_register ) : ?>
				<div class="rptl-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Log in or sign up', 'sbytes-restrict-preview-for-tutor-lms' ); ?>">
					<button type="button" class="rptl-tab is-active" role="tab"
						id="<?php echo esc_attr( $uid ); ?>-tab-login"
						aria-controls="<?php echo esc_attr( $uid ); ?>-panel-login"
						aria-selected="true" data-rptl-tab="login">
						<?php esc_html_e( 'Log In', 'sbytes-restrict-preview-for-tutor-lms' ); ?>
					</button>
					<button type="button" class="rptl-tab" role="tab"
						id="<?php echo esc_attr( $uid ); ?>-tab-register"
						aria-controls="<?php echo esc_attr( $uid ); ?>-panel-register"
						aria-selected="false" data-rptl-tab="register">
						<?php esc_html_e( 'Sign Up', 'sbytes-restrict-preview-for-tutor-lms' ); ?>
					</button>
					<span class="rptl-tab-indicator" aria-hidden="true"></span>
				</div>
			<?php endif; ?>

			<div class="rptl-panel is-active" role="tabpanel"
				id="<?php echo esc_attr( $uid ); ?>-panel-login"
				aria-labelledby="<?php echo esc_attr( $uid ); ?>-tab-login"
				data-rptl-panel="login">
				<?php echo do_shortcode( '[tutor_login]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Tutor LMS output. ?>
			</div>

			<?php if ( $can_register ) : ?>
				<div class="rptl-panel" role="tabpanel" hidden
					id="<?php echo esc_attr( $uid ); ?>-panel-register"
					aria-labelledby="<?php echo esc_attr( $uid ); ?>-tab-register"
					data-rptl-panel="register">
					<?php echo do_shortcode( '[tutor_student_registration_form]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Tutor LMS output. ?>
				</div>
			<?php endif; ?>

			<input type="hidden" data-rptl-redirect value="<?php echo esc_url( $redirect_to ); ?>" />
		</div>
		<?php
		return ob_get_clean();
	}
}
