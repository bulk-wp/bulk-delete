<?php

namespace BulkWP\BulkDelete\Core\Posts\Metabox;

use BulkWP\BulkDelete\Core\Posts\PostsMetabox;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Delete Posts by Category Metabox.
 *
 * @since 6.0.0
 */
class DeletePostsByCategoryMetabox extends PostsMetabox {
	protected function initialize() {
		$this->item_type     = 'posts';
		$this->field_slug    = 'cats';
		$this->meta_box_slug = 'bd_by_category';
		$this->action        = 'delete_posts_by_category';
		$this->cron_hook     = 'do-bulk-delete-cat';
		$this->scheduler_url = 'http://bulkwp.com/addons/scheduler-for-deleting-posts-by-category/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-sc';
		$this->messages      = array(
			'box_label' => __( 'By Post Category', 'bulk-delete' ),
			'scheduled' => __( 'The selected posts are scheduled for deletion', 'bulk-delete' ),
		);
	}

	/**
	 * Render Delete posts by category box.
	 */
	public function render() {
		?>
		<!-- Category Start-->
		<h4><?php _e( 'Select the post type from which you want to delete posts by category', 'bulk-delete' ); ?></h4>
		<fieldset class="options">
			<table class="optiontable">
				<?php $this->render_post_type_dropdown(); ?>
			</table>

			<h4><?php _e( 'Select the categories from which you want to delete posts', 'bulk-delete' ); ?></h4>
			<p>
				<?php _e( 'Note: The post count below for each category is the total number of posts in that category, irrespective of post type', 'bulk-delete' ); ?>
			.</p>

			<table class="form-table">
				<tr>
					<td scope="row">
						<?php $this->render_category_dropdown(); ?>
					</td>
				</tr>
			</table>

			<table class="optiontable">
				<?php
				$this->render_filtering_table_header();
				$this->render_restrict_settings();
				$this->render_delete_settings();
				$this->render_private_post_settings();
				$this->render_limit_settings();
				$this->render_cron_settings();
				?>
			</table>

		</fieldset>
		<?php
		$this->render_submit_button();
	}

	/**
	 * Process delete posts user inputs by category.
	 *
	 * @param array $request Request array.
	 * @param array $options Options for deleting posts.
	 *
	 * @return array $options  Inputs from user for posts that were need to delete
	 */
	protected function convert_user_input_to_options( $request, $options ) {
		$options['post_type']     = bd_array_get( $request, 'smbd_' . $this->field_slug . '_post_type', 'post' );
		$options['selected_cats'] = bd_array_get( $request, 'smbd_' . $this->field_slug . '_category' );
		$options['private']       = bd_array_get_bool( $request, 'smbd_' . $this->field_slug . '_private', false );

		return $options;
	}

	/**
	 * Build query from delete options.
	 *
	 * @param array $options Delete options.
	 *
	 * @return array Query.
	 */
	protected function build_query( $options ) {
		$query = array();

		if ( in_array( 'all', $options['selected_cats'], true ) ) {
			$query['category__not__in'] = array( 0 );
		} else {
			$query['category__in'] = $options['selected_cats'];
		}

		return $query;
	}

	/**
	 * Response message for deleting posts.
	 *
	 * @param int $items_deleted Total number of posts deleted.
	 *
	 * @return string Response message
	 */
	protected function get_success_message( $items_deleted ) {
		/* translators: 1 Number of posts deleted */
		return _n( 'Deleted %d post with the selected post category', 'Deleted %d posts with the selected post category', $items_deleted, 'bulk-delete' );
	}
}
