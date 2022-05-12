<?php

namespace Bloom_UX\Outdated_Pages;

use WP_Background_Process;
use WP_Query;

class Check_Links_Process extends WP_Background_Process {

	private $status = [];

	protected $action = 'outdated_pages_check_link';

	public function __construct() {
		parent::__construct();
		$this->status = get_option( $this->get_option_name(), [] );
	}

	private function get_option_name() : string {
		return "{$this->action}__status";
	}

	public function save() {
		parent::save();
		$this->init_status();
		return $this;
	}

	private function init_status() {
		$status = array(
			'total'      => count( $this->data ),
			'current'    => 0,
			'start_time' => time(),
			'end_time'   => null,
			'status'     => 'created',
			'has_links'  => []
		);
		$this->status = $status;
		$this->update_data();
	}

	private function update_data() {
		update_option( $this->get_option_name(), $this->status );
	}

	private function search_links( $item ) : bool {
		$page = get_post( $item );
		$permalink = get_permalink( $page );

		// buscar enlaces en otros posts
		$maybe_has_links = new WP_Query( array(
			'post_status'    => 'any',
			'post_type'      => 'any',
			's'              => $permalink,
			'no_found_rows'  => true,
			'posts_per_page' => 1
		) );
		if ( $maybe_has_links->have_posts() ) {
			return true;
		}

		global $wpdb;

		// es post_parent?
		$is_post_parent = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_status = 'publish'",
				$item
			)
		);
		if ( $is_post_parent ) {
			return true;
		}

		// buscar referencias en menús de navegación
		$is_menu_item = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_id FROM $wpdb->postmeta WHERE meta_key = '_menu_item_object_id' AND meta_value = %d",
				$item
			)
		);
		if ( $is_menu_item ) {
			return true;
		}
		return false;
	}

    protected function task( $item ) {
		$found = true;
		$has_links = Has_Links_Repository::get( $item, $found );

		if ( ! $found ) {
			$has_links = $this->search_links( $item );
		}

		Has_Links_Repository::set( $item, $has_links );

		// actualizar status
		$this->status['status'] = 'working';
		$this->status['current'] = $this->status['current'] + 1;
		$this->status['has_links'][ $item ] = (bool) $has_links;
		$this->update_data();

		return false;
	}

	protected function complete() {
		parent::complete();
		$this->status['end_time'] = time();
		$this->status['status'] = 'finished';
		$this->update_data();
	}

	public function get_status() {
		return get_option( $this->get_option_name(), null );
	}

}
