<?php
defined( 'ABSPATH' ) || exit;

class GA_Shortcode {

	public function __construct() {
		add_shortcode( 'ga_byline', array( $this, 'byline' ) );
		add_shortcode( 'ga_author_box', array( $this, 'author_box' ) );
	}

	public function byline( $atts ) {
		$atts = shortcode_atts(
			array(
				'link'    => 'yes',
				'post_id' => 0,
			),
			$atts,
			'ga_byline'
		);

		$post_id = absint( $atts['post_id'] );
		return ga_get_byline(
			$post_id ? $post_id : null,
			array( 'link' => ( 'no' !== strtolower( $atts['link'] ) ) )
		);
	}

	public function author_box( $atts ) {
		$atts    = shortcode_atts( array( 'post_id' => 0 ), $atts, 'ga_author_box' );
		$post_id = absint( $atts['post_id'] );
		$items   = GA_Author_Override::get_byline_items( $post_id ? $post_id : null );
		if ( empty( $items ) ) {
			return '';
		}

		$first = $items[0];
		$guest = GA_Author_Override::get_guest( $first['id'] );
		if ( ! $guest ) {
			return '';
		}

		$role = trim( $first['job_title'] . ( $first['job_title'] && $first['company'] ? ', ' : '' ) . $first['company'] );

		ob_start();
		?>
		<div class="ga-author-box">
			<?php if ( has_post_thumbnail( $guest->ID ) ) : ?>
				<div class="ga-author-box-avatar"><?php echo get_the_post_thumbnail( $guest->ID, 'ga-avatar' ); ?></div>
			<?php endif; ?>
			<div class="ga-author-box-body">
				<p class="ga-author-box-name"><a href="<?php echo esc_url( $first['url'] ); ?>"><?php echo esc_html( $first['name'] ); ?></a></p>
				<?php if ( $role ) : ?>
					<p class="ga-author-box-role"><?php echo esc_html( $role ); ?></p>
				<?php endif; ?>
				<div class="ga-author-box-bio"><?php echo wp_kses_post( get_the_excerpt( $guest ) ); ?></div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
