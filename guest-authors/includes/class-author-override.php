<?php
defined( 'ABSPATH' ) || exit;

class GA_Author_Override {

	public function __construct() {
		add_filter( 'the_author', array( $this, 'filter_the_author' ) );
		add_filter( 'the_author_posts_link', array( $this, 'filter_author_posts_link' ) );
		add_filter( 'author_link', array( $this, 'filter_author_link' ), 10, 3 );
		add_filter( 'get_the_author_display_name', array( $this, 'filter_author_meta' ), 10, 3 );
		add_filter( 'get_the_author_description', array( $this, 'filter_author_meta' ), 10, 3 );
		add_filter( 'get_the_author_user_url', array( $this, 'filter_author_meta' ), 10, 3 );
		add_filter( 'pre_get_avatar_data', array( $this, 'filter_avatar' ), 10, 2 );
	}

	/**
	 * Ordered guest author IDs attributed to a post.
	 *
	 * @param int|null $post_id Post ID; defaults to the current post.
	 * @return int[]
	 */
	public static function get_guest_ids( $post_id = null ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		if ( ! $post_id ) {
			return array();
		}
		$ids = get_post_meta( $post_id, GA_Plugin::META_GUEST_ID, false );
		$ids = array_map( 'absint', (array) $ids );
		return array_values( array_unique( array_filter( $ids ) ) );
	}

	public static function get_guest( $guest_id ) {
		$guest_id = absint( $guest_id );
		if ( ! $guest_id ) {
			return null;
		}
		$guest = get_post( $guest_id );
		if ( ! $guest instanceof WP_Post || GA_Plugin::PT !== $guest->post_type || 'trash' === $guest->post_status ) {
			return null;
		}
		return $guest;
	}

	/**
	 * Byline items (id, name, url, job_title, company) in display order.
	 *
	 * @param int|null $post_id Post ID; defaults to the current post.
	 * @return array[]
	 */
	public static function get_byline_items( $post_id = null ) {
		$items = array();
		foreach ( self::get_guest_ids( $post_id ) as $guest_id ) {
			$guest = self::get_guest( $guest_id );
			if ( ! $guest ) {
				continue;
			}
			$items[] = array(
				'id'        => (int) $guest_id,
				'name'      => get_the_title( $guest ),
				'url'       => get_permalink( $guest ),
				'job_title' => (string) get_post_meta( $guest_id, GA_Plugin::JOB_TITLE_META, true ),
				'company'   => (string) get_post_meta( $guest_id, GA_Plugin::COMPANY_META, true ),
			);
		}
		return $items;
	}

	/**
	 * Join author names as "A", "A and B" or "A, B and C".
	 *
	 * @param string[] $names Author display names.
	 * @return string
	 */
	public static function join_names( $names ) {
		$names = array_values( array_filter( array_map( 'strval', (array) $names ) ) );
		$count = count( $names );
		if ( 0 === $count ) {
			return '';
		}
		if ( 1 === $count ) {
			return $names[0];
		}
		$last  = array_pop( $names );
		$join  = apply_filters( 'ga_byline_join_word', __( 'and', 'ga' ) );
		return implode( ', ', $names ) . ' ' . $join . ' ' . $last;
	}

	/**
	 * Guest avatar URL: featured image, falling back to Gravatar from the
	 * private email when set.
	 *
	 * @param int   $guest_id Guest author post ID.
	 * @param int   $size     Requested avatar size in pixels.
	 * @return string
	 */
	public static function guest_avatar_url( $guest_id, $size = 96 ) {
		$size = max( 1, (int) $size );
		$image_size = ( $size <= 150 ) ? 'thumbnail' : 'ga-avatar';
		$url = get_the_post_thumbnail_url( $guest_id, $image_size );
		if ( $url ) {
			return $url;
		}
		$email = get_post_meta( $guest_id, GA_Plugin::EMAIL_META, true );
		if ( $email && is_email( $email ) ) {
			return get_avatar_url( $email, array( 'size' => $size ) );
		}
		return '';
	}

	private function current_post_id() {
		$id = get_the_ID();
		if ( $id ) {
			return (int) $id;
		}
		global $post;
		return ( $post instanceof WP_Post ) ? (int) $post->ID : 0;
	}

	public function filter_the_author( $display_name ) {
		$items = self::get_byline_items( $this->current_post_id() );
		if ( empty( $items ) ) {
			return $display_name;
		}
		return self::join_names( wp_list_pluck( $items, 'name' ) );
	}

	public function filter_author_posts_link( $link ) {
		$post_id = $this->current_post_id();
		if ( empty( self::get_byline_items( $post_id ) ) ) {
			return $link;
		}
		return ga_get_byline( $post_id );
	}

