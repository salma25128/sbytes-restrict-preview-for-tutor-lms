<?php
/**
 * Lesson and curriculum helpers.
 *
 * @package SbytesRestrictPreviewForTutorLMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads Tutor LMS preview data.
 */
class RPTL_Lessons {

	/**
	 * Post meta key Tutor LMS uses to flag a lesson as a free preview.
	 *
	 * Mirrors TUTOR\Lesson::PREVIEW_META_KEY.
	 */
	const PREVIEW_META = '_is_preview';

	/**
	 * Whether a lesson is marked as a free preview.
	 *
	 * Tutor stores this as an integer, and checks it the same way in
	 * CourseModel::has_course_content_access(). Comparing against the
	 * string 'yes' silently matches nothing.
	 *
	 * @param int $lesson_id Lesson post ID.
	 * @return bool
	 */
	public static function is_preview( $lesson_id ) {
		return (bool) (int) get_post_meta( $lesson_id, self::PREVIEW_META, true );
	}

	/**
	 * Tutor's lesson post type, with a safe fallback.
	 *
	 * @return string
	 */
	public static function lesson_post_type() {
		if ( function_exists( 'tutor' ) && isset( tutor()->lesson_post_type ) ) {
			return tutor()->lesson_post_type;
		}
		return 'lesson';
	}

	/**
	 * Tutor's course post type, with a safe fallback.
	 *
	 * @return string
	 */
	public static function course_post_type() {
		if ( function_exists( 'tutor' ) && isset( tutor()->course_post_type ) ) {
			return tutor()->course_post_type;
		}
		return 'courses';
	}

	/**
	 * Whether the current request is a single lesson.
	 *
	 * @return int Lesson ID, or 0.
	 */
	public static function current_lesson_id() {
		return is_singular( self::lesson_post_type() ) ? (int) get_the_ID() : 0;
	}

	/**
	 * Post meta key Tutor LMS uses to mark a whole course as public.
	 *
	 * Mirrors TUTOR\Course::PUBLIC_COURSE_META.
	 */
	const PUBLIC_COURSE_META = '_tutor_is_public_course';

	/**
	 * Whether the course a lesson belongs to is marked public in Tutor.
	 *
	 * A public course is one the site owner has deliberately opened to
	 * everyone: Tutor's own has_course_content_access() grants access to
	 * every lesson in it without enrolment or login. Gating a lesson in
	 * such a course would override an explicit decision by the site
	 * owner, so preview lessons in public courses are left alone.
	 *
	 * @param int $lesson_id Lesson post ID.
	 * @return bool
	 */
	public static function is_public_course_lesson( $lesson_id ) {
		$course_id = self::course_id_for_lesson( $lesson_id );
		if ( ! $course_id ) {
			return false;
		}
		return 'yes' === get_post_meta( $course_id, self::PUBLIC_COURSE_META, true );
	}

	/**
	 * Resolve the course a lesson belongs to.
	 *
	 * Prefers Tutor's own helper, falling back to walking the
	 * lesson -> topic -> course post parent chain.
	 *
	 * @param int $lesson_id Lesson post ID.
	 * @return int Course ID, or 0 if it cannot be determined.
	 */
	public static function course_id_for_lesson( $lesson_id ) {
		if ( function_exists( 'tutor_utils' ) && method_exists( tutor_utils(), 'get_course_id_by_subcontent' ) ) {
			$course_id = (int) tutor_utils()->get_course_id_by_subcontent( $lesson_id );
			if ( $course_id ) {
				return $course_id;
			}
		}

		$topic_id = (int) wp_get_post_parent_id( $lesson_id );
		return $topic_id ? (int) wp_get_post_parent_id( $topic_id ) : 0;
	}

	/**
	 * Tutor's topic post type, with a safe fallback.
	 *
	 * @return string
	 */
	public static function topic_post_type() {
		if ( function_exists( 'tutor' ) && isset( tutor()->topics_post_type ) ) {
			return tutor()->topics_post_type;
		}
		return 'topics';
	}

	/**
	 * Whether the plugin has anything to do on the current request.
	 *
	 * Used by both the asset loader and the popup renderer so the two can
	 * never disagree: printing the popup on a page whose stylesheet has not
	 * loaded would drop two unstyled Tutor forms into the footer.
	 *
	 * @return bool
	 */
	public static function is_relevant_context() {
		$relevant = is_singular( self::course_post_type() ) || is_singular( self::lesson_post_type() );

		/**
		 * Filters whether the plugin runs on the current request.
		 *
		 * Useful when a curriculum is rendered somewhere unusual, such as a
		 * page builder template or a custom archive.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $relevant Whether the plugin should run here.
		 */
		return (bool) apply_filters( 'rptl_is_relevant_context', $relevant );
	}

	/**
	 * Preview lessons for the current course, grouped by curriculum unit.
	 *
	 * Built from the real preview meta rather than by reading the rendered
	 * page, because many themes and course setups never print the word
	 * "Free" in the curriculum at all — matching on that text finds nothing
	 * on those courses.
	 *
	 * Every unit is included, even ones with no preview lessons, so the
	 * array index lines up with the units rendered in the DOM.
	 *
	 * @param int $course_id Course post ID.
	 * @return array[] List of arrays with topicId, count and urls keys.
	 */
	public static function course_units( $course_id ) {
		$course_id = (int) $course_id;
		if ( ! $course_id ) {
			return array();
		}

		$cache_key = 'rptl_units_' . $course_id;
		$cached    = wp_cache_get( $cache_key, 'rptl' );
		if ( false !== $cached ) {
			return $cached;
		}

		// A public course is not gated, so it gets no badges either —
		// advertising "free preview" there would be meaningless when every
		// lesson is already open.
		if ( 'yes' === get_post_meta( $course_id, self::PUBLIC_COURSE_META, true ) ) {
			wp_cache_set( $cache_key, array(), 'rptl', MINUTE_IN_SECONDS * 10 );
			return array();
		}

		$topics = get_posts(
			array(
				'post_type'              => self::topic_post_type(),
				'post_parent'            => $course_id,
				'posts_per_page'         => 100,
				'fields'                 => 'ids',
				'orderby'                => 'menu_order',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			)
		);

		$units = array();

		foreach ( $topics as $topic_id ) {
			$lessons = get_posts(
				array(
					'post_type'              => self::lesson_post_type(),
					'post_parent'            => $topic_id,
					'posts_per_page'         => 500,
					'fields'                 => 'ids',
					'orderby'                => 'menu_order',
					'order'                  => 'ASC',
					'no_found_rows'          => true,
					'update_post_term_cache' => false,
				)
			);

			$urls = array();
			foreach ( $lessons as $lesson_id ) {
				if ( self::is_preview( $lesson_id ) ) {
					$urls[] = get_permalink( $lesson_id );
				}
			}

			$units[] = array(
				'topicId' => (int) $topic_id,
				'count'   => count( $urls ),
				'urls'    => $urls,
			);
		}

		wp_cache_set( $cache_key, $units, 'rptl', MINUTE_IN_SECONDS * 10 );

		return $units;
	}
}
