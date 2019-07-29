<?php

namespace BulkWP\BulkDelete\Core\Metas\Modules;

use BulkWP\BulkDelete\Core\Metas\MetasModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Delete Comment Meta Module.
 *
 * @since 6.0.0
 */
class DeleteCommentMetaModule extends MetasModule {
	protected function initialize() {
		$this->field_slug    = 'comment_meta';
		$this->meta_box_slug = 'bd-comment-meta';
		$this->action        = 'delete_comment_meta';
		$this->cron_hook     = 'do-bulk-delete-comment-meta';
		$this->messages      = array(
			'box_label'         => __( 'Bulk Delete Comment Meta', 'bulk-delete' ),
			'scheduled'         => __( 'Comment meta fields from the comments with the selected criteria are scheduled for deletion.', 'bulk-delete' ),
			'cron_label'        => __( 'Delete Comment Meta', 'bulk-delete' ),
			'confirm_deletion'  => __( 'Are you sure you want to delete all the comment meta fields that match the selected filters?', 'bulk-delete' ),
			'confirm_scheduled' => __( 'Are you sure you want to schedule deletion for all the comment meta fields that match the selected filters?', 'bulk-delete' ),
			'validation_error'  => __( 'Please enter meta key', 'bulk-delete' ),
			/* translators: 1 Number of comments deleted */
			'deleted_one'       => __( 'Deleted comment meta field from %d comment', 'bulk-delete' ),
			/* translators: 1 Number of comments deleted */
			'deleted_multiple'  => __( 'Deleted comment meta field from %d comments', 'bulk-delete' ),
		);

		$this->register_cron_hooks();
	}

	public function register( $hook_suffix, $page_slug ) {
		parent::register( $hook_suffix, $page_slug );

		add_action( 'bd_delete_comment_meta_form', array( $this, 'add_filtering_options' ) );
		add_filter( 'bd_delete_comment_meta_options', array( $this, 'process_filtering_options' ), 10, 2 );
	}

	/**
	 * Register additional module specific hooks that are needed in cron jobs.
	 *
	 * During a cron request, the register method is not called. So these hooks should be registered separately.
	 *
	 * @since 6.0.2
	 */
	protected function register_cron_hooks() {
		add_filter( 'bd_delete_comment_meta_query', array( $this, 'change_meta_query' ), 10, 2 );
	}

	/**
	 * Render the Delete Comment Meta box.
	 */
	public function render() {
		?>
		<!-- Comment Meta box start-->
		<fieldset class="options">
			<h4><?php _e( 'Select the post type whose comment meta fields you want to delete', 'bulk-delete' ); ?></h4>
			<table class="optiontable">
				<?php $this->render_post_type_with_status( false ); ?>
			</table>

			<h4><?php _e( 'Choose your comment meta field settings', 'bulk-delete' ); ?></h4>
			<table class="optiontable">
				<tr>
					<td>
						<input name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_use_value" class="use-value" value="false" type="radio" checked>
						<label for="smbd_<?php echo esc_attr( $this->field_slug ); ?>_use_value"><?php echo __( 'Delete based on comment meta key name only', 'bulk-delete' ); ?></label>
					</td>
				</tr>

				<tr>
					<td>
						<input type="radio" class="use-value" value="true" name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_use_value" id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_use_value">

						<label for="smbd_<?php echo esc_attr( $this->field_slug ); ?>_use_value"><?php echo __( 'Delete based on comment meta key name and value', 'bulk-delete' ); ?></label>
					</td>
				</tr>

				<tr>
					<td>
						<label for="smbd_<?php echo esc_attr( $this->field_slug ); ?>_meta_key"><?php _e( 'Comment Meta Key ', 'bulk-delete' ); ?></label>
						<input name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_meta_key" id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_meta_key" placeholder="<?php _e( 'Meta Key', 'bulk-delete' ); ?>" class="validate">
					</td>
				</tr>
			</table>

			<?php
			/**
			 * Add more fields to the delete comment meta field form.
			 * This hook can be used to add more fields to the delete comment meta field form.
			 *
			 * @since 5.4
			 */
			do_action( 'bd_delete_comment_meta_form' );
			?>
			<table class="optiontable">
				<tr>
					<td colspan="2">
						<h4><?php _e( 'Choose your deletion options', 'bulk-delete' ); ?></h4>
					</td>
				</tr>

				<?php $this->render_restrict_settings( 'comments' ); ?>
				<?php $this->render_limit_settings(); ?>
				<?php $this->render_cron_settings(); ?>

			</table>
		</fieldset>

		<?php $this->render_submit_button(); ?>

		<!-- Comment Meta box end-->
		<?php
	}

