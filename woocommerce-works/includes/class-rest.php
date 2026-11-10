<?php
defined( 'ABSPATH' ) || exit;

/**
 * Read-only REST endpoints used by the grid and the quick view. Both are
 * public and cacheable: no nonce, no user state, no mutation.
 */
class WCW_Rest {

	const NS = 'woocommerce-works/v1';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	public function routes() {
		register_rest_route(
			self::NS,
			'/works',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'works' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'cat'      => array(
						'default'           => '',
						'sanitize_callback' => static function ( $value ) {
							return sanitize_title( (string) $value );
						},
					),
					'medium'   => array(
						'default'           => '',
						'sanitize_callback' => static function ( $value ) {
							return (string) $value;
						},
					),
					'year'     => array(
						'default'           => '',
						'sanitize_callback' => static function ( $value ) {
							return (string) $value;
						},
					),
					'page'     => array(
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
					'per_page' => array(
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
					'base'     => array(
						'default'           => '',
						'sanitize_callback' => static function ( $value ) {
							return esc_url_raw( (string) $value );
						},
					),
					'chips'    => array(
						'default'           => '1',
						'sanitize_callback' => static function ( $value ) {
							return '0' === (string) $value ? '0' : '1';
						},
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/product/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'product' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	public function works( WP_REST_Request $request ) {
		$cat      = (string) $request->get_param( 'cat' );
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = (int) $request->get_param( 'per_page' );

		if ( '' !== $cat && ! WCW_Query::has_category( $cat ) ) {
			$cat = '';
		}
		if ( $per_page < 1 ) {
			$per_page = (int) WCW_Plugin::get_setting( 'per_page', 24 );
		}
		$per_page = max( 1, min( 100, $per_page ) );

		$filters = array(
			'cat'    => $cat,
			'medium' => WCW_Query::sanitize_facet_value( 'medium', (string) $request->get_param( 'medium' ) ),
			'year'   => WCW_Query::sanitize_facet_value( 'year', (string) $request->get_param( 'year' ) ),
		);

		$query = WCW_Query::run(
			array(
				'filters'  => $filters,
				'page'     => $page,
				'per_page' => $per_page,
			)
		);

		$total = (int) $query->found_posts;
		$shown = min( $total, ( $page - 1 ) * $per_page + (int) $query->post_count );

		$base  = (string) $request->get_param( 'base' );
		$chips = '1' === (string) $request->get_param( 'chips' );

		return rest_ensure_response(
			array(
				'html'        => WCW_Render::cards( $query ),
				'facets_html' => WCW_Render::filters_html( $filters, WCW_Query::facets( $filters ), $base, $chips ),
				'filters'     => $filters,
				'cat'         => $cat,
				'page'        => $page,
				'per_page'    => $per_page,
				'total'       => $total,
				'shown'       => $shown,
				'more'        => $page < (int) $query->max_num_pages,
				'count_text'  => WCW_Render::count_text( $shown, $total ),
			)
		);
	}

	public function product( WP_REST_Request $request ) {
		$id = absint( $request->get_param( 'id' ) );

		if ( ! $id || 'product' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
			return new WP_Error(
				'wcw_not_found',
				__( 'Work not found.', 'woocommerce-works' ),
				array( 'status' => 404 )
			);
		}

		$product = wc_get_product( $id );
		if ( ! $product || ! $product->is_visible() ) {
			return new WP_Error(
				'wcw_not_found',
				__( 'Work not found.', 'woocommerce-works' ),
				array( 'status' => 404 )
			);
		}

		return rest_ensure_response(
			array(
				'id'    => $id,
				'title' => $product->get_name(),
				'url'   => get_permalink( $id ),
				'html'  => WCW_Render::quick_view_content( $product ),
			)
		);
	}
}
