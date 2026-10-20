<?php
defined( 'ABSPATH' ) || exit;

class GA_Post_Type {

	public static function register() {
		register_post_type(
			GA_Plugin::PT,
			array(
				'labels'             => array(
					'name'               => __( 'Guest Authors', 'ga' ),
					'singular_name'      => __( 'Guest Author', 'ga' ),
					'add_new_item'       => __( 'Add New Guest Author', 'ga' ),
					'edit_item'          => __( 'Edit Guest Author', 'ga' ),
					'new_item'           => __( 'New Guest Author', 'ga' ),
					'view_item'          => __( 'View Guest Author', 'ga' ),
					'search_items'       => __( 'Search Guest Authors', 'ga' ),
					'not_found'          => __( 'No guest authors found.', 'ga' ),
					'not_found_in_trash' => __( 'No guest authors found in Trash.', 'ga' ),
					'all_items'          => __( 'All Guest Authors', 'ga' ),
					'menu_name'          => __( 'Guest Authors', 'ga' ),
				),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_rest'       => true,
				'show_in_menu'       => true,
				'menu_position'      => 25,
				'menu_icon'          => 'dashicons-admin-users',
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
				'has_archive'        => GA_Plugin::ARCHIVE_SLUG,
				'rewrite'            => array(
					'slug'       => GA_Plugin::SINGLE_SLUG,
					'with_front' => false,
				),
				'capability_type'    => 'post',
				'map_meta_cap'       => true,
			)
		);

		add_image_size( 'ga-avatar', 200, 200, true );
	}
}
