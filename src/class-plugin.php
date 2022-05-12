<?php

namespace Bloom_UX\Outdated_Pages;

use Queulat\Singleton;

class Plugin {
	use Singleton;

	/**
	 * Instance of the admin page
	 * @var Admin_Page
	 */
	private $admin_page;

	/**
	 * Instance of the link checker process
	 * @var Check_Links_Process
	 */
	private $check_links_process;

	public function init() {
		require __DIR__ . '/class-admin-page.php';
		$this->admin_page = new Admin_Page();
		$this->admin_page->init();

		require __DIR__ . '/class-check-links-process.php';
		$this->check_links_process = new Check_Links_Process();

		require __DIR__ . '/class-has-links-repository.php';

		add_action( 'wp_ajax_outdated-pages__delete', array( $this, 'ajax_delete_pages' ) );
		add_action( 'wp_ajax_outdated-pages__check-links', array( $this, 'ajax_check_links' ) );
		add_action( 'wp_ajax_outdated-pages__check-status', array( $this, 'ajax_check_status' ) );
	}

	public function ajax_check_status() {
		wp_send_json_success( $this->check_links_process->get_status() );
	}

	public function ajax_check_links() {
		$ids = array_map( 'absint', explode(',', filter_input( INPUT_GET, 'ids', FILTER_SANITIZE_STRING ) ) );
		foreach ( $ids as $id ) {
			$this->check_links_process->push_to_queue( $id );
		}
		$this->check_links_process->save()->dispatch();
		wp_send_json_success([
			'status' => $this->check_links_process->get_status()
		]);
	}

	public function ajax_delete_pages() {
		$ids = array_map( 'absint', explode(',', filter_input( INPUT_GET, 'ids', FILTER_SANITIZE_STRING ) ) );
		$deleted = array();
		foreach ( $ids as $id ) {
			$delete = wp_trash_post( $id );
			if ( $delete ) {
				$deleted[] = $id;
			}
		}
		wp_send_json_success([
			'deleted' => $deleted
		]);
	}
}
