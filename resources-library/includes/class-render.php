<?php
defined( 'ABSPATH' ) || exit;

class RL_Render {

	public static function render_library( $base_url = '' ) {
		$tag_slug = isset( $_GET['tag'] ) ? sanitize_title( wp_unslash( $_GET['tag'] ) ) : '';
		$format_slug = isset( $_GET['format'] ) ? sanitize_title( wp_unslash( $_GET['format'] ) ) : '';
		$paged = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
		$favourites = ( '1' === ( isset( $_GET['favourites'] ) ? sanitize_key( wp_unslash( $_GET['favourites'] ) ) : '' ) );

		if ( ! $base_url ) {
			$base_url = get_permalink();
		}

		$formats = self::applicable_formats( $tag_slug, $favourites );

		$query = self::query( $tag_slug, $format_slug, $paged, $favourites );

		$html = '<div class="rl-library">';
		$html .= self::sidebar_html( $tag_slug, $format_slug, $base_url );

		$html .= '<main class="rl-main" id="rl-main">';
		$html .= '<div class="rl-head">';
		if ( $tag_slug ) {
			$term = get_term_by( 'slug', $tag_slug, RL_Plugin::TAG_TAX );
			$title = ( $term && ! is_wp_error( $term ) ) ? $term->name : get_the_title();
		} elseif ( $favourites ) {
			$title = __( 'Favourites', 'rl' );
		} else {
			$title = __( 'Featured Resources', 'rl' );
		}
		$html .= '<h1 class="rl-title" data-title="' . esc_attr( get_the_title() ) . '">' . esc_html( $title ) . '</h1>';
		$html .= self::formats_html( $formats, $format_slug, $tag_slug, $base_url, $favourites );
		$html .= '</div>';

		$html .= '<div class="rl-posts" id="rl-posts" data-page="' . esc_attr( max( 1, $paged ) ) . '">';
		$cards = self::cards( $query );
		if ( $cards ) {
			$html .= $cards;
		} else {
			$html .= self::empty_html( $favourites );
		}
		$html .= '</div>';

		if ( $query->max_num_pages > $paged ) {
			$html .= '<div class="rl-load-more-wrap"><button type="button" class="rl-load-more" id="rl-load-more">' . esc_html__( 'Load more', 'rl' ) . '</button></div>';
		}
		$html .= '</main>';
		$html .= '</div>';

		return $html;
	}

