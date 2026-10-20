<?php
defined( 'ABSPATH' ) || exit;

class FEUR_Page_Installer {

	const OPTION = 'feur_account_page_id';

	public static function activate() {
		self::ensure_page();
	}

	public static function init() {
		if ( self::get_page_id() ) {
			$page = get_post( self::get_page_id() );
			if ( ! $page || 'publish' !== $page->post_status ) {
				self::ensure_page();
			}
		}
	}

	public static function ensure_page() {
		$existing = self::get_page_id();
		if ( $existing && get_post( $existing ) ) {
			return (int) $existing;
		}
		$page_id = wp_insert_post(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_title'     => __( 'Account', 'feur' ),
				'post_name'      => 'account',
				'post_content'   => '[fe_account_form]',
				'comment_status' => 'closed',
			)
		);
		if ( ! is_wp_error( $page_id ) && $page_id ) {
			update_option( self::OPTION, (int) $page_id, false );
		}
		return $page_id;
	}

	public static function get_page_id() {
		return (int) get_option( self::OPTION, 0 );
	}

	public static function get_url() {
		$id = self::get_page_id();
		if ( $id ) {
			$url = get_permalink( $id );
			if ( $url ) {
				return $url;
			}
		}
		return home_url( '/' );
	}

	public static function delete_page() {
		$id = self::get_page_id();
		if ( $id && get_post( $id ) ) {
			wp_delete_post( $id, true );
		}
		delete_option( self::OPTION );
	}

	public static function purge_cache() {
		if ( ! class_exists( 'Breeze_PurgeCache' ) ) {
			return;
		}
		$url = self::get_url();
		if ( ! $url ) {
			return;
		}
		if ( method_exists( 'Breeze_PurgeCache', 'purge_varnish_urls' ) ) {
			Breeze_PurgeCache::purge_varnish_urls( array( $url ) );
		} else {
			do_action( 'breeze_clear_all_cache' );
		}
	}
}
