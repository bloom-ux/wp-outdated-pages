<?php
/**
 * Background process to check entries, generate export and send as e-mail attachment
 *
 * @package Bloom_UX\Outdated_Pages
 */

namespace Bloom_UX\Outdated_Pages;

use WP_Post;
use WP_Error;
use WP_Query;
use WP_User_Query;
use WP_Background_Process;

/**
 * Check entries, generate export and send as e-mail attachment
 */
class Create_Export_Process extends WP_Background_Process {

	/**
	 * Hold the process identifier
	 *
	 * @var string
	 */
	protected $action = 'b_op_create_export';

	/**
	 * Hold the current run ID
	 *
	 * @var string
	 */
	protected $current_run = '';

	/**
	 * Use as query param to get entries before this date
	 *
	 * @var string
	 */
	protected $before = '';

	/**
	 * Hold the generating user id
	 *
	 * @var int
	 */
	protected $user_id = 0;

	/**
	 * Process one item from the queue
	 *
	 * @param array $item A queued item, with run_id, user_id, before and post_id keys.
	 * @return false False to remove item from the queue
	 */
	protected function task( $item ) {
		if ( ! isset( $item['run_id'] ) ) {
			return false;
		}
		$this->current_run = $item['run_id'];
		$this->user_id     = (int) $item['user_id'];
		$this->before      = $item['before'];
		$page_check        = new Page_Checks( $item['post_id'] );

		// We're trying to get fresh data here.
		if ( ! $page_check->was_recently_checked() ) {
			$page_check->update();
		}
		return false;
	}

	/**
	 * Trigger when done with the queue.
	 *
	 * Creates an export file and sends it to the requesting user.
	 *
	 * @return void
	 */
	protected function complete() {
		parent::complete();

		$export_data = $this->generate_export();
		$upload_dir  = wp_upload_dir();
		$filename    = 'outdated-pages-export--' . substr( $this->current_run, 0, 8 ) . '.csv';
		$full_path   = trailingslashit( $upload_dir['basedir'] ) . $filename;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
		file_put_contents(
			$full_path,
			$export_data
		);
		$this->send_export( $full_path, $this->user_id );
	}

	/**
	 * Send the generated export file as e-mail attachment
	 *
	 * @param string $full_path The full path to the export file.
	 * @param int    $user_id User ID that will receive the file.
	 * @return bool True if successfully sent, false otherwhise
	 *
	 * phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fopen, WordPress.WP.AlternativeFunctions.file_system_read_fclose
	 */
	private function send_export( $full_path, $user_id ) : bool {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return false;
		}
		$subject = sprintf(
			// translators: %s is the name of the blog.
			_x( '[%s] Exportación de páginas desactualizadas', 'export email', 'bloom_outdated_pages' ),
			get_bloginfo( 'name' )
		);

		$message = sprintf(
			// translators: %s is the basename of the export file.
			_x(
				"
Revisa la lista de páginas desactualizadas en el archivo adjunto a este correo.\n
---
%s
				",
				'export email',
				'bloom_outdated_pages'
			),
			basename( $full_path )
		);
		$headers = array();
		add_action(
			'wp_mail_failed',
			function( WP_Error $error ) {
				if ( function_exists( 'SimpleLogger' ) ) {
					SimpleLogger()->error(
						$error->get_error_message(),
						(array) $error
					);
				}
			}
		);
		// Send as e-mail.
		$sent = wp_mail(
			$user ? $user->user_email : get_bloginfo( 'admin' ),
			$subject,
			$message,
			$headers,
			array(
				$full_path,
			)
		);
		wp_delete_file( $full_path );
		return $sent;
	}

	/**
	 * Create a CSV export and return as string
	 *
	 * @return string CSV string with export data
	 */
	private function generate_export() : string {
		$params  = array(
			'date_query' => array(
				array(
					'column' => 'post_modified_gmt',
					'before' => $this->before,
				),
			),
		);
		$fp      = fopen( 'php://temp/maxmemory:' . ( 12 * 1024 * 1024 ), 'r+' );
		$headers = array(
			'ID',
			'Título',
			'Fecha publicación',
			'Última actualización',
			'E-mail autor',
			'Tiene enlaces entrantes',
			'Dirección'
		);
		fputcsv( $fp, $headers );
		$entries    = $this->get_export_entries( $params );
		$author_ids = array_reduce(
			$entries->posts,
			function( array $carry, WP_Post $post ) : array {
				$carry[] = (int) $post->post_author;
				return $carry;
			},
			array()
		);
		$author_ids = array_unique( $author_ids );
		// Get all authors data at once.
		$authors = new WP_User_Query(
			array(
				'include' => $author_ids,
			)
		);
		foreach ( $entries->posts as $post ) {
			$page_check = new Page_Checks( $post );
			$row        = array(
				$post->ID,
				$post->post_title,
				$post->post_date,
				$post->post_modified,
				get_user_by( 'id', $post->post_author )->user_email,
				(bool) $page_check->any_check(),
				get_permalink( $post->ID )
			);
			fputcsv( $fp, $row );
		}
		rewind( $fp );
		$output = stream_get_contents( $fp );
		fclose( $fp );
		return $output;
	}

	/**
	 * Get contents that will be exported
	 *
	 * @param array $params Additional query params.
	 * @return WP_Query Posts that will be part of the export.
	 */
	public static function get_export_entries( array $params = array() ) {
		$default_params = array(
			'post_type'      => 'page',
			'orderby'        => 'modified',
			'order'          => 'ASC',
			'posts_per_page' => -1, // 	phpcs:ignore
			'post_status'    => 'publish',
			'lang'           => '',
		);
		$query_params   = wp_parse_args( $params, $default_params );
		$query          = new WP_Query( $query_params );
		return $query;
	}

}
