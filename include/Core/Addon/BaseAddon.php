<?php

namespace BulkWP\BulkDelete\Core\Addon;

use BD_License_Handler;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Encapsulates the logic for a add-on.
 *
 * @since 6.0.0
 */
abstract class BaseAddon {

	/**
	 * Details of the Add-on.
	 *
	 * @var \BulkWP\BulkDelete\Core\Addon\AddonInfo
	 */
	protected $addon_info;

	/**
	 * Handler for license.
	 *
	 * @var \BD_License_Handler
	 */
	protected $license_handler;

	/**
	 * Initialize and setup variables.
	 *
	 * @return void
	 */
	abstract protected function initialize();

	/**
	 * Create a new instance of the add-on.
	 *
	 * @param \BulkWP\BulkDelete\Core\Addon\AddonInfo $addon_info Add-on Details.
	 */
	public function __construct( $addon_info ) {
		$this->addon_info = $addon_info;

		$this->initialize();
		$this->setup_license_handler();
	}

	/**
	 * Setup License Handler.
	 *
	 * TODO: Need this to be refactored.
	 */
	protected function setup_license_handler() {
		$this->license_handler = new BD_License_Handler(
			$this->addon_info->get_name(),
			$this->addon_info->get_code(),
			$this->addon_info->get_version(),
			$this->addon_info->get_root_file(),
			$this->addon_info->get_author()
		);
	}
}
