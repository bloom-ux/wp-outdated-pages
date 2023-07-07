<?php
/**
 * Controller for the admin page
 *
 * @package Bloom_UX\Outdated_Pages
 * phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion
 */

namespace Bloom_UX\Outdated_Pages;

use Queulat\Helpers\Abstract_Admin;

/**
 * Admin page for the plugin
 */
class Admin_Page extends Abstract_Admin {

	/**
	 * Hold the path to js/css dependencies
	 *
	 * @var stdClass
	 */
	private $dependencies;

	/**
	 * Initialize the admin page
	 *
	 * @return void
	 */
	public function init() {
		parent::init();
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Register and enqueue scripts and styles
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		$this_id = get_plugin_page_hookname( $this->get_id(), $this->get_parent_page() );
		if ( get_current_screen()->id !== $this_id ) {
			return;
		}
		if ( ! $this->dependencies ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$this->dependencies = json_decode( file_get_contents( __DIR__ . '/../assets/dist/manifest.json' ) );
		}
		wp_register_script(
			'bloom-ux-outdated-pages-runtime',
			site_url( $this->dependencies->{'runtime.js'} ),
			array(),
			null,
			true
		);
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
		wp_enqueue_style(
			'bloom-ux-outdated-pages',
			site_url( $this->dependencies->{'backend-scripts.css'} ),
			array( 'list-tables' ),
			null,
			'all'
		);
	}

	/**
	 * Get JS localization data
	 *
	 * Use for passing info to the JS app.
	 *
	 * @return array API and ajax endpoints and other data for the js app
	 */
	private function get_localization_data() : array {
		$data = array(
			'requestUri'               => rest_url( '/wp/v2/pages' ),
			'baseEditUri'              => admin_url( 'post.php' ),
			'ajaxDeleteEndpoint'       => add_query_arg( 'action', 'outdated-pages__delete', admin_url( 'admin-ajax.php' ) ),
			'ajaxCheckLinksEndpoint'   => add_query_arg( 'action', 'outdated-pages__check-links', admin_url( 'admin-ajax.php' ) ),
			'ajaxCheckStatusEndpoint'  => add_query_arg( 'action', 'outdated-pages__check-status', admin_url( 'admin-ajax.php' ) ),
			'ajaxCreateExportEndpoint' => add_query_arg( 'action', 'outdated-pages__create-export', admin_url( 'admin-ajax.php' ) ),
		);
		return $data;
	}

	/**
	 * Get admin page ID
	 *
	 * @return string 'outdated_pages_admin'
	 */
	public function get_id(): string {
		return 'outdated_pages_admin';
	}

	/**
	 * Get admin page title
	 *
	 * @return string Admin page title
	 */
	public function get_title(): string {
		return __( 'Páginas desactualizadas', 'bloom_ux_outdated_pages' );
	}

	/**
	 * Get the menu title
	 *
	 * @return string Menu title for admin
	 */
	public function get_menu_title(): string {
		return $this->get_title();
	}

	/**
	 * Get admin page form elements
	 *
	 * @return array Empty array (we're using vue to build UI)
	 */
	public function get_form_elements(): array {
		return array();
	}

	/**
	 * Sanitize submitted data
	 *
	 * @param array $input User submitted data (unslashed).
	 * @return array Sanitized data
	 */
	public function sanitize_data( $input ): array {
		return array();
	}

	/**
	 * Get validation rules for submitted data
	 *
	 * @param array $sanitized_data Submitted data.
	 * @return array Validation rules
	 */
	public function get_validation_rules( array $sanitized_data ): array {
		return array();
	}

	/**
	 * Get admin menu icon
	 *
	 * @return string Empty string, no icon
	 */
	public function get_icon(): string {
		return '';
	}

	/**
	 * Process admin page data submission
	 *
	 * @param array $data Submitted data.
	 * @return bool True (not really processing data here)
	 */
	public function process_data( array $data ): bool {
		return true;
	}

	/**
	 * Get the parent for the menu page
	 *
	 * @return string Parent for "page" menu
	 */
	public function get_parent_page(): string {
		return 'edit.php?post_type=page';
	}

	/**
	 * Output for the admin page
	 *
	 * We'll just output the container element, let vue take care
	 *
	 * @return void
	 */
	public function admin_page() {
		echo '<div id="outdated-pages-admin"></div>';
	}

}
