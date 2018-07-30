<?php

namespace BulkWP\BulkDelete\Core\Terms\Modules;

use BulkWP\BulkDelete\Core\Terms\TermsModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Delete Terms by Postfix and Prefix.
 *
 * @since 6.0.0
 */
class DeleteTermsByPostfixAndPrefixModule extends TermsModule {
	/**
	 * Initialize the values.
	 */
	protected function initialize() {
		$this->item_type     = 'terms';
		$this->field_slug    = 'terms_by_name';
		$this->meta_box_slug = 'bd_delete_terms_by_name';
		$this->action        = 'delete_terms_by_name';
		$this->cron_hook     = 'do-bulk-delete-term-by-name';
		$this->scheduler_url = '';
		$this->messages      = array(
			'box_label'  => __( 'Delete Terms by Name', 'bulk-delete' ),
			'scheduled'  => __( 'The selected terms are scheduled for deletion', 'bulk-delete' ),
			'cron_label' => __( 'Delete Terms By Name', 'bulk-delete' ),
		);
	}

	/**
	 * Render Delete terms by postfix and prefix box.
	 */
	public function render() {
		?>
		<!-- Category Start-->
		<h4><?php _e( 'Select the taxonomy from which you want to delete terms', 'bulk-delete' ); ?></h4>
		<fieldset class="options">
			<table class="optiontable">
				<?php $this->render_taxonomy_dropdown(); ?>
			</table>

			<table class="optiontable">
				<?php $this->render_term_options(); ?>
			</table>

		</fieldset>
		<?php
		$this->render_submit_button();
	}

	/**
	 * Filter the js array.
	 *
	 * @param array $js_array JavaScript Array.
	 *
	 * @return array Modified JavaScript Array
	 */
	public function filter_js_array( $js_array ) {
		$js_array['validators'][ $this->action ] = 'validatePostTypeSelect2';
		$js_array['error_msg'][ $this->action ]  = 'selectPostType';
		$js_array['msg']['selectPostType']       = __( 'Please select at least one post type', 'bulk-delete' );

		$js_array['dt_iterators'][] = '_' . $this->field_slug;

		$js_array['validators'][ $this->action ] = 'noValidation';

		$js_array['pre_action_msg'][ $this->action ] = 'deleteTermsWarning';
		$js_array['msg']['deleteTermsWarning']       = __( 'Are you sure you want to delete all the terms based on the selected option?', 'bulk-delete' );

		return $js_array;
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
		$options['taxonomy']  = bd_array_get( $request, 'smbd_' . $this->field_slug . '_taxonomy' );
		$options['term_opt']  = bd_array_get( $request, 'smbd_' . $this->field_slug . '_term_opt' );
		$options['term_text'] = bd_array_get( $request, 'smbd_' . $this->field_slug . '_term_text' );
		$options['no_posts']  = bd_array_get( $request, 'smbd_' . $this->field_slug . '_no_posts' );

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
		$query     = array();
		$term_text = $options['term_text'];
		$term_opt  = $options['term_opt'];

		switch ( $term_opt ) {
			case 'equal_to':
				$query['name__like'] = $term_text;
				break;

			case 'not_equal_to':
				$term_ids         = $this->term_query( array( 'name__like' => $term_text ), $options['taxonomy'] );
				$query['exclude'] = $term_ids;
				break;

			case 'starts':
				$term_ids         = $this->term_starts( $term_text, $options );
				$query['include'] = $term_ids;
				break;

			case 'ends':
				$term_ids         = $this->term_ends( $term_text, $options );
				$query['include'] = $term_ids;
				break;

			case 'contains':
				$term_ids         = $this->term_contains( $term_text, $options );
				$query['include'] = $term_ids;
				break;

			case 'not_contains':
				$term_ids         = $this->term_query( array( 'name__like' => "%$term_text%" ), $options['taxonomy'] );
				$query['exclude'] = $term_ids;
				break;
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
		return _n( 'Deleted %d term with the selected options', 'Deleted %d terms with the selected terms', $items_deleted, 'bulk-delete' );
	}
}