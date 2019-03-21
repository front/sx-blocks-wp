<?php
/**
 * Plugin Name: Starterx Blocks Collection
 * Author: Frontkom
 * Author URI: https://frontkom.no
 * Version: 1.0.0
 *
 * @package starterx-blocks-collection
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*********************************************
 * Registering assets and translations       *
 * of the `starterx/related-articles` block. *
 *********************************************/

/**
 * Load all translations for our plugin from the MO file.
*/
add_action( 'init', 'starterx_related_articles_load_textdomain' );

function starterx_related_articles_load_textdomain() {
	load_plugin_textdomain( 'starterx-blocks', false, basename( __DIR__ ) . '/languages' );
}

/**
 * Registers all block assets so that they can be enqueued through Gutenberg in
 * the corresponding context.
 *
 * Passes translations to JavaScript.
 */
function starterx_related_articles_register_block() {
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
add_action( 'init', 'starterx_related_articles_register_block' );


/*********************************************
 * Changing REST API and adding image size   *
 * of the `starterx/related-articles` block. *
 *********************************************/

/**
 * Add more data in the REST API that we'll use in the related article.
 */
function starterx_related_articles_rest_fields() {
	// Featured image urls.
	register_rest_field( 'post', 'featured_media_data',
	array(
			'get_callback' => 'starterx_related_articles_featured_media_data',
			'update_callback' => null,
			'schema' => array(
					'description' => __( 'Different sized featured images' ),
					'type' => 'array',
			),
	)
	);
}
add_action( 'rest_api_init', 'starterx_related_articles_rest_fields' );

/**
 * Get the featured image data that the post will use.
 */
function starterx_related_articles_featured_media_data( $object ) {
	// $attachment = get_post( $object['featured_media'] );

	return array(
		'id' => $object['featured_media'],
		// 'caption' => $attachment->post_excerpt,
		// 'description' => $attachment->post_content,
		'alt_text' => get_post_meta( $object['featured_media'], '_wp_attachment_image_alt', true),
		'media_details' => wp_get_attachment_metadata($object['featured_media']),
		'source_url' => wp_get_attachment_image_src( $object['featured_media'], 'startex-square-196', false )[0],
	);
}

/**
 * Add image sizes.
 */
function starterx_related_articles_image_sizes() {
		add_image_size( 'startex-square-196', 196, 196, true );
}
add_action( 'after_setup_theme', 'starterx_related_articles_image_sizes' );


/*********************************************
 * Server-side rendering                     *
 * of the `starterx/related-articles` block. *
 *********************************************/

/**
 * Renders the `starterx/related-articles` block on server.
 *
 * @param array $attributes The block attributes.
 *
 * @return string Returns the post content with latest posts added.
 */
function render_block_startex_related_articles( $attributes ) {
	$args = array(
		'posts_per_page'   => $attributes['columns'],
		'post_status'      => 'publish',
		'order'            => 'desc',
		'orderby'          => 'date',
		'suppress_filters' => false,
	);

	if ( isset( $attributes['categories'] ) ) {
		$args['category'] = $attributes['categories'];
	}

	if ( isset( $attributes['tags'] ) ) {
		$args['tags'] = $attributes['tags'];
	}

	$recent_posts = get_posts( $args );
	$list_items_markup = '';

	foreach ( $recent_posts as $post ) {
		$title = get_the_title( $post );
		$attachment = get_post( get_post_thumbnail_id( $post->ID ) );

		if ( ! $title ) {
			$title = __( '(Untitled)' );
		}

		$list_items_markup .= sprintf(
			'<div class="related-articles__item wp-block-column">'.
				'<a href="%1$s">'.
					'<img src="%2$s" alt="%3$s" />',
			esc_url( get_permalink( $post ) ),
			$attachment->guid,
			get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true )
		);

		if ( isset( $attributes['displayPostDate'] ) && $attributes['displayPostDate'] ) {
			$list_items_markup .= sprintf(
				'<time datetime="%1$s" class="related-articles__post-date">%2$s</time>',
				esc_attr( get_the_date( 'c', $post ) ),
				esc_html( get_the_date( '', $post ) )
			);
		}

		$list_items_markup .= sprintf(
					'<p class="related-articles__item__title">%1$s</p>'.
				'</a>'.
			'</div>',
			$title
		);
	}

	$class = 'related-articles';
	if ( isset( $attributes['className'] ) ) {
		$class .= ' ' . $attributes['className'];	}

	// if ( isset( $attributes['displayPostDate'] ) && $attributes['displayPostDate'] ) {
	// 	$class .= ' has-dates';
	// }

	$columnsClass = 'related-articles__items wp-block-columns';
	if ( isset( $attributes['columns'] ) ) {
		$columnsClass .= ' columns-' . $attributes['columns'];
	}

	$block_content = sprintf(
		'<div class="%1$s">'.
			'<h%2$s class="related-articles__title">%3$s</h%2$s>'.
			'<div class="%4$s">%5$s</div>'.
		'</div>',
		esc_attr( $class ),
		$attributes['titleLevel'],
		$attributes['title'],
		esc_attr( $columnsClass ),
		$list_items_markup
	);

	return $block_content;
}

/**
 * Registers the `starterx/related-articles` block on server-side.
 */
function register_block_startex_related_articles() {
	if ( ! function_exists( 'register_block_type' ) ) {
		// Gutenberg is not active.
		return;
	}

	register_block_type(
		'starterx/related-articles',
		array(
			'attributes' => array(
				'title' => array(
					'type' => 'string',
					'selector' => 'h1,h2,h3,h4,h5,h6',
					'default' => 'Related Articles',
				),
				'titleLevel' => array(
					'type' => 'number',
					'default' => 3,
				),
				'categories' => array(
					'type' => 'string',
				),
				'tags' => array(
					'type' => 'string',
				),
				'className' => array(
					'type' => 'string',
				),
				'columns' => array(
					'type'    => 'number',
					'default' => 4,
				),
				'displayPostDate' => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
			'render_callback' => 'render_block_startex_related_articles',
		)
	);
}
add_action( 'init', 'register_block_startex_related_articles' );
