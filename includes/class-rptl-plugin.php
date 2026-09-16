<?php
/**
 * Plugin bootstrap.
 *
 * @package SbytesRestrictPreviewForTutorLMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin's pieces together.
 */
class RPTL_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var RPTL_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Access handler.
	 *
	 * @var RPTL_Access
	 */
	private $access;

	/**
	 * Get the shared instance.
	 *
	 * @return RPTL_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->access = new RPTL_Access();

		$settings = new RPTL_Settings();
		$settings->init();

		$this->access->init();

		$assets = new RPTL_Assets( $this->access );
		$assets->init();

		add_action( 'wp_footer', array( $this, 'render_modal' ) );
	}

	/**
	 * Print the login popup in the footer.
	 *
	 * @return void
	 */
	public function render_modal() {
		if ( ! RPTL_Settings::get( 'gate_previews' ) ) {
			return;
		}
		RPTL_Auth_UI::render_modal( $this->access );
	}
}