	protected function convert_user_input_to_options( $request, $options ) {
		$options['post_type'] = esc_sql( bd_array_get( $request, 'smbd_' . $this->field_slug ) );

		$options['use_value'] = bd_array_get_bool( $request, 'smbd_' . $this->field_slug . '_use_value', false );
		$options['meta_key']  = esc_sql( bd_array_get( $request, 'smbd_' . $this->field_slug . '_meta_key', '' ) );

		/**
		 * Delete comment-meta delete options filter.
		 *
		 * This filter is for processing filtering options for deleting comment meta.
		 *
		 * @since 5.4
		 */
		return apply_filters( 'bd_delete_comment_meta_options', $options, $request );
	}

	protected function do_delete( $options ) {
		$args = $this->get_post_type_and_status_args( $options['post_type'] );

		if ( $options['limit_to'] > 0 ) {
			$args['number'] = $options['limit_to'];
		}

		if ( $options['restrict'] ) {
			$args['date_query'] = array(
				array(
					'column'            => 'comment_date',
					$options['date_op'] => "{$options['days']} day ago",
				),
			);
		}

		if ( $options['use_value'] ) {
			$args['meta_query'] = apply_filters( 'bd_delete_comment_meta_query', array(), $options );
		} else {
			$args['meta_key'] = $options['meta_key'];
		}

		$meta_deleted = 0;
		$comments     = get_comments( $args );

		foreach ( $comments as $comment ) {
			// Todo: Don't delete all meta rows if there are duplicate meta keys.
			// See https://github.com/sudar/bulk-delete/issues/515 for details.
			if ( delete_comment_meta( $comment->comment_ID, $options['meta_key'] ) ) {
				$meta_deleted ++;
			}
		}

		return $meta_deleted;
	}

	protected function append_to_js_array( $js_array ) {
		$js_array['validators'][ $this->action ] = 'validateTextbox';

		return $js_array;
	}

