<?php

namespace Bloom_UX\Outdated_Pages;

use Queulat\Singleton;

class Plugin {
	use Singleton;

	/**
	 * Instance of the admin page
	 * @var mixed
	 */
	private $admin_page;

	public function init() {
		require __DIR__ . '/class-admin-page.php';
		$this->admin_page = new Admin_Page();
		$this->admin_page->init();
	}
}
