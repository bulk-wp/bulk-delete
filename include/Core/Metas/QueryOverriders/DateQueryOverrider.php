<?php

namespace BulkWP\BulkDelete\Core\Metas\QueryOverriders;

use BulkWP\BulkDelete\Core\Base\BaseQueryOverrider;

/**
 * Class that encapsulates the logic for handling date format specified by the user.
 *
 * @package Bulk Delete
 *
 * @author  Sudar
 *
 * @since   6.0
 */
class DateQueryOverrider extends BaseQueryOverrider {
	/**
	 * Delete Options.
	 *
	 * @var array
	 */
	protected $delete_options;
	/**
	 * Date format of meta value that is stored in `wp_post_meta` table..
	 *
	 * @var string
	 */
	protected $meta_value_date_format;
	/**
	 * Meta belongs to which table.  It can be post,comment or user.
	 *
	 * @var string
	 */
	protected $whose_meta;

	/**
	 * Setup hooks and load.
	 *
	 * @since 1.0
	 */
	public function load() {
		if ( 'comment' === $this->whose_meta ) {
			add_action( 'parse_comment_query', array( $this, 'parse_query' ) );
		} elseif ( 'post' === $this->whose_meta ) {
			add_action( 'parse_query', array( $this, 'parse_query' ) );
		} elseif ( 'user' === $this->whose_meta ) {
			add_action( 'pre_user_query', array( $this, 'parse_query' ) );
		}
	}

	/**
	 * Creates query object after processing date with specified date format.
	 *
	 * @param array $delete_options Delete Options.
	 *
	 * @return \WP_Query $query Query object.
	 */
	public function get_query( $delete_options ) {
		$query = $this->process_date_fields( $delete_options );

		return $query;
	}

	/**
	 * Process date fields and returns query built.
	 *
	 * @param array $delete_options Delete Options.
	 *
	 * @return array $options Query.
	 */
	public function process_date_fields( $delete_options ) {
		if ( ! empty( $delete_options['relative_date'] ) && 'custom' !== $delete_options['relative_date'] ) {
			$delete_options['meta_value'] = date( 'c', strtotime( $delete_options['relative_date'] ) );
		}

		if ( ! empty( $delete_options['date_unit'] ) && ! empty( $delete_options['date_type'] ) ) {
			$interval_unit = $delete_options['date_unit'];
			$interval_type = $delete_options['date_type'];

			switch ( $delete_options['meta_op'] ) {
				case '<':
				case '<=':
					$delete_options['meta_value'] = date( 'Y-m-d', strtotime( '-' . $interval_unit . ' ' . $interval_type ) );
					break;
				default:
					$delete_options['meta_value'] = date( 'Y-m-d', strtotime( $interval_unit . ' ' . $interval_type ) );
			}
		}

		$meta_query = array(
			'key'     => $delete_options['meta_key'],
			'value'   => $delete_options['meta_value'],
			'compare' => $delete_options['meta_op'],
			'type'    => $delete_options['meta_type'],
		);

		$options = array( $meta_query );

		if ( 'DATE' === $meta_query['type'] && ! empty( $delete_options['meta_value_date_format'] ) ) {
			$options['bd_meta_value_date_format'] = $delete_options['meta_value_date_format'];
			$this->whose_meta                     = $delete_options['whose_meta'];

			$this->load();
		}

		return $options;
	}

	/**
	 * Parse the query object.
	 *
	 * @param \WP_Query $query Query object.
	 *
	 * @since  0.3
	 */
	public function parse_query( $query ) {
		if ( isset( $query->query_vars['meta_query']['bd_meta_value_date_format'] ) ) {
			$this->meta_value_date_format = $query->query_vars['meta_query']['bd_meta_value_date_format'];

			add_filter( 'get_meta_sql', array( $this, 'process_sql_date_format' ), 10, 6 );
			add_action( 'bd_after_meta_query', array( $this, 'remove_filter' ) );
		}
	}

	/**
	 * Process date format in sql query.
	 *
	 * @param array  $query          Array containing the query's JOIN and WHERE clauses.
	 * @param array  $input          Array of meta queries.
	 * @param string $type           Type of meta.
	 * @param string $primary_table  Primary table.
	 * @param string $primary_column Primary column ID.
	 * @param object $context        The main query object.
	 *
	 * @return array $query Processed query.
	 *
	 * @since 0.3
	 */
	public function process_sql_date_format( $query, $input, $type, $primary_table, $primary_column, $context ) {
		global $wpdb;
		if ( 'DATE' === $input[0]['type'] && $this->whose_meta === $type && 'comment_ID' === $primary_column ) {
			$meta_table = _get_meta_table( $type );
			if ( 'unixtimestamp' === strtolower( str_replace( ' ', '', $this->meta_value_date_format ) ) ) {
				$query['where'] = $wpdb->prepare(
					" AND ( $meta_table.meta_key = %s AND FROM_UNIXTIME($meta_table.meta_value, %s) {$input[0]['compare']} STR_TO_DATE(%s, %s) ) ",
					$input[0]['key'],
					'%Y-%m-%d', // Meta value format.
					$input[0]['value'],
					'%Y-%m-%d'
				);
			} else {
				$query['where'] = $wpdb->prepare(
					" AND ( $meta_table.meta_key = %s AND STR_TO_DATE($meta_table.meta_value, %s) {$input[0]['compare']} STR_TO_DATE(%s, %s) ) ",
					$input[0]['key'],
					$this->meta_value_date_format,
					$input[0]['value'],
					'%Y-%m-%d'
				);
			}
		}

		return $query;
	}

	/**
	 * Remove meta sql filter.
	 *
	 * @return void
	 */
	public function remove_filter() {
		remove_filter( 'get_meta_sql', array( $this, 'process_sql_date_format' ) );
	}
}