	/**
	 * Append filtering options to the delete comment meta form.
	 *
	 * This function was originally part of the Bulk Delete Comment Meta add-on.
	 *
	 * @since 0.1 of Bulk Delete Comment Meta add-on
	 */
	public function add_filtering_options() {
		?>
		<table class="optiontable" style="display:none;">
			<tr>
				<td>
					<?php _e( 'Comment Meta Value ', 'bulk-delete' ); ?>
					<?php $this->render_data_types_dropdown(); ?>
					<?php $this->render_numeric_operators_dropdown(); ?>	
					<?php $this->render_string_operators_dropdown(); ?>
					<?php
						$operators = array( '=', '!=', '>', '<=', '>', '>=', 'EXISTS', 'NOT EXISTS' );
						$class     = 'date';
					?>
					<?php $this->render_numeric_operators_dropdown( $class, $operators ); ?>
					<input name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_value"
						id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_value" class="date-picker">
					<span class="date-fields">
						<?php _e( 'Or', 'bulk-delete' ); ?>
						<select name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_relative_date" id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_relative_date" class="relative-date-fields">
							<option value=""><?php _e( 'Select Relative date', 'bulk-delete' ); ?></option>
							<option value="yesterday"><?php _e( 'Yesterday', 'bulk-delete' ); ?></option>
							<option value="today"><?php _e( 'Today', 'bulk-delete' ); ?></option>
							<option value="tomorrow"><?php _e( 'Tomorrow', 'bulk-delete' ); ?></option>
							<option value="custom"><?php _e( 'Custom', 'bulk-delete' ); ?></option>
						</select>
						<?php echo apply_filters( 'bd_help_tooltip', '', __( 'You can select a date or enter a date which is relative to today.', 'bulk-delete' ) ); ?>
					</span>
					<span class="custom-date-fields">
						<input type="number" name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_date_unit" id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_date_unit" style="width: 5%;">
						<select name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_date_type" id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_date_type">
							<option value="day"><?php _e( 'Day', 'bulk-delete' ); ?></option>
							<option value="week"><?php _e( 'Week', 'bulk-delete' ); ?></option>
							<option value="month"><?php _e( 'Month', 'bulk-delete' ); ?></option>
							<option value="year"><?php _e( 'Year', 'bulk-delete' ); ?></option>
						</select>
					</span>
				</td>
			</tr>
			<tr class="date-format-fields">
				<td colspan="2">
					<label for="smbd_<?php echo esc_attr( $this->field_slug ); ?>_date_format">
						<?php _e( 'Meta value date format', 'bulk-delete' ); ?>
					</label>
					<input name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_date_format" placeholder="%Y-%m-%d">
					<?php echo apply_filters( 'bd_help_tooltip', '', __( "If you leave date format blank, then '%Y-%m-%d', will be assumed.", 'bulk-delete' ) ); ?>
					<p>
						<?php
						printf(
							/* translators: 1 Mysql Format specifier url.  */
							__( 'If you are storing the date in a format other than <em>YYYY-MM-DD</em> then enter the date format using <a href="%s" target="_blank" rel="noopener noreferrer">Mysql format specifiers</a>.', 'bulk-delete' ),
							'https://dev.mysql.com/doc/refman/5.5/en/date-and-time-functions.html#function_date-format'
						);
						?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Process additional delete options.
	 *
	 * This function was originally part of the Bulk Delete Comment Meta add-on.
	 *
	 * @since 0.1 of Bulk Delete Comment Meta add-on
	 *
	 * @param array $delete_options Delete options array.
	 * @param array $post           The POST array.
	 *
	 * @return array Processed delete options array.
	 */
	public function process_filtering_options( $delete_options, $post ) {
		if ( 'true' == bd_array_get( $post, 'smbd_' . $this->field_slug . '_use_value', 'false' ) ) {
			$delete_options['meta_op']       = bd_array_get( $post, 'smbd_' . $this->field_slug . '_operator', '=' );
			$delete_options['meta_type']     = bd_array_get( $post, 'smbd_' . $this->field_slug . '_type', 'CHAR' );
			$delete_options['meta_value']    = bd_array_get( $post, 'smbd_' . $this->field_slug . '_value', '' );
			$delete_options['relative_date'] = bd_array_get( $post, 'smbd_' . $this->field_slug . '_relative_date', '' );
			$delete_options['date_unit']     = bd_array_get( $post, 'smbd_' . $this->field_slug . '_date_unit', '' );
			$delete_options['date_type']     = bd_array_get( $post, 'smbd_' . $this->field_slug . '_date_type', '' );
			$delete_options['date_format']   = bd_array_get( $post, 'smbd_' . $this->field_slug . '_date_format' );
		}

		return $delete_options;
	}

	/**
	 * Change the meta query.
	 *
	 * This function was originally part of the Bulk Delete Comment Meta add-on.
	 *
	 * @since 0.1 of Bulk Delete Comment Meta add-on
	 *
	 * @param array $meta_query     Meta query.
	 * @param array $delete_options List of options chosen by the user.
	 *
	 * @return array Modified meta query.
	 */
	public function change_meta_query( $meta_query, $delete_options ) {
		$query_vars = array(
			'key'     => $delete_options['meta_key'],
			'compare' => $delete_options['meta_op'],
			'type'    => $delete_options['meta_type'],
		);
		if ( in_array( $delete_options['meta_op'], array( 'EXISTS', 'NOT EXISTS' ), true ) ) {
			$meta_query = array( $query_vars );

			return $meta_query;
		}
		if ( 'DATE' === $delete_options['meta_type'] ) {
			$bd_date_handler = new \Bulk_Delete_Date_Handler();
			$meta_query      = $bd_date_handler->get_query( $delete_options );

			return $meta_query;
		}
		switch ( $delete_options['meta_op'] ) {
			case 'IN':
				$meta_value = explode( ',', $delete_options['meta_value'] );
				break;
			case 'BETWEEN':
				$meta_value = explode( ',', $delete_options['meta_value'] );
				break;
			default:
				$meta_value = $delete_options['meta_value'];
		}

		$query_vars['value'] = $meta_value;
		$meta_query          = array( $query_vars );

		return $meta_query;
	}

	/**
	 * Hook handler.
	 *
	 * This function was originally part of the Bulk Delete Comment Meta add-on.
	 *
	 * @since 0.1 of Bulk Delete Comment Meta add-on
	 *
	 * @param array $delete_options Delete options array.
	 */
	public function do_delete_comment_meta( $delete_options ) {
		do_action( 'bd_before_scheduler', $this->messages['cron_label'] );
		$count = $this->delete( $delete_options );
		do_action( 'bd_after_scheduler', $this->messages['cron_label'], $count );
	}
}
