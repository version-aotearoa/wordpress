<?php
defined( 'ABSPATH' ) || exit;

/**
 * Bootstrap, settings storage and attribute auto-detection.
 */
final class WCW_Plugin {

	const SETTINGS_OPTION = 'wcw_settings';
	const VERSION_OPTION  = 'wcw_version';

	/**
	 * Attribute slots the plugin can display, and the attribute slugs it tries
	 * to match automatically when nothing has been mapped yet.
	 */
	const ATTRIBUTE_SLOTS = array(
		'year'       => array( 'pa_year', 'year', 'pa_date', 'created', 'pa_created' ),
		'medium'     => array( 'pa_medium', 'medium', 'pa_media', 'media', 'pa_materials', 'materials' ),
		'dimensions' => array( 'pa_dimensions', 'dimensions', 'pa_dimension', 'pa_size', 'size' ),
		'status'     => array( 'pa_status', 'status', 'pa_availability', 'availability' ),
	);

	private static $default_settings = array(
		// Grid.
		'per_page'                 => 24,
		'orderby'                  => 'menu_order',
		'order'                    => 'asc',
		'columns'                  => 3,
		'columns_tablet'           => 2,
		'columns_mobile'           => 1,
		'gap'                      => 24,
		'hover_image'              => 1,
		'show_price_on_cards'      => 0,
		'hide_out_of_stock'        => 0,
		'respect_visibility'       => 1,
		'show_page_title'          => 0,
		'card_image_size'          => 'woocommerce_single',
		'large_image_size'         => 'woocommerce_single',

		// Attributes.
		'attributes'               => array(
			'year'       => '',
			'medium'     => '',
			'dimensions' => '',
			'status'     => '',
		),
		'filter_attributes'        => array(
			'medium' => 1,
			'year'   => 1,
		),
		'status_sold'              => 'sold',
		'status_reserved'          => 'reserved',
		'stock_as_sold'            => 1,
		'show_attribute_lines'     => 1,
		'show_badge'               => 1,

		// Quick view.
		'quick_view'               => 1,
		'quick_view_description'   => 1,
		'description_words'        => 40,
		'quick_view_thumbs'        => 1,
		'quick_view_add_to_cart'   => 1,

		// Enquiry.
		'enquiry_url'              => '',
		'enquiry_label'            => 'Enquire',
		'enquiry_subject'          => 'Enquiry: {title}',

		// Single product.
		'restyle_related'          => 1,
		'related_count'            => 4,
	);

	private static $instance = null;

	private function __construct() {
		add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );

		if ( ! self::woocommerce_active() ) {
			return;
		}

		new WCW_Template();
		new WCW_Rest();
		new WCW_Browse();
		new WCW_Settings();

		add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade' ) );
	}

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	public static function activate() {
		$settings = self::get_settings();
		if ( ! array_filter( (array) $settings['attributes'] ) ) {
			$settings['attributes'] = self::detect_attributes();
		}
		update_option( self::SETTINGS_OPTION, $settings, false );
		update_option( self::VERSION_OPTION, WCW_VERSION, false );
	}

	public static function deactivate() {
		delete_transient( 'wcw_categories' );
	}

	public static function maybe_upgrade() {
		$stored = get_option( self::VERSION_OPTION, '' );
		if ( WCW_VERSION !== $stored ) {
			$settings = self::get_settings();
			if ( ! array_filter( (array) $settings['attributes'] ) ) {
				$settings['attributes'] = self::detect_attributes();
				update_option( self::SETTINGS_OPTION, $settings, false );
			}
			update_option( self::VERSION_OPTION, WCW_VERSION, false );
		}
	}

	public static function dependency_notice() {
		if ( self::woocommerce_active() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'WooCommerce Works needs WooCommerce to be installed and active. The plugin is inactive until then.', 'woocommerce-works' );
		echo '</p></div>';
	}

	public static function get_settings() {
		$saved = get_option( self::SETTINGS_OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		$settings = wp_parse_args( $saved, self::$default_settings );
		if ( ! is_array( $settings['attributes'] ) ) {
			$settings['attributes'] = self::$default_settings['attributes'];
		} else {
			$settings['attributes'] = wp_parse_args( $settings['attributes'], self::$default_settings['attributes'] );
		}
		if ( ! is_array( $settings['filter_attributes'] ) ) {
			$settings['filter_attributes'] = self::$default_settings['filter_attributes'];
		} else {
			$settings['filter_attributes'] = wp_parse_args( $settings['filter_attributes'], self::$default_settings['filter_attributes'] );
		}
		return $settings;
	}

	public static function get_setting( $key, $default = '' ) {
		$settings = self::get_settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Saved mapping, with unmapped slots filled by detection for this request
	 * only. Nothing is persisted here so a half-configured site keeps working
	 * as attributes are renamed.
	 */
	public static function attribute_map() {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}

		$settings = self::get_settings();
		$map      = (array) $settings['attributes'];
		$auto     = null;

		foreach ( array_keys( self::ATTRIBUTE_SLOTS ) as $slot ) {
			if ( ! empty( $map[ $slot ] ) ) {
				continue;
			}
			if ( null === $auto ) {
				$auto = self::detect_attributes();
			}
			$map[ $slot ] = isset( $auto[ $slot ] ) ? $auto[ $slot ] : '';
		}

		$cache = $map;
		return $map;
	}

	/**
	 * Match global WooCommerce attributes to our slots by slug, then by label.
	 *
	 * @return array slot => attribute slug (pa_* or custom slug), empty when unmatched.
	 */
	public static function detect_attributes() {
		$found = array(
			'year'       => '',
			'medium'     => '',
			'dimensions' => '',
			'status'     => '',
		);

		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return $found;
		}

		$taxonomies = wc_get_attribute_taxonomies();
		if ( ! is_array( $taxonomies ) ) {
			return $found;
		}

		$by_slug  = array();
		$by_label = array();
		foreach ( $taxonomies as $taxonomy ) {
			$slug = isset( $taxonomy->attribute_name ) ? 'pa_' . $taxonomy->attribute_name : '';
			if ( ! $slug ) {
				continue;
			}
			$by_slug[ $slug ] = $slug;
			$label            = isset( $taxonomy->attribute_label ) ? strtolower( trim( $taxonomy->attribute_label ) ) : '';
			if ( $label ) {
				$by_label[ $label ] = $slug;
			}
			if ( isset( $taxonomy->attribute_name ) ) {
				$by_label[ strtolower( $taxonomy->attribute_name ) ] = $slug;
			}
		}

		$labels = array(
			'year'       => array( 'year', 'date', 'created' ),
			'medium'     => array( 'medium', 'media', 'materials', 'material' ),
			'dimensions' => array( 'dimensions', 'dimension', 'size' ),
			'status'     => array( 'status', 'availability' ),
		);

		foreach ( self::ATTRIBUTE_SLOTS as $slot => $candidates ) {
			foreach ( $candidates as $candidate ) {
				if ( isset( $by_slug[ $candidate ] ) ) {
					$found[ $slot ] = $by_slug[ $candidate ];
					break;
				}
				if ( isset( $by_label[ $candidate ] ) ) {
					$found[ $slot ] = $by_label[ $candidate ];
					break;
				}
			}
			if ( $found[ $slot ] ) {
				continue;
			}
			foreach ( (array) $labels[ $slot ] as $label ) {
				if ( isset( $by_label[ $label ] ) ) {
					$found[ $slot ] = $by_label[ $label ];
					break;
				}
			}
		}

		return $found;
	}
}
