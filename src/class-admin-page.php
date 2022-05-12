<?php

namespace Bloom_UX\Outdated_Pages;

use WP_Query;
use Queulat\Helpers\Abstract_Admin;

class Admin_Page extends Abstract_Admin {

	private $dependencies;

	public function init() {
		parent::init();
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	public function admin_enqueue_scripts() {
		$this_id = get_plugin_page_hookname( $this->get_id(), $this->get_parent_page() );
		if ( get_current_screen()->id !== $this_id ) {
			return;
		}
		if ( ! $this->dependencies ) {
			$this->dependencies = json_decode( file_get_contents( __DIR__ . '/../assets/dist/manifest.json' ) );
		}
		// runtime
		wp_register_script(
			'bloom-ux-outdated-pages-runtime',
			site_url( $this->dependencies->{'runtime.js'} ),
			array(),
			null,
			true
		);
		// backend-scripts.js
		wp_enqueue_script(
			'bloom-ux-outdated-pages-backend',
			site_url( $this->dependencies->{'backend-scripts.js'} ),
			array( 'bloom-ux-outdated-pages-runtime' ),
			null,
			true
		);
		wp_localize_script(
			'bloom-ux-outdated-pages-backend',
			'Outdated_Pages',
			$this->get_localization_data()
		);
		// backend-scripts.css
		wp_enqueue_style(
			'bloom-ux-outdated-pages',
			site_url( $this->dependencies->{'backend-scripts.css'} ),
			array( 'list-tables' ),
			null,
			'all'
		);
	}

	private function get_localization_data() : array {
		$data = array(
			'requestUri' => rest_url( '/wp/v2/pages' ),
			'baseEditUri' => admin_url( 'post.php' ),
			'ajaxDeleteEndpoint' => add_query_arg( 'action', 'outdated-pages__delete', admin_url( 'admin-ajax.php' ) ),
			'ajaxCheckLinksEndpoint' => add_query_arg( 'action', 'outdated-pages__check-links', admin_url( 'admin-ajax.php' ) ),
			'ajaxCheckStatusEndpoint' => add_query_arg( 'action', 'outdated-pages__check-status', admin_url( 'admin-ajax.php' ) )
		);
		return $data;
	}

    public function get_id(): string {
		return 'outdated_pages_admin';
	}

    public function get_title(): string {
		return __( 'Páginas desactualizadas', 'outdated_pages' );
	}

    public function get_menu_title(): string {
		return $this->get_title();
	}

    public function get_form_elements(): array {
		return array();
	}

    public function sanitize_data($input): array {
		return array();
	}

    public function get_validation_rules(array $sanitized_data): array {
		return array();
	}

    public function get_icon(): string {
		return '';
	}

    public function process_data(array $data): bool {
		return true;
	}

	public function get_parent_page(): string {
		return 'edit.php?post_type=page';
	}

	private function get_items() : array {
		$posts_per_page = 25;
		$page = 1;
		$args = array(
			'post_type'      => 'page',
			'posts_per_page' => $posts_per_page,
			'paged'          => $page,
			'post_status'    => 'publish'
		);
		$pages = new WP_Query( $args );
		return $pages->posts;
	}

	public function admin_page() {
		echo '<div id="outdated-pages-admin"></div>';
	}

}
