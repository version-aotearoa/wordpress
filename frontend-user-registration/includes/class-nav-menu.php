<?php
defined( 'ABSPATH' ) || exit;

/**
 * Switches the account links for logged-in visitors.
 *
 * Logged out nothing changes. Logged in:
 *  - the "Join" / registration link becomes "My Account" pointing at the
 *    account page (query string stripped), and
 *  - the "Login" link is removed.
 *
 * Both menu systems are covered: classic menus through wp_nav_menu_objects and
 * block navigation through render_block.
 */
class FEUR_Nav_Menu {

	/** Block types that can carry an account link. */
	const BLOCKS = array( 'core/button', 'core/navigation-link' );

	public function __construct() {
		add_filter( 'render_block', array( $this, 'filter_block' ), 10, 2 );
		add_filter( 'wp_nav_menu_objects', array( $this, 'filter_menu_objects' ), 10, 1 );
	}

	/** Account URL, filterable so a differently named page can be targeted. */
	public function account_url() {
		return apply_filters( 'feur_nav_account_url', FEUR_Page_Installer::account_url() );
	}

	private function label() {
		return apply_filters( 'feur_nav_account_label', __( 'My Account', 'feur' ) );
	}

	/* ---------------------------------------------------------------- blocks */

	public function filter_block( $content, $block ) {
		if ( ! is_user_logged_in() ) {
			return $content;
		}
		if ( empty( $block['blockName'] ) || ! in_array( $block['blockName'], self::BLOCKS, true ) ) {
			return $content;
		}
		if ( false === strpos( $content, 'href=' ) ) {
			return $content;
		}

		$href = $this->first_href( $content );
		if ( '' === $href || ! $this->is_account_href( $href ) ) {
			return $content;
		}

		$text = $this->text( $content );

		if ( $this->is_registration( $href, $text ) ) {
			return $this->render_account_link( $content, $text );
		}

		if ( $this->is_login( $href, $text ) ) {
			return '';
		}

		return $content;
	}

	/* --------------------------------------------------------- classic menus */

	public function filter_menu_objects( $items ) {
		if ( ! is_user_logged_in() || ! is_array( $items ) ) {
			return $items;
		}

		$kept = array();
		foreach ( $items as $item ) {
			$url   = isset( $item->url ) ? (string) $item->url : '';
			$title = isset( $item->title ) ? (string) $item->title : '';
			$text  = $this->text( $title );

			if ( '' === $url || ! $this->is_account_href( $url ) ) {
				$kept[] = $item;
				continue;
			}

			if ( $this->is_login( $url, $text ) ) {
				continue; // Removed for logged-in visitors.
			}

			if ( $this->is_registration( $url, $text ) ) {
				$item->title = $this->label();
				$item->url   = $this->account_url();
			}

			$kept[] = $item;
		}

		return $kept;
	}

	/* ------------------------------------------------------------- helpers */

	private function first_href( $html ) {
		if ( preg_match( '/href="([^"]+)"/i', $html, $matches ) ) {
			return html_entity_decode( $matches[1], ENT_QUOTES, 'UTF-8' );
		}
		return '';
	}

	private function account_path() {
		$path = wp_parse_url( $this->account_url(), PHP_URL_PATH );
		return is_string( $path ) && '' !== $path ? trailingslashit( $path ) : '';
	}

	private function is_account_href( $href ) {
		$path   = wp_parse_url( $href, PHP_URL_PATH );
		$target = $this->account_path();
		if ( ! is_string( $path ) || '' === $target ) {
			return false;
		}
		return trailingslashit( $path ) === $target;
	}

	private function query_args( $href ) {
		$query = wp_parse_url( $href, PHP_URL_QUERY );
		$args  = array();
		if ( is_string( $query ) && '' !== $query ) {
			parse_str( $query, $args );
		}
		return $args;
	}

	private function tab_arg( $href ) {
		$args = $this->query_args( $href );
		if ( isset( $args['fe_tab'] ) ) {
			return (string) $args['fe_tab'];
		}
		return isset( $args['tab'] ) ? (string) $args['tab'] : '';
	}

	private function text( $html ) {
		$text = trim( wp_strip_all_tags( (string) $html ) );
		return trim( preg_replace( '/\s+/', ' ', $text ) );
	}

	private function is_registration( $href, $text ) {
		if ( in_array( $this->tab_arg( $href ), array( 'register', 'reg' ), true ) ) {
			return true;
		}
		return $this->has_label( $text, $this->join_labels() );
	}

	private function is_login( $href, $text ) {
		if ( 'login' === $this->tab_arg( $href ) ) {
			return true;
		}
		return $this->has_label( $text, $this->login_labels() );
	}

	/**
	 * Wording differs per site ("Join NZAHTA", "Member Login"), so these lists
	 * are filters rather than constants.
	 */
	private function login_labels() {
		return (array) apply_filters(
			'feur_nav_login_labels',
			array( 'login', 'log in', 'log-in', 'sign in', 'sign-in' )
		);
	}

	private function join_labels() {
		return (array) apply_filters(
			'feur_nav_join_labels',
			array( 'join', 'register', 'sign up', 'sign-up' )
		);
	}

	private function has_label( $text, $labels ) {
		$text = strtolower( $text );
		if ( '' === $text ) {
			return false;
		}
		foreach ( $labels as $label ) {
			$label = strtolower( trim( (string) $label ) );
			if ( '' !== $label && false !== strpos( $text, $label ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Point the item at the account page and relabel it. The link text is only
	 * rewritten when the block holds a short label, so a navigation item with
	 * nested markup is left structurally intact.
	 */
	private function render_account_link( $content, $text ) {
		if ( '' !== $text && strlen( $text ) <= 40 ) {
			$pattern = '/>\s*' . preg_quote( $text, '/' ) . '\s*</';
			$content = preg_replace( $pattern, '>' . esc_html( $this->label() ) . '<', $content, 1 );
		}

		return $this->set_href( $content, $this->account_url() );
	}

	private function set_href( $html, $url ) {
		if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$processor = new WP_HTML_Tag_Processor( $html );
			if ( $processor->next_tag( 'a' ) ) {
				$processor->set_attribute( 'href', $url );
				return $processor->get_updated_html();
			}
			return $html;
		}

		// Fallback for WordPress versions without the HTML API.
		$updated = preg_replace( '/href="[^"]*"/i', 'href="' . esc_attr( $url ) . '"', $html, 1 );
		return is_string( $updated ) ? $updated : $html;
	}
}
