<?php
defined( 'ABSPATH' ) || exit;

class RL_Cache {

	private static $urls = array();

	public function __construct() {
		add_action( 'save_post_' . RL_Plugin::PT, array( __CLASS__, 'on_save' ) );
		add_action( 'transition_post_status', array( __CLASS__, 'on_transition' ), 10, 3 );
		add_action( 'added_post_meta', array( __CLASS__, 'on_meta' ), 10, 4 );
		add_action( 'updated_post_meta', array( __CLASS__, 'on_meta' ), 10, 4 );
		add_action( 'deleted_post_meta', array( __CLASS__, 'on_meta' ), 10, 4 );
		add_action( 'set_object_terms', array( __CLASS__, 'on_terms' ), 10, 6 );
		add_action( 'before_delete_post', array( __CLASS__, 'on_delete' ) );
		add_action( 'shutdown', array( __CLASS__, 'flush' ) );
	}

	public static function on_save( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		$status = get_post_status( $post_id );
		if ( in_array( $status, array( 'publish', 'private' ), true ) ) {
			self::queue( $post_id );
		}
	}

	public static function on_transition( $new_status, $old_status, $post ) {
		if ( ! $post instanceof WP_Post || RL_Plugin::PT !== $post->post_type ) {
			return;
		}
		if ( 'publish' === $new_status || 'publish' === $old_status ) {
			self::queue( $post->ID );
		}
	}

	public static function on_meta( $meta_id, $post_id, $meta_key, $meta_value ) {
		if ( RL_Plugin::FEATURED_META !== $meta_key ) {
			return;
		}
		if ( RL_Plugin::PT === get_post_type( $post_id ) ) {
			self::queue( $post_id );
		}
	}

	public static function on_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		if ( ! in_array( $taxonomy, array( RL_Plugin::TAG_TAX, RL_Plugin::FORMAT_TAX ), true ) ) {
			return;
		}
		if ( RL_Plugin::PT === get_post_type( $object_id ) ) {
			self::queue( $object_id );
		}
	}

	public static function on_delete( $post_id ) {
		if ( RL_Plugin::PT === get_post_type( $post_id ) ) {
			self::queue( $post_id );
		}
	}

	private static function queue( $post_id ) {
		$post_id = (int) $post_id;
		if ( ! $post_id ) {
			return;
		}
		foreach ( self::library_urls( $post_id ) as $url ) {
			self::$urls[ $url ] = true;
		}
	}

	public static function flush() {
		$urls       = array_keys( self::$urls );
		self::$urls = array();

		if ( ! class_exists( 'Breeze_PurgeCache' ) ) {
			return;
		}

		if ( ! empty( $urls ) ) {
			if ( method_exists( 'Breeze_PurgeCache', 'purge_varnish_urls' ) ) {
				Breeze_PurgeCache::purge_varnish_urls( $urls );
			} else {
				do_action( 'breeze_clear_all_cache' );
			}
		}
		RL_Page_Template::clear_library_url();
	}

	private static function library_urls( $post_id ) {
		$base = RL_Page_Template::library_url();
		if ( ! $base ) {
			return array();
		}
		$base = untrailingslashit( $base );
		$urls = array( $base . '/' );

		$tag_slugs = array();
		$tags      = get_the_terms( $post_id, RL_Plugin::TAG_TAX );
		if ( is_array( $tags ) ) {
			foreach ( $tags as $tag ) {
				$tag_slugs[ $tag->slug ] = true;
				foreach ( get_ancestors( $tag->term_id, RL_Plugin::TAG_TAX, 'taxonomy' ) as $ancestor_id ) {
					$ancestor = get_term( $ancestor_id, RL_Plugin::TAG_TAX );
					if ( $ancestor && ! is_wp_error( $ancestor ) ) {
						$tag_slugs[ $ancestor->slug ] = true;
					}
				}
			}
		}

		$format_slugs = array();
		$formats      = get_the_terms( $post_id, RL_Plugin::FORMAT_TAX );
		if ( is_array( $formats ) ) {
			foreach ( $formats as $format ) {
				$format_slugs[ $format->slug ] = true;
			}
		}

		foreach ( array_keys( $tag_slugs ) as $tag_slug ) {
			$urls[] = $base . '/?tag=' . $tag_slug;
			foreach ( array_keys( $format_slugs ) as $format_slug ) {
				$urls[] = $base . '/?tag=' . $tag_slug . '&format=' . $format_slug;
			}
		}
		foreach ( array_keys( $format_slugs ) as $format_slug ) {
			$urls[] = $base . '/?format=' . $format_slug;
		}

		$default_sort = RL_Render::default_sort();
		$sort_variants = array();
		foreach ( array_keys( RL_Plugin::sort_options() ) as $sort_key ) {
			if ( $sort_key === $default_sort ) {
				continue;
			}
			foreach ( $urls as $url ) {
				$sort_variants[] = $url . ( false === strpos( $url, '?' ) ? '?sort=' . $sort_key : '&sort=' . $sort_key );
			}
		}

		return array_merge( $urls, $sort_variants );
	}
}
