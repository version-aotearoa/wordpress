<?php
defined( 'ABSPATH' ) || exit;

class CF7S_Capture {

	public function __construct() {
		add_action( 'wpcf7_mail_sent', array( $this, 'save' ) );
	}

	public function save( $contact_form ) {
		if ( ! class_exists( 'WPCF7_Submission' ) ) {
			return;
		}

		$submission = WPCF7_Submission::get_instance();
		if ( ! $submission ) {
			return;
		}

		$meta_timestamp  = $submission->get_meta( 'timestamp' );
		$meta_remote_ip  = $submission->get_meta( 'remote_ip' );
		$meta_user_agent = $submission->get_meta( 'user_agent' );
		$meta_url        = $submission->get_meta( 'url' );
		$meta_container  = $submission->get_meta( 'container_post_id' );
		$meta_user       = $submission->get_meta( 'current_user_id' );
		$meta_no_store   = $submission->get_meta( 'do_not_store' );

		if ( ! empty( $meta_no_store ) ) {
			return;
		}

		$data = $this->clean_posted_data( $submission->get_posted_data() );

		CF7S_DB::insert(
			array(
				'form_id'           => (int) $contact_form->id(),
				'form_title'        => $contact_form->title(),
				'data'              => maybe_serialize( $data ),
				'remote_ip'         => $meta_remote_ip ? $meta_remote_ip : '',
				'user_agent'        => $meta_user_agent ? $meta_user_agent : '',
				'url'               => $meta_url ? $meta_url : '',
				'container_post_id' => absint( $meta_container ),
				'user_id'           => absint( $meta_user ),
				'created_at'        => gmdate( 'Y-m-d H:i:s', $meta_timestamp ? $meta_timestamp : time() ),
			)
		);
	}

	private function clean_posted_data( $posted_data ) {
		if ( ! is_array( $posted_data ) ) {
			return array();
		}

		$clean = array();
		foreach ( $posted_data as $key => $value ) {
			if ( 0 === strpos( (string) $key, '_wpcf7' ) ) {
				continue;
			}
			$clean[ $key ] = $this->clean_value( $value );
		}
		return $clean;
	}

	private function clean_value( $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $k => $v ) {
				if ( is_array( $v ) && isset( $v['name'] ) ) {
					$value[ $k ] = $v['name'];
				} else {
					$value[ $k ] = $this->clean_value( $v );
				}
			}
			return $value;
		}
		return is_scalar( $value ) ? $value : (string) $value;
	}
}
