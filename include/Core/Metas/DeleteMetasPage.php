<?php

namespace BulkWP\BulkDelete\Core\Metas;

use BulkWP\BulkDelete\Core\Base\MetaboxPage;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Bulk Delete Metas Page.
 *
 * Shows the list of modules that allows you to delete metas.
 *
 * @since 6.0.0
 */
class DeleteMetasPage extends MetaboxPage {
	/**
	 * Initialize and setup variables.
	 */
	protected function initialize() {
		$this->page_slug  = 'bulk-delete-metas';
		$this->item_type  = 'metas';
		$this->capability = 'edit_others_posts';

		$this->label = array(
			'page_title' => __( 'Bulk Delete Meta Fields', 'bulk-delete' ),
			'menu_title' => __( 'Bulk Delete Meta Fields', 'bulk-delete' ),
		);

		$this->messages = array(
			'warning_message' => __( 'WARNING: Meta Fields deleted once cannot be retrieved back. Use with caution.', 'bulk-delete' ),
		);
	}

	/**
	 * Add Help tabs.
	 *
	 * @param array $help_tabs Help tabs.
	 *
	 * @return array Modified list of Help tabs.
	 */
	protected function add_help_tab( $help_tabs ) {
		$overview_tab = array(
			'title'    => __( 'Overview', 'bulk-delete' ),
			'id'       => 'overview_tab',
			'content'  => '<p>' . __( 'This screen contains different modules that allows you to delete meta fields or schedule them for deletion.', 'bulk-delete' ) . '</p>',
			'callback' => false,
		);

		$help_tabs['overview_tab'] = $overview_tab;

		return $help_tabs;
	}
}
