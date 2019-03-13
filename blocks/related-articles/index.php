<?php
/**
 * Plugin Name: Starterx Blocks Collection
 * Author: Frontkom
 * Author URI: https://frontkom.no
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

include 'src/index.php';

/**
 * Registers all block assets so that they can be enqueued through Gutenberg in
 * the corresponding context.
 *
 * Passes translations to JavaScript.
 */
function starterx_blocks_collection_related_articles_register_block() {
	if ( ! function_exists( 'register_block_type' ) ) {
		// Gutenberg is not active.
		return;
	}

	$block = 'related-articles';

	wp_register_script(
		'starterx-blocks-collection-' . $block,
		plugins_url( 'build/index.js', __FILE__ ),
		array( 'wp-editor', 'wp-i18n', 'wp-element', 'wp-components', 'wp-data' ),
		filemtime( plugin_dir_path( __FILE__ ) . 'build/index.js' )
	);

	wp_register_style(
		'starterx-blocks-collection-' . $block . '-editor',
		plugins_url( 'build/editor.css', __FILE__ ),
		array( 'wp-edit-blocks' ),
		filemtime( plugin_dir_path( __FILE__ ) . 'build/editor.css' )
	);

	wp_register_style(
		'starterx-blocks-collection-' . $block,
		plugins_url( 'build/style.css', __FILE__ ),
		array( ),
		filemtime( plugin_dir_path( __FILE__ ) . 'build/style.css' )
	);

	register_block_type( 'starterx-blocks-collection/' . $block, array(
		'style' => 'starterx-blocks-collection-' . $block,
		'editor_style' => 'starterx-blocks-collection-' . $block. '-editor',
		'editor_script' => 'starterx-blocks-collection-' . $block,
	) );
}
add_action( 'init', 'starterx_blocks_collection_related_articles_register_block' );