	public static function query( $tag = '', $format = '', $paged = 1, $favourites = false ) {
		$per_page = (int) apply_filters( 'rl_posts_per_page', (int) RL_Plugin::get_setting( 'posts_per_page', 6 ) );
		$args = array(
			'post_type'      => RL_Plugin::PT,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => max( 1, (int) $paged ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$tax_query = array();

		if ( $tag ) {
			$tax_query[] = array(
				'taxonomy' => RL_Plugin::TAG_TAX,
				'field'    => 'slug',
				'terms'    => $tag,
			);
		}

		if ( $format ) {
			$tax_query[] = array(
				'taxonomy' => RL_Plugin::FORMAT_TAX,
				'field'    => 'slug',
				'terms'    => $format,
			);
		}

		if ( ! empty( $tax_query ) ) {
			$tax_query['relation'] = 'AND';
			$args['tax_query'] = $tax_query;
		}

		if ( $favourites ) {
			$user_id = get_current_user_id();
			$favs = $user_id ? get_user_meta( $user_id, 'rl_favourites', true ) : array();
			$favs = is_array( $favs ) ? array_values( array_filter( array_map( 'absint', $favs ) ) ) : array();
			$args['post__in'] = $favs ? $favs : array( 0 );
		} elseif ( ! $tag ) {
			$args['meta_key'] = RL_Plugin::FEATURED_META;
			$args['meta_value'] = '1';
		}

		return new WP_Query( $args );
	}

	public static function sidebar_html( $tag_slug = '', $format_slug = '', $base_url = '' ) {
		if ( ! $base_url ) {
			$base_url = get_permalink();
		}
		$tags = get_terms(
			array(
				'taxonomy'   => RL_Plugin::TAG_TAX,
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);
		$html = '<aside class="rl-sidebar" id="rl-sidebar">';
		$html .= '<h2 class="rl-sidebar-title">' . esc_html__( 'Resource Library', 'rl' ) . '</h2>';
		if ( is_user_logged_in() ) {
			$fav_link = add_query_arg( array( 'favourites' => 1, 'tag' => false, 'format' => false, 'page' => false ), $base_url );
			$html .= '<a href="' . esc_url( $fav_link ) . '" class="rl-tag rl-fav-nav" data-fav-nav="1">' . esc_html__( 'Favourites', 'rl' ) . '</a>';
		}
		if ( ! is_wp_error( $tags ) && ! empty( $tags ) ) {
			$by_parent = self::group_tags( $tags );
			$html .= '<nav class="rl-tag-nav" aria-label="' . esc_attr__( 'Resource sections', 'rl' ) . '">';
			$html .= self::tag_list_html( $by_parent, 0, '', $tag_slug, $format_slug, $base_url );
			$html .= '</nav>';
		} else {
			$html .= '<p>' . esc_html__( 'No sections yet.', 'rl' ) . '</p>';
		}
		$html .= '</aside>';
		return $html;
	}

	private static function group_tags( $tags ) {
		$by_parent = array( 0 => array() );
		foreach ( $tags as $tag ) {
			$pid = (int) $tag->parent;
			if ( ! isset( $by_parent[ $pid ] ) ) {
				$by_parent[ $pid ] = array();
			}
			$by_parent[ $pid ][] = $tag;
		}
		foreach ( $by_parent as $pid => $group ) {
			usort( $group, array( __CLASS__, 'sort_tags' ) );
			$by_parent[ $pid ] = $group;
		}
		return $by_parent;
	}

	private static function sort_tags( $a, $b ) {
		$oa = (int) get_term_meta( $a->term_id, 'rl_sidebar_order', true );
		$ob = (int) get_term_meta( $b->term_id, 'rl_sidebar_order', true );
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

	private static function tag_list_html( $by_parent, $parent_id, $parent_slug, $tag_slug, $format_slug, $base_url ) {
		$html = '';
		if ( empty( $by_parent[ $parent_id ] ) ) {
			return $html;
		}
		foreach ( $by_parent[ $parent_id ] as $tag ) {
			$has_children = ! empty( $by_parent[ (int) $tag->term_id ] );
			$link = add_query_arg( array( 'tag' => $tag->slug, 'page' => false ), $base_url );
			$link = $format_slug ? add_query_arg( 'format', $format_slug, $link ) : remove_query_arg( 'format', $link );
			$class = 'rl-tag' . ( $tag->slug === $tag_slug ? ' rl-tag-active' : '' );
			$data_parent = '';
			if ( $parent_id ) {
				$class .= ' rl-tag-child';
				$data_parent = ' data-parent="' . esc_attr( $parent_slug ) . '"';
			}
			$html .= '<div class="rl-tag-row">';
			$html .= '<a href="' . esc_url( $link ) . '" class="' . esc_attr( $class ) . '" data-tag="' . esc_attr( $tag->slug ) . '"' . $data_parent . '>' . esc_html( $tag->name ) . '</a>';
			if ( $has_children ) {
				$html .= '<button type="button" class="rl-tag-arrow" data-arrow="' . esc_attr( $tag->slug ) . '" aria-expanded="false" aria-label="' . esc_attr( sprintf( __( 'Toggle children of %s', 'rl' ), $tag->name ) ) . '"><svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M9 5l7 7-7 7V5z"/></svg></button>';
			}
			$html .= '</div>';
			$html .= self::tag_list_html( $by_parent, (int) $tag->term_id, $tag->slug, $tag_slug, $format_slug, $base_url );
		}
		return $html;
	}

	public static function single_page_html( $post, $base_url = '' ) {
		if ( ! $base_url ) {
			$base_url = get_permalink();
		}
		$html = '<div class="rl-library">';
		$html .= self::sidebar_html( '', '', $base_url );
		$html .= '<main class="rl-main">';
		$html .= self::single( $post, $base_url );
		$html .= '</main>';
		$html .= '</div>';
		return $html;
	}

	public static function applicable_formats( $tag_slug = '', $favourites = false ) {
		$all = get_terms(
			array(
				'taxonomy'   => RL_Plugin::FORMAT_TAX,
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);
		if ( is_wp_error( $all ) || empty( $all ) ) {
			return array();
		}

		$applicable = array();
		foreach ( $all as $format ) {
			$args = array(
				'post_type'      => RL_Plugin::PT,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => array(
					'relation' => 'AND',
					array(
						'taxonomy' => RL_Plugin::FORMAT_TAX,
						'field'    => 'slug',
						'terms'    => $format->slug,
					),
				),
			);
			if ( $tag_slug ) {
				$args['tax_query'][] = array(
					'taxonomy' => RL_Plugin::TAG_TAX,
					'field'    => 'slug',
					'terms'    => $tag_slug,
				);
			}
			if ( $favourites ) {
				$user_id = get_current_user_id();
				$favs = $user_id ? get_user_meta( $user_id, 'rl_favourites', true ) : array();
				$favs = is_array( $favs ) ? array_values( array_filter( array_map( 'absint', $favs ) ) ) : array();
				$args['post__in'] = $favs ? $favs : array( 0 );
			} elseif ( ! $tag_slug ) {
				$args['meta_key']   = RL_Plugin::FEATURED_META;
				$args['meta_value'] = '1';
			}
			$posts = get_posts( $args );
			if ( $posts ) {
				$applicable[] = $format;
			}
		}

		return $applicable;
	}

	public static function formats_html( $formats, $format_slug, $tag_slug, $base_url, $favourites = false ) {
		$html = '<div class="rl-formats" id="rl-formats" role="group" aria-label="' . esc_attr__( 'Filter by format', 'rl' ) . '">';
		$all_link = add_query_arg( array( 'format' => false, 'page' => false ), $base_url );
		if ( $favourites ) {
			$all_link = add_query_arg( 'favourites', 1, $all_link );
		}
		if ( $tag_slug ) {
			$all_link = add_query_arg( 'tag', $tag_slug, $all_link );
		}
		$html .= '<a href="' . esc_url( $all_link ) . '" class="rl-chip' . ( $format_slug ? '' : ' rl-chip-active' ) . '" data-format="">' . esc_html__( 'All', 'rl' ) . '</a>';
		foreach ( $formats as $format ) {
			$link = add_query_arg( array( 'format' => $format->slug, 'page' => false ), $base_url );
			if ( $favourites ) {
				$link = add_query_arg( 'favourites', 1, $link );
			}
			if ( $tag_slug ) {
				$link = add_query_arg( 'tag', $tag_slug, $link );
			}
			$html .= '<a href="' . esc_url( $link ) . '" class="rl-chip' . ( $format->slug === $format_slug ? ' rl-chip-active' : '' ) . '" data-format="' . esc_attr( $format->slug ) . '">' . esc_html( $format->name ) . '</a>';
		}
		$html .= '</div>';
		return $html;
	}

	public static function cards( $query ) {
		if ( ! $query->have_posts() ) {
			return '';
		}
		$html = '';
		while ( $query->have_posts() ) {
			$query->the_post();
			$html .= self::card( $query->post );
		}
		wp_reset_postdata();
		return $html;
	}

	public static function empty_html( $is_favourites = false ) {
		if ( $is_favourites ) {
			$message = __( 'Click the heart icon on resources to save your favourites here', 'rl' );
		} else {
			$message = __( 'No resources found.', 'rl' );
		}
		return '<p class="rl-empty">' . esc_html( $message ) . '</p>';
	}

	public static function card( $post ) {
		$title = get_the_title( $post );
		if ( '' === $title ) {
			$title = __( '(no title)', 'rl' );
		}
		$external_url = get_post_meta( $post->ID, RL_Plugin::URL_META, true );
		$format = self::format_of( $post->ID );
		$format_slug = self::format_slug_of( $post->ID );
		$is_video = ( $external_url && ( 'video' === $format_slug || self::is_video_url( $external_url ) ) );

		if ( $is_video ) {
			$thumb_attrs = ' data-video="' . esc_url( $external_url ) . '"';
			$title_attrs = ' data-rl-single="' . esc_attr( $post->ID ) . '"';
			$url = get_permalink( $post );
			$external = false;
		} elseif ( $external_url ) {
			$thumb_attrs = $title_attrs = ' target="_blank" rel="noopener"';
			$url = $external_url;
			$external = true;
		} else {
			$thumb_attrs = $title_attrs = ' data-rl-single="' . esc_attr( $post->ID ) . '"';
			$url = get_permalink( $post );
			$external = false;
		}

		$html = '<article class="rl-card"' . ( $format_slug ? ' data-format="' . esc_attr( $format_slug ) . '"' : '' ) . '>';
		if ( has_post_thumbnail( $post ) ) {
			$html .= '<a class="rl-card-thumb" href="' . esc_url( $url ) . '"' . $thumb_attrs . '>' . get_the_post_thumbnail( $post, 'rl-card' );
			if ( $is_video ) {
				$html .= '<span class="rl-play" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>';
			}
			$html .= '</a>';
		}
		$html .= '<div class="rl-card-body">';
		if ( $format ) {
			$html .= '<span class="rl-badge rl-badge-' . esc_attr( sanitize_title( $format ) ) . '">' . esc_html( $format ) . '</span>';
		}
		$html .= '<h3 class="rl-card-title"><a href="' . esc_url( $url ) . '"' . $title_attrs . '>' . esc_html( $title ) . '</a></h3>';
		$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( get_the_content( null, false, $post ) ), 24 );
		if ( $excerpt ) {
			$html .= '<p class="rl-card-excerpt">' . esc_html( $excerpt ) . '</p>';
		}

		$html .= '<div class="rl-card-actions">';
		if ( is_user_logged_in() ) {
			$is_faved = self::is_favourited( $post->ID );
			$html .= '<button type="button" class="rl-fav' . ( $is_faved ? ' rl-faved' : '' ) . '" data-fav="' . esc_attr( $post->ID ) . '" aria-pressed="' . ( $is_faved ? 'true' : 'false' ) . '" aria-label="' . esc_attr__( 'Add to favourites', 'rl' ) . '"><svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M12 21s-6-4.35-9-8.5C1.1 8.2 3.4 5 6.7 5c1.9 0 3.3 1 5.3 3 2-2 3.4-3 5.3-3 3.3 0 5.6 3.2 4.7 7.5C18 16.65 12 21 12 21z"/></svg></button>';
		}
		$html .= '<button type="button" class="rl-share" data-share="' . esc_url( get_permalink( $post ) ) . '" aria-label="' . esc_attr__( 'Share', 'rl' ) . '"><svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/></svg></button>';
		$html .= '</div>';

		$html .= '</div>';
		$html .= '</article>';
		return $html;
	}

	public static function is_favourited( $post_id ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		$favs = get_user_meta( $user_id, 'rl_favourites', true );
		if ( ! is_array( $favs ) ) {
			return false;
		}
		return in_array( (int) $post_id, array_map( 'intval', $favs ), true );
	}

	private static function format_of( $post_id ) {
		$terms = get_the_terms( $post_id, RL_Plugin::FORMAT_TAX );
		if ( is_array( $terms ) && ! empty( $terms ) ) {
			$term = reset( $terms );
			return $term->name;
		}
		return '';
	}

	private static function format_slug_of( $post_id ) {
		$terms = get_the_terms( $post_id, RL_Plugin::FORMAT_TAX );
		if ( is_array( $terms ) && ! empty( $terms ) ) {
			$term = reset( $terms );
			return $term->slug;
		}
		return '';
	}

	private static function is_video_url( $url ) {
		if ( ! $url ) {
			return false;
		}
		if ( preg_match( '#(youtube\.com/(watch\?v=|embed/|v/)|youtu\.be/|vimeo\.com/\d+)#i', $url ) ) {
			return true;
		}
		return (bool) preg_match( '/\.(mp4|webm|ogg|ogv|m4v)(\?.*)?$/i', $url );
	}

	public static function single( $post, $base = '', $tag_slug = '' ) {
		$format = self::format_of( $post->ID );
		$back = $base ? $base : get_permalink();

		if ( $tag_slug ) {
			$term = get_term_by( 'slug', $tag_slug, RL_Plugin::TAG_TAX );
			if ( $term && ! is_wp_error( $term ) ) {
				$back_label = sprintf( __( '← Show all %s', 'rl' ), $term->name );
			} else {
				$back_label = __( '← Show all Resources', 'rl' );
			}
		} else {
			$back_label = __( '← Show all Resources', 'rl' );
		}

		$html = '<article class="rl-single">';
		$html .= '<p><a class="rl-back" href="' . esc_url( $back ) . '">' . esc_html( $back_label ) . '</a></p>';
		if ( $format ) {
			$html .= '<span class="rl-badge rl-badge-' . esc_attr( sanitize_title( $format ) ) . '">' . esc_html( $format ) . '</span>';
		}
		$html .= '<h1 class="rl-single-title">' . esc_html( get_the_title( $post ) ) . '</h1>';

		$external_url = get_post_meta( $post->ID, RL_Plugin::URL_META, true );
		$is_video = ( $external_url && ( 'video' === self::format_slug_of( $post->ID ) || self::is_video_url( $external_url ) ) );

		if ( has_post_thumbnail( $post ) ) {
			$html .= '<div class="rl-single-thumb' . ( $is_video ? ' rl-single-thumb-video' : '' ) . '"' . ( $is_video ? ' data-video="' . esc_url( $external_url ) . '"' : '' ) . '>' . get_the_post_thumbnail( $post, 'large' );
			if ( $is_video ) {
				$html .= '<button type="button" class="rl-play rl-play-single" data-video="' . esc_url( $external_url ) . '" aria-label="' . esc_attr__( 'Play video', 'rl' ) . '"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg></button>';
			}
			$html .= '</div>';
		}

		setup_postdata( $post );
		$content = apply_filters( 'the_content', $post->post_content );
		wp_reset_postdata();
		$html .= '<div class="rl-single-content">' . $content . '</div>';

		if ( $is_video && ! has_post_thumbnail( $post ) ) {
			$html .= '<p><button type="button" class="rl-play-standalone" data-video="' . esc_url( $external_url ) . '">' . esc_html__( 'Play video', 'rl' ) . '</button></p>';
		}

		$html .= '</article>';
		return $html;
	}
}
