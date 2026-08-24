<?php
defined( 'ABSPATH' ) || exit;

class RL_Reorder {

	const META = 'rl_sidebar_order';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
	}

	public function menu() {
		add_submenu_page(
			'edit.php?post_type=' . RL_Plugin::PT,
			__( 'Reorder Sections', 'rl' ),
			__( 'Reorder Sections', 'rl' ),
			'manage_options',
			'rl-reorder',
			array( $this, 'render' )
		);
	}

	public function admin_assets( $hook ) {
		if ( 'resource_page_rl-reorder' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'rl-admin', RL_URL . 'assets/css/admin.css', array(), RL_VERSION );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_add_inline_script(
			'jquery-ui-sortable',
			'jQuery(function($){' . $this->inline_js() . '});'
		);
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$saved = false;
		if ( isset( $_POST['rl_reorder_nonce'], $_POST['rl_reorder_data'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['rl_reorder_nonce'] ) ), 'rl_reorder' ) ) {
			$this->save( wp_unslash( $_POST['rl_reorder_data'] ) );
			$saved = true;
		}

		$tags = get_terms(
			array(
				'taxonomy'   => RL_Plugin::TAG_TAX,
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Reorder Sections', 'rl' ) . '</h1>';
		if ( $saved ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Section order saved.', 'rl' ) . '</p></div>';
		}
		if ( is_wp_error( $tags ) || empty( $tags ) ) {
			echo '<p>' . esc_html__( 'No tags yet. Create tags in Resource Tags first.', 'rl' ) . '</p>';
			echo '</div>';
			return;
		}

		echo '<p>' . esc_html__( 'Drag tags to set the order they appear in the library sidebar. Children appear beneath their parent only while the parent section is selected.', 'rl' ) . '</p>';

		$by_parent = $this->group_tags( $tags );

		echo '<form method="post" id="rl-reorder-form">';
		wp_nonce_field( 'rl_reorder', 'rl_reorder_nonce' );
		echo '<input type="hidden" name="rl_reorder_data" id="rl-reorder-data" value="" />';
		echo '<ul id="rl-reorder-root" class="rl-reorder-list">';
		echo $this->list_html( $by_parent, 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup built with esc_*.
		echo '</ul>';
		submit_button( __( 'Save order', 'rl' ) );
		echo '</form>';
		echo '</div>';
	}

	private function group_tags( $tags ) {
		$by_parent = array( 0 => array() );
		foreach ( $tags as $tag ) {
			$pid = (int) $tag->parent;
			if ( ! isset( $by_parent[ $pid ] ) ) {
				$by_parent[ $pid ] = array();
			}
			$by_parent[ $pid ][] = $tag;
		}
		foreach ( $by_parent as $pid => $group ) {
			usort( $group, array( $this, 'sort_tags' ) );
			$by_parent[ $pid ] = $group;
		}
		return $by_parent;
	}

	public function sort_tags( $a, $b ) {
		$oa = (int) get_term_meta( $a->term_id, self::META, true );
		$ob = (int) get_term_meta( $b->term_id, self::META, true );
		if ( $oa === $ob ) {
			return strcasecmp( $a->name, $b->name );
		}
		if ( ! $oa ) {
			return 1;
		}
		if ( ! $ob ) {
			return -1;
		}
		return $oa < $ob ? -1 : 1;
	}

	private function list_html( $by_parent, $parent_id ) {
		$html = '';
		if ( empty( $by_parent[ $parent_id ] ) ) {
			return $html;
		}
		foreach ( $by_parent[ $parent_id ] as $tag ) {
			$html .= '<li class="rl-reorder-item" data-id="' . esc_attr( $tag->term_id ) . '">';
			$html .= '<span class="rl-reorder-handle" aria-hidden="true"><span class="dashicons dashicons-menu"></span></span>';
			$html .= '<span class="rl-reorder-name">' . esc_html( $tag->name ) . '</span>';
			if ( ! empty( $by_parent[ (int) $tag->term_id ] ) ) {
				$html .= '<ul class="rl-reorder-list">';
				$html .= $this->list_html( $by_parent, (int) $tag->term_id );
				$html .= '</ul>';
			}
			$html .= '</li>';
		}
		return $html;
	}

	private function save( $data ) {
		if ( ! is_string( $data ) ) {
			return;
		}
		$decoded = json_decode( $data, true );
		if ( ! is_array( $decoded ) ) {
			return;
		}

		$ordered = array();
		foreach ( $decoded as $parent => $ids ) {
			$parent_id = (int) $parent;
			if ( ! is_array( $ids ) ) {
				continue;
			}
			foreach ( $ids as $i => $id ) {
				$term_id = absint( $id );
				if ( ! $term_id ) {
					continue;
				}
				$ordered[ $term_id ] = $parent_id;
				update_term_meta( $term_id, self::META, $i + 1 );
			}
		}

		$all = get_terms(
			array(
				'taxonomy'   => RL_Plugin::TAG_TAX,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);
		if ( is_wp_error( $all ) ) {
			return;
		}
		foreach ( $all as $term_id ) {
			if ( ! isset( $ordered[ (int) $term_id ] ) ) {
				delete_term_meta( (int) $term_id, self::META );
			}
		}
	}

	private function inline_js() {
		return <<<'JS'
$('.rl-reorder-list').sortable({
	items: '> .rl-reorder-item',
	handle: '.rl-reorder-handle',
	placeholder: 'rl-reorder-placeholder',
	forcePlaceholderSize: true
});
$('#rl-reorder-form').on('submit', function (e) {
	var data = {};
	$('#rl-reorder-root, #rl-reorder-root .rl-reorder-list').each(function () {
		var parent = $(this).attr('id') === 'rl-reorder-root' ? '0' : $(this).closest('.rl-reorder-item').attr('data-id');
		var ids = [];
		$(this).children('.rl-reorder-item').each(function () {
			ids.push($(this).attr('data-id'));
		});
		if (ids.length) {
			data[parent] = ids;
		}
	});
	$('#rl-reorder-data').val(JSON.stringify(data));
});
JS;
	}
}
