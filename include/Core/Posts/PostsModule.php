<?php
namespace BulkWP\BulkDelete\Core\Posts;

use BulkWP\BulkDelete\Core\Base\BaseModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Module for deleting posts.
 *
 * @since 6.0.0
 */
abstract class PostsModule extends BaseModule {
	/**
	 * Build query params for WP_Query by using delete options.
	 *
	 * Return an empty query array to short-circuit deletion.
	 *
	 * @param array $options Delete options.
	 *
	 * @return array Query.
	 */
	abstract protected function build_query( $options );

	/**
	 * Item Type. Possible values 'posts', 'pages', 'users' etc.
	 *
	 * @var string
	 */
	protected $item_type = 'posts';

	/**
	 * Handle common filters.
	 *
	 * @param array $request Request array.
	 *
	 * @return array User options.
	 */
	protected function parse_common_filters( $request ) {
		$options = array();

		$options['restrict']     = bd_array_get_bool( $request, 'smbd_' . $this->field_slug . '_restrict', false );
		$options['limit_to']     = absint( bd_array_get( $request, 'smbd_' . $this->field_slug . '_limit_to', 0 ) );
		$options['force_delete'] = bd_array_get_bool( $request, 'smbd_' . $this->field_slug . '_force_delete', false );

		$options['date_op'] = bd_array_get( $request, 'smbd_' . $this->field_slug . '_op' );
		$options['days']    = absint( bd_array_get( $request, 'smbd_' . $this->field_slug . '_days' ) );

		return $options;
	}

	/**
	 * Filter JS Array and add pro hooks.
	 *
	 * @since 5.5
	 *
	 * @param array $js_array JavaScript Array.
	 *
	 * @return array Modified JavaScript Array
	 */
	public function filter_js_array( $js_array ) {
		$js_array['msg']['deletePostsWarning'] = __( 'Are you sure you want to delete all the posts based on the selected option?', 'bulk-delete' );
		$js_array['msg']['selectPostOption']   = __( 'Please select posts from at least one option', 'bulk-delete' );

		$js_array['validators']['delete_posts_by_category'] = 'validateSelect2';
		$js_array['error_msg']['delete_posts_by_category']  = 'selectCategory';
		$js_array['msg']['selectCategory']                  = __( 'Please select at least one category', 'bulk-delete' );

		$js_array['validators']['delete_posts_by_tag']     = 'validateSelect2';
		$js_array['error_msg']['delete_posts_by_category'] = 'selectTag';
		$js_array['msg']['selectTag']                      = __( 'Please select at least one tag', 'bulk-delete' );

		$js_array['validators']['delete_posts_by_url'] = 'validateUrl';
		$js_array['error_msg']['delete_posts_by_url']  = 'enterUrl';
		$js_array['msg']['enterUrl']                   = __( 'Please enter at least one post url', 'bulk-delete' );

		$js_array['dt_iterators'][] = '_cats';
		$js_array['dt_iterators'][] = '_tags';
		$js_array['dt_iterators'][] = '_taxs';
		$js_array['dt_iterators'][] = '_post_status';

		return $js_array;
	}

	/**
	 * Perform the deletion.
	 *
	 * @param array $options Array of Delete options.
	 *
	 * @return int Number of items that were deleted.
	 */
	protected function do_delete( $options ) {
		$query = $this->build_query( $options );

		if ( empty( $query ) ) {
			// Short circuit deletion, if nothing needs to be deleted.
			return 0;
		}

		return $this->delete_posts_from_query( $query, $options );
	}

	/**
	 * Build the query using query params and then Delete posts.
	 *
	 * @param array $query   Params for WP Query.
	 * @param array $options Delete Options.
	 *
	 * @return int Number of posts deleted.
	 */
	protected function delete_posts_from_query( $query, $options ) {
		$query        = bd_build_query_options( $options, $query );
		$post_ids     = bd_query( $query );
		$force_delete = isset( $options['force_delete'] ) ? $options['force_delete'] : false;

		return $this->delete_posts_by_id( $post_ids, $force_delete );
	}

	/**
	 * Render the "private post" setting fields.
	 */
	protected function render_private_post_settings() {
		bd_render_private_post_settings( $this->field_slug );
	}

	/**
	 * Render Category dropdown.
	 */
	protected function render_category_dropdown() {
		$categories = $this->get_categories();
		?>

		<select name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_category[]" data-placeholder="<?php _e( 'Select Categories', 'bulk-delete' ); ?>"
				class="<?php echo sanitize_html_class( $this->enable_ajax_if_needed_to_dropdown_class_name( count( $categories ), 'select2-taxonomy' ) ); ?>"
				data-taxonomy="category" multiple>

			<option value="all">
				<?php _e( 'All Categories', 'bulk-delete' ); ?>
			</option>

			<?php foreach ( $categories as $category ) : ?>
				<option value="<?php echo absint( $category->cat_ID ); ?>">
					<?php echo esc_html( $category->cat_name ), ' (', absint( $category->count ), ' ', __( 'Posts', 'bulk-delete' ), ')'; ?>
				</option>
			<?php endforeach; ?>

		</select>
		<?php
	}

