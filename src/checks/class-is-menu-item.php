<?php
/**
 * Check if the entry is on a navigation menu
 *
 * @package Bloom_UX\Outdated_Pages
 */

namespace Bloom_UX\Outdated_Pages\Checks;

/**
 * Check if entry is on a navigation menu
 */
class Is_Menu_Item extends Abstract_Check implements Page_Check {

	/**
	 * Refresh whether the entry is on a navigation menu
	 *
	 * @return void
	 */
	public function refresh() {
		global $wpdb;
		// phpcs:ignore
		$is_menu_item = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_id FROM $wpdb->postmeta WHERE meta_key = '_menu_item_object_id' AND meta_value = %d LIMIT 1",
				$this->post->ID
			)
		);
		update_post_meta( $this->post->ID, '_b_op__is_menu_item', (int) $is_menu_item );
	}

	/**
	 * Indicates whether the entry is on a navigation menu
	 *
	 * @return bool True if present on a nav menu
	 */
	public function check(): bool {
		return (bool) get_post_meta( $this->post->ID, '_b_op__is_menu_item', true );
	}

}
