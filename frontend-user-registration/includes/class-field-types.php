<?php
defined( 'ABSPATH' ) || exit;

class FEUR_Field_Types {

	public static function types() {
		return array(
			'text'     => __( 'Text', 'feur' ),
			'email'    => __( 'Email', 'feur' ),
			'textarea' => __( 'Textarea', 'feur' ),
			'select'   => __( 'Select', 'feur' ),
			'radio'    => __( 'Radio', 'feur' ),
			'checkbox' => __( 'Checkbox', 'feur' ),
		);
	}

	public static function is_choice( $type ) {
		return in_array( $type, array( 'select', 'radio', 'checkbox' ), true );
	}

	public static function options( $field ) {
		$options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();
		$out = array();
		foreach ( $options as $opt ) {
			$opt = trim( (string) $opt );
			if ( '' !== $opt ) {
				$out[ $opt ] = $opt;
			}
		}
		return $out;
	}

	public static function label( $field, $value ) {
		if ( self::is_choice( $field['type'] ) ) {
			$options = self::options( $field );
			if ( is_array( $value ) ) {
				$labels = array();
				foreach ( $value as $v ) {
					$labels[] = isset( $options[ $v ] ) ? $options[ $v ] : $v;
				}
				return implode( ', ', $labels );
			}
			return isset( $options[ $value ] ) ? $options[ $value ] : $value;
		}
		return $value;
	}

	public static function render( $field, $value = '', $attrs = array() ) {
		$attrs = wp_parse_args(
			$attrs,
			array(
				'name'  => 'fe_field_' . $field['id'],
				'class' => 'feur-input',
			)
		);
		$type = $field['type'];
		$required = ! empty( $field['required'] ) ? ' required="required"' : '';
		$id_attr = 'feur-field-' . $field['id'];
		$placeholder = isset( $field['placeholder'] ) ? ' placeholder="' . esc_attr( $field['placeholder'] ) . '"' : '';

		switch ( $type ) {
			case 'textarea':
				return '<textarea name="' . esc_attr( $attrs['name'] ) . '" id="' . esc_attr( $id_attr ) . '" class="' . esc_attr( $attrs['class'] ) . '"' . $required . $placeholder . '>' . esc_textarea( is_array( $value ) ? '' : (string) $value ) . '</textarea>';

			case 'select':
				$html = '<select name="' . esc_attr( $attrs['name'] ) . '" id="' . esc_attr( $id_attr ) . '" class="' . esc_attr( $attrs['class'] ) . '"' . $required . '>';
				$html .= '<option value="">' . esc_html__( 'Select…', 'feur' ) . '</option>';
				foreach ( self::options( $field ) as $opt => $label ) {
					$html .= '<option value="' . esc_attr( $opt ) . '"' . selected( $value, $opt, false ) . '>' . esc_html( $label ) . '</option>';
				}
				return $html . '</select>';

			case 'radio':
				$html = '<div class="feur-radio-group">';
				foreach ( self::options( $field ) as $opt => $label ) {
					$html .= '<label class="feur-radio"><input type="radio" name="' . esc_attr( $attrs['name'] ) . '" value="' . esc_attr( $opt ) . '"' . checked( $value, $opt, false ) . $required . '> ' . esc_html( $label ) . '</label>';
				}
				return $html . '</div>';

			case 'checkbox':
				$options = self::options( $field );
				if ( empty( $options ) ) {
					return '<input type="checkbox" name="' . esc_attr( $attrs['name'] ) . '" id="' . esc_attr( $id_attr ) . '" value="1"' . checked( $value, '1', false ) . $required . '>';
				}
				$values = is_array( $value ) ? $value : ( '' !== $value ? array( $value ) : array() );
				$html = '<div class="feur-checkbox-group">';
				foreach ( $options as $opt => $label ) {
					$html .= '<label class="feur-checkbox"><input type="checkbox" name="' . esc_attr( $attrs['name'] ) . '[]" value="' . esc_attr( $opt ) . '"' . checked( in_array( $opt, $values, true ), true, false ) . '> ' . esc_html( $label ) . '</label>';
				}
				return $html . '</div>';
		}

		$input_type = 'email' === $type ? 'email' : 'text';
		return '<input type="' . esc_attr( $input_type ) . '" name="' . esc_attr( $attrs['name'] ) . '" id="' . esc_attr( $id_attr ) . '" class="' . esc_attr( $attrs['class'] ) . '" value="' . esc_attr( is_array( $value ) ? '' : (string) $value ) . '"' . $required . $placeholder . '>';
	}

	public static function sanitize( $field, $value ) {
		$type = $field['type'];
		if ( self::is_choice( $type ) ) {
			$options = self::options( $field );
			if ( is_array( $value ) ) {
				$out = array();
				foreach ( $value as $v ) {
					$v = (string) $v;
					if ( isset( $options[ $v ] ) ) {
						$out[] = $v;
					}
				}
				return $out;
			}
			$value = (string) $value;
			if ( 'checkbox' === $type && empty( $options ) ) {
				return $value ? '1' : '';
			}
			return isset( $options[ $value ] ) ? $value : '';
		}
		switch ( $type ) {
			case 'email':
				return sanitize_email( $value );
			case 'textarea':
				return sanitize_textarea_field( $value );
			default:
				return sanitize_text_field( $value );
		}
	}

	public static function validate( $field, $value ) {
		$type = $field['type'];
		$required = ! empty( $field['required'] );
		$errors = array();
		$label = isset( $field['label'] ) ? $field['label'] : '';

		$has_value = $value;
		if ( is_array( $has_value ) ) {
			$has_value = array_values(
				array_filter( $has_value, function ( $v ) {
					return '' !== $v && null !== $v;
				} )
			);
		}

		if ( $required && empty( $has_value ) ) {
			$errors[] = sprintf( __( '%s is required.', 'feur' ), $label );
		}

		if ( ! is_array( $value ) && '' !== $value && null !== $value ) {
			if ( 'email' === $type && ! is_email( $value ) ) {
				$errors[] = sprintf( __( '%s must be a valid email address.', 'feur' ), $label );
			}
			if ( isset( $field['validation'] ) && 'email' === $field['validation'] && 'email' !== $type && ! is_email( $value ) ) {
				$errors[] = sprintf( __( '%s must be a valid email address.', 'feur' ), $label );
			}
			if ( isset( $field['validation'] ) && 'custom' === $field['validation'] && ! empty( $field['validation_regex'] ) ) {
				if ( @preg_match( $field['validation_regex'], (string) $value ) !== 1 ) {
					$errors[] = sprintf( __( '%s is not valid.', 'feur' ), $label );
				}
			}
			if ( 'text' === $type && mb_strlen( (string) $value ) > 255 ) {
				$errors[] = sprintf( __( '%s is too long.', 'feur' ), $label );
			}
			if ( 'textarea' === $type && mb_strlen( (string) $value ) > 5000 ) {
				$errors[] = sprintf( __( '%s is too long.', 'feur' ), $label );
			}
		}

		return $errors;
	}
}
