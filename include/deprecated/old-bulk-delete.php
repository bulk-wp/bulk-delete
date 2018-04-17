<?php
/**
 * Old version of Bulk_Delete.
 *
 * This class is deprecated since 6.0.0. But included here for backward compatibility.
 * Don't depend on functionality from this class.
 */

use BulkWP\BulkDelete\Core\BulkDelete;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Main Bulk_Delete class.
 *
 * @property null|string translations
 * @property null|string posts_page
 * @property null|string pages_page
 *
 * @since 5.0 Singleton
 * @since 6.0.0 Deprecated.
 */
final class Bulk_Delete {
	/**
	 * The one true Bulk_Delete instance.
	 *
	 * @var Bulk_Delete
	 *
	 * @since 5.0
	 */
	private static $instance;

	/**
	 * Path to the main plugin file.
	 *
	 * @var string
	 */
	private $plugin_file;

	// Deprecated constants. They are defined here for backward compatibility.
	const VERSION = '5.6.1';

	const JS_HANDLE = 'bulk-delete';

	// Cron hooks.
	const CRON_HOOK_PAGES_STATUS = 'do-bulk-delete-pages-by-status'; // used in Scheduler For Deleting Pages by Post status add-on v0.6.

	const CRON_HOOK_POST_STATUS = 'do-bulk-delete-post-status';      // used in Scheduler For Deleting Posts by Post status add-on v0.6.
	const CRON_HOOK_CATEGORY    = 'do-bulk-delete-cat';              // used in Scheduler For Deleting Posts by Category add-on v0.6.
	const CRON_HOOK_TAG         = 'do-bulk-delete-tag';              // used in Scheduler For Deleting Posts by Tag add-on v0.6.
	const CRON_HOOK_TAXONOMY    = 'do-bulk-delete-taxonomy';         // used in Scheduler For Deleting Posts by Taxonomy add-on v0.6.
	const CRON_HOOK_POST_TYPE   = 'do-bulk-delete-post-type';        // used in Scheduler For Deleting Posts by Post Type add-on v0.6.


	// page slugs
	const POSTS_PAGE_SLUG           = 'bulk-delete-posts';
	const PAGES_PAGE_SLUG           = 'bulk-delete-pages';
	const CRON_PAGE_SLUG            = 'bulk-delete-cron';
	const ADDON_PAGE_SLUG           = 'bulk-delete-addon';

	// Cron hooks
	const CRON_HOOK_CUSTOM_FIELD    = 'do-bulk-delete-custom-field';
	const CRON_HOOK_TITLE           = 'do-bulk-delete-by-title';
	const CRON_HOOK_DUPLICATE_TITLE = 'do-bulk-delete-by-duplicate-title';
	const CRON_HOOK_POST_BY_ROLE    = 'do-bulk-delete-posts-by-role';

	// meta boxes for delete posts
	const BOX_CUSTOM_FIELD          = 'bd_by_custom_field';
	const BOX_TITLE                 = 'bd_by_title';
	const BOX_DUPLICATE_TITLE       = 'bd_by_duplicate_title';
	const BOX_POST_FROM_TRASH       = 'bd_posts_from_trash';
	const BOX_POST_BY_ROLE          = 'bd_post_by_user_role';

	// meta boxes for delete pages
	const BOX_PAGE_FROM_TRASH       = 'bd_pages_from_trash';

	// Settings constants
	const SETTING_OPTION_GROUP      = 'bd_settings';
	const SETTING_OPTION_NAME       = 'bd_licenses';
	const SETTING_SECTION_ID        = 'bd_license_section';

	// Transient keys
	const LICENSE_CACHE_KEY_PREFIX  = 'bd-license_';

	// path variables
	// Ideally these should be constants, but because of PHP's limitations, these are static variables
	public static $PLUGIN_DIR;
	public static $PLUGIN_FILE;

	// Instance variables
	public $settings_page;
	public $meta_page;
	public $misc_page;
	public $display_activate_license_form = false;

	// Deprecated.
	// Will be removed in v6.0
	const CRON_HOOK_USER_ROLE = 'do-bulk-delete-users-by-role';
	public $users_page;

	/**
	 * Main Bulk_Delete Instance.
	 *
	 * Insures that only one instance of Bulk_Delete exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 5.0
	 * @static
	 * @staticvar array $instance
	 *
	 * @see BULK_DELETE()
	 *
	 * @return Bulk_Delete The one true instance of Bulk_Delete
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Bulk_Delete ) ) {
			self::$instance = new Bulk_Delete();
		}

		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since  5.0
	 * @access protected
	 *
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'bulk-delete' ), '5.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since  5.0
	 * @access protected
	 *
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'bulk-delete' ), '5.0' );
	}

	/**
	 * Set path to main plugin file.
	 *
	 * @param string $plugin_file Path to main plugin file.
	 */
	public function set_plugin_file( $plugin_file ) {
		$this->plugin_file = $plugin_file;

		self::$PLUGIN_DIR  = plugin_dir_path( $plugin_file );
		self::$PLUGIN_FILE = $plugin_file;
	}

	/**
	 * Get path to main plugin file.
	 *
	 * @return string Plugin file.
	 */
	public function get_plugin_file() {
		return $this->plugin_file;
	}

	/**
	 * Monkey patch the old `add_script` method.
	 *
	 * @since 6.0.0
	 */
	public function add_script() {
		$bd = BulkDelete::get_instance();

		$primary_pages = $bd->get_primary_pages();
		$post_page     = $primary_pages[ self::POSTS_PAGE_SLUG ];

		$post_page->enqueue_assets();
	}

	/**
	 * Provide access to old public fields through Magic method.
	 *
	 * This function is added to provide backward compatibility and will be eventually removed from future versions.
	 *
	 * @since 6.0.0
	 *
	 * @param string $name Field.
	 *
	 * @return null|string
	 */
	public function __get( $name ) {
		$new_bd = BulkDelete::get_instance();

		switch ( $name ) {
			case 'translations':
				return $new_bd->get_translations_path();
				break;

			case 'posts_page':
				return $new_bd->get_page_hook_suffix( 'bulk-delete-posts' );
				break;

			case 'pages_page':
				return $new_bd->get_page_hook_suffix( 'bulk-delete-pages' );
				break;
		}

		$trace = debug_backtrace();
		trigger_error( 'Undefined property via __get(): ' . $name . ' in ' . $trace[0]['file'] . ' on line ' . $trace[0]['line'], E_USER_NOTICE );

		return null;
	}
}

/**
 * The main function responsible for returning the one true Bulk_Delete
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: `<?php $bulk_delete = BULK_DELETE(); ?>`
 *
 * @since 5.0
 *
 * @return Bulk_Delete The one true Bulk_Delete Instance
 */
function BULK_DELETE() {
	return Bulk_Delete::get_instance();
}

/**
 * Setup old Bulk_Delete class for backward compatibility reasons.
 *
 * Eventually this will be removed.
 *
 * @since 6.0.0
 *
 * @param string $plugin_file Main plugin file.
 */
function bd_setup_backward_compatibility( $plugin_file ) {
	$bd = BULK_DELETE();
	$bd->set_plugin_file( $plugin_file );
}
add_action( 'bd_loaded', 'bd_setup_backward_compatibility' );