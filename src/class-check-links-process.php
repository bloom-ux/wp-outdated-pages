<?php
/**
 * Background processs to check links to entries
 *
 * @package Bloom_UX\Outdated_Pages
 */

namespace Bloom_UX\Outdated_Pages;

use WP_Background_Process;

/**
 * Check links as a background proces
 */
class Check_Links_Process extends WP_Background_Process {

	/**
	 * Process status data
	 *
	 * @var array
	 */
	private $status = array(
		'total'      => 0,
		'current'    => 0,
		'start_time' => 0,
		'end_time'   => null,
		'status'     => '',
		'has_links'  => array(),
	);

	/**
	 * The name of this process action
	 *
	 * @var string
	 */
	protected $action = 'outdated_pages_check_link';

	/**
	 * Initialize new check process
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->status = get_option( $this->get_option_name(), array() );
	}

	/**
	 * Get the name of the option that will store process status
	 *
	 * @return string Option name for process status
	 */
	private function get_option_name() : string {
		return "{$this->action}__status";
	}

	/**
	 * Save queued items before processing
	 *
	 * @return $this
	 */
	public function save() {
		parent::save();
		$this->init_status();
		return $this;
	}

	/**
	 * Initialize process status
	 *
	 * @return void
	 */
	private function init_status() {
		$status       = array(
			'total'      => count( $this->data ),
			'current'    => 0,
			'start_time' => time(),
			'end_time'   => null,
			'status'     => 'created',
			'has_links'  => array(),
		);
		$this->status = $status;
		$this->update_data();
	}

	/**
	 * Update process status data
	 *
	 * @return void
	 */
	private function update_data() {
		update_option( $this->get_option_name(), $this->status );
	}

	/**
	 * Process 1 item from the queue.
	 *
	 * @param int $item Post ID that will be checked.
	 * @return false Return false to remove item from queue
	 */
	protected function task( $item ) {
		$page_check = new Page_Checks( $item );

		// We're trying to get fresh data here.
		if ( ! $page_check->was_recently_checked() ) {
			$page_check->update();
		}

		// Update process status.
		$this->status['status']             = 'working';
		$this->status['current']            = $this->status['current'] + 1;
		$this->status['has_links'][ $item ] = (bool) $page_check->any_check();
		$this->update_data();

		return false;
	}

	/**
	 * Execute when finishing a queueu
	 *
	 * @return void
	 */
	protected function complete() {
		parent::complete();
		$this->status['end_time'] = time();
		$this->status['status']   = 'finished';
		$this->update_data();
	}

	/**
	 * Get link check process status
	 *
	 * @return array Process status
	 */
	public function get_status() {
		return get_option( $this->get_option_name(), null );
	}

}
