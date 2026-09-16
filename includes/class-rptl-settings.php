<?php
/**
 * Plugin settings: storage, defaults, sanitisation and the admin screen.
 *
 * @package SbytesRestrictPreviewForTutorLMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings handler.
 */
class RPTL_Settings {

	const OPTION_KEY = 'rptl_settings';
	const GROUP      = 'rptl_settings_group';
	const PAGE_SLUG  = 'rptl-settings';

	/**
	 * Cached options.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Default option values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'gate_previews'     => 1,
			'show_badges'       => 1,
			'show_register_tab' => 1,
			'accent_color'      => '#1E2A78',
			'eyebrow_text'      => __( 'Free preview', 'sbytes-restrict-preview-for-tutor-lms' ),
			'modal_heading'     => __( 'Log in to watch this lesson', 'sbytes-restrict-preview-for-tutor-lms' ),
			'modal_message'     => __( "It's free — you'll go straight to the lesson once you're in.", 'sbytes-restrict-preview-for-tutor-lms' ),
			'lesson_message'    => __( 'This lesson is free to preview — you just need an account. Log in or sign up below and you will land right back on this lesson.', 'sbytes-restrict-preview-for-tutor-lms' ),
			'lesson_badge_text' => __( 'Free Preview', 'sbytes-restrict-preview-for-tutor-lms' ),
			'account_page_url'  => '',
		);
	}

	/**
	 * Get all settings, merged over defaults.
	 *
	 * @return array
	 */
	public static function all() {
		if ( null === self::$cache ) {
			$stored = get_option( self::OPTION_KEY, array() );
			$stored = is_array( $stored ) ? $stored : array();

			// Drop empty values so the translated default applies instead.
			// Defaults are translated at read time and never written to the
			// database, so switching site language changes the wording
			// rather than leaving the original text frozen in options.
			// Clearing a field in the admin therefore restores the default.
			foreach ( $stored as $key => $value ) {
				if ( is_string( $value ) && '' === trim( $value ) ) {
					unset( $stored[ $key ] );
				}
			}

			self::$cache = wp_parse_args( $stored, self::defaults() );
		}
		return self::$cache;
	}

	/**
	 * Get a single setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback when the key is unknown.
	 * @return mixed
	 */
	public static function get( $key, $default = '' ) {
		$all = self::all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
	}

	/**
	 * Hook the admin screen.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . RPTL_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Add the settings page under Settings.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_options_page(
			__( 'Sbytes Restrict Preview for Tutor LMS', 'sbytes-restrict-preview-for-tutor-lms' ),
			__( 'Restrict Preview', 'sbytes-restrict-preview-for-tutor-lms' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Add a Settings link on the Plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function action_links( $links ) {
		$url = admin_url( 'options-general.php?page=' . self::PAGE_SLUG );

		array_unshift(
			$links,
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'sbytes-restrict-preview-for-tutor-lms' ) . '</a>'
		);

		return $links;
	}

	/**
	 * Register the option and its fields.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::GROUP,
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section(
			'rptl_behaviour',
			__( 'Behaviour', 'sbytes-restrict-preview-for-tutor-lms' ),
			array( $this, 'behaviour_intro' ),
			self::PAGE_SLUG
		);

		$this->add_checkbox( 'gate_previews', __( 'Restrict preview lessons', 'sbytes-restrict-preview-for-tutor-lms' ), 'rptl_behaviour', __( 'Require visitors to be logged in before they can view free preview lessons.', 'sbytes-restrict-preview-for-tutor-lms' ) );
		$this->add_checkbox( 'show_badges', __( 'Show curriculum badges', 'sbytes-restrict-preview-for-tutor-lms' ), 'rptl_behaviour', __( 'Add a badge to preview lessons and a free-lesson count to each curriculum section.', 'sbytes-restrict-preview-for-tutor-lms' ) );
		$this->add_checkbox( 'show_register_tab', __( 'Show the Sign Up tab', 'sbytes-restrict-preview-for-tutor-lms' ), 'rptl_behaviour', __( 'Requires "Anyone can register" under Settings → General. Hidden automatically when registration is disabled.', 'sbytes-restrict-preview-for-tutor-lms' ) );

		add_settings_section(
			'rptl_text',
			__( 'Wording', 'sbytes-restrict-preview-for-tutor-lms' ),
			'__return_false',
			self::PAGE_SLUG
		);

		$this->add_text( 'eyebrow_text', __( 'Badge label', 'sbytes-restrict-preview-for-tutor-lms' ), 'rptl_text' );
		$this->add_text( 'lesson_badge_text', __( 'Curriculum lesson badge', 'sbytes-restrict-preview-for-tutor-lms' ), 'rptl_text' );
		$this->add_text( 'modal_heading', __( 'Popup heading', 'sbytes-restrict-preview-for-tutor-lms' ), 'rptl_text' );
		$this->add_textarea( 'modal_message', __( 'Popup message', 'sbytes-restrict-preview-for-tutor-lms' ), 'rptl_text' );
		$this->add_textarea( 'lesson_message', __( 'Lesson page message', 'sbytes-restrict-preview-for-tutor-lms' ), 'rptl_text' );

		add_settings_section(
			'rptl_appearance',
			__( 'Appearance', 'sbytes-restrict-preview-for-tutor-lms' ),
			'__return_false',
			self::PAGE_SLUG
		);

		add_settings_field(
			'accent_color',
			__( 'Accent colour', 'sbytes-restrict-preview-for-tutor-lms' ),
			array( $this, 'render_color' ),
			self::PAGE_SLUG,
			'rptl_appearance',
			array( 'key' => 'accent_color' )
		);

		add_settings_field(
			'account_page_url',
			__( 'Account page URL', 'sbytes-restrict-preview-for-tutor-lms' ),
			array( $this, 'render_url' ),
			self::PAGE_SLUG,
			'rptl_appearance',
			array(
				'key'  => 'account_page_url',
				'desc' => __( 'Optional. Only used as a fallback link if the Tutor LMS login form cannot be rendered.', 'sbytes-restrict-preview-for-tutor-lms' ),
			)
		);
	}

	/**
	 * Intro copy for the behaviour section.
	 *
	 * @return void
	 */
	public function behaviour_intro() {
		echo '<p>' . esc_html__( 'Enrolled students, instructors and administrators are never affected — only logged-out visitors are asked to sign in.', 'sbytes-restrict-preview-for-tutor-lms' ) . '</p>';
	}

