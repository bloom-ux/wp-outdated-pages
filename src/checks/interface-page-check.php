<?php
/**
 * Page check interface
 *
 * @package Bloom_UX\Outdated_Pages
 */

namespace Bloom_UX\Outdated_Pages\Checks;

/**
 * Interface for a Page_Check
 *
 * Implementing classes must define at least these methods.
 */
interface Page_Check {

	/**
	 * Refresh (update) the check (query database or whatever)
	 *
	 * @return void
	 */
	public function refresh();

	/**
	 * A "good" entry should return true, false otherwhise
	 *
	 * @return bool True if entry passes the check
	 */
	public function check() : bool;
}
