<?php

namespace BulkWP\BulkDelete\Core\Posts\Modules;

use BulkWP\BulkDelete\Core\Posts\PostsModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Delete Posts by Taxonomy Module.
 *
 * @since 6.0.0
 */
class DeletePostsByTaxonomyModule extends PostsModule {
	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	protected function initialize() {
		$this->item_type     = 'posts';
		$this->field_slug    = 'taxs';
		$this->meta_box_slug = 'bd_posts_by_taxonomy';
		$this->action        = 'bd_delete_posts_by_taxonomy';
		$this->cron_hook     = 'do-bulk-delete-taxonomy';
		$this->scheduler_url = 'https://bulkwp.com/addons/scheduler-for-deleting-posts-by-taxonomy/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bd-stx';
		$this->messages      = array(
			'box_label'         => __( 'Delete Posts by Taxonomy (Category, Tag or custom taxonomy)', 'bulk-delete' ),
			'scheduled'         => __( 'The selected posts are scheduled for deletion', 'bulk-delete' ),
			'cron_label'        => __( 'Delete Post By Taxonomy', 'bulk-delete' ),
			'confirm_deletion'  => __( 'Are you sure you want to delete posts from the selected taxonomy?', 'bulk-delete' ),
			'confirm_scheduled' => __( 'Are you sure you want to schedule deletion for all the posts from the selected taxonomy?', 'bulk-delete' ),
			'validation_error'  => __( 'Please select the taxonomy from which posts should be deleted', 'bulk-delete' ),
			/* translators: 1 Number of posts deleted */
			'deleted_one'       => __( 'Deleted %d post from the selected taxonomy', 'bulk-delete' ),
			/* translators: 1 Number of posts deleted */
			'deleted_multiple'  => __( 'Deleted %d posts from the selected taxonomy', 'bulk-delete' ),
		);
	}

	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	public function render() {
		$taxs = get_taxonomies( array(), 'objects'
		);

		$terms_array = array();
		if ( count( $taxs ) > 0 ) {
			foreach ( $taxs as $tax ) {
				$terms = get_terms( $tax->name );
				if ( count( $terms ) > 0 ) {
					$terms_array[ $tax->name ] = $terms;
				}
			}
		}

		if ( count( $terms_array ) > 0 ) {
			?>
			<h4><?php _e( 'Select the post type from which you want to delete posts by taxonomy', 'bulk-delete' ); ?></h4>

			<fieldset class="options">
				<table class="optiontable">
					<?php $this->render_post_type_dropdown(); ?>
				</table>

				<h4>
					<?php _e( 'Select the taxonomies from which you want to delete posts', 'bulk-delete' ); ?>
				</h4>

				<table class="optiontable">
					<?php
					foreach ( $terms_array as $tax => $terms ) {
						?>
						<tr>
							<td scope="row">
								<input name="smbd_taxs" value="<?php echo esc_attr( $tax ); ?>" type="radio" class="custom-tax">
							</td>
							<td>
								<label for="smbd_taxs"><?php echo esc_attr( $taxs[ $tax ]->labels->name ); ?></label>
							</td>
						</tr>
						<?php
					}
					?>
				</table>

				<h4>
					<?php _e( 'The selected taxonomy has the following terms. Select the terms from which you want to delete posts', 'bulk-delete' ); ?>
				</h4>

				<p>
					<?php _e( 'Note: The post count below for each term is the total number of posts in that term, irrespective of post type', 'bulk-delete' ); ?>.
				</p>

				<?php
				foreach ( $terms_array as $tax => $terms ) {
					?>
					<table class="optiontable terms_<?php echo $tax; ?> terms">
						<?php
						foreach ( $terms as $term ) {
							?>
							<tr>
								<td scope="row">
									<input name="smbd_taxs_terms[]" value="<?php echo $term->slug; ?>" type="checkbox"
										class="terms">
								</td>
								<td>
									<label for="smbd_taxs_terms"><?php echo $term->name; ?>
										(<?php echo $term->count . ' ';
										_e( 'Posts', 'bulk-delete' ); ?>)</label>
								</td>
							</tr>
							<?php
						}
						?>
					</table>
					<?php
				}
				?>
				<table class="optiontable">
					<?php
					$this->render_filtering_table_header();
					$this->render_restrict_settings();
					$this->render_exclude_sticky_settings();
					$this->render_delete_settings();
					$this->render_limit_settings();
					$this->render_cron_settings();
					?>
				</table>

			</fieldset>
			<?php
			$this->render_submit_button();
		} else {
			?>
			<h4><?php _e( "This WordPress installation doesn't have any non-empty taxonomies defined", 'bulk-delete' ) ?></h4>
			<?php
		}
	}

	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	protected function convert_user_input_to_options( $request, $options ) {
		$options['post_type']          = bd_array_get( $request, 'smbd_' . $this->field_slug . '_post_type', 'post' );
		$options['selected_taxs']      = bd_array_get( $request, 'smbd_' . $this->field_slug );
		$options['selected_tax_terms'] = bd_array_get( $request, 'smbd_' . $this->field_slug . '_terms' );

		return $options;
	}

	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	protected function build_query( $delete_options ) {
		// For compatibility reasons set default post type to 'post'
		$post_type = bd_array_get( $delete_options, 'post_type', 'post' );

		$taxonomy = $delete_options['selected_taxs'];
		$terms    = $delete_options['selected_tax_terms'];

		$options = array(
			'post_status' => 'publish',
			'post_type'   => $post_type,
			'tax_query'   => array(
				array(
					'taxonomy' => $taxonomy,
					'terms'    => $terms,
					'field'    => 'slug',
				),
			),
		);

		return $options;
	}

	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	protected function get_non_standard_input_key_map() {
		$prefix = $this->get_ui_input_prefix();

		$prefix_without_underscore_at_end = substr( $prefix, 0, -1 );

		return array(
			$prefix_without_underscore_at_end => $prefix . 'taxonomy',
		);
	}

	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	protected function prepare_cli_input( $input ) {
		// Handle multiple terms.
		$input['terms'] = explode( ',', $input['terms'] );

		return parent::prepare_cli_input( $input );
	}
}
