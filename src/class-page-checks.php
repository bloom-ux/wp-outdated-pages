<?php
/**
 * Handle page checks
 *
 * The class registers several checks to check whether the entry is still relevant.
 *
 * @package Bloom_UX\Outdated_Pages
 */

namespace Bloom_UX\Outdated_Pages;

use WP_Post;
use Bloom_UX\Outdated_Pages\Checks\Page_Check;
use Bloom_UX\Outdated_Pages\Checks\Is_Menu_Item;
use Bloom_UX\Outdated_Pages\Checks\Is_Post_Parent;
use Bloom_UX\Outdated_Pages\Checks\Has_Incoming_Links;

/**
 * Check if an entry is still relevant
 */
class Page_Checks {

	/**
	 * WP_Post instance
	 *
	 * @var WP_Post|array|null
	 */
	private $post;

	/**
	 * How many seconds the check will be considered "fresh"
	 *
	 * @var int
	 */
	private $cache_is_valid = HOUR_IN_SECONDS;

	/**
	 * The different page checks
	 *
	 * @var Page_Check[] Page checks
	 */
	private $checks = array();

	/**
	 * Build and initialize a new Page_Check object
	 *
	 * @param int|WP_Post $post ID or WP_Post object.
	 * @return void Instantiated object
	 */
	public function __construct( $post ) {
		$this->post   = get_post( $post );
		$this->checks = array(
			new Checks\Is_Post_Parent( $post ),
			new Checks\Is_Menu_Item( $post ),
			new Checks\Has_Incoming_Links( $post ),
		);
	}

	/**
	 * Indicates whether the checks are fresh
	 *
	 * @return bool True if was recently checked, false otherwhise
	 */
	public function was_recently_checked() : bool {
		// Never been checked, so, false.
		$check = get_post_meta( $this->post->ID, '_b_op__page_checked', true );
		if ( ! $check ) {
			return false;
		}
		return (int) time() - (int) $check < $this->cache_is_valid;
	}

	/**
	 * Return true if any of the check is true
	 *
	 * @return bool True if any check is true, false if none
	 */
	public function any_check() : bool {
		return array_reduce(
			$this->checks,
			function( bool $carry, Page_Check $check ) : bool {
				if ( $check->check() ) {
					$carry = true;
				}
				return $carry;
			},
			false
		);
	}

	/**
	 * Update all checks
	 *
	 * @return void
	 */
	public function update() {
		foreach ( $this->checks as $check ) {
			$check->refresh();
		}
	}

}