	public function filter_author_link( $link, $author_id, $author_nicename ) {
		$items = self::get_byline_items( $this->current_post_id() );
		if ( empty( $items ) || empty( $items[0]['url'] ) ) {
			return $link;
		}
		return $items[0]['url'];
	}

	public function filter_author_meta( $value, $user_id = 0, $original_user_id = null ) {
		$items = self::get_byline_items( $this->current_post_id() );
		if ( empty( $items ) ) {
			return $value;
		}
		$field    = str_replace( 'get_the_author_', '', current_filter() );
		$guest_id = $items[0]['id'];

		switch ( $field ) {
			case 'display_name':
				return $items[0]['name'];

			case 'description':
				$guest = self::get_guest( $guest_id );
				if ( ! $guest ) {
					return $value;
				}
				if ( $guest->post_excerpt ) {
					return $guest->post_excerpt;
				}
				return wp_trim_words( wp_strip_all_tags( $guest->post_content ), 40 );

			case 'user_url':
				$url = get_post_meta( $guest_id, GA_Plugin::WEBSITE_META, true );
				return $url ? $url : $value;
		}

		return $value;
	}

	public function filter_avatar( $args, $id_or_email ) {
		$post_id = $this->current_post_id();
		if ( ! $post_id ) {
			return $args;
		}
		$items = self::get_byline_items( $post_id );
		if ( empty( $items ) ) {
			return $args;
		}
		if ( ! $this->avatar_matches_author( $id_or_email, $post_id ) ) {
			return $args;
		}

		$size = isset( $args['size'] ) ? (int) $args['size'] : 96;
		$url  = self::guest_avatar_url( $items[0]['id'], $size );
		if ( $url ) {
			$args['url']          = $url;
			$args['found_avatar'] = true;
		}
		return $args;
	}

	private function avatar_matches_author( $id_or_email, $post_id ) {
		$author_id = (int) get_post_field( 'post_author', $post_id );
		if ( ! $author_id ) {
			return false;
		}

		if ( is_numeric( $id_or_email ) ) {
			return (int) $id_or_email === $author_id;
		}
		if ( $id_or_email instanceof WP_User ) {
			return (int) $id_or_email->ID === $author_id;
		}
		if ( $id_or_email instanceof WP_Post ) {
			return (int) $id_or_email->post_author === $author_id;
		}
		if ( $id_or_email instanceof WP_Comment ) {
			return (int) $id_or_email->user_id === $author_id;
		}
		if ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );
			return $user && (int) $user->ID === $author_id;
		}
		return false;
	}
}

/**
 * Whether the given (or current) post has guest authors.
 *
 * @param int|null $post_id Post ID; defaults to the current post.
 * @return bool
 */
function ga_has_guest_authors( $post_id = null ) {
	return ! empty( GA_Author_Override::get_byline_items( $post_id ) );
}

/**
 * Guest author items for a post.
 *
 * @param int|null $post_id Post ID; defaults to the current post.
 * @return array[]
 */
function ga_get_guest_authors( $post_id = null ) {
	return GA_Author_Override::get_byline_items( $post_id );
}

/**
 * Guest avatar URL for a guest author post.
 *
 * @param int $guest_id Guest author post ID.
 * @param int $size     Avatar size in pixels.
 * @return string
 */
function ga_get_avatar_url( $guest_id, $size = 96 ) {
	return GA_Author_Override::guest_avatar_url( $guest_id, $size );
}

/**
 * Rendered byline HTML with each name linked to its guest author page.
 *
 * @param int|null $post_id Post ID; defaults to the current post.
 * @param array    $args    { link: bool, rel: string, class: string }.
 * @return string
 */
function ga_get_byline( $post_id = null, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'link'  => true,
			'rel'   => 'author',
			'class' => 'ga-byline',
		)
	);

	$items = GA_Author_Override::get_byline_items( $post_id );
	if ( empty( $items ) ) {
		return '';
	}

	$parts = array();
	foreach ( $items as $item ) {
		$name = esc_html( $item['name'] );
		if ( $args['link'] && ! empty( $item['url'] ) ) {
			$parts[] = '<a href="' . esc_url( $item['url'] ) . '" rel="' . esc_attr( $args['rel'] ) . '">' . $name . '</a>';
		} else {
			$parts[] = $name;
		}
	}

	$last = array_pop( $parts );
	$join = apply_filters( 'ga_byline_join_word', __( 'and', 'ga' ) );
	$html = $parts ? implode( ', ', $parts ) . ' ' . $join . ' ' . $last : $last;

	return '<span class="' . esc_attr( $args['class'] ) . '">' . $html . '</span>';
}
