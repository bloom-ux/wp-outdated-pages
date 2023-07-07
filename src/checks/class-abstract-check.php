<?php
/**
 * Abstract entry check, eases adding new checks
 *
 * @package Bloom_UX\Outdated_Pages
 */

namespace Bloom_UX\Outdated_Pages\Checks;

use WP_Post;

/**
 * Abstract entry check
 */
abstract class Abstract_Check implements Page_Check {

	/**
	 * Hold the post instance
	 *
	 * @var WP_Post|array|null
	 */
	protected $post;

	/**
	 * Create new page check
	 *
	 * @param WP_Post|int $post Post ID or object.
	 * @return void
	 */
	public function __construct( $post ) {
		$this->post = get_post( $post );
	}
}
