<?php
/**
 * Async export task
 *
 * @package Bloom_UX\Outdated_Pages;
 */

namespace Bloom_UX\Outdated_Pages;

use WP_Async_Request;

/**
 * Create an export task
 */
class Async_Export_Task extends WP_Async_Request {

	/**
	 * Task id
	 *
	 * @var string
	 */
	protected $action = 'b_op_async_export';

	/**
	 * Create an export process
	 *
	 * @return void
	 */
	protected function handle() {
		$export_entries = Create_Export_Process::get_export_entries(
			array(
				'date_query' => array(
					array(
						'column' => 'post_modified_gmt',
						'before' => filter_input( INPUT_POST, 'before', FILTER_SANITIZE_STRING ),
					),
				),
				'fields'     => 'ids',
			)
		);
		foreach ( $export_entries->posts as $entry ) {
			Plugin::get_instance()->create_export_process->push_to_queue(
				array(
					'user_id' => (int) filter_input( INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT ),
					'run_id'  => filter_input( INPUT_POST, 'run_id', FILTER_SANITIZE_STRING ),
					'post_id' => $entry,
					'before'  => filter_input( INPUT_POST, 'before', FILTER_SANITIZE_STRING ),
				)
			);
		}
		Plugin::get_instance()->create_export_process->save()->dispatch();
	}

}
