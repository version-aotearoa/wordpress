<?php
defined( 'ABSPATH' ) || exit;

class RL_Post_Type {

	public static function register() {
		register_post_type(
			RL_Plugin::PT,
			array(
				'labels'          => array(
					'name'          => __( 'Resources', 'rl' ),
					'singular_name' => __( 'Resource', 'rl' ),
					'add_new_item'  => __( 'Add New Resource', 'rl' ),
					'edit_item'     => __( 'Edit Resource', 'rl' ),
					'new_item'      => __( 'New Resource', 'rl' ),
					'view_item'     => __( 'View Resource', 'rl' ),
					'search_items'  => __( 'Search Resources', 'rl' ),
					'not_found'     => __( 'No resources found.', 'rl' ),
					'menu_name'     => __( 'Resources', 'rl' ),
				),
				'public'          => true,
				'show_in_rest'    => true,
				'menu_position'   => 3,
				'menu_icon'       => 'dashicons-portfolio',
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
				'has_archive'     => false,
				'rewrite'         => array( 'slug' => 'resources' ),
				'taxonomies'      => array( RL_Plugin::TAG_TAX, RL_Plugin::FORMAT_TAX ),
			)
		);

		register_taxonomy(
			RL_Plugin::TAG_TAX,
			RL_Plugin::PT,
			array(
				'labels'       => array(
					'name'                       => __( 'Resource Tags', 'rl' ),
					'singular_name'              => __( 'Resource Tag', 'rl' ),
					'menu_name'                  => __( 'Resource Tags', 'rl' ),
					'name_admin_bar'             => __( 'Resource Tag', 'rl' ),
					'all_items'                  => __( 'All Resource Tags', 'rl' ),
					'popular_items'              => __( 'Popular Resource Tags', 'rl' ),
					'most_used'                  => __( 'Recent', 'rl' ),
					'search_items'               => __( 'Search Resource Tags', 'rl' ),
					'add_new_item'               => __( 'Add New Resource Tag', 'rl' ),
					'new_item_name'              => __( 'New Resource Tag Name', 'rl' ),
					'edit_item'                  => __( 'Edit Resource Tag', 'rl' ),
					'view_item'                  => __( 'View Resource Tag', 'rl' ),
					'update_item'                => __( 'Update Resource Tag', 'rl' ),
					'add_or_remove_items'        => __( 'Add or remove resource tags', 'rl' ),
					'choose_from_most_used'      => __( 'Choose from the most used resource tags', 'rl' ),
					'separate_items_with_commas' => __( 'Separate resource tags with commas', 'rl' ),
					'not_found'                  => __( 'No resource tags found.', 'rl' ),
					'no_terms'                   => __( 'No resource tags', 'rl' ),
					'parent_item'                => __( 'Parent Resource Tag', 'rl' ),
					'parent_item_colon'          => __( 'Parent Resource Tag:', 'rl' ),
					'items_list_navigation'      => __( 'Resource tags list navigation', 'rl' ),
					'items_list'                 => __( 'Resource tags list', 'rl' ),
					'back_to_items'              => __( '&larr; Go to Resource Tags', 'rl' ),
				),
				'public'       => true,
				'hierarchical' => true,
				'show_in_rest' => false,
				'meta_box_cb'  => false,
				'rewrite'      => array( 'slug' => 'resource-tag' ),
			)
		);

		register_taxonomy(
			RL_Plugin::FORMAT_TAX,
			RL_Plugin::PT,
			array(
				'labels'       => array(
					'name'                       => __( 'Resource Formats', 'rl' ),
					'singular_name'              => __( 'Resource Format', 'rl' ),
					'menu_name'                  => __( 'Resource Formats', 'rl' ),
					'name_admin_bar'             => __( 'Resource Format', 'rl' ),
					'all_items'                  => __( 'All Resource Formats', 'rl' ),
					'popular_items'              => __( 'Popular Resource Formats', 'rl' ),
					'most_used'                  => __( 'Recent', 'rl' ),
					'search_items'               => __( 'Search Resource Formats', 'rl' ),
					'add_new_item'               => __( 'Add New Resource Format', 'rl' ),
					'new_item_name'              => __( 'New Resource Format Name', 'rl' ),
					'edit_item'                  => __( 'Edit Resource Format', 'rl' ),
					'view_item'                  => __( 'View Resource Format', 'rl' ),
					'update_item'                => __( 'Update Resource Format', 'rl' ),
					'add_or_remove_items'        => __( 'Add or remove resource formats', 'rl' ),
					'choose_from_most_used'      => __( 'Choose from the most used resource formats', 'rl' ),
					'separate_items_with_commas' => __( 'Separate resource formats with commas', 'rl' ),
					'not_found'                  => __( 'No resource formats found.', 'rl' ),
					'no_terms'                   => __( 'No resource formats', 'rl' ),
					'parent_item'                => __( 'Parent Resource Format', 'rl' ),
					'parent_item_colon'          => __( 'Parent Resource Format:', 'rl' ),
					'items_list_navigation'      => __( 'Resource formats list navigation', 'rl' ),
					'items_list'                 => __( 'Resource formats list', 'rl' ),
					'back_to_items'              => __( '&larr; Go to Resource Formats', 'rl' ),
				),
				'public'       => true,
				'hierarchical' => true,
				'show_in_rest' => false,
				'meta_box_cb'  => false,
				'rewrite'      => array( 'slug' => 'resource-format' ),
			)
		);

		self::seed_formats();

		add_image_size( 'rl-card', 640, 360, true );
	}

