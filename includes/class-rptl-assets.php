<?php
/**
 * Front-end asset loading.
 *
 * @package SbytesRestrictPreviewForTutorLMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and enqueues the front-end stylesheet and script.
 */
class RPTL_Assets {

	/**
	 * Access handler.
	 *
	 * @var RPTL_Access
	 */
	private $access;

	/**
	 * Constructor.
	 *
	 * @param RPTL_Access $access Access handler.
	 */
	public function __construct( RPTL_Access $access ) {
		$this->access = $access;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue assets only on pages that can actually use them.
	 *
	 * @return void
	 */
	public function enqueue() {
		if ( ! $this->should_load() ) {
			return;
		}

		wp_enqueue_style(
			'rptl-frontend',
			RPTL_URL . 'assets/css/restrict-preview.css',
			array(),
			RPTL_VERSION
		);

		wp_add_inline_style( 'rptl-frontend', $this->accent_css() );

		wp_enqueue_script(
			'rptl-frontend',
			RPTL_URL . 'assets/js/restrict-preview.js',
			array(),
			RPTL_VERSION,
			true
		);

		wp_localize_script( 'rptl-frontend', 'rptlData', $this->script_data() );
	}

	/**
	 * Whether the current request needs the assets.
	 *
	 * @return bool
	 */
	private function should_load() {
		return RPTL_Lessons::is_relevant_context();
	}

	/**
	 * Inline custom property for the configured accent colour.
	 *
	 * @return string
	 */
	private function accent_css() {
		$accent = sanitize_hex_color( RPTL_Settings::get( 'accent_color' ) );
		if ( ! $accent ) {
			return '';
		}
		return '.rptl-card, .rptl-modal { --rptl-accent: ' . $accent . '; }';
	}

	/**
	 * Data handed to the front-end script.
	 *
	 * @return array
	 */
	private function script_data() {
		$units       = array();
		$preview_all = array();

		if ( is_singular( RPTL_Lessons::course_post_type() ) ) {
			$units = RPTL_Lessons::course_units( get_the_ID() );

			foreach ( $units as $unit ) {
				foreach ( $unit['urls'] as $url ) {
					$preview_all[] = $url;
				}
			}
		}

		return array(
			'isLoggedIn'  => $this->access->user_may_view(),
			'showBadges'  => (bool) RPTL_Settings::get( 'show_badges' ),
			'previewUrls' => array_values( array_unique( $preview_all ) ),
			'courseUnits' => array_values( $units ),
			'cookieName'  => RPTL_Access::RETURN_COOKIE,
			'i18n'        => array(
				'lessonBadge'  => RPTL_Settings::get( 'lesson_badge_text' ),
				/* translators: %d: number of free lessons. */
				'unitBadgeOne' => __( '%d Free Lesson', 'sbytes-restrict-preview-for-tutor-lms' ),
				/* translators: %d: number of free lessons. */
				'unitBadgeMany' => __( '%d Free Lessons', 'sbytes-restrict-preview-for-tutor-lms' ),
				'phoneLabel'   => __( 'Phone Number', 'sbytes-restrict-preview-for-tutor-lms' ),
				'mobileLabel'  => __( 'Mobile Number', 'sbytes-restrict-preview-for-tutor-lms' ),
			),
		);
	}
}
