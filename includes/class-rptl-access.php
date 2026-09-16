<?php
/**
 * Access control for preview lessons.
 *
 * @package SbytesRestrictPreviewForTutorLMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gates preview lesson content and handles the post-login redirect.
 */
class RPTL_Access {

	/**
	 * Name of the short-lived cookie used by the redirect fallback.
	 */
	const RETURN_COOKIE = 'rptl_return_to';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init() {
		if ( ! RPTL_Settings::get( 'gate_previews' ) ) {
			return;
		}

		add_filter( 'the_content', array( $this, 'gate_lesson_content' ), 999 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_action( 'template_redirect', array( $this, 'maybe_redirect_after_auth' ), 5 );
	}

	/**
	 * Whether the current visitor may view preview content.
	 *
	 * Any authenticated user passes: a registered visitor, an enrolled
	 * student, an instructor or an administrator. Only logged-out
	 * visitors are gated, so this can never restrict someone who already
	 * had access.
	 *
	 * @return bool
	 */
	public function user_may_view() {
		/**
		 * Filters whether the current user may view preview lessons.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $allowed Whether access is granted.
		 */
		return (bool) apply_filters( 'rptl_user_may_view_preview', is_user_logged_in() );
	}

	/**
	 * Whether the current request is a preview lesson locked for this visitor.
	 *
	 * @return bool
	 */
	public function is_locked_request() {
		$lesson_id = RPTL_Lessons::current_lesson_id();

		return $lesson_id
			&& $this->should_gate_lesson( $lesson_id )
			&& ! $this->user_may_view();
	}

	/**
	 * Whether a given lesson is one this plugin should gate at all.
	 *
	 * Only free preview lessons qualify, and only in courses that are not
	 * marked public. A public course is an explicit decision by the site
	 * owner to open everything to everyone, and Tutor grants access to
	 * every lesson in it regardless of the preview flag, so gating there
	 * would restrict content the owner deliberately published.
	 *
	 * @param int $lesson_id Lesson post ID.
	 * @return bool
	 */
	public function should_gate_lesson( $lesson_id ) {
		if ( ! RPTL_Lessons::is_preview( $lesson_id ) ) {
			return false;
		}
		if ( RPTL_Lessons::is_public_course_lesson( $lesson_id ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Replace preview lesson content with the login/signup prompt for guests.
	 *
	 * Non-preview lessons are left completely untouched so Tutor's own
	 * enrolment gating continues to govern them unchanged.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function gate_lesson_content( $content ) {
		if ( ! is_singular( RPTL_Lessons::lesson_post_type() ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$lesson_id = (int) get_the_ID();

		if ( ! $this->should_gate_lesson( $lesson_id ) || $this->user_may_view() ) {
			return $content;
		}

		return RPTL_Auth_UI::locked_lesson( $lesson_id );
	}

	/**
	 * Add a body class on a locked lesson so stray players can be hidden.
	 *
	 * @param array $classes Body classes.
	 * @return array
	 */
	public function body_class( $classes ) {
		if ( $this->is_locked_request() ) {
			$classes[] = 'rptl-locked-lesson';
		}
		return $classes;
	}

	/**
	 * Fallback redirect to the requested lesson after logging in.
	 *
	 * The primary mechanism is the redirect_to field written into both
	 * Tutor forms by the front-end script, which Tutor honours natively.
	 * This is a safety net for flows that drop that field (for example
	 * some social login integrations): the script also stores a
	 * short-lived cookie, and if the visitor arrives logged in with that
	 * cookie still set they are forwarded to the lesson.
	 *
	 * The cookie is cleared on read so it can never loop, and the target
	 * must resolve to a real lesson on this site, so a tampered value
	 * cannot turn this into an open redirect.
	 *
	 * @return void
	 */
	public function maybe_redirect_after_auth() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Reading a
		// non-authoritative navigation hint, not processing a form submission. The
		// value is validated below and must resolve to a real lesson on this site.
		if ( empty( $_COOKIE[ self::RETURN_COOKIE ] ) || ! is_user_logged_in() ) {
			return;
		}

		$target = esc_url_raw( wp_unslash( $_COOKIE[ self::RETURN_COOKIE ] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Path must match the one the script sets ('/'), or the cookie is never
		// actually cleared on installs where COOKIEPATH is a subdirectory.
		setcookie( self::RETURN_COOKIE, '', time() - HOUR_IN_SECONDS, '/' );
		unset( $_COOKIE[ self::RETURN_COOKIE ] );

		if ( ! $target ) {
			return;
		}

		if ( wp_parse_url( $target, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
			return;
		}

		$post_id = url_to_postid( $target );
		if ( ! $post_id || RPTL_Lessons::lesson_post_type() !== get_post_type( $post_id ) ) {
			return;
		}

		if ( untrailingslashit( $target ) === untrailingslashit( $this->current_url() ) ) {
			return;
		}

		wp_safe_redirect( $target );
		exit;
	}

	/**
	 * Current request URL.
	 *
	 * @return string
	 */
	private function current_url() {
		global $wp;
		return home_url( add_query_arg( array(), $wp->request ) );
	}
}
