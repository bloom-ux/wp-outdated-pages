<?php

namespace Bloom_UX\Outdated_Pages;

class Has_Links_Repository {
	const CACHE_IS_VALID = HOUR_IN_SECONDS;
	public static function get( int $id, &$found = true ) : bool {
		$check = get_post_meta( $id, '_has_links_checked', true );
		if ( ! $check ) {
			$found = false;
			return false;
		}
		if ( (int) time() - (int) $check < static::CACHE_IS_VALID ) {
			$has_links = get_post_meta( $id, '_has_links', true );
			if ( ! $has_links ) {
				$found = false;
			}
			return (bool) $has_links;
		}
		return false;
	}
	public static function set( int $id, bool $has_links ) {
		update_post_meta( $id, '_has_links', (int) $has_links );
		update_post_meta( $id, '_has_links_checked', (int) time() );
	}
}
