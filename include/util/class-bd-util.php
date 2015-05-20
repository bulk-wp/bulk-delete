<?php
/**
 * Utility classes and functions.
 *
 * @author     Sudar
 * @package    BulkDelete\Util
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Utility class.
 *
 * Ideally most of the functions should be inside the `BulkDelete\Util` and not as static functions.
 */
class BD_Util {

	// simple login log
	const SIMPLE_LOGIN_LOG_TABLE = 'simple_login_log';

	// Meta boxes
	const VISIBLE_POST_BOXES     = 'metaboxhidden_toplevel_page_bulk-delete-posts';
	const VISIBLE_PAGE_BOXES     = 'metaboxhidden_bulk-delete_page_bulk-delete-pages';
	const VISIBLE_USER_BOXES     = 'metaboxhidden_bulk-delete_page_bulk-delete-users';

	/**
	 * Find out if Simple Login Log is installed or not
	 * http://wordpress.org/plugins/simple-login-log/
	 *
	 * @static
	 * @access public
	 * @return bool    True if plugin is installed, False otherwise
	 */
	public static function is_simple_login_log_present() {
		global $wpdb;

		if ( $wpdb->get_row( "SHOW TABLES LIKE '{$wpdb->prefix}" . self::SIMPLE_LOGIN_LOG_TABLE . "'" ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check whether the meta box in posts page is hidden or not
	 *
	 * @static
	 * @access public
	 * @param string  $box The name of the box
	 * @return bool        True if the box is hidden, False otherwise
	 */
	public static function is_posts_box_hidden( $box ) {
		$hidden_boxes = self::get_posts_hidden_boxes();
		return is_array( $hidden_boxes ) && in_array( $box, $hidden_boxes );
	}

	/**
	 * Get the list of hidden boxes in posts page
	 *
	 * @static
	 * @access public
	 * @return array The list of hidden meta boxes
	 */
	public static function get_posts_hidden_boxes() {
		$current_user = wp_get_current_user();
		return get_user_meta( $current_user->ID, self::VISIBLE_POST_BOXES, true );
	}

	/**
	 * Check whether the meta box in pages page is hidden or not
	 *
	 * @since  5.0
	 * @static
	 * @access public
	 * @param string  $box The name of the box to check
	 * @return bool        True if the box is hidden, False otherwise
	 */
	public static function is_pages_box_hidden( $box ) {
		$hidden_boxes = self::get_pages_hidden_boxes();
		return is_array( $hidden_boxes ) && in_array( $box, $hidden_boxes );
	}

	/**
	 * Get the list of hidden boxes in posts page
	 *
	 * @since  5.0
	 * @static
	 * @access public
	 * @return the array of hidden meta boxes
	 */
	public static function get_pages_hidden_boxes() {
		$current_user = wp_get_current_user();
		return get_user_meta( $current_user->ID, self::VISIBLE_PAGE_BOXES, true );
	}

	/**
	 * Check whether the meta box in users page is hidden or not
	 *
	 * @static
	 * @access public
	 * @param string  $box The name of the box to check
	 * @return bool        True if the box is hidden, False otherwise
	 */
	public static function is_users_box_hidden( $box ) {
		$hidden_boxes = self::get_users_hidden_boxes();
		return is_array( $hidden_boxes ) && in_array( $box, $hidden_boxes );
	}

	/**
	 * Get the list of hidden boxes in users page
	 *
	 * @static
	 * @access public
	 * @return array The array of hidden meta boxes
	 */
	public static function get_users_hidden_boxes() {
		$current_user = wp_get_current_user();
		return get_user_meta( $current_user->ID, self::VISIBLE_USER_BOXES, true );
	}

	/**
	 * Get the list of cron schedules
	 *
	 * @static
	 * @access public
	 * @return array The list of cron schedules
	 */
	public static function get_cron_schedules() {

		$cron_items = array();
		$cron = _get_cron_array();
		$date_format = _x( 'M j, Y @ G:i', 'Cron table date format', 'bulk-delete' );
		$i = 0;

		foreach ( $cron as $timestamp => $cronhooks ) {
			foreach ( (array) $cronhooks as $hook => $events ) {
				if ( 'do-bulk-delete-' == substr( $hook, 0, 15 ) ) {
					$cron_item = array();

					foreach ( (array) $events as $key => $event ) {
						$cron_item['timestamp'] = $timestamp;
						$cron_item['due'] = date_i18n( $date_format, $timestamp + ( get_option( 'gmt_offset' ) * 60 * 60 ) );
						$cron_item['schedule'] = $event['schedule'];
						$cron_item['type'] = $hook;
						$cron_item['args'] = $event['args'];
						$cron_item['id'] = $i;
					}

					$cron_items[ $i ] = $cron_item;
					$i++;
				}
			}
		}
		return $cron_items;
	}

	/**
	 * Generate display name from post type and status
	 *
	 * @static
	 * @param string $str
	 * @return string Label
	 */
	public static function display_post_type_status( $str ) {
		$type_status = self::split_post_type_status( $str );

		$status = $type_status['status'];
		$type   = $type_status['type'];
		$label  = '';

		switch ( $status ) {
			case 'private':
				$label = $type . ' - Private Posts';
				break;
			case 'future':
				$label = $type . ' - Scheduled Posts';
				break;
			case 'draft':
				$label = $type . ' - Draft Posts';
				break;
			case 'pending':
				$label = $type . ' - Pending Posts';
				break;
			case 'publish':
				$label = $type . ' - Published Posts';
				break;
		}

		return $label;
	}

	/**
	 * Split post type and status
	 *
	 * @static
	 * @access public
	 * @param string $str
	 * @return array
	 */
	public static function split_post_type_status( $str ) {
		$type_status = array();

		$str_arr = explode( '-', $str );

		if ( count( $str_arr ) > 1 ) {
			$type_status['status'] = end( $str_arr );
			$type_status['type']   = implode( '-', array_slice( $str_arr, 0, -1 ) );
		} else {
			$type_status['status'] = 'publish';
			$type_status['type']   = $str;
		}

		return $type_status;
	}
}

if ( ! function_exists( 'array_get' ) ) {
	/**
	 * Get a value from an array based on key.
	 * If key is present returns the value, else returns the default value
	 *
	 * @param array   $array   Array from which value has to be retrieved
	 * @param string  $key     Key, whose value to be retrieved
	 * @param string  $default Optional. Default value to be returned, if the key is not found
	 * @return mixed           Value if key is present, else the default value
	 */
	function array_get( $array, $key, $default = null ) {
		return isset( $array[ $key ] ) ? $array[ $key ] : $default;
	}
}

if ( ! function_exists( 'array_get_bool' ) ) {
	/**
	 * Get a value from an array based on key and convert it into bool.
	 *
	 * @param array   $array   Array from which value has to be retrieved
	 * @param string  $key     Key, whose value to be retrieved
	 * @param string  $default Optional. Default value to be returned, if the key is not found
	 * @return mixed           Boolean converted Value if key is present, else the default value
	 */
	function array_get_bool( $array, $key, $default = null ) {
		return filter_var( array_get( $array, $key, $default ), FILTER_VALIDATE_BOOLEAN );
	}
}

/**
 * Convert a string value into boolean, based on whether the value "True" or "False" is present.
 *
 * @since 5.5
 * @param string $string String value to compare.
 * @return bool True if string is "True", False otherwise.
 */
function bd_to_bool( $string ) {
	return filter_var( $string, FILTER_VALIDATE_BOOLEAN );
}

/**
 * Get the formatted list of allowed mime types.
 * This function was originally defined in the Bulk Delete Attachment addon.
 *
 * @since 5.5
 * @return array List of allowed mime types after formatting
 */
function bd_get_allowed_mime_types() {
	$mime_types = get_allowed_mime_types();
	sort( $mime_types );

	$processed_mime_types = array();
	$processed_mime_types['all'] = __( 'All mime types', 'bulk-delete' );

	$last_value = '';
	foreach ( $mime_types as $key => $value ) {
		$splitted = explode( '/', $value, 2 );
		$prefix = $splitted[0];

		if ( '' == $last_value || $prefix != $last_value ) {
			$processed_mime_types[ $prefix ] = __( 'All', 'bulk-delete' ) . ' ' . $prefix;
			$last_value = $prefix;
		}

		$processed_mime_types[ $value ] = $value;
	}

	return $processed_mime_types;
}
?>
