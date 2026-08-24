<?php
defined( 'ABSPATH' ) || exit;

class FEUR_Member_Access {

	private $restricted = null;

	public function __construct() {
		add_action( 'template_redirect', array( $this, 'enforce' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_queries' ) );
	}

	private function restricted_types() {
		if ( null === $this->restricted ) {
			$this->restricted = array_filter( (array) FEUR_Plugin::get_setting( 'restricted_post_types', array() ) );
		}
		return $this->restricted;
	}

	public function enforce() {
		if ( FEUR_Role::has_member_access() ) {
			return;
		}
		if ( get_queried_object_id() === FEUR_Page_Installer::get_page_id() ) {
			return;
		}

		$access_page_id = (int) FEUR_Plugin::get_setting( 'access_page_id', 0 );
		if ( $access_page_id && is_page( $access_page_id ) ) {
			$url = add_query_arg( array( 'redirect_to' => get_permalink(), 'fe_status' => 'members_only' ), FEUR_Page_Installer::get_url() );
			wp_safe_redirect( $url );
			exit;
		}

		$types = $this->restricted_types();
		if ( empty( $types ) ) {
			return;
		}

		$target = '';
		if ( is_singular( $types ) ) {
			$target = get_permalink();
		} elseif ( is_post_type_archive( $types ) ) {
			$type = get_query_var( 'post_type' );
			if ( $type && in_array( $type, $types, true ) ) {
				$target = get_post_type_archive_link( $type );
			}
		}

		if ( $target ) {
			$url = add_query_arg( array( 'redirect_to' => $target, 'fe_status' => 'members_only' ), FEUR_Page_Installer::get_url() );
			wp_safe_redirect( $url );
			exit;
		}

		if ( is_feed() ) {
			$type = get_query_var( 'post_type' );
			if ( $type && in_array( $type, $types, true ) ) {
				wp_die( esc_html__( 'This content is for members only.', 'feur' ), '', array( 'response' => 403 ) );
			}
		}
	}

	public function filter_queries( $query ) {
		if ( is_admin() || ! $query->is_main_query() || FEUR_Role::has_member_access() ) {
			return;
		}
		$types = $this->restricted_types();
		if ( empty( $types ) ) {
			return;
		}
		$public = array_values( get_post_types( array( 'public' => true ), 'names' ) );
		$allowed = array_values( array_diff( $public, $types ) );

		if ( $query->is_search() ) {
			$current = $query->get( 'post_type' );
			if ( empty( $current ) ) {
				$query->set( 'post_type', $allowed );
			} elseif ( is_array( $current ) ) {
				$query->set( 'post_type', array_values( array_diff( $current, $types ) ) );
			}
			return;
		}

		if ( $query->is_home() || $query->is_feed() ) {
			$current = $query->get( 'post_type' );
			if ( empty( $current ) ) {
				$query->set( 'post_type', $allowed );
			}
		}
	}
}
