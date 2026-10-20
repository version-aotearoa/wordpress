<?php
defined( 'ABSPATH' ) || exit;

class GA_Meta {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
	}

	public function add_boxes() {
		foreach ( GA_Plugin::supported_post_types() as $type ) {
			add_meta_box(
				'ga_coauthors',
				__( 'Guest Authors', 'ga' ),
				array( $this, 'render' ),
				$type,
				'side',
				'high',
				array( '__block_editor_compatible_meta_box' => true )
			);
		}
	}

	public function admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! GA_Plugin::is_supported( $screen->post_type ) ) {
			return;
		}
		wp_enqueue_style( 'ga-admin', GA_URL . 'assets/css/admin.css', array(), GA_VERSION );
		wp_enqueue_script( 'ga-admin', GA_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable' ), GA_VERSION, true );
	}

	private function all_guest_authors() {
		return get_posts(
			array(
				'post_type'        => GA_Plugin::PT,
				'post_status'      => 'publish',
				'posts_per_page'   => -1,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);
	}

	public function render( $post ) {
		wp_nonce_field( 'ga_save_coauthors', 'ga_coauthors_nonce' );

		$selected = GA_Author_Override::get_guest_ids( $post->ID );
		$all      = $this->all_guest_authors();
		?>
		<div class="ga-coauthors">
			<input type="hidden" name="ga_guest_authors" class="ga-coauthors-value" value="<?php echo esc_attr( implode( ',', $selected ) ); ?>" />

			<ul class="ga-coauthors-list">
				<?php foreach ( $selected as $guest_id ) : ?>
					<?php $guest = GA_Author_Override::get_guest( $guest_id ); ?>
					<?php if ( ! $guest ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<li class="ga-coauthor" data-id="<?php echo esc_attr( $guest->ID ); ?>">
						<span class="ga-handle" aria-hidden="true"><span class="dashicons dashicons-menu"></span></span>
						<span class="ga-coauthor-name"><?php echo esc_html( get_the_title( $guest ) ); ?></span>
						<button type="button" class="button-link ga-remove" aria-label="<?php esc_attr_e( 'Remove', 'ga' ); ?>">&times;</button>
					</li>
				<?php endforeach; ?>
			</ul>

			<p class="ga-coauthors-add">
				<select class="ga-coauthor-picker">
					<option value=""><?php esc_html_e( 'Add a guest author…', 'ga' ); ?></option>
					<?php foreach ( $all as $guest ) : ?>
						<option value="<?php echo esc_attr( $guest->ID ); ?>"><?php echo esc_html( get_the_title( $guest ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button ga-add"><?php esc_html_e( 'Add', 'ga' ); ?></button>
			</p>

			<p class="description"><?php esc_html_e( 'The post is attributed to the selected guest authors, in order. Leave empty to use the real WordPress author.', 'ga' ); ?></p>

			<?php if ( empty( $all ) ) : ?>
				<p><a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . GA_Plugin::PT ) ); ?>"><?php esc_html_e( 'Create a guest author', 'ga' ); ?></a></p>
			<?php endif; ?>
		</div>
		<?php
	}

	public function save( $post_id, $post ) {
		if ( ! isset( $_POST['ga_coauthors_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ga_coauthors_nonce'] ) ), 'ga_save_coauthors' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! $post instanceof WP_Post || ! GA_Plugin::is_supported( $post->post_type ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		delete_post_meta( $post_id, GA_Plugin::META_GUEST_ID );

		$raw = isset( $_POST['ga_guest_authors'] ) ? sanitize_text_field( wp_unslash( $_POST['ga_guest_authors'] ) ) : '';
		if ( '' === $raw ) {
			return;
		}

		$ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );
		$ids = array_values( array_unique( $ids ) );
		foreach ( $ids as $guest_id ) {
			if ( GA_Author_Override::get_guest( $guest_id ) ) {
				add_post_meta( $post_id, GA_Plugin::META_GUEST_ID, $guest_id, false );
			}
		}
	}
}
