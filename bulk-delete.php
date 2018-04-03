<?php
/**
 * Plugin Name: Bulk Delete
 * Plugin Script: bulk-delete.php
 * Plugin URI: https://bulkwp.com
 * Description: Bulk delete users and posts from selected categories, tags, post types, custom taxonomies or by post status like drafts, scheduled posts, revisions etc.
 * Version: 5.6.1
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Author: Sudar
 * Author URI: https://sudarmuthu.com/
 * Text Domain: bulk-delete
 * Domain Path: languages/
 * === RELEASE NOTES ===
 * Check readme file for full release notes.
 */
use BulkWP\BulkDelete\Core\Base\BasePage;
use BulkWP\BulkDelete\Core\Controller;
use BulkWP\BulkDelete\Core\Pages\DeletePagesPage;
use BulkWP\BulkDelete\Core\Pages\Metabox\DeletePagesByStatusMetabox;
use BulkWP\BulkDelete\Core\Posts\DeletePostsPage;
use BulkWP\BulkDelete\Core\Posts\Metabox\DeletePostsByCategoryMetabox;
use BulkWP\BulkDelete\Core\Posts\Metabox\DeletePostsByStatusMetabox;

/**
 * Copyright 2009  Sudar Muthu  (email : sudar@sudarmuthu.com)
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA.
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Main Bulk_Delete class.
 *
 * Singleton @since 5.0
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

	/**
	 * Path where translations are stored.
	 *
	 * @var string
	 */
	private $translations_path;

	/**
	 * Is the plugin is loaded?
	 *
	 * @since 6.0.0
	 *
	 * @var bool
	 */
	private $loaded = false;

	/**
	 * Controller that handles all requests and nonce checks.
	 *
	 * @var \BulkWP\BulkDelete\Core\Controller
	 */
	private $controller;

	/**
	 * List of Admin pages.
	 *
	 * @var BasePage[]
	 *
	 * @since 6.0.0
	 */
	private $admin_pages = array();

	// version
	const VERSION                   = '5.6.1';

	// page slugs
	const POSTS_PAGE_SLUG           = 'bulk-delete-posts';
	const PAGES_PAGE_SLUG           = 'bulk-delete-pages';
	const CRON_PAGE_SLUG            = 'bulk-delete-cron';
	const ADDON_PAGE_SLUG           = 'bulk-delete-addon';

	// JS constants
	const JS_HANDLE                 = 'bulk-delete';
	const CSS_HANDLE                = 'bulk-delete';

	// Cron hooks
	const CRON_HOOK_CATEGORY        = 'do-bulk-delete-cat';
	const CRON_HOOK_POST_STATUS     = 'do-bulk-delete-post-status';
	const CRON_HOOK_TAG             = 'do-bulk-delete-tag';
	const CRON_HOOK_TAXONOMY        = 'do-bulk-delete-taxonomy';
	const CRON_HOOK_POST_TYPE       = 'do-bulk-delete-post-type';
	const CRON_HOOK_CUSTOM_FIELD    = 'do-bulk-delete-custom-field';
	const CRON_HOOK_TITLE           = 'do-bulk-delete-by-title';
	const CRON_HOOK_DUPLICATE_TITLE = 'do-bulk-delete-by-duplicate-title';
	const CRON_HOOK_POST_BY_ROLE    = 'do-bulk-delete-posts-by-role';

	const CRON_HOOK_PAGES_STATUS    = 'do-bulk-delete-pages-by-status';

	// meta boxes for delete posts
	const BOX_POST_STATUS           = 'bd_by_post_status';
	const BOX_CATEGORY              = 'bd_by_category';
	const BOX_TAG                   = 'bd_by_tag';
	const BOX_TAX                   = 'bd_by_tax';
	const BOX_POST_TYPE             = 'bd_by_post_type';
	const BOX_URL                   = 'bd_by_url';
	const BOX_POST_REVISION         = 'bd_by_post_revision';
	const BOX_CUSTOM_FIELD          = 'bd_by_custom_field';
	const BOX_TITLE                 = 'bd_by_title';
	const BOX_DUPLICATE_TITLE       = 'bd_by_duplicate_title';
	const BOX_POST_FROM_TRASH       = 'bd_posts_from_trash';
	const BOX_POST_BY_ROLE          = 'bd_post_by_user_role';

	// meta boxes for delete pages
	const BOX_PAGE_STATUS           = 'bd_by_page_status';
	const BOX_PAGE_FROM_TRASH       = 'bd_pages_from_trash';

	// Settings constants
	const SETTING_OPTION_GROUP      = 'bd_settings';
	const SETTING_OPTION_NAME       = 'bd_licenses';
	const SETTING_SECTION_ID        = 'bd_license_section';

	// Transient keys
	const LICENSE_CACHE_KEY_PREFIX  = 'bd-license_';

	const MAX_SELECT2_LIMIT  = 50;

	// path variables
	// Ideally these should be constants, but because of PHP's limitations, these are static variables
	public static $PLUGIN_DIR;
	public static $PLUGIN_URL;
	public static $PLUGIN_FILE;

	// Instance variables
	public $translations;
	public $posts_page;
	public $pages_page;
	public $cron_page;
	public $addon_page;
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
	 * @uses Bulk_Delete::setup_paths() Setup the plugin paths
	 * @uses Bulk_Delete::includes() Include the required files
	 * @uses Bulk_Delete::load_textdomain() Load text domain for translation
	 * @uses Bulk_Delete::setup_actions() Setup the hooks and actions
	 *
	 * @return Bulk_Delete The one true instance of Bulk_Delete
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Bulk_Delete ) ) {
			self::$instance = new Bulk_Delete();

			self::$instance->setup_paths();
			self::$instance->includes();
		}

		return self::$instance;
	}

	/**
	 * Load the plugin if it is not loaded.
	 *
	 * This function will be invoked in the `plugins_loaded` hook.
	 */
	public function load() {
		if ( $this->loaded ) {
			return;
		}

		add_action( 'init', array( $this, 'on_init' ) );

		$this->load_dependencies();
		$this->setup_actions();

		$this->loaded = true;

		/**
		 * Bulk Delete plugin loaded.
		 *
		 * @since 6.0.0
		 */
		do_action( 'bd_loaded' );
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
		// Cloning instances of the class is forbidden
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
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'bulk-delete' ), '5.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access private
	 *
	 * @since  5.0
	 *
	 * @return void
	 */
	private function setup_paths() {
		// Plugin Folder Path
		self::$PLUGIN_DIR = plugin_dir_path( __FILE__ );

		// Plugin Folder URL
		self::$PLUGIN_URL = plugin_dir_url( __FILE__ );

		// Plugin Root File
		self::$PLUGIN_FILE = __FILE__;
	}

	/**
	 * Include required files.
	 *
	 * // TODO: Replace includes with autoloader.
	 *
	 * @access private
	 *
	 * @since  5.0
	 *
	 * @return void
	 */
	private function includes() {
		require_once self::$PLUGIN_DIR . '/include/Core/Base/BasePage.php';
		require_once self::$PLUGIN_DIR . '/include/Core/Base/MetaboxPage.php';

		require_once self::$PLUGIN_DIR . '/include/Core/Pages/DeletePagesPage.php';
		require_once self::$PLUGIN_DIR . '/include/Core/Posts/DeletePostsPage.php';

		require_once self::$PLUGIN_DIR . '/include/Core/Base/BaseMetabox.php';
		require_once self::$PLUGIN_DIR . '/include/Core/Pages/PagesMetabox.php';
		require_once self::$PLUGIN_DIR . '/include/Core/Pages/Metabox/DeletePagesByStatusMetabox.php';

		require_once self::$PLUGIN_DIR . '/include/Core/Posts/PostsMetabox.php';
		require_once self::$PLUGIN_DIR . '/include/Core/Posts/Metabox/DeletePostsByStatusMetabox.php';
		require_once self::$PLUGIN_DIR . '/include/Core/Posts/Metabox/DeletePostsByCategoryMetabox.php';

		require_once self::$PLUGIN_DIR . '/include/base/class-bd-meta-box-module.php';
		require_once self::$PLUGIN_DIR . '/include/base/users/class-bd-user-meta-box-module.php';
		require_once self::$PLUGIN_DIR . '/include/base/class-bd-base-page.php';
		require_once self::$PLUGIN_DIR . '/include/base/class-bd-page.php';

		require_once self::$PLUGIN_DIR . '/include/Core/Controller.php';

		require_once self::$PLUGIN_DIR . '/include/ui/form.php';

		require_once self::$PLUGIN_DIR . '/include/posts/class-bulk-delete-posts.php';
//		require_once self::$PLUGIN_DIR . '/include/pages/class-bulk-delete-pages.php';

		require_once self::$PLUGIN_DIR . '/include/users/class-bd-users-page.php';
		require_once self::$PLUGIN_DIR . '/include/users/modules/class-bulk-delete-users-by-user-role.php';
		require_once self::$PLUGIN_DIR . '/include/users/modules/class-bulk-delete-users-by-user-meta.php';

		require_once self::$PLUGIN_DIR . '/include/meta/class-bulk-delete-meta.php';
		require_once self::$PLUGIN_DIR . '/include/meta/class-bulk-delete-post-meta.php';
		require_once self::$PLUGIN_DIR . '/include/meta/class-bulk-delete-comment-meta.php';
		require_once self::$PLUGIN_DIR . '/include/meta/class-bulk-delete-user-meta.php';

		require_once self::$PLUGIN_DIR . '/include/misc/class-bulk-delete-misc.php';
		require_once self::$PLUGIN_DIR . '/include/misc/class-bulk-delete-jetpack-contact-form-messages.php';

		require_once self::$PLUGIN_DIR . '/include/settings/class-bd-settings-page.php';
		require_once self::$PLUGIN_DIR . '/include/settings/setting-helpers.php';
		require_once self::$PLUGIN_DIR . '/include/settings/class-bd-settings.php';

		require_once self::$PLUGIN_DIR . '/include/system-info/class-bd-system-info-page.php';

		require_once self::$PLUGIN_DIR . '/include/util/class-bd-util.php';
		require_once self::$PLUGIN_DIR . '/include/util/query.php';

		require_once self::$PLUGIN_DIR . '/include/compatibility/simple-login-log.php';
		require_once self::$PLUGIN_DIR . '/include/compatibility/the-event-calendar.php';
		require_once self::$PLUGIN_DIR . '/include/compatibility/woocommerce.php';
		require_once self::$PLUGIN_DIR . '/include/compatibility/advanced-custom-fields-pro.php';

		require_once self::$PLUGIN_DIR . '/include/deprecated/class-bulk-delete-users.php';
		require_once self::$PLUGIN_DIR . '/include/deprecated/deprecated.php';

		require_once self::$PLUGIN_DIR . '/include/addons/base/class-bd-addon.php';
		require_once self::$PLUGIN_DIR . '/include/addons/base/class-bd-base-addon.php';
		require_once self::$PLUGIN_DIR . '/include/addons/base/class-bd-scheduler-addon.php';

		require_once self::$PLUGIN_DIR . '/include/addons/addon-list.php';
		require_once self::$PLUGIN_DIR . '/include/addons/posts.php';
		require_once self::$PLUGIN_DIR . '/include/addons/pages.php';
		require_once self::$PLUGIN_DIR . '/include/addons/util.php';

		require_once self::$PLUGIN_DIR . '/include/license/class-bd-license.php';
		require_once self::$PLUGIN_DIR . '/include/license/class-bd-license-handler.php';
		require_once self::$PLUGIN_DIR . '/include/license/class-bd-edd-api-wrapper.php';

		require_once self::$PLUGIN_DIR . '/include/ui/admin-ui.php';
		require_once self::$PLUGIN_DIR . '/include/ui/class-bulk-delete-help-screen.php';
	}

	/**
	 * Triggered when the `init` hook is fired.
	 *
	 * @since 6.0.0
	 */
	public function on_init() {
		$this->load_textdomain();
	}

	/**
	 * Loads the plugin language files.
	 *
	 * @since  5.0
	 */
	private function load_textdomain() {
		load_plugin_textdomain( 'bulk-delete', false, $this->get_translations_path() );
	}

	/**
	 * Load all dependencies.
	 *
	 * @since 6.0.0
	 */
	private function load_dependencies() {
		$this->controller = new Controller();
	}

	/**
	 * Loads the plugin's actions and hooks.
	 *
	 * @access private
	 *
	 * @since  5.0
	 *
	 * @return void
	 */
	private function setup_actions() {
		add_action( 'admin_menu', array( $this, 'on_admin_menu' ) );

		/**
		 * This is Ajax hook, It's runs when user search categories or tags on bulk-delete-posts page.
		 *
		 * @since 6.0.0
		 */
		add_action( 'wp_ajax_bd_load_taxonomy_term', array( $this, 'load_taxonomy_term' ) );

		add_filter( 'bd_help_tooltip', 'bd_generate_help_tooltip', 10, 2 );

		add_filter( 'plugin_action_links', array( $this, 'filter_plugin_action_links' ), 10, 2 );

		if ( defined( 'BD_DEBUG' ) && BD_DEBUG ) {
			add_action( 'bd_after_query', array( $this, 'log_sql_query' ) );
		}
	}

	/**
	 * Adds the settings link in the Plugin page.
	 *
	 * Based on http://striderweb.com/nerdaphernalia/2008/06/wp-use-action-links/.
	 *
	 * @staticvar string $this_plugin
	 *
	 * @param array  $action_links Action Links.
	 * @param string $file         Plugin file name.
	 *
	 * @return array Modified links.
	 */
	public function filter_plugin_action_links( $action_links, $file ) {
		static $this_plugin;

		if ( ! $this_plugin ) {
			$this_plugin = plugin_basename( $this->get_plugin_file() );
		}

		if ( $file == $this_plugin ) {
			/**
			 * Filter plugin action links added by Bulk Move.
			 *
			 * @since 6.0.0
			 *
			 * @param array Plugin Links.
			 */
			$bm_action_links = apply_filters( 'bd_plugin_action_links', array() );

			if ( ! empty( $bm_action_links ) ) {
				$action_links = array_merge( $bm_action_links, $action_links );
			}
		}

		return $action_links;
	}

	/**
	 * Log SQL query used by Bulk Delete.
	 *
	 * Query is logged only when `BD_DEBUG` is set.
	 *
	 * @since 5.6
	 *
	 * @param \WP_Query $wp_query WP Query object.
	 */
	public function log_sql_query( $wp_query ) {
		$query = $wp_query->request;

		/**
		 * Bulk Delete query is getting logged.
		 *
		 * @since 5.6
		 *
		 * @param string $query Bulk Delete SQL Query.
		 */
		do_action( 'bd_log_sql_query', $query );

		error_log( 'Bulk Delete Query: ' . $query );
	}

	/**
	 * Triggered when the `admin_menu` hook is fired.
	 *
	 * Register all admin pages.
	 *
	 * @since 6.0.0
	 */
	public function on_admin_menu() {
		foreach ( $this->get_admin_pages() as $page ) {
			$page->register();
		}

		$this->load_legacy_menu();
	}

	/**
	 * Get the list of registered admin pages.
	 *
	 * @since 6.0.0
	 *
	 * @return BasePage[] List of Admin pages.
	 */
	private function get_admin_pages() {
		if ( empty( $this->admin_pages ) ) {
			$posts_page = $this->get_delete_posts_admin_page();
			$pages_page = $this->get_delete_pages_admin_page();

			$this->admin_pages[ $posts_page->get_page_slug() ] = $posts_page;
			$this->admin_pages[ $pages_page->get_page_slug() ] = $pages_page;
		}

		/**
		 * List of admin pages.
		 *
		 * @since 6.0.0
		 *
		 * @param BasePage[] List of Admin pages.
		 */
		return apply_filters( 'bd_admin_pages', $this->admin_pages );
	}

	/**
	 * Get Bulk Delete Posts admin page.
	 *
	 * @return \BulkWP\BulkDelete\Core\Posts\DeletePostsPage
	 */
	private function get_delete_posts_admin_page() {
		$posts_page = new DeletePostsPage( $this->get_plugin_file() );

		$posts_page->add_metabox( new DeletePostsByStatusMetabox() );
		$posts_page->add_metabox( new DeletePostsByCategoryMetabox() );

		return $posts_page;
	}

	/**
	 * Get Bulk Delete Pages admin page.
	 *
	 * @since 6.0.0
	 *
	 * @return DeletePagesPage Bulk Move Post admin page.
	 */
	private function get_delete_pages_admin_page() {
		$pages_page = new DeletePagesPage( $this->get_plugin_file() );

		$pages_page->add_metabox( new DeletePagesByStatusMetabox() );

		return $pages_page;
	}

	/**
	 * Add navigation menu.
	 */
	public function load_legacy_menu() {
		$this->posts_page = add_submenu_page( self::POSTS_PAGE_SLUG, __( 'Bulk Delete Posts - Old', 'bulk-delete' ), __( 'Bulk Delete Posts - Old', 'bulk-delete' ), 'delete_posts', 'bulk-delete-posts-old', array( $this, 'display_posts_page' ) );

		/**
		 * Runs just after adding all *delete* menu items to Bulk WP main menu.
		 *
		 * This action is primarily for adding extra *delete* menu items to the Bulk WP main menu.
		 *
		 * @since 5.3
		 */
		do_action( 'bd_after_primary_menus' );

		/**
		 * Runs just before adding non-action menu items to Bulk WP main menu.
		 *
		 * This action is primarily for adding extra menu items before non-action menu items to the Bulk WP main menu.
		 *
		 * @since 5.3
		 */
		do_action( 'bd_before_secondary_menus' );

		$this->cron_page  = add_submenu_page( self::POSTS_PAGE_SLUG, __( 'Bulk Delete Schedules', 'bulk-delete' ), __( 'Scheduled Jobs', 'bulk-delete' ), 'delete_posts'    , self::CRON_PAGE_SLUG , array( $this, 'display_cron_page' ) );
		$this->addon_page = add_submenu_page( self::POSTS_PAGE_SLUG, __( 'Addon Licenses'       , 'bulk-delete' ), __( 'Addon Licenses', 'bulk-delete' ), 'activate_plugins', self::ADDON_PAGE_SLUG, array( 'BD_License', 'display_addon_page' ) );

		/**
		 * Runs just after adding all menu items to Bulk WP main menu.
		 *
		 * This action is primarily for adding extra menu items to the Bulk WP main menu.
		 *
		 * @since 5.3
		 */
		do_action( 'bd_after_all_menus' );

		$admin_pages = $this->get_admin_pages();
		$pages_page  = $admin_pages['bulk-delete-pages'];

		// enqueue JavaScript
		add_action( 'admin_print_scripts-' . $this->posts_page, array( $pages_page, 'enqueue_assets' ) );

		// delete posts page
		add_action( "load-{$this->posts_page}", array( $this, 'add_delete_posts_settings_panel' ) );
		add_action( "add_meta_boxes_{$this->posts_page}", array( $this, 'add_delete_posts_meta_boxes' ) );
	}

	/**
	 * Add settings Panel for delete posts page.
	 */
	public function add_delete_posts_settings_panel() {
		/**
		 * Add contextual help for admin screens.
		 *
		 * @since 5.1
		 */
		do_action( 'bd_add_contextual_help', $this->posts_page );

		/* Trigger the add_meta_boxes hooks to allow meta boxes to be added */
		do_action( 'add_meta_boxes_' . $this->posts_page, null );

		/* Enqueue WordPress' script for handling the meta boxes */
		wp_enqueue_script( 'postbox' );
	}

	/**
	 * Ajax call back function for getting taxonomies to load select2 options.
	 *
	 * @since 6.0.0
	 */
	public function load_taxonomy_term(){
		$response = array();

		$taxonomy = sanitize_text_field( $_GET['taxonomy'] );

		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'search'     => sanitize_text_field($_GET['q']),
		) );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
			foreach ( $terms as $term ) {
				$response[] = array( absint($term->term_id), $term->name . ' (' . $term->count . __( ' Posts', 'bulk-delete' ) . ')' );
			}
		}

		echo json_encode( $response );
		die;
	}

	/**
	 * Register meta boxes for delete posts page.
	 */
	public function add_delete_posts_meta_boxes() {
		add_meta_box( self::BOX_POST_STATUS   , __( 'By Post Status'       , 'bulk-delete' ) , 'Bulk_Delete_Posts::render_delete_posts_by_status_box'    , $this->posts_page , 'advanced' );
		add_meta_box( self::BOX_CATEGORY      , __( 'By Category'          , 'bulk-delete' ) , 'Bulk_Delete_Posts::render_delete_posts_by_category_box'  , $this->posts_page , 'advanced' );
		add_meta_box( self::BOX_TAG           , __( 'By Tag'               , 'bulk-delete' ) , 'Bulk_Delete_Posts::render_delete_posts_by_tag_box'       , $this->posts_page , 'advanced' );
		add_meta_box( self::BOX_TAX           , __( 'By Custom Taxonomy'   , 'bulk-delete' ) , 'Bulk_Delete_Posts::render_delete_posts_by_taxonomy_box'  , $this->posts_page , 'advanced' );
		add_meta_box( self::BOX_POST_TYPE     , __( 'By Custom Post Type'  , 'bulk-delete' ) , 'Bulk_Delete_Posts::render_delete_posts_by_post_type_box' , $this->posts_page , 'advanced' );
		add_meta_box( self::BOX_URL           , __( 'By URL'               , 'bulk-delete' ) , 'Bulk_Delete_Posts::render_delete_posts_by_url_box'       , $this->posts_page , 'advanced' );
		add_meta_box( self::BOX_POST_REVISION , __( 'By Post Revision'     , 'bulk-delete' ) , 'Bulk_Delete_Posts::render_posts_by_revision_box'         , $this->posts_page , 'advanced' );

		/**
		 * Add meta box in delete posts page
		 * This hook can be used for adding additional meta boxes in delete posts page.
		 *
		 * @since 5.3
		 */
		do_action( 'bd_add_meta_box_for_posts' );
	}

	/**
	 * Enqueue Scripts and Styles.
	 */
	public function add_script() {
		// TODO: Remove this function.

		$admin_pages = $this->get_admin_pages();
		$pages_page  = $admin_pages['bulk-delete-pages'];
		$pages_page->enqueue_assets();
	}

	/**
	 * Show the delete posts page.
	 *
	 * @Todo Move this function to Bulk_Delete_Posts class
	 */
	public function display_posts_page() {
?>
<div class="wrap">
    <h2><?php _e( 'Bulk Delete Posts', 'bulk-delete' );?></h2>
    <?php settings_errors(); ?>

    <form method = "post">
<?php
		// nonce for bulk delete
		wp_nonce_field( 'sm-bulk-delete-posts', 'sm-bulk-delete-posts-nonce' );

		/* Used to save closed meta boxes and their order */
		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
?>
    <div id = "poststuff">
        <div id="post-body" class="metabox-holder columns-1">

            <div class="notice notice-warning">
                <p><strong><?php _e( 'WARNING: Posts deleted once cannot be retrieved back. Use with caution.', 'bulk-delete' ); ?></strong></p>
            </div>

            <div id="postbox-container-2" class="postbox-container">
                <?php do_meta_boxes( '', 'advanced', null ); ?>
            </div> <!-- #postbox-container-2 -->

        </div> <!-- #post-body -->
    </div><!-- #poststuff -->
    </form>
</div><!-- .wrap -->

<?php
		/**
		 * Runs just before displaying the footer text in the "Bulk Delete Posts" admin page.
		 *
		 * This action is primarily for adding extra content in the footer of "Bulk Delete Posts" admin page.
		 *
		 * @since 5.0
		 */
		do_action( 'bd_admin_footer_posts_page' );
	}

	/**
	 * Display the schedule page.
	 */
	public function display_cron_page() {
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . WPINC . '/class-wp-list-table.php';
		}

		if ( ! class_exists( 'Cron_List_Table' ) ) {
			require_once self::$PLUGIN_DIR . '/include/cron/class-cron-list-table.php';
		}

		// Prepare Table of elements
		$cron_list_table = new Cron_List_Table();
		$cron_list_table->prepare_items();
