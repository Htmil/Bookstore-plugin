<?php

/**
 * Plugin name: Bookstore
 * Description: A plugin to manage books
 * Version: 1.0
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('init', 'bookstore_register_book_post_type');

function bookstore_register_book_post_type()
{
    // Register the Book post type
    $args = array(
        'labels' => array(
            'name'          => 'Books',
            'singular_name' => 'Book',
            'menu_name'     => 'Books',
            'add_new'       => 'Add New Book',
            'add_new_item'  => 'Add New Book',
            'new_item'      => 'New Book',
            'edit_item'     => 'Edit Book',
            'view_item'     => 'View Book',
            'all_items'     => 'All Books',
        ),
        'public' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields'),
    );

    register_post_type('book', $args);

    // Register the Genre taxonomy for the Book post type
    $taxonomy_args = array(
        'labels' => array(
            'name'          => 'Genres',
            'singular_name' => 'Genre',
            'edit_item'     => 'Edit Genre',
            'update_item'   => 'Update Genre',
            'add_new_item'  => 'Add New Genre',
            'new_item_name' => 'New Genre Name',
            'menu_name'     => 'Genre',
        ),
        'hierarchical' => true,
        'rewrite'      => array('slug' => 'genre'),
        'show_in_rest' => true,
    );
    register_taxonomy('genre', 'book', $taxonomy_args);
}

add_filter( 'postmeta_form_keys', 'bookstore_add_isbn_to_quick_edit', 10, 2 );
function bookstore_add_isbn_to_quick_edit($keys, $post){
    if ($post->post_type === 'book') {
        $keys[] = 'isbn';
    }
    return $keys; 
}


add_action( 'wp_enqueue_scripts', 'bookstore_enqueue_scripts' );
function bookstore_enqueue_scripts() {
    wp_enqueue_style(
        'bookstore-style',
        plugins_url() . '/bookstore/bookstore.css'
    );
}

// Add shortcode to display search form
function bookstore_search_form() {
    ob_start();
    ?>
    <form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
        <input type="hidden" name="post_type" value="book" /> <!-- Search only for books -->
        <input type="text" name="s" placeholder="Search Books..." value="<?php echo get_search_query(); ?>" />
        
        <!-- Genre Dropdown -->
        <select name="genre">
            <option value="">Select Genre</option>
            <?php
            $terms = get_terms(array(
                'taxonomy' => 'genre',
                'hide_empty' => true,
            ));
            foreach ($terms as $term) {
                echo '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
            }
            ?>
        </select>
        
        <input type="submit" value="Search">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('bookstore_search', 'bookstore_search_form');

// Modify search query to include genre and book post type
function bookstore_search_filter($query) {
    // Only modify the main query and on the front-end search page
    if ($query->is_search() && !is_admin() && $query->is_main_query()) {
        
        // Ensure we only search for books
        if (isset($_GET['post_type']) && $_GET['post_type'] === 'book') {
            $query->set('post_type', 'book');
        }

        // Handle taxonomy (genre) search
        if (!empty($_GET['genre'])) {
            $query->set('tax_query', array(
                array(
                    'taxonomy' => 'genre',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field($_GET['genre']),
                ),
            ));
        }
    }
}
add_action('pre_get_posts', 'bookstore_search_filter');
