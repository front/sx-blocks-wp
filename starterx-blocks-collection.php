<?php
/**
 * Plugin Name: Starterx Blocks Collection
 * Author: Frontkom
 * Author URI: https://frontkom.no
 * Version: 1.0
 * Description: A collection of Gutenberg Blocks by Frontkom
 * License: GPL2
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

function starterx_collection_plugin_load_plugin_textdomain() {
  load_plugin_textdomain( 'starterx-blocks-collection', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'starterx_collection_plugin_load_plugin_textdomain' );

// Including blocks
include 'blocks/related-articles/index.php';
