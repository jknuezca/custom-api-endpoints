<?php
/**
 * Plugin Name: Custom API Endpoints
 * Description: This plugin adds custom endpoints to the WordPress REST API.
 * Version: 1.0
 * Author: Jorge
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Register custom REST API route
add_action('rest_api_init', 'register_custom_route');

function register_custom_route() {
    register_rest_route('custom/v3', '/all-posts/', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'get_all_posts',
    ));
}

function get_all_posts($request) {
    // Check user capability
    // if (!current_user_can('read')) {
    //     return new WP_Error('rest_forbidden', __('You cannot view the posts resource.'), array('status' => is_user_logged_in() ? 403 : 401));
    // }

    // Verify nonce
    // $nonce = $request->get_header('X-WP-Nonce');
    // if (!wp_verify_nonce($nonce, 'wp_rest')) {
    //     return new WP_Error('rest_forbidden', __('Nonce verification failed.'), array('status' => 403));
    // }

    // Get parameters from request and sanitize them
    $category = sanitize_text_field($request->get_param('category'));

    // Query to get all published posts
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    );

    if ($category) {
        $args['category_name'] = $category;
    }

    $posts_query = new WP_Query($args);

    if (!$posts_query->have_posts()) {
        return new WP_Error('no_posts', 'No posts found', array('status' => 404));
    }

    $posts = $posts_query->posts;

    // Prepare the post data
    $response_data = array();
    foreach ($posts as $post) {
        $post_thumbnail_id = get_post_thumbnail_id($post->ID);
        $post_thumbnail_url = wp_get_attachment_url($post_thumbnail_id);
        $permalink = get_permalink($post->ID);
        $post_categories = get_the_category($post->ID);
        $categories = array();
        foreach ($post_categories as $cat) {
            $categories[] = $cat->name;
        }

        $post_tags = get_the_tags($post->ID);
        $tags = array();
        if ($post_tags) {
            foreach ($post_tags as $tag) {
                $tags[] = $tag->name;
            }
        }
        
        // Allowed HTML tags and attributes
        $allowed_html = array(
            'a' => array(
                'href' => array(),
                'title' => array(),
            ),
            'br' => array(),
            'em' => array(),
            'strong' => array(),
            'img' => array(
                'src' => array(),
                'alt' => array(),
                'width' => array(),
                'height' => array(),
            ),
            'p' => array(),
            'ul' => array(),
            'ol' => array(),
            'li' => array(),
        );

        $response_data[] = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            // 'content' => $post->post_content,
            'content' => wp_kses($post->post_content, $allowed_html),
            'author' => $post->post_author,
            'date' => $post->post_date,
            'excerpt' => $post->post_excerpt,
            'status' => $post->post_status,
            'comment_status' => $post->comment_status,
            'ping_status' => $post->ping_status,
            'post_name' => $post->post_name,
            'post_modified' => $post->post_modified,
            'post_modified_gmt' => $post->post_modified_gmt,
            'post_parent' => $post->post_parent,
            'guid' => $post->guid,
            'menu_order' => $post->menu_order,
            'post_type' => $post->post_type,
            'post_mime_type' => $post->post_mime_type,
            'comment_count' => $post->comment_count,
            'filter' => $post->filter,
            'thumbnail' => $post_thumbnail_url,
            'permalink' => $permalink,
            'categories' => $categories,
            'tags' => $tags,
        );
    }

    // Return the response
    return new WP_REST_Response($response_data, 200);
}

?>