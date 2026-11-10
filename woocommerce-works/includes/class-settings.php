<?php
defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce → Works settings.
 */
class WCW_Settings {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( WCW_FILE ), array( $this, 'action_links' ) );
	}

	public function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Works', 'woocommerce-works' ),
			__( 'Works', 'woocommerce-works' ),
			'manage_options',
			'wcw-settings',
			array( $this, 'render' )
		);
	}

	public function action_links( $links ) {
		$url = admin_url( 'admin.php?page=wcw-settings' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'woocommerce-works' ) . '</a>' );
		return $links;
	}

	private function option_name( $key = null ) {
		if ( null === $key ) {
			return WCW_Plugin::SETTINGS_OPTION;
		}
		if ( false !== strpos( $key, '[' ) ) {
			$key = str_replace( ']', '', $key );
			$key = str_replace( '[', '][', $key );
		}
		return WCW_Plugin::SETTINGS_OPTION . '[' . $key . ']';
	}

	public function register() {
		register_setting(
			'wcw_settings_group',
			WCW_Plugin::SETTINGS_OPTION,
			array( 'sanitize_callback' => array( $this, 'sanitize' ) )
		);

		add_settings_section( 'wcw_grid', __( 'Works grid', 'woocommerce-works' ), '__return_false', 'wcw-settings' );
		add_settings_field( 'per_page', __( 'Works per page', 'woocommerce-works' ), array( $this, 'field_per_page' ), 'wcw-settings', 'wcw_grid' );
		add_settings_field( 'orderby', __( 'Order by', 'woocommerce-works' ), array( $this, 'field_orderby' ), 'wcw-settings', 'wcw_grid' );
		add_settings_field( 'order', __( 'Order', 'woocommerce-works' ), array( $this, 'field_order' ), 'wcw-settings', 'wcw_grid' );
		add_settings_field( 'columns', __( 'Columns', 'woocommerce-works' ), array( $this, 'field_columns' ), 'wcw-settings', 'wcw_grid' );
		add_settings_field( 'gap', __( 'Gap', 'woocommerce-works' ), array( $this, 'field_gap' ), 'wcw-settings', 'wcw_grid' );
		add_settings_field( 'hover_image', __( 'Hover image', 'woocommerce-works' ), array( $this, 'field_hover_image' ), 'wcw-settings', 'wcw_grid' );
		add_settings_field( 'card_image_size', __( 'Card image size', 'woocommerce-works' ), array( $this, 'field_card_image_size' ), 'wcw-settings', 'wcw_grid' );
		add_settings_field( 'show_price_on_cards', __( 'Prices on cards', 'woocommerce-works' ), array( $this, 'field_show_price_on_cards' ), 'wcw-settings', 'wcw_grid' );
		add_settings_field( 'hide_out_of_stock', __( 'Out of stock', 'woocommerce-works' ), array( $this, 'field_hide_out_of_stock' ), 'wcw-settings', 'wcw_grid' );
		add_settings_field( 'respect_visibility', __( 'Catalog visibility', 'woocommerce-works' ), array( $this, 'field_respect_visibility' ), 'wcw-settings', 'wcw_grid' );
		add_settings_field( 'show_page_title', __( 'Page title', 'woocommerce-works' ), array( $this, 'field_show_page_title' ), 'wcw-settings', 'wcw_grid' );

		add_settings_section( 'wcw_attributes', __( 'Work details', 'woocommerce-works' ), array( $this, 'attributes_intro' ), 'wcw-settings' );
		foreach ( array(
			'year'       => __( 'Year', 'woocommerce-works' ),
			'medium'     => __( 'Medium', 'woocommerce-works' ),
			'dimensions' => __( 'Dimensions', 'woocommerce-works' ),
			'status'     => __( 'Status', 'woocommerce-works' ),
		) as $slot => $label ) {
			add_settings_field(
				'attribute_' . $slot,
				$label,
				function () use ( $slot ) {
					$this->field_attribute( $slot );
				},
				'wcw-settings',
				'wcw_attributes'
			);
		}
		add_settings_field( 'status_sold', __( 'Sold value', 'woocommerce-works' ), array( $this, 'field_status_sold' ), 'wcw-settings', 'wcw_attributes' );
		add_settings_field( 'status_reserved', __( 'Reserved value', 'woocommerce-works' ), array( $this, 'field_status_reserved' ), 'wcw-settings', 'wcw_attributes' );
		add_settings_field( 'stock_as_sold', __( 'Stock status', 'woocommerce-works' ), array( $this, 'field_stock_as_sold' ), 'wcw-settings', 'wcw_attributes' );
		add_settings_field( 'show_attribute_lines', __( 'Detail lines', 'woocommerce-works' ), array( $this, 'field_show_attribute_lines' ), 'wcw-settings', 'wcw_attributes' );
		add_settings_field( 'show_badge', __( 'Status badge', 'woocommerce-works' ), array( $this, 'field_show_badge' ), 'wcw-settings', 'wcw_attributes' );

		add_settings_section( 'wcw_filters', __( 'Filters', 'woocommerce-works' ), array( $this, 'filters_intro' ), 'wcw-settings' );
		add_settings_field(
			'filter_medium',
			__( 'Medium', 'woocommerce-works' ),
			function () {
				$this->field_filter( 'medium' );
			},
			'wcw-settings',
			'wcw_filters'
		);
		add_settings_field(
			'filter_year',
			__( 'Year', 'woocommerce-works' ),
			function () {
				$this->field_filter( 'year' );
			},
			'wcw-settings',
			'wcw_filters'
		);

		add_settings_section( 'wcw_quick_view', __( 'Quick view', 'woocommerce-works' ), '__return_false', 'wcw-settings' );		add_settings_field( 'quick_view', __( 'Quick view', 'woocommerce-works' ), array( $this, 'field_quick_view' ), 'wcw-settings', 'wcw_quick_view' );
		add_settings_field( 'quick_view_thumbs', __( 'Image thumbs', 'woocommerce-works' ), array( $this, 'field_quick_view_thumbs' ), 'wcw-settings', 'wcw_quick_view' );
		add_settings_field( 'quick_view_description', __( 'Description', 'woocommerce-works' ), array( $this, 'field_quick_view_description' ), 'wcw-settings', 'wcw_quick_view' );
		add_settings_field( 'description_words', __( 'Description length', 'woocommerce-works' ), array( $this, 'field_description_words' ), 'wcw-settings', 'wcw_quick_view' );
		add_settings_field( 'quick_view_add_to_cart', __( 'Add to cart', 'woocommerce-works' ), array( $this, 'field_quick_view_add_to_cart' ), 'wcw-settings', 'wcw_quick_view' );
		add_settings_field( 'large_image_size', __( 'Large image size', 'woocommerce-works' ), array( $this, 'field_large_image_size' ), 'wcw-settings', 'wcw_quick_view' );

		add_settings_section( 'wcw_enquiry', __( 'Enquiry', 'woocommerce-works' ), array( $this, 'enquiry_intro' ), 'wcw-settings' );
		add_settings_field( 'enquiry_url', __( 'Enquiry target', 'woocommerce-works' ), array( $this, 'field_enquiry_url' ), 'wcw-settings', 'wcw_enquiry' );
		add_settings_field( 'enquiry_label', __( 'Button label', 'woocommerce-works' ), array( $this, 'field_enquiry_label' ), 'wcw-settings', 'wcw_enquiry' );
		add_settings_field( 'enquiry_subject', __( 'Email subject', 'woocommerce-works' ), array( $this, 'field_enquiry_subject' ), 'wcw-settings', 'wcw_enquiry' );

		add_settings_section( 'wcw_single', __( 'Single work', 'woocommerce-works' ), '__return_false', 'wcw-settings' );
		add_settings_field( 'restyle_related', __( 'Related works', 'woocommerce-works' ), array( $this, 'field_restyle_related' ), 'wcw-settings', 'wcw_single' );
		add_settings_field( 'related_count', __( 'Related count', 'woocommerce-works' ), array( $this, 'field_related_count' ), 'wcw-settings', 'wcw_single' );
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'WooCommerce Works', 'woocommerce-works' ); ?></h1>
			<p class="description">
				<?php
				printf(
					/* translators: %s: page template name. */
					esc_html__( 'Create a page and choose the "%s" page template in Page Attributes. Products with a price keep their price and add to cart; works without a price show the enquiry button.', 'woocommerce-works' ),
					'<strong>' . esc_html__( 'Works', 'woocommerce-works' ) . '</strong>'
				);
				?>
			</p>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'wcw_settings_group' );
				do_settings_sections( 'wcw-settings' );
				submit_button();
				?>
			</form>
			<?php $this->render_diagnostics(); ?>
		</div>
		<?php
	}

	private function render_diagnostics() {
		$settings = WCW_Plugin::get_settings();
		$map      = WCW_Plugin::attribute_map();
		$labels   = array(
			'year'       => __( 'Year', 'woocommerce-works' ),
			'medium'     => __( 'Medium', 'woocommerce-works' ),
			'dimensions' => __( 'Dimensions', 'woocommerce-works' ),
			'status'     => __( 'Status', 'woocommerce-works' ),
		);

		echo '<hr><h2>' . esc_html__( 'Detected attributes', 'woocommerce-works' ) . '</h2>';
		echo '<table class="widefat striped" style="max-width:640px"><tbody>';
		foreach ( $labels as $slot => $label ) {
			$value = $map[ $slot ] ? $map[ $slot ] : '—';
			$note  = '';
			if ( ! $map[ $slot ] ) {
				$note = ' <span style="color:#b32d2e">' . esc_html__( 'no matching attribute found', 'woocommerce-works' ) . '</span>';
			} elseif ( empty( $settings['attributes'][ $slot ] ) ) {
				$note = ' <span style="color:#646970">' . esc_html__( 'auto-detected, not saved', 'woocommerce-works' ) . '</span>';
			}
			echo '<tr><th style="width:160px">' . esc_html( $label ) . '</th><td><code>' . esc_html( $value ) . '</code>' . $note . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</tbody></table>';
		echo '<p class="description">' . esc_html__( 'Attributes are read from each product. Map them above, or rename your global attributes to year, medium, dimensions and status and they will be picked up automatically.', 'woocommerce-works' ) . '</p>';
	}

	public function attributes_intro() {
		echo '<p class="description">' . esc_html__( 'Choose which WooCommerce attribute holds each detail. Leave a field on "Auto-detect" to match by attribute slug or label.', 'woocommerce-works' ) . '</p>';
	}

	public function filters_intro() {
		echo '<p class="description">' . esc_html__( 'Filter chips for mapped attributes. Counts follow the other active filters, and works with no value get a "Not specified" chip instead of disappearing.', 'woocommerce-works' ) . '</p>';
	}

	public function enquiry_intro() {
		echo '<p class="description">' . esc_html__( 'Used for works with no price, and for works marked sold or reserved. Enter a contact page URL, a bare email address or a mailto: link. Leave empty to hide the button.', 'woocommerce-works' ) . '</p>';
	}

	/* ---------------------------------------------------------------- fields */

	public function field_per_page() {
		$value = (int) WCW_Plugin::get_setting( 'per_page', 24 );
		echo '<input type="number" min="1" max="100" step="1" name="' . esc_attr( $this->option_name( 'per_page' ) ) . '" value="' . esc_attr( $value ) . '" class="small-text">';
		echo '<p class="description">' . esc_html__( 'Works loaded before "Load more" appears.', 'woocommerce-works' ) . '</p>';
	}

	public function field_orderby() {
		$value   = WCW_Plugin::get_setting( 'orderby', 'menu_order' );
		$choices = array(
			'menu_order' => __( 'Manual order (menu_order)', 'woocommerce-works' ),
			'date'       => __( 'Date added', 'woocommerce-works' ),
			'modified'   => __( 'Last modified', 'woocommerce-works' ),
			'title'      => __( 'Title', 'woocommerce-works' ),
			'rand'       => __( 'Random', 'woocommerce-works' ),
		);
		$this->select( 'orderby', $choices, $value );
	}

	public function field_order() {
		$this->select(
			'order',
			array(
				'asc'  => __( 'Ascending', 'woocommerce-works' ),
				'desc' => __( 'Descending', 'woocommerce-works' ),
			),
			WCW_Plugin::get_setting( 'order', 'asc' )
		);
	}

	public function field_columns() {
		$columns = (int) WCW_Plugin::get_setting( 'columns', 3 );
		$tablet  = (int) WCW_Plugin::get_setting( 'columns_tablet', 2 );
		$mobile  = (int) WCW_Plugin::get_setting( 'columns_mobile', 1 );

		echo '<label>' . esc_html__( 'Desktop', 'woocommerce-works' ) . ' <input type="number" min="1" max="6" step="1" class="small-text" name="' . esc_attr( $this->option_name( 'columns' ) ) . '" value="' . esc_attr( $columns ) . '"></label> ';
		echo '<label>' . esc_html__( 'Tablet', 'woocommerce-works' ) . ' <input type="number" min="1" max="6" step="1" class="small-text" name="' . esc_attr( $this->option_name( 'columns_tablet' ) ) . '" value="' . esc_attr( $tablet ) . '"></label> ';
		echo '<label>' . esc_html__( 'Mobile', 'woocommerce-works' ) . ' <input type="number" min="1" max="6" step="1" class="small-text" name="' . esc_attr( $this->option_name( 'columns_mobile' ) ) . '" value="' . esc_attr( $mobile ) . '"></label>';
	}

	public function field_gap() {
		$value = (int) WCW_Plugin::get_setting( 'gap', 24 );
		echo '<input type="number" min="0" max="80" step="1" name="' . esc_attr( $this->option_name( 'gap' ) ) . '" value="' . esc_attr( $value ) . '" class="small-text"> px';
	}

	public function field_hover_image() {
		$this->checkbox( 'hover_image', __( 'Swap to the second gallery image on hover', 'woocommerce-works' ) );
	}

	public function field_card_image_size() {
		$this->select( 'card_image_size', WCW_Work_Data::image_size_choices(), WCW_Plugin::get_setting( 'card_image_size', 'woocommerce_single' ) );
		echo '<p class="description">' . esc_html__( 'The grid relies on uncropped images so works keep their real proportions. Regenerate thumbnails if the size changes.', 'woocommerce-works' ) . '</p>';
	}

	public function field_show_price_on_cards() {
		$this->checkbox( 'show_price_on_cards', __( 'Show the price under the title on cards', 'woocommerce-works' ) );
	}

	public function field_hide_out_of_stock() {
		$this->checkbox( 'hide_out_of_stock', __( 'Hide out of stock works from the grid', 'woocommerce-works' ) );
	}

	public function field_respect_visibility() {
		$this->checkbox( 'respect_visibility', __( 'Respect WooCommerce catalog visibility', 'woocommerce-works' ) );
	}

	public function field_show_page_title() {
		$this->checkbox( 'show_page_title', __( 'Show the page title above the grid', 'woocommerce-works' ) );
	}

	public function field_attribute( $slot ) {
		$saved   = WCW_Plugin::get_settings();
		$value   = isset( $saved['attributes'][ $slot ] ) ? $saved['attributes'][ $slot ] : '';
		$choices = array( '' => __( 'Auto-detect', 'woocommerce-works' ) );

		if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
			foreach ( (array) wc_get_attribute_taxonomies() as $taxonomy ) {
				if ( empty( $taxonomy->attribute_name ) ) {
					continue;
				}
				$slug             = 'pa_' . $taxonomy->attribute_name;
				$label            = $taxonomy->attribute_label ? $taxonomy->attribute_label : $taxonomy->attribute_name;
				$choices[ $slug ] = $label . ' (' . $slug . ')';
			}
		}

		$this->select( 'attributes[' . $slot . ']', $choices, $value );

		$current = WCW_Plugin::attribute_map();
		$hint    = ! empty( $current[ $slot ] ) ? $current[ $slot ] : __( 'nothing matched', 'woocommerce-works' );
		echo '<p class="description">' . esc_html__( 'Currently used:', 'woocommerce-works' ) . ' <code>' . esc_html( $hint ) . '</code></p>';
	}

	public function field_filter( $slot ) {
		$settings  = WCW_Plugin::get_settings();
		$value     = isset( $settings['filter_attributes'][ $slot ] ) ? (int) $settings['filter_attributes'][ $slot ] : 0;
		$map       = WCW_Plugin::attribute_map();
		$attribute = isset( $map[ $slot ] ) ? $map[ $slot ] : '';

		echo '<label><input type="checkbox" name="' . esc_attr( $this->option_name( 'filter_attributes[' . $slot . ']' ) ) . '" value="1"' . checked( 1, $value, false ) . '> ' . esc_html__( 'Show this facet above the grid', 'woocommerce-works' ) . '</label>';

		if ( ! $attribute ) {
			echo '<p class="description" style="color:#b32d2e">' . esc_html__( 'No attribute mapped yet — this facet stays hidden until one is mapped under Work details.', 'woocommerce-works' ) . '</p>';
			return;
		}

		echo '<p class="description">' . esc_html( sprintf( /* translators: %s: attribute taxonomy. */ __( 'Using %s', 'woocommerce-works' ), $attribute ) ) . '</p>';
	}

	public function field_status_sold() {		$value = WCW_Plugin::get_setting( 'status_sold', 'sold' );
		echo '<input type="text" name="' . esc_attr( $this->option_name( 'status_sold' ) ) . '" value="' . esc_attr( $value ) . '" class="regular-text">';
		echo '<p class="description">' . esc_html__( 'Status attribute value that marks a work as sold. Sold works hide their price and show the enquiry button.', 'woocommerce-works' ) . '</p>';
	}

	public function field_status_reserved() {
		$value = WCW_Plugin::get_setting( 'status_reserved', 'reserved' );
		echo '<input type="text" name="' . esc_attr( $this->option_name( 'status_reserved' ) ) . '" value="' . esc_attr( $value ) . '" class="regular-text">';
	}

	public function field_stock_as_sold() {
		$this->checkbox( 'stock_as_sold', __( 'Treat out of stock products as sold when no status attribute is set', 'woocommerce-works' ) );
	}

	public function field_show_attribute_lines() {
		$this->checkbox( 'show_attribute_lines', __( 'Show year, medium and dimensions under the title', 'woocommerce-works' ) );
	}

	public function field_show_badge() {
		$this->checkbox( 'show_badge', __( 'Show a sold / reserved badge on cards and detail views', 'woocommerce-works' ) );
	}

	public function field_quick_view() {
		$this->checkbox( 'quick_view', __( 'Enable the quick view dialog', 'woocommerce-works' ) );
	}

	public function field_quick_view_thumbs() {
		$this->checkbox( 'quick_view_thumbs', __( 'Show gallery thumbnails in quick view', 'woocommerce-works' ) );
	}

	public function field_quick_view_description() {
		$this->checkbox( 'quick_view_description', __( 'Show the short description in quick view', 'woocommerce-works' ) );
	}

	public function field_description_words() {
		$value = (int) WCW_Plugin::get_setting( 'description_words', 40 );
		echo '<input type="number" min="5" max="200" step="5" name="' . esc_attr( $this->option_name( 'description_words' ) ) . '" value="' . esc_attr( $value ) . '" class="small-text"> ' . esc_html__( 'words', 'woocommerce-works' );
	}

	public function field_quick_view_add_to_cart() {
		$this->checkbox( 'quick_view_add_to_cart', __( 'Allow adding simple products to the cart from quick view', 'woocommerce-works' ) );
	}

	public function field_large_image_size() {
		$this->select( 'large_image_size', WCW_Work_Data::image_size_choices(), WCW_Plugin::get_setting( 'large_image_size', 'woocommerce_single' ) );
		echo '<p class="description">' . esc_html__( 'Used for quick view, the single product gallery and the zoom target.', 'woocommerce-works' ) . '</p>';
	}

	public function field_enquiry_url() {
		$value = WCW_Plugin::get_setting( 'enquiry_url', '' );
		echo '<input type="text" name="' . esc_attr( $this->option_name( 'enquiry_url' ) ) . '" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="https://example.com/contact/ or hello@example.com">';
		echo '<p class="description">' . esc_html__( 'Use {title} in a URL to insert the work title.', 'woocommerce-works' ) . '</p>';
	}

	public function field_enquiry_label() {
		$value = WCW_Plugin::get_setting( 'enquiry_label', 'Enquire' );
		echo '<input type="text" name="' . esc_attr( $this->option_name( 'enquiry_label' ) ) . '" value="' . esc_attr( $value ) . '" class="regular-text">';
	}

	public function field_enquiry_subject() {
		$value = WCW_Plugin::get_setting( 'enquiry_subject', 'Enquiry: {title}' );
		echo '<input type="text" name="' . esc_attr( $this->option_name( 'enquiry_subject' ) ) . '" value="' . esc_attr( $value ) . '" class="regular-text">';
		echo '<p class="description">' . esc_html__( 'Email subject for mailto links. {title} is replaced with the work title.', 'woocommerce-works' ) . '</p>';
	}

	public function field_restyle_related() {
		$this->checkbox( 'restyle_related', __( 'Replace WooCommerce related products with the works grid', 'woocommerce-works' ) );
	}

	public function field_related_count() {
		$value = (int) WCW_Plugin::get_setting( 'related_count', 4 );
		echo '<input type="number" min="1" max="12" step="1" name="' . esc_attr( $this->option_name( 'related_count' ) ) . '" value="' . esc_attr( $value ) . '" class="small-text">';
	}

	/* --------------------------------------------------------------- helpers */

	private function checkbox( $key, $label ) {
		$value = (int) WCW_Plugin::get_setting( $key, 0 );
		echo '<label><input type="checkbox" name="' . esc_attr( $this->option_name( $key ) ) . '" value="1"' . checked( 1, $value, false ) . '> ' . esc_html( $label ) . '</label>';
	}

	private function select( $key, $choices, $value ) {
		echo '<select name="' . esc_attr( $this->option_name( $key ) ) . '">';
		foreach ( $choices as $choice => $label ) {
			echo '<option value="' . esc_attr( $choice ) . '"' . selected( (string) $value, (string) $choice, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}

	/* ------------------------------------------------------------- sanitising */

	public function sanitize( $input ) {
		$settings = WCW_Plugin::get_settings();
		$input    = is_array( $input ) ? $input : array();

		// Only ever read scalars: a malformed POST must not trigger array-to-string notices.
		$scalar = static function ( $key, $default = '' ) use ( $input ) {
			if ( ! isset( $input[ $key ] ) || ! is_scalar( $input[ $key ] ) ) {
				return $default;
			}
			return (string) $input[ $key ];
		};

		$settings['per_page']          = max( 1, min( 100, absint( $scalar( 'per_page', '24' ) ) ) );
		$settings['related_count']     = max( 1, min( 12, absint( $scalar( 'related_count', '4' ) ) ) );
		$settings['gap']               = max( 0, min( 80, absint( $scalar( 'gap', '24' ) ) ) );
		$settings['columns']           = max( 1, min( 6, absint( $scalar( 'columns', '3' ) ) ) );
		$settings['columns_tablet']    = max( 1, min( 6, absint( $scalar( 'columns_tablet', '2' ) ) ) );
		$settings['columns_mobile']    = max( 1, min( 6, absint( $scalar( 'columns_mobile', '1' ) ) ) );
		$settings['description_words'] = max( 5, min( 200, absint( $scalar( 'description_words', '40' ) ) ) );

		$settings['orderby'] = $this->sanitize_choice(
			$scalar( 'orderby', '' ),
			array( 'menu_order', 'date', 'modified', 'title', 'rand' ),
			'menu_order'
		);
		$settings['order'] = $this->sanitize_choice(
			$scalar( 'order', '' ),
			array( 'asc', 'desc' ),
			'asc'
		);

		$sizes                        = array_keys( WCW_Work_Data::image_size_choices() );
		$settings['card_image_size']  = $this->sanitize_choice( $scalar( 'card_image_size', '' ), $sizes, 'woocommerce_single' );
		$settings['large_image_size'] = $this->sanitize_choice( $scalar( 'large_image_size', '' ), $sizes, 'woocommerce_single' );

		foreach ( array(
			'hover_image',
			'show_price_on_cards',
			'hide_out_of_stock',
			'respect_visibility',
			'show_page_title',
			'stock_as_sold',
			'show_attribute_lines',
			'show_badge',
			'quick_view',
			'quick_view_thumbs',
			'quick_view_description',
			'quick_view_add_to_cart',
			'restyle_related',
		) as $flag ) {
			$settings[ $flag ] = empty( $input[ $flag ] ) ? 0 : 1;
		}

		$attribute_slugs = array();
		if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
			foreach ( (array) wc_get_attribute_taxonomies() as $taxonomy ) {
				if ( ! empty( $taxonomy->attribute_name ) ) {
					$attribute_slugs[] = 'pa_' . $taxonomy->attribute_name;
				}
			}
		}

		$attributes             = isset( $input['attributes'] ) && is_array( $input['attributes'] ) ? $input['attributes'] : array();
		$settings['attributes'] = array();
		foreach ( array( 'year', 'medium', 'dimensions', 'status' ) as $slot ) {
			$raw = isset( $attributes[ $slot ] ) && is_scalar( $attributes[ $slot ] ) ? sanitize_key( $attributes[ $slot ] ) : '';
			$raw = $raw ? ( 0 === strpos( $raw, 'pa_' ) ? $raw : 'pa_' . $raw ) : '';
			$settings['attributes'][ $slot ] = in_array( $raw, $attribute_slugs, true ) ? $raw : '';
		}

		$filters                       = isset( $input['filter_attributes'] ) && is_array( $input['filter_attributes'] ) ? $input['filter_attributes'] : array();
		$settings['filter_attributes'] = array();
		foreach ( WCW_Query::FACET_SLOTS as $slot ) {
			$settings['filter_attributes'][ $slot ] = empty( $filters[ $slot ] ) ? 0 : 1;
		}

		$settings['status_sold']     = sanitize_text_field( $scalar( 'status_sold', 'sold' ) );
		$settings['status_reserved'] = sanitize_text_field( $scalar( 'status_reserved', 'reserved' ) );
		$settings['enquiry_label']   = sanitize_text_field( $scalar( 'enquiry_label', 'Enquire' ) );
		$settings['enquiry_subject'] = sanitize_text_field( $scalar( 'enquiry_subject', 'Enquiry: {title}' ) );

		$target = trim( $scalar( 'enquiry_url', '' ) );
		if ( '' !== $target && ! is_email( $target ) ) {
			$target = esc_url_raw( $target );
		}
		$settings['enquiry_url'] = $target;

		delete_transient( 'wcw_categories' );

		return $settings;
	}

	private function sanitize_choice( $value, $allowed, $default ) {
		$value = is_string( $value ) ? $value : '';
		return in_array( $value, $allowed, true ) ? $value : $default;
	}
}
