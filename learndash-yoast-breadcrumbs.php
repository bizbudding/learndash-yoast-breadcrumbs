<?php

/**
 * Plugin Name:     LearnDash Yoast SEO Breadcrumbs
 * Plugin URI:      https://bizbudding.com
 * Description:     Use the correct LearnDash Course > Lesson > Topic hierarchy in Yoast SEO breadcrumbs.
 * Version:         1.0.0
 *
 * Author:          BizBudding, Mike Hemberger
 * Author URI:      https://bizbudding.com
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main LD_Yoast_Breadcrumbs Class.
 *
 * @since 1.0.0
 */
final class LD_Yoast_Breadcrumbs {

	/**
	 * @var LD_Yoast_Breadcrumbs The one true LD_Yoast_Breadcrumbs
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Main LD_Yoast_Breadcrumbs Instance.
	 *
	 * Insures that only one instance of LD_Yoast_Breadcrumbs exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   1.0.0
	 * @static  var array $instance
	 * @uses    LD_Yoast_Breadcrumbs::setup_constants() Setup the constants needed.
	 * @uses    LD_Yoast_Breadcrumbs::includes() Include the required files.
	 * @uses    LD_Yoast_Breadcrumbs::setup() Activate, deactivate, etc.
	 * @see     LD_Yoast_Breadcrumbs()
	 * @return  object | LD_Yoast_Breadcrumbs The one true LD_Yoast_Breadcrumbs
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new LD_Yoast_Breadcrumbs;
			// Methods
			self::$instance->setup_constants();
			self::$instance->run();
		}
		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since   1.0.0
	 * @access  protected
	 * @return  void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'textdomain' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since   1.0.0
	 * @access  protected
	 * @return  void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'textdomain' ), '1.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access  private
	 * @since   1.0.0
	 * @return  void
	 */
	private function setup_constants() {

		// Plugin version.
		if ( ! defined( 'LD_YOAST_BREADCRUMBS_VERSION' ) ) {
			define( 'LD_YOAST_BREADCRUMBS_VERSION', '1.0.0' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'LD_YOAST_BREADCRUMBS_PLUGIN_DIR' ) ) {
			define( 'LD_YOAST_BREADCRUMBS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Includes Path
		if ( ! defined( 'LD_YOAST_BREADCRUMBS_INCLUDES_DIR' ) ) {
			define( 'LD_YOAST_BREADCRUMBS_INCLUDES_DIR', LD_YOAST_BREADCRUMBS_PLUGIN_DIR . 'includes/' );
		}

		// Plugin Folder URL.
		if ( ! defined( 'LD_YOAST_BREADCRUMBS_PLUGIN_URL' ) ) {
			define( 'LD_YOAST_BREADCRUMBS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin Root File.
		if ( ! defined( 'LD_YOAST_BREADCRUMBS_PLUGIN_FILE' ) ) {
			define( 'LD_YOAST_BREADCRUMBS_PLUGIN_FILE', __FILE__ );
		}

		// Plugin Base Name.
		if ( ! defined( 'LD_YOAST_BREADCRUMBS_BASENAME' ) ) {
			define( 'LD_YOAST_BREADCRUMBS_BASENAME', dirname( plugin_basename( __FILE__ ) ) );
		}

	}

	/**
	 * Run this thing.
	 *
	 * @return  void
	 */
	public function run() {

		add_action( 'admin_init',             array( $this, 'updater' ) );
		add_filter( 'wpseo_breadcrumb_links', array( $this, 'breadcrumb_links' ) );

		register_activation_hook(   __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
	}

	/**
	 * Setup the updater.
	 *
	 * @uses    https://github.com/YahnisElsts/plugin-update-checker/
	 *
	 * @return  void
	 */
	public function updater() {
		if ( ! class_exists( 'Puc_v4_Factory' ) ) {
			require_once LD_YOAST_BREADCRUMBS_INCLUDES_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php'; // 4.4
		}
		$updater = Puc_v4_Factory::buildUpdateChecker( 'https://github.com/bizbudding/learndash-yoast-breadcrumbs', __FILE__, 'learndash-yoast-breadcrumbs' );
	}

	/**
	 * Convert Yoast Breadcrumbs to follow LearnDash hierarchy.
	 *
	 * @param   array  $link  The breadcrumbs array.
	 *
	 * @return  array  The modified breadcrumbs array.
	 */
	public function breadcrumb_links( $links ) {

		// Bail if not a single learndash post.
		if ( ! is_singular( array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-quiz', 'sfwd-topic', 'sfwd-certificates' ) ) ) {
			return $links;
		}

		$items = array();

		// Don't add course link to the course page.
		if ( ! is_singular( 'sfwd-courses' ) ) {

			// Add course link.
			$course_id = learndash_get_course_id( get_the_ID() );
			if ( $course_id ) {
				$items[] = array( 'id' => $course_id );
			}

		}

		// Don't add lesson link to the lesson page.
		if ( ! is_singular( 'sfwd-lessons' ) ) {

			// Add lesson link.
			$lesson_id = learndash_get_lesson_id( get_the_ID() );
			if ( $lesson_id ) {
				$items[] = array( 'id' => $lesson_id );
			}

		}

		// Bail if no items.
		if ( ! $items ) {
			return $links;
		}

		// Remove last item, and store it.
		$last = array_pop( $links );

		// Merge existing with LearnDash items.
		$links = array_merge( $links, $items );

		// Add the last item back.
		$links[] = $last;

		return $links;
	}

	/**
	 * Flush permalinks just incase they get in the way of breadcrumb links.
	 *
	 * @return  void
	 */
	public function activate() {
		flush_rewrite_rules();
	}

}

/**
 * The main function for that returns LD_Yoast_Breadcrumbs
 *
 * @since 1.0.0
 *
 * @return object|LD_Yoast_Breadcrumbs The one true LD_Yoast_Breadcrumbs Instance.
 */
function ld_yoast_breadcrumbs() {
	return LD_Yoast_Breadcrumbs::instance();
}

// Get LD_Yoast_Breadcrumbs Running.
ld_yoast_breadcrumbs();
