<?php
/**
 * Check if the entry has incoming links (same site)
 *
 * @package Bloom_UX\Outdated_Pages
 */

namespace Bloom_UX\Outdated_Pages\Checks;

use WP_Query;

/**
 * Check if the entry has incoming links (same site)
 */
class Has_Incoming_Links extends Abstract_Check implements Page_Check {

	/**
	 * Refresh whether the entry has incoming links
	 *
	 * @return void
	 */
	public function refresh() {
		$permalink       = get_permalink( $this->post->ID );
		$maybe_has_links = new WP_Query(
			array(
				'post_status'    => 'any',
				'post_type'      => 'any',
				's'              => $permalink,
				'no_found_rows'  => true,
				'posts_per_page' => 1,
			)
		);
		update_post_meta( $this->post->ID, '_b_op__has_incoming_links', (int) $maybe_has_links->have_posts() );
	}

	/**
	 * Indicates whether the entry has links from other entries.
	 *
	 * @return bool True if other entries link to the checked.
	 */
	public function check(): bool {
		return (bool) get_post_meta( $this->post->ID, '_b_op__has_incoming_links', true );
	}

}
