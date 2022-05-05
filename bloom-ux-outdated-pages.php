<?php
/**
 * Plugin Name: Outdated pages
 * Description: List ancient pages and check if they have links
 */

namespace Bloom_UX\Outdated_Pages;

( function(){
	require_once __DIR__ . '/src/class-plugin.php';
	Plugin::get_instance()->init();
} )( );
