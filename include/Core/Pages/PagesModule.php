<?php

namespace BulkWP\BulkDelete\Core\Pages;

use BulkWP\BulkDelete\Core\Posts\PostsModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Module for deleting pages.
 *
 * This class extends PostsModule since Page is a type of Post.
 *
 * @since 6.0.0
 */
abstract class PagesModule extends PostsModule {
	protected $item_type = 'pages';

	public function filter_js_array( $js_array ) {
		$js_array['dt_iterators'][] = '_pages';

		return $js_array;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_trash_url() {
		return admin_url( 'edit.php?post_status=trash&post_type=page' );
	}
}
