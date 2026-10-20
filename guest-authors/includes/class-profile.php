<?php
defined( 'ABSPATH' ) || exit;

class GA_Profile {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_box' ) );
		add_action( 'save_post_' . GA_Plugin::PT, array( $this, 'save' ), 10, 2 );
	}

	public function add_box() {
		add_meta_box(
			'ga_profile',
			__( 'Guest Author Details', 'ga' ),
			array( $this, 'render' ),
			GA_Plugin::PT,
			'normal',
			'high',
			array( '__block_editor_compatible_meta_box' => true )
		);
	}

	private static function fields() {
		return array(
			GA_Plugin::JOB_TITLE_META       => array( __( 'Job title / role', 'ga' ), 'text' ),
			GA_Plugin::COMPANY_META         => array( __( 'Company', 'ga' ), 'text' ),
			GA_Plugin::WEBSITE_META         => array( __( 'Website', 'ga' ), 'url' ),
			GA_Plugin::SOCIAL_X_META        => array( __( 'X (Twitter) URL', 'ga' ), 'url' ),
			GA_Plugin::SOCIAL_LINKEDIN_META => array( __( 'LinkedIn URL', 'ga' ), 'url' ),
			GA_Plugin::EMAIL_META           => array( __( 'Private email (avatar fallback, never shown)', 'ga' ), 'email' ),
		);
	}

	public function render( $post ) {
		wp_nonce_field( 'ga_save_profile', 'ga_profile_nonce' );

		echo '<table class="form-table" role="presentation"><tbody>';
		foreach ( self::fields() as $meta => $field ) {
			$value = get_post_meta( $post->ID, $meta, true );
			echo '<tr>';
			echo '<th scope="row"><label for="' . esc_attr( $meta ) . '">' . esc_html( $field[0] ) . '</label></th>';
			echo '<td><input type="' . esc_attr( $field[1] ) . '" class="regular-text" name="' . esc_attr( $meta ) . '" id="' . esc_attr( $meta ) . '" value="' . esc_attr( $value ) . '" /></td>';
			echo '</tr>';
		}

		$linked = (int) get_post_meta( $post->ID, GA_Plugin::LINKED_USER_META, true );
		echo '<tr>';
		echo '<th scope="row"><label for="' . esc_attr( GA_Plugin::LINKED_USER_META ) . '">' . esc_html__( 'Linked WordPress user', 'ga' ) . '</label></th>';
		echo '<td>';
		wp_dropdown_users(
			array(
				'name'             => GA_Plugin::LINKED_USER_META,
				'id'               => GA_Plugin::LINKED_USER_META,
				'selected'         => $linked,
				'show_option_none' => __( '— None —', 'ga' ),
				'option_none_value' => 0,
			)
		);
		echo '<p class="description">' . esc_html__( 'Optional. Only used to link a guest to a real account; it does not change the post attribution.', 'ga' ) . '</p>';
		echo '</td></tr>';
		echo '</tbody></table>';

		echo '<p class="description">' . esc_html__( 'The featured image is used as the author avatar. The editor content is the author bio.', 'ga' ) . '</p>';
	}

	public function save( $post_id, $post ) {
		if ( ! isset( $_POST['ga_profile_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ga_profile_nonce'] ) ), 'ga_save_profile' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( self::fields() as $meta => $field ) {
			$raw = isset( $_POST[ $meta ] ) ? wp_unslash( $_POST[ $meta ] ) : '';
			switch ( $field[1] ) {
				case 'url':
					$value = esc_url_raw( $raw );
					break;
				case 'email':
					$value = sanitize_email( $raw );
					break;
				default:
					$value = sanitize_text_field( $raw );
			}
			if ( '' === $value ) {
				delete_post_meta( $post_id, $meta );
			} else {
				update_post_meta( $post_id, $meta, $value );
			}
		}

		$linked = isset( $_POST[ GA_Plugin::LINKED_USER_META ] ) ? absint( wp_unslash( $_POST[ GA_Plugin::LINKED_USER_META ] ) ) : 0;
		if ( $linked && get_userdata( $linked ) ) {
			update_post_meta( $post_id, GA_Plugin::LINKED_USER_META, $linked );
		} else {
			delete_post_meta( $post_id, GA_Plugin::LINKED_USER_META );
		}
	}
}
