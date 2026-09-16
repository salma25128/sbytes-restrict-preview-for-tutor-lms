<?php
/**
 * Plugin Name:       Sbytes Restrict Preview for Tutor LMS
 * Plugin URI:        https://github.com/salma25128/sbytes-restrict-preview-for-tutor-lms
 * Description:       Require visitors to log in or register before they can watch free preview lessons. Shows a tabbed login/signup form and sends them straight to the lesson afterwards, plus optional "Free Preview" badges in the course curriculum.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Salma Basuony
 * Author URI:        https://github.com/salma25128
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sbytes-restrict-preview-for-tutor-lms
 * Domain Path:       /languages
 *
 * @package SbytesRestrictPreviewForTutorLMS
 */

defined( 'ABSPATH' ) || exit;

define( 'RPTL_VERSION', '1.0.0' );
define( 'RPTL_FILE', __FILE__ );
define( 'RPTL_PATH', plugin_dir_path( __FILE__ ) );
define( 'RPTL_URL', plugin_dir_url( __FILE__ ) );
define( 'RPTL_BASENAME', plugin_basename( __FILE__ ) );

require_once RPTL_PATH . 'includes/class-rptl-settings.php';
require_once RPTL_PATH . 'includes/class-rptl-lessons.php';
require_once RPTL_PATH . 'includes/class-rptl-access.php';
require_once RPTL_PATH . 'includes/class-rptl-auth-ui.php';
require_once RPTL_PATH . 'includes/class-rptl-assets.php';
require_once RPTL_PATH . 'includes/class-rptl-plugin.php';

/**
 * Whether Tutor LMS is active.
 *
 * Everything this plugin does is built on Tutor's post types and preview
 * meta, so without it there is nothing to gate.
 *
 * @return bool
 */
function rptl_tutor_is_active() {
	return function_exists( 'tutor' ) || class_exists( 'TUTOR\Tutor' );
}

/**
 * Boot the plugin once all plugins are loaded.
 *
 * @return void
 */
function rptl_bootstrap() {
	if ( ! rptl_tutor_is_active() ) {
		add_action( 'admin_notices', 'rptl_missing_tutor_notice' );
		return;
	}

	RPTL_Plugin::instance();
}
add_action( 'plugins_loaded', 'rptl_bootstrap' );

/**
 * Admin notice shown when Tutor LMS is not available.
 *
 * @return void
 */
function rptl_missing_tutor_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	// Only show where the notice is actionable — the Plugins screen and
	// this plugin's own settings page — rather than on every admin screen.
	// It also self-dismisses as soon as Tutor LMS is activated.
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$allowed = array( 'plugins', 'plugins-network', 'settings_page_' . RPTL_Settings::PAGE_SLUG );

	if ( ! $screen || ! in_array( $screen->id, $allowed, true ) ) {
		return;
	}
	?>
	<div class="notice notice-warning">
		<p>
			<?php
			printf(
				/* translators: %s: plugin name */
				esc_html__( '%s requires Tutor LMS. Install and activate Tutor LMS, and this notice will disappear.', 'sbytes-restrict-preview-for-tutor-lms' ),
				'<strong>' . esc_html__( 'Sbytes Restrict Preview for Tutor LMS', 'sbytes-restrict-preview-for-tutor-lms' ) . '</strong>'
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Create the options row on activation.
 *
 * Deliberately stores only an empty array rather than the defaults: the
 * text defaults are translated, and writing them here would freeze the
 * activation-time language into the database. RPTL_Settings::all() merges
 * the translated defaults in at read time instead.
 *
 * add_option() leaves an existing configuration untouched, so deactivating
 * and reactivating never resets a site's settings.
 *
 * @return void
 */
function rptl_activate() {
	add_option( RPTL_Settings::OPTION_KEY, array() );
}
register_activation_hook( __FILE__, 'rptl_activate' );
