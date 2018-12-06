<?php

namespace BulkWP\BulkDelete\Core\Base;

use BulkWP\BulkDelete\Core\BulkDelete;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Encapsulates the logic for running a scheduler for a module.
 *
 * All Schedulers for Modules will be extending this class.
 *
 * @since 6.0.0
 */
abstract class BaseScheduler {
	/**
	 * The item type that will be deleted by the Scheduler.
	 *
	 * @var string
	 */
	protected $item_type;

	/**
	 * The class name of the module to which this is the scheduler.
	 *
	 * @var string
	 */
	protected $module_class_name;

	/**
	 * The module to which this is the scheduler.
	 *
	 * @var \BulkWP\BulkDelete\Core\Base\BaseModule
	 */
	protected $module = null;

	/**
	 * Initialize and setup variables.
	 *
	 * This method can be overridden by sub-classes if additional customization is needed.
	 */
	abstract protected function initialize();

	/**
	 * Create new instances of the Scheduler.
	 */
	public function __construct() {
		$this->initialize();
		$this->setup_module();
	}

	/**
	 * Setup module from class name.
	 */
	protected function setup_module() {
		$bd = BulkDelete::get_instance();

		$this->module = $bd->get_module( 'bulk-delete-' . $this->item_type, $this->module_class_name );
	}

	/**
	 * Register the scheduler.
	 *
	 * Setups the hooks and filters.
	 */
	public function register() {
		add_filter( 'bd_javascript_array', array( $this, 'filter_js_array' ) );

		if ( is_null( $this->module ) ) {
			return;
		}

		$cron_hook = $this->module->get_cron_hook();
		if ( ! empty( $cron_hook ) ) {
			add_action( $cron_hook, array( $this, 'do_delete' ) );
		}
	}

	/**
	 * Filter JS Array and add pro hooks.
	 *
	 * @param array $js_array JavaScript Array.
	 *
	 * @return array Modified JavaScript Array
	 */
	public function filter_js_array( $js_array ) {
		$js_array['pro_iterators'][] = $this->module->get_field_slug();

		return $js_array;
	}

	/**
	 * Trigger the deletion.
	 *
	 * @param array $delete_options Delete options.
	 */
	public function do_delete( $delete_options ) {
		if ( is_null( $this->module ) ) {
			return;
		}

		/**
		 * Triggered before the scheduler is run.
		 *
		 * @since 6.0.0
		 *
		 * @param string $label Cron Label.
		 */
		do_action( 'bd_before_scheduler', $this->module->get_cron_label() );

		$items_deleted = $this->module->delete( $delete_options );

		/**
		 * Triggered after the scheduler is run.
		 *
		 * @since 6.0.0
		 *
		 * @param string $label         Cron Label.
		 * @param int    $items_deleted Number of items that were deleted.
		 */
		do_action( 'bd_after_scheduler', $this->module->get_cron_label(), $items_deleted );
	}
}