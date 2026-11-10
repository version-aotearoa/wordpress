<?php
defined( 'ABSPATH' ) || exit;

/**
 * Restricts wp-admin for Members Managers.
 *
 * Grants them the content screens they need (Posts, Pages, Media, Resources)
 * while removing the areas that are not part of managing members: comments,
 * tools, and the featured item (portfolio) post type. Menus are removed for
 * clarity, but access is also blocked - a hidden menu is not access control.
 */
class FEUR_Manager_Admin {

	/** Post types whose admin screens are out of scope for managers. */
	const POST_TYPES = array( 'featured_item', 'blocks' );

	/** Admin scripts denied outright, regardless of query string. */
	const DENY_SCRIPTS = array( 'edit-comments.php', 'tools.php', 'import.php', 'export.php', 'profile.php' );

	public function __construct() {
		// Priority 999 so every other plugin has registered its menus first.
		add_action( 'admin_menu', array( $this, 'hide_menus' ), 999 );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 9999 );
		add_action( 'admin_init', array( $this, 'block_screens' ) );
	}

	/**
	 * Post types to keep out of reach. Filters are applied here so a site can add
	 * its own without editing the plugin.
	 */
	private function post_types() {
		return (array) apply_filters( 'feur_manager_hidden_post_types', self::POST_TYPES );
	}

	/**
	 * Members Managers only. Administrators (including users who hold both
	 * roles) are never restricted.
	 */
	private function applies() {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return false;
		}
		return current_user_can( FEUR_Role::MANAGE );
	}

	public function hide_menus() {
		if ( ! $this->applies() ) {
			return;
		}
		remove_menu_page( 'edit-comments.php' );
		remove_menu_page( 'tools.php' );
		// Contact Form 7: managers only get the Submissions screen, which the
		// submissions plugin registers as its own top-level menu for them.
		remove_menu_page( 'wpcf7-dashboard' );
		remove_menu_page( 'wpcf7' );
		foreach ( $this->post_types() as $post_type ) {
			// Removing the parent also hides its taxonomy submenus.
			remove_menu_page( 'edit.php?post_type=' . $post_type );
		}
		// Profile exists either as its own menu or under Users, depending on
		// whether the user can list users.
		remove_menu_page( 'profile.php' );
		remove_submenu_page( 'users.php', 'profile.php' );
	}

	/**
	 * Point the admin bar at the front-end profile editor.
	 *
	 * Every node linking to profile.php is rewritten in place rather than
	 * removed and re-added: another plugin may register its own profile entry,
	 * and a remove/add cycle leaves two of them. Duplicates are then dropped so
	 * exactly one remains.
	 */
	public function admin_bar( $bar ) {
		if ( ! $this->applies() ) {
			return;
		}
		if ( ! ( $bar instanceof WP_Admin_Bar ) ) {
			return;
		}

		$url   = FEUR_Profile_Editor::url();
		$nodes = $bar->get_nodes();
		if ( ! is_array( $nodes ) ) {
			return;
		}

		$keep = '';
		$drop = array();

		foreach ( $nodes as $id => $node ) {
			if ( empty( $node->href ) ) {
				continue;
			}

			// get_nodes() returns clones, so a modified node has to be added
			// back for the change to take effect.
			if ( false !== strpos( $node->href, '/wp-admin/profile.php' ) ) {
				$node->href = $url;
				$bar->add_node( $node );
			}

			if ( $node->href === $url && isset( $node->title ) && false !== stripos( wp_strip_all_tags( $node->title ), 'profile' ) ) {
				if ( '' === $keep ) {
					$keep = $id;
				} else {
					$drop[] = $id;
				}
			}
		}

		foreach ( $drop as $id ) {
			$bar->remove_node( $id );
		}

		$account = $bar->get_node( 'my-account' );
		if ( $account ) {
			$account->href = $url;
			$bar->add_node( $account );
		}
	}

	public function block_screens() {
		if ( ! $this->applies() || wp_doing_ajax() ) {
			return;
		}

		global $pagenow;

		if ( ! $this->is_denied( (string) $pagenow ) ) {
			return;
		}

		// The profile screen has a front-end equivalent, so send managers there
		// instead of the dashboard.
		$target = 'profile.php' === $pagenow ? FEUR_Profile_Editor::url() : admin_url();

		wp_safe_redirect( $target );
		exit;
	}

	private function is_denied( $pagenow ) {
		if ( in_array( $pagenow, self::DENY_SCRIPTS, true ) ) {
			return true;
		}

		// Contact Form 7 admin pages, all of which live under a "wpcf7" page
		// slug. The submissions screen uses its own slug and is unaffected.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( '' !== $page && 0 === strpos( $page, 'wpcf7' ) ) {
			return true;
		}

		$types     = $this->post_types();
		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';

		if ( 'edit.php' === $pagenow ) {
			return in_array( $post_type, $types, true );
		}

		if ( 'edit-tags.php' === $pagenow ) {
			if ( in_array( $post_type, $types, true ) ) {
				return true;
			}
			// The post_type argument is not always present; fall back to the
			// taxonomy's own attachment.
			$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';
			$tax      = $taxonomy ? get_taxonomy( $taxonomy ) : null;
			return $tax && array_intersect( (array) $tax->object_type, $types );
		}

		if ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) {
			$type = $post_type;
			if ( '' === $type && 'post.php' === $pagenow && ! empty( $_GET['post'] ) ) {
				$type = (string) get_post_type( absint( $_GET['post'] ) );
			}
			return in_array( $type, $types, true );
		}

		return false;
	}
}
