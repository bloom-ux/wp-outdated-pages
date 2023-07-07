<?php
/**
 * Plugin Name: Outdated pages
 * Description: List ancient pages and check if they have links
 * Plugin URI: https://github.com/bloom-ux/wp-outdated-pages
 * Version: 0.2.0
 * Author: Bloom User Experience
 * Author URI: https://www.bloom.lat
 * License: GPL-3.0-or-later
 *
 * @package Bloom_UX\Outdated_Pages
 */

namespace Bloom_UX\Outdated_Pages;

( function() {
	require_once __DIR__ . '/src/class-plugin.php';
	Plugin::get_instance()->init();
} )();
