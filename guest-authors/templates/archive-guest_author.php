<?php
/**
 * Guest author archive template (fallback when the theme has no archive-guest_author.php).
 *
 * @package GuestAuthors
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="ga-archive">
	<header class="ga-archive-header">
		<h1 class="page-title"><?php post_type_archive_title(); ?></h1>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="ga-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				$job = (string) get_post_meta( get_the_ID(), GA_Plugin::JOB_TITLE_META, true );
				?>
				<article <?php post_class( 'ga-card' ); ?>>
					<a class="ga-card-link" href="<?php the_permalink(); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="ga-card-avatar"><?php the_post_thumbnail( 'ga-avatar' ); ?></div>
						<?php endif; ?>
						<h2 class="ga-card-name"><?php the_title(); ?></h2>
						<?php if ( $job ) : ?>
							<p class="ga-card-role"><?php echo esc_html( $job ); ?></p>
						<?php endif; ?>
					</a>
				</article>
			<?php endwhile; ?>
		</div>

		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'No guest authors yet.', 'ga' ); ?></p>
	<?php endif; ?>
</main>
<?php
get_footer();