?>
    <div class="wrap">
        <h2><?php _e( 'Bulk Delete Schedules', 'bulk-delete' );?></h2>
        <?php settings_errors(); ?>
<?php
		// Table of elements
		$cron_list_table->display();
		bd_display_available_addon_list();
?>
    </div>
<?php
		/**
		 * Runs just before displaying the footer text in the "Schedules" admin page.
		 *
		 * This action is primarily for adding extra content in the footer of "Schedules" admin page.
		 *
		 * @since 5.0
		 */
		do_action( 'bd_admin_footer_cron_page' );
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
	 * Set path to main plugin file.
	 *
	 * @param string $plugin_file Path to main plugin file.
	 */
	public function set_plugin_file( $plugin_file ) {
		$this->plugin_file       = $plugin_file;
		$this->translations_path = dirname( plugin_basename( $this->get_plugin_file() ) ) . '/languages/';
	}

	/**
	 * Get path to translations.
	 *
	 * @return string Translations path.
	 */
	public function get_translations_path() {
		return $this->translations_path;
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
 * Load Bulk Delete plugin.
 *
 * @since 6.0.0
 */
function load_bulk_delete() {
	$bulk_delete = BULK_DELETE();
	$bulk_delete->set_plugin_file( __FILE__ );

	add_action( 'plugins_loaded', array( $bulk_delete, 'load' ), 101 );
}

load_bulk_delete();
