<?php
/**
 * Check if the entry is parent to other published entries
 *
 * @package Bloom_UX\Outdated_Pages
 */

namespace Bloom_UX\Outdated_Pages\Checks;

/**
 * Is the entry parent to other published entries
 */
class Is_Post_Parent extends Abstract_Check implements Page_Check {

	/**
	 * Refresh whether the entry is parent
	 *
	 * @return void
	 */
	public function refresh() {
		global $wpdb;
		// phpcs:ignore
		$is_post_parent = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_status = 'publish' LIMIT 1",
				$this->post->ID
			)
		);
		update_post_meta( $this->post->ID, '_b_op__is_post_parent', (int) $is_post_parent );
	}

	/**
	 * Indicates if the entry has published children
	 *
	 * @return bool True if the entry has published children
	 */
	public function check(): bool {
		return (bool) get_post_meta( $this->post->ID, '_b_op__is_post_parent', true );
	}

}
