<?php
/**
 * Main plugin class, hooks into WordPress
 *
 * @package Bloom_UX\Outdated_Pages
 */

namespace Bloom_UX\Outdated_Pages;

use Queulat\Singleton;

/**
 * Main plugin class, hook into WordPress
 */
class Plugin {
	use Singleton;

	/**
	 * Instance of the admin page
	 *
	 * @var Admin_Page
	 */
	private $admin_page;

	/**
	 * Instance of the link checker process
	 *
	 * @var Check_Links_Process
	 */
	private $check_links_process;

	/**
	 * Instance of the create export process
	 *
	 * @var Create_Export_Process
	 */
	private $create_export_process;

	/**
	 * Indicates whether the plugin was initialized
	 * @var false
	 */
	private static $was_initialized = false;

	/**
	 * Include required files and initialize actions
	 *
	 * @return void
	 */
	public function init() {
		if ( static::$was_initialized ) {
			return;
		}
		require __DIR__ . '/checks/interface-page-check.php';
		require __DIR__ . '/checks/class-abstract-check.php';
		require __DIR__ . '/checks/class-has-incoming-links.php';
		require __DIR__ . '/checks/class-is-menu-item.php';
		require __DIR__ . '/checks/class-is-post-parent.php';
		require __DIR__ . '/class-page-checks.php';
		require __DIR__ . '/class-admin-page.php';
		require __DIR__ . '/class-check-links-process.php';
		require __DIR__ . '/class-create-export-process.php';
		$this->admin_page            = new Admin_Page();
		$this->check_links_process   = new Check_Links_Process();
		$this->create_export_process = new Create_Export_Process();
		$this->admin_page->init();
		add_action( 'wp_ajax_outdated-pages__delete', array( $this, 'ajax_delete_pages' ) );
		add_action( 'wp_ajax_outdated-pages__check-links', array( $this, 'ajax_check_links' ) );
		add_action( 'wp_ajax_outdated-pages__check-status', array( $this, 'ajax_check_status' ) );
		add_action( 'wp_ajax_outdated-pages__create-export', array( $this, 'ajax_create_export' ) );
		static::$was_initialized = true;
	}

	/**
	 * Handle ajax request to get check status
	 *
	 * @return void
	 */
	public function ajax_check_status() {
		wp_send_json_success( $this->check_links_process->get_status() );
	}

	/**
	 * Handle ajax request to check links
	 *
	 * @return void
	 */
	public function ajax_check_links() {
		$ids = array_map( 'absint', explode( ',', filter_input( INPUT_GET, 'ids', FILTER_SANITIZE_STRING ) ) );
		foreach ( $ids as $id ) {
			$this->check_links_process->push_to_queue( $id );
		}
		$this->check_links_process->save()->dispatch();
		wp_send_json_success(
			array(
				'status' => $this->check_links_process->get_status(),
			)
		);
	}

	/**
	 * Handle ajax request to create export file
	 *
	 * @return void
	 */
	public function ajax_create_export() {
		$user           = get_user_by( 'id', filter_input( INPUT_GET, 'user_id', FILTER_SANITIZE_NUMBER_INT ) );
		$before         = filter_input( INPUT_GET, 'before', FILTER_SANITIZE_STRING );
		$export_entries = Create_Export_Process::get_export_entries(
			array(
				'date_query' => array(
					array(
						'column' => 'post_modified_gmt',
						'before' => $before,
					),
				),
			)
		);
		$run_id         = md5( wp_json_encode( $export_entries->query_vars ) );
		foreach ( $export_entries->posts as $entry ) {
			$this->create_export_process->push_to_queue(
				array(
					'user_id' => $user->ID,
					'run_id'  => $run_id,
					'post_id' => $entry->ID,
					'before'  => $before,
				)
			);
		}
		$this->create_export_process->save()->dispatch();
		wp_send_json_success(
			array(
				'email'      => $user->user_email,
				'page_count' => $export_entries->post_count,
			)
		);
	}

	/**
	 * Handle ajax request to delete pages
	 *
	 * @return void
	 */
	public function ajax_delete_pages() {
		$ids     = array_map( 'absint', explode( ',', filter_input( INPUT_GET, 'ids', FILTER_SANITIZE_STRING ) ) );
		$deleted = array();
		foreach ( $ids as $id ) {
			$delete = wp_trash_post( $id );
			if ( $delete ) {
				$deleted[] = $id;
			}
		}
		wp_send_json_success(
			array(
				'deleted' => $deleted,
			)
		);
	}
}
