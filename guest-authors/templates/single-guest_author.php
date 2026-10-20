<?php
/**
 * Guest author single template (fallback when the theme has no single-guest_author.php).
 *
 * @package GuestAuthors
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$guest_id = get_the_ID();
	$job      = (string) get_post_meta( $guest_id, GA_Plugin::JOB_TITLE_META, true );
	$company  = (string) get_post_meta( $guest_id, GA_Plugin::COMPANY_META, true );
	$website  = (string) get_post_meta( $guest_id, GA_Plugin::WEBSITE_META, true );
	$social_x = (string) get_post_meta( $guest_id, GA_Plugin::SOCIAL_X_META, true );
	$linkedin = (string) get_post_meta( $guest_id, GA_Plugin::SOCIAL_LINKEDIN_META, true );
	$role     = trim( $job . ( $job && $company ? ', ' : '' ) . $company );
	?>
	<main id="primary" class="ga-profile">
		<article <?php post_class(); ?>>
			<header class="ga-profile-header">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="ga-profile-avatar"><?php the_post_thumbnail( 'ga-avatar' ); ?></div>
				<?php endif; ?>

				<h1 class="ga-profile-name"><?php the_title(); ?></h1>

				<?php if ( $role ) : ?>
					<p class="ga-profile-role"><?php echo esc_html( $role ); ?></p>
				<?php endif; ?>

				<?php if ( $website || $social_x || $linkedin ) : ?>
					<ul class="ga-profile-links">
						<?php if ( $website ) : ?>
							<li><a href="<?php echo esc_url( $website ); ?>" rel="noopener"><?php esc_html_e( 'Website', 'ga' ); ?></a></li>
						<?php endif; ?>
						<?php if ( $social_x ) : ?>
							<li><a href="<?php echo esc_url( $social_x ); ?>" rel="noopener"><?php esc_html_e( 'X', 'ga' ); ?></a></li>
						<?php endif; ?>
						<?php if ( $linkedin ) : ?>
							<li><a href="<?php echo esc_url( $linkedin ); ?>" rel="noopener"><?php esc_html_e( 'LinkedIn', 'ga' ); ?></a></li>
						<?php endif; ?>
					</ul>
				<?php endif; ?>

				<div class="ga-profile-bio"><?php the_content(); ?></div>
			</header>

			<section class="ga-profile-posts">
				<h2>
					<?php
					/* translators: %s: guest author name. */
					printf( esc_html__( 'Posts by %s', 'ga' ), esc_html( get_the_title() ) );
					?>
				</h2>
				<?php
				$guest_posts = new WP_Query(
					array(
						'post_type'           => GA_Plugin::supported_post_types(),
						'post_status'         => 'publish',
						'posts_per_page'      => 10,
						'meta_key'            => GA_Plugin::META_GUEST_ID,
						'meta_value'          => $guest_id,
						'ignore_sticky_posts' => true,
					)
				);

				if ( $guest_posts->have_posts() ) :
					echo '<ul class="ga-post-list">';
					while ( $guest_posts->have_posts() ) :
						$guest_posts->the_post();
						?>
						<li>
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						</li>
						<?php
					endwhile;
					echo '</ul>';
					wp_reset_postdata();
				else :
					echo '<p>' . esc_html__( 'No posts yet.', 'ga' ) . '</p>';
				endif;
				?>
			</section>
		</article>
	</main>
	<?php
endwhile;

get_footer();