	/**
	 * Register a checkbox field.
	 *
	 * @param string $key     Option key.
	 * @param string $label   Field label.
	 * @param string $section Section id.
	 * @param string $desc    Description.
	 * @return void
	 */
	private function add_checkbox( $key, $label, $section, $desc = '' ) {
		add_settings_field(
			$key,
			$label,
			array( $this, 'render_checkbox' ),
			self::PAGE_SLUG,
			$section,
			array(
				'key'  => $key,
				'desc' => $desc,
			)
		);
	}

	/**
	 * Register a text field.
	 *
	 * @param string $key     Option key.
	 * @param string $label   Field label.
	 * @param string $section Section id.
	 * @return void
	 */
	private function add_text( $key, $label, $section ) {
		add_settings_field(
			$key,
			$label,
			array( $this, 'render_text' ),
			self::PAGE_SLUG,
			$section,
			array( 'key' => $key )
		);
	}

	/**
	 * Register a textarea field.
	 *
	 * @param string $key     Option key.
	 * @param string $label   Field label.
	 * @param string $section Section id.
	 * @return void
	 */
	private function add_textarea( $key, $label, $section ) {
		add_settings_field(
			$key,
			$label,
			array( $this, 'render_textarea' ),
			self::PAGE_SLUG,
			$section,
			array( 'key' => $key )
		);
	}

	/**
	 * Render a checkbox.
	 *
	 * @param array $args Field args.
	 * @return void
	 */
	public function render_checkbox( $args ) {
		$key   = $args['key'];
		$value = (int) self::get( $key, 0 );
		printf(
			'<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s /> %4$s</label>',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $key ),
			checked( 1, $value, false ),
			esc_html( isset( $args['desc'] ) ? wp_strip_all_tags( $args['desc'] ) : '' )
		);
	}

	/**
	 * Render a text input.
	 *
	 * @param array $args Field args.
	 * @return void
	 */
	public function render_text( $args ) {
		printf(
			'<input type="text" class="regular-text" name="%1$s[%2$s]" value="%3$s" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $args['key'] ),
			esc_attr( self::get( $args['key'] ) )
		);
	}

	/**
	 * Render a textarea.
	 *
	 * @param array $args Field args.
	 * @return void
	 */
	public function render_textarea( $args ) {
		printf(
			'<textarea class="large-text" rows="3" name="%1$s[%2$s]">%3$s</textarea>',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $args['key'] ),
			esc_textarea( self::get( $args['key'] ) )
		);
	}

	/**
	 * Render a colour input.
	 *
	 * @param array $args Field args.
	 * @return void
	 */
	public function render_color( $args ) {
		printf(
			'<input type="color" name="%1$s[%2$s]" value="%3$s" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $args['key'] ),
			esc_attr( self::get( $args['key'] ) )
		);
	}

	/**
	 * Render a URL input.
	 *
	 * @param array $args Field args.
	 * @return void
	 */
	public function render_url( $args ) {
		printf(
			'<input type="url" class="regular-text" name="%1$s[%2$s]" value="%3$s" placeholder="https://" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $args['key'] ),
			esc_attr( self::get( $args['key'] ) )
		);

		if ( ! empty( $args['desc'] ) ) {
			echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
		}
	}

	/**
	 * Sanitise submitted settings.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize( $input ) {
		$defaults = self::defaults();
		$clean    = array();
		$input    = is_array( $input ) ? $input : array();

		foreach ( array( 'gate_previews', 'show_badges', 'show_register_tab' ) as $key ) {
			$clean[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
		}

		foreach ( array( 'eyebrow_text', 'modal_heading', 'lesson_badge_text' ) as $key ) {
			$clean[ $key ] = isset( $input[ $key ] )
				? sanitize_text_field( $input[ $key ] )
				: $defaults[ $key ];
		}

		foreach ( array( 'modal_message', 'lesson_message' ) as $key ) {
			$clean[ $key ] = isset( $input[ $key ] )
				? sanitize_textarea_field( $input[ $key ] )
				: $defaults[ $key ];
		}

		$color = isset( $input['accent_color'] ) ? sanitize_hex_color( $input['accent_color'] ) : '';
		$clean['accent_color'] = $color ? $color : $defaults['accent_color'];

		$clean['account_page_url'] = isset( $input['account_page_url'] )
			? esc_url_raw( trim( $input['account_page_url'] ) )
			: '';

		self::$cache = null;

		return $clean;
	}

	/**
	 * Render the settings screen.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( ! get_option( 'users_can_register' ) ) : ?>
				<div class="notice notice-info inline">
					<p>
						<?php
						printf(
							/* translators: %s: link to the general settings screen */
							wp_kses(
								__( 'User registration is currently disabled, so only the Log In tab will be shown. Enable "Anyone can register" in <a href="%s">General Settings</a> to offer sign up too.', 'sbytes-restrict-preview-for-tutor-lms' ),
								array( 'a' => array( 'href' => array() ) )
							),
							esc_url( admin_url( 'options-general.php' ) )
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<form action="options.php" method="post">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