	private static function seed_formats() {
		foreach ( RL_Plugin::FORMATS as $format ) {
			if ( ! term_exists( $format, RL_Plugin::FORMAT_TAX ) ) {
				wp_insert_term( $format, RL_Plugin::FORMAT_TAX );
			}
		}
	}

	public static function taxonomy_meta_box( $post, $box ) {
		$taxonomy = isset( $box['args']['taxonomy'] ) ? $box['args']['taxonomy'] : '';
		$tax = $taxonomy ? get_taxonomy( $taxonomy ) : null;
		if ( ! $tax ) {
			return;
		}
		$tax_name = esc_attr( $taxonomy );

		$field_names = array(
			RL_Plugin::TAG_TAX    => array( 'rl_new_tag', 'rl_new_tag_slug' ),
			RL_Plugin::FORMAT_TAX => array( 'rl_new_format', 'rl_new_format_slug' ),
		);
		if ( isset( $field_names[ $taxonomy ] ) ) {
			$name_field = $field_names[ $taxonomy ][0];
			$slug_field = $field_names[ $taxonomy ][1];
		} else {
			$name_field = 'rl_new_' . $taxonomy;
			$slug_field = 'rl_new_' . $taxonomy . '_slug';
		}

		$checked_terms = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
		if ( is_wp_error( $checked_terms ) ) {
			$checked_terms = array();
		}
		$recent = self::recent_tags( $taxonomy );
		?>
		<div id="taxonomy-<?php echo $tax_name; ?>" class="categorydiv">
			<ul id="<?php echo $tax_name; ?>-tabs" class="category-tabs">
				<li class="tabs"><a href="#<?php echo $tax_name; ?>-all" role="tab" aria-selected="true" aria-controls="<?php echo $tax_name; ?>-all"><?php echo esc_html( $tax->labels->all_items ); ?></a></li>
				<li class="hide-if-no-js"><a href="#<?php echo $tax_name; ?>-pop" role="tab" aria-selected="false" aria-controls="<?php echo $tax_name; ?>-pop"><?php echo esc_html( $tax->labels->most_used ); ?></a></li>
			</ul>

			<div id="<?php echo $tax_name; ?>-pop" class="tabs-panel" style="display:none;" role="tabpanel" aria-hidden="true">
				<ul id="<?php echo $tax_name; ?>checklist-pop" class="categorychecklist form-no-clear">
					<?php if ( ! empty( $recent ) ) : ?>
						<?php foreach ( $recent as $term ) : ?>
							<?php
							$term_id = (int) $term->term_id;
							$checked = in_array( $term_id, $checked_terms, true ) ? ' checked="checked"' : '';
							?>
							<li id="popular-<?php echo $tax_name; ?>-<?php echo $term_id; ?>" class="popular-category">
								<label class="selectit">
									<input id="in-popular-<?php echo $tax_name; ?>-<?php echo $term_id; ?>" type="checkbox" name="tax_input[<?php echo $tax_name; ?>][]" value="<?php echo esc_attr( $term_id ); ?>"<?php echo $checked; // phpcs:ignore WordPress.Security.EscapeOutput ?> />
									<?php echo esc_html( $term->name ); ?>
								</label>
							</li>
						<?php endforeach; ?>
					<?php else : ?>
						<li><?php esc_html_e( 'No tags used yet.', 'rl' ); ?></li>
					<?php endif; ?>
				</ul>
			</div>

			<div id="<?php echo $tax_name; ?>-all" class="tabs-panel" role="tabpanel" aria-hidden="false">
				<input type="hidden" name="tax_input[<?php echo $tax_name; ?>][]" value="0" />
				<ul id="<?php echo $tax_name; ?>checklist" class="categorychecklist form-no-clear">
					<?php wp_terms_checklist( $post->ID, array( 'taxonomy' => $taxonomy ) ); ?>
				</ul>
			</div>

			<?php if ( current_user_can( $tax->cap->edit_terms ) ) : ?>
				<details class="rl-tax-add">
					<summary><?php echo esc_html( $tax->labels->add_new_item ); ?></summary>
					<p>
						<label for="<?php echo esc_attr( $name_field ); ?>"><?php echo esc_html( $tax->labels->new_item_name ); ?></label>
						<input type="text" name="<?php echo esc_attr( $name_field ); ?>" id="<?php echo esc_attr( $name_field ); ?>" class="widefat" value="" />
					</p>
					<p>
						<label for="<?php echo esc_attr( $slug_field ); ?>"><?php esc_html_e( 'Slug', 'rl' ); ?></label>
						<input type="text" name="<?php echo esc_attr( $slug_field ); ?>" id="<?php echo esc_attr( $slug_field ); ?>" class="widefat" value="" />
						<span class="howto"><?php esc_html_e( 'Optional. Leave blank to auto-generate. The tag is created when you update this resource.', 'rl' ); ?></span>
					</p>
				</details>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function recent_tags( $taxonomy, $number = 0 ) {
		if ( $number < 1 ) {
			$number = (int) RL_Plugin::get_setting( 'recent_tags_count', 10 );
		}
		$number = max( 1, $number );
		$query = new WP_Query(
			array(
				'post_type'      => RL_Plugin::PT,
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		if ( empty( $query->posts ) ) {
			return array();
		}
		$terms = wp_get_object_terms( $query->posts, $taxonomy, array( 'orderby' => 'none' ) );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}
		$seen = array();
		$out = array();
		foreach ( $terms as $term ) {
			if ( isset( $seen[ $term->term_id ] ) ) {
				continue;
			}
			$seen[ $term->term_id ] = true;
			$out[] = $term;
			if ( count( $out ) >= $number ) {
				break;
			}
		}
		return $out;
	}
}
