<?php
defined( 'ABSPATH' ) || exit;

class GA_Rest {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register' ) );
	}

	public function register() {
		register_rest_field(
			GA_Plugin::supported_post_types(),
			'guest_authors',
			array(
				'get_callback' => array( $this, 'get_field' ),
				'schema'       => array(
					'description' => __( 'Guest authors attributed to the post, in order.', 'ga' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
			)
		);
	}

	public function get_field( $post_arr ) {
		$out = array();
		foreach ( GA_Author_Override::get_byline_items( $post_arr['id'] ) as $item ) {
			$out[] = array(
				'id'        => $item['id'],
				'name'      => $item['name'],
				'url'       => $item['url'],
				'job_title' => $item['job_title'],
				'company'   => $item['company'],
			);
		}
		return $out;
	}
}
