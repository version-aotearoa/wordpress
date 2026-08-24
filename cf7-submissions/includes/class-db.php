<?php
defined( 'ABSPATH' ) || exit;

class CF7S_DB {

	const SCHEMA_VERSION = '1.0.0';

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . CF7S_Plugin::DB_TABLE;
	}

	public static function maybe_upgrade() {
		if ( get_option( CF7S_Plugin::DB_VERSION_OPTION, '' ) === self::SCHEMA_VERSION ) {
			return;
		}
		self::create_table();
		update_option( CF7S_Plugin::DB_VERSION_OPTION, self::SCHEMA_VERSION, false );
	}

	public static function create_table() {
		global $wpdb;
		$table = self::table_name();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			form_title VARCHAR(255) NOT NULL DEFAULT '',
			data LONGTEXT NOT NULL,
			remote_ip VARCHAR(45) NOT NULL DEFAULT '',
			user_agent TEXT NULL,
			url TEXT NULL,
			container_post_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY form_id (form_id),
			KEY created_at (created_at)
		) $charset;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public static function insert( $data ) {
		global $wpdb;
		return $wpdb->insert( self::table_name(), $data );
	}

	public static function get( $id ) {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' WHERE id = %d', $id )
		);
		if ( $row ) {
			$row->data = maybe_unserialize( $row->data );
		}
		return $row;
	}

	public static function delete( $ids ) {
		global $wpdb;
		$ids = array_filter( array_map( 'absint', (array) $ids ) );
		if ( ! $ids ) {
			return 0;
		}
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$query = $wpdb->prepare(
			'DELETE FROM ' . self::table_name() . ' WHERE id IN (' . $placeholders . ')',
			$ids
		);
		return $wpdb->query( $query );
	}

	public static function distinct_forms() {
		global $wpdb;
		return $wpdb->get_results(
			'SELECT form_id, form_title, COUNT(*) AS total FROM ' . self::table_name() . ' GROUP BY form_id, form_title ORDER BY form_title ASC'
		);
	}

	public static function count( $filter = array() ) {
		global $wpdb;
		list( $where, $values ) = self::build_where( $filter );
		$sql = 'SELECT COUNT(*) FROM ' . self::table_name();
		if ( $where ) {
			$sql .= ' WHERE ' . $where;
		}
		if ( $values ) {
			$sql = $wpdb->prepare( $sql, $values );
		}
		return (int) $wpdb->get_var( $sql );
	}

	public static function query( $filter = array() ) {
		global $wpdb;
		list( $where, $values ) = self::build_where( $filter );

		$sql = 'SELECT * FROM ' . self::table_name();
		if ( $where ) {
			$sql .= ' WHERE ' . $where;
		}
		$sql .= ' ORDER BY created_at DESC, id DESC';

		if ( ! empty( $filter['limit'] ) ) {
			$sql .= ' LIMIT %d';
			$values[] = absint( $filter['limit'] );
			if ( ! empty( $filter['offset'] ) ) {
				$sql .= ' OFFSET %d';
				$values[] = absint( $filter['offset'] );
			}
		}

		if ( $values ) {
			$sql = $wpdb->prepare( $sql, $values );
		}

		$rows = $wpdb->get_results( $sql );
		foreach ( $rows as $row ) {
			$row->data = maybe_unserialize( $row->data );
		}
		return $rows;
	}

	private static function build_where( $filter ) {
		global $wpdb;
		$where = array();
		$values = array();

		if ( ! empty( $filter['form_id'] ) ) {
			$where[] = 'form_id = %d';
			$values[] = absint( $filter['form_id'] );
		}

		if ( ! empty( $filter['search'] ) ) {
			$like = '%' . $wpdb->esc_like( $filter['search'] ) . '%';
			$where[] = '(data LIKE %s OR form_title LIKE %s)';
			$values[] = $like;
			$values[] = $like;
		}

		return array( implode( ' AND ', $where ), $values );
	}
}