	/**
	 * Render Tags dropdown.
	 */
	protected function render_tags_dropdown() {
		$tags = $this->get_tags();
		?>

		<select name="smbd_<?php echo esc_attr( $this->field_slug ); ?>[]" data-placeholder="<?php _e( 'Select Tags', 'bulk-delete' ); ?>"
				class="<?php echo sanitize_html_class( $this->enable_ajax_if_needed_to_dropdown_class_name( count( $tags ), 'select2-taxonomy' ) ); ?>"
				data-taxonomy="post_tag" multiple>

			<option value="all">
				<?php _e( 'All Tags', 'bulk-delete' ); ?>
			</option>

			<?php foreach ( $tags as $tag ) : ?>
				<option value="<?php echo absint( $tag->term_id ); ?>">
					<?php echo esc_html( $tag->name ), ' (', absint( $tag->count ), ' ', __( 'Posts', 'bulk-delete' ), ')'; ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render Sticky Posts dropdown.
	 */
	protected function render_sticky_post_dropdown() {
		$posts = $this->get_sticky_posts();
		?>
		<table class="optiontable">
			<tr>
				<td scope="row">
					<input type="checkbox" class="smbd_sticky_post_options" name="smbd_<?php echo esc_attr( $this->field_slug ); ?>[]" value="All">
					<label>All</label>
				</td>
			</tr>
			<?php
			foreach ( $posts as $post ) :
				$user = get_userdata( $post->post_author );
				?>
			<tr>
				<td scope="row">
				<input type="checkbox" class="smbd_sticky_post_options" name="smbd_<?php echo esc_attr( $this->field_slug ); ?>[]" value="<?php echo absint( $post->ID ); ?>">
				<label><?php echo esc_html( $post->post_title . ' Published by ' . $user->display_name . ' on ' . $post->post_date ); ?></label>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}

	/**
	 * Get the list of sticky posts.
	 *
	 * @return array List of sticky posts.
	 */
	protected function get_sticky_posts() {
		$posts = get_posts( array( 'post__in' => get_option( 'sticky_posts' ) ) );

		return $posts;
	}

	/**
	 * Get the list of categories.
	 *
	 * @return array List of categories.
	 */
	protected function get_categories() {
		$enhanced_select_threshold = $this->get_enhanced_select_threshold();

		$categories = get_categories(
			array(
				'hide_empty' => false,
				'number'     => $enhanced_select_threshold,
			)
		);

		return $categories;
	}

	/**
	 * Are tags present in this WordPress installation?
	 *
	 * Only one tag is retrieved to check if tags are present for performance reasons.
	 *
	 * @return bool True if tags are present, False otherwise.
	 */
	protected function are_tags_present() {
		$tags = $this->get_tags( 1 );

		return ( count( $tags ) > 0 );
	}

	/**
	 * Are sticky post present in this WordPress?
	 *
	 * Only one post is retrieved to check if stick post are present for performance reasons.
	 *
	 * @return bool True if posts are present, False otherwise.
	 */
	protected function are_sticky_post_present() {
		$sticky_post_ids = get_option( 'sticky_posts' );

		if ( ! is_array( $sticky_post_ids ) ) {
			return false;
		}

		return ( count( $sticky_post_ids ) > 0 );
	}

	/**
	 * Get the list of tags.
	 *
	 * @param int $max_count The maximum number of tags to be returned (Optional). Default 0.
	 *                       If 0 then the maximum number of tags specified in `get_enhanced_select_threshold` will be returned.
	 *
	 * @return array List of tags.
	 */
	protected function get_tags( $max_count = 0 ) {
		if ( absint( $max_count ) === 0 ) {
			$max_count = $this->get_enhanced_select_threshold();
		}

		$tags = get_tags(
			array(
				'hide_empty' => false,
				'number'     => $max_count,
			)
		);

		return $tags;
	}

	/**
	 * Delete sticky posts.
	 *
	 * @param bool $force_delete Whether to bypass trash and force deletion.
	 *
	 * @return int Number of posts deleted.
	 */
	protected function delete_sticky_posts( $force_delete ) {
		$sticky_post_ids = get_option( 'sticky_posts' );

		if ( ! is_array( $sticky_post_ids ) ) {
			return 0;
		}

		return $this->delete_posts_by_id( $sticky_post_ids, $force_delete );
	}

	/**
	 * Delete posts by ids.
	 *
	 * @param int[] $post_ids     List of post ids to delete.
	 * @param bool  $force_delete True to force delete posts, False otherwise.
	 *
	 * @return int Number of posts deleted.
	 */
	protected function delete_posts_by_id( $post_ids, $force_delete ) {
		foreach ( $post_ids as $post_id ) {
			// `$force_delete` parameter to `wp_delete_post` won't work for custom post types.
			// See https://core.trac.wordpress.org/ticket/43672
			if ( $force_delete ) {
				wp_delete_post( $post_id, true );
			} else {
				wp_trash_post( $post_id );
			}
		}

		return count( $post_ids );
	}
}
