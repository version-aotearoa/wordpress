<?php
defined( 'ABSPATH' ) || exit;

class GA_Render {

	public function __construct() {
		add_filter( 'template_include', array( $this, 'template_include' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
	}

	public function template_include( $template ) {
		if ( is_singular( GA_Plugin::PT ) ) {
			$theme = locate_template( array( 'single-' . GA_Plugin::PT . '.php' ) );
			if ( ! $theme ) {
				return GA_PATH . 'templates/single-guest_author.php';
			}
		}

		if ( is_post_type_archive( GA_Plugin::PT ) ) {
			$theme = locate_template( array( 'archive-' . GA_Plugin::PT . '.php' ) );
			if ( ! $theme ) {
				return GA_PATH . 'templates/archive-guest_author.php';
			}
		}

		return $template;
	}

	public function assets() {
		if ( is_singular( GA_Plugin::PT ) || is_post_type_archive( GA_Plugin::PT ) ) {
			wp_enqueue_style( 'ga-front', GA_URL . 'assets/css/guest-authors.css', array(), GA_VERSION );
		}
	}
}
