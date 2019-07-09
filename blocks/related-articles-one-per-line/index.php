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
 * Registering assets                        *
 * of the `starterx/related-articles-one-per-line` block. *
 *********************************************/
/**
 * Registers all block assets so that they can be enqueued through Gutenberg in
 * the corresponding context.
 *
 * Passes translations to JavaScript.
 */
function starterx_related_articles_register_block_one_per_line() {
	if ( ! function_exists( 'register_block_type' ) ) {
		// Gutenberg is not active.
		return;
	}

	$block = 'related-articles-one-per-line-one-per-line';

	wp_register_script(
		'starterx-blocks-collection-' . $block,
		plugins_url( 'build/index.js', __FILE__ ),
		array( 'wp-editor', 'wp-i18n', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n' ),
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
add_action( 'init', 'starterx_related_articles_register_block_one_per_line' );

/*********************************************
 * Changing REST API and adding image size   *
 * of the `starterx/related-articles-one-per-line` block. *
 *********************************************/

/**
 * Add more data in the REST API that we'll use in the related article.
 */
function starterx_related_articles_one_per_linerest_fields() {
	// Featured image urls.
	register_rest_field( 'post', 'featured_media_data',
	array(
			'get_callback' => 'starterx_related_articles_one_per_linefeatured_media_data',
			'update_callback' => null,
			'schema' => array(
					'description' => __( 'Different sized featured images', 'starterx-blocks-collection' ),
					'type' => 'array',
			),
	)
	);
}
add_action( 'rest_api_init', 'starterx_related_articles_one_per_linerest_fields' );

/**
 * Get the featured image data that the post will use.
 */
function starterx_related_articles_one_per_linefeatured_media_data( $object ) {
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
function starterx_related_articles_one_per_lineimage_sizes() {
		add_image_size( 'startex-square-196', 196, 196, true );
}
add_action( 'after_setup_theme', 'starterx_related_articles_one_per_lineimage_sizes' );


/*********************************************
 * Server-side rendering                     *
 * of the `starterx/related-articles-one-per-line` block. *
 *********************************************/

/**
 * Renders the `starterx/related-articles-one-per-line` block on server.
 *
 * @param array $attributes The block attributes.
 *
 * @return string Returns the post content with latest posts added.
 */
function render_block_startex_related_articles_one_per_line( $attributes ) {
	$args = array(
		'posts_per_page'   => $attributes['columns'],
		'post_status'      => 'publish',
		'order'            => 'desc',
		'orderby'          => 'date',
		'suppress_filters' => false,
		'offset'					 => $attributes['offset'],
	);

	if ( isset( $attributes['categories'] ) ) {
		$args['category'] = $attributes['categories'];
	}

	if ( isset( $attributes['tags'] ) ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'post_tag',
				'filed' => 'id',
				'terms' => $attributes['tags']
			)
		);
	}

	$recent_posts = get_posts( $args );
	$list_items_markup = '';
    $list_items_markup .= '<div class="articles articles--from-block articles--one-col">';

	// Get total of posts
	$n_posts = count($recent_posts);

	for ( $i = 0; $i < $attributes['columns']; $i++ ) {
		if ( $i < $n_posts ) {

            $post = $recent_posts[$i];

			$post_id = $post->ID;
			$post_title = $post->post_title;
			$post_excerpt = $post->post_excerpt;
			$post_link = $post->guid;
			$post_attachment = wp_get_attachment_url( get_post_thumbnail_id( $post_id ) );
			$post_categories_ids = wp_get_post_categories( $post_id );
            $post_categories = [];
			$post_author = get_the_author_meta('nicename', $post->post_author);
            $post_date = '';

            if ( isset( $attributes['displayPostDate'] ) && $attributes['displayPostDate'] ) {
                $post_date = get_the_date('', $post);
            }
            
            if ($post_categories_ids) {
                foreach ($post_categories_ids as $key => $post_categories_id) {
                    $category_link = get_category_link($post_categories_id);
                    $category_name = get_cat_name($post_categories_id);
                    $post_categories[] = '<a href="'. $category_link .'">'. $category_name .'</a>';
                }
            }

            if (strlen($post_excerpt) > 500) {
                $post_excerpt = substr($post_excerpt, '0', '500') . '...';
            }

			$list_items_markup .= '
                <article class="article">
                    <img class="article__img" src="' . $post_attachment . '" alt="article img">
                    <div class="article-info">
                        <div class="article-info__header">
                            <div>
                                <hr class="wp-block-separator is-short no-margin-top no-margin-left">
                                <div class="article-info__category">' . implode(",", $post_categories) . '</div>
                                <a href="'. $post_link .'">
                                    <h2 class="article-info__title">' . $post_title . '</h2>
                                </a>
                            </div>
                            <img class="article__img-mobile" src="' . $post_attachment . '" alt="article img">
                        </div>
                      <div class="article-info__credits">
                        '. __('by', 'starterx-blocks-collection') .' <span class="article-info__author">' . $post_author . '</span> '. ($post_date ? "|" : "") .' <span class="article-info__date">'. $post_date .' </span>
                      </div>
                      <p class="article-info__content">
                        ' . $post_excerpt .'
                      </p>
                    </div>
                </article>
                ';
		} else {
			$list_items_markup .= '<div class="related-articles-one-per-line__item"></div>';
		}
	}

    $list_items_markup .= '</div>';

	$class = 'related-articles-one-per-line';

	if ( isset( $attributes['className'] ) ) {
		$class .= ' ' . $attributes['className'];
	}

	// if ( isset( $attributes['displayPostDate'] ) && $attributes['displayPostDate'] ) {
	// 	$class .= ' has-dates';
	// }

	$columnsClass = 'related-articles-one-per-line__items';
	if ( isset( $attributes['columns'] ) ) {
		$columnsClass .= ' has-' . $attributes['columns'] . '-columns';
	}

	$block_content = sprintf(
		'<div class="%1$s">'.
			'<h%2$s class="related-articles-one-per-line__title">%3$s</h%2$s>'.
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
 * Registers the `starterx/related-articles-one-per-line` block on server-side.
 */
function register_block_startex_related_articles_one_per_line() {
	if ( ! function_exists( 'register_block_type' ) ) {
		// Gutenberg is not active.
		return;
	}

	register_block_type(
		'starterx/related-articles-one-per-line',
		array(
			'attributes' => array(
				'title' => array(
					'type' => 'string',
					'selector' => 'h1,h2,h3,h4,h5,h6',
					'default' => __( 'Related Articles One Per Line', 'starterx-blocks-collection' ),
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
				'offset' => array(
					'type'    => 'number',
					'default' => 0,
				),
			),
			'render_callback' => 'render_block_startex_related_articles_one_per_line',
		)
	);
}
add_action( 'init', 'register_block_startex_related_articles_one_per_line' );
