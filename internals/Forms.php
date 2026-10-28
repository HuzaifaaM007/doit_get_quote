<?php

/**
 * DOIT_Get_Quote
 *
 * @package   DOIT_Get_Quote
 * @license   GPL 2.0+
 */

namespace DOIT_Get_Quote\Internals;

/**
 * The quote forms: where they are stored, the defaults, and how a form is
 * turned into HTML (the real popup form and the preview in the settings).
 *
 * Everything here is static, so any class can call it, for example
 * Forms::render_fields( Forms::selected_id() ).
 *
 * A form is stored like this in the option doitgq_forms:
 *
 *   'form_a' => array(
 *       'name'   => 'Basic Contact Form',
 *       'fields' => array(
 *           array( 'id' => 'user_name', 'label' => 'Your Name', 'type' => 'text',
 *                  'required' => true, 'placeholder' => 'name', 'options' => array() ),
 *       ),
 *   )
 *
 * The 'id' of a field is the name of its input, so the submit handler reads
 * $_POST[ $field['id'] ]. A checkbox with several options is submitted as an
 * array (name="id[]").
 */
class Forms {

	const OPTION_FORMS    = 'doitgq_forms';
	const OPTION_SELECTED = 'doitgq_form_type';

	/**
	 * Field types the form builder offers and the renderer understands.
	 */
	const TYPES = array(
		'text',
		'email',
		'textarea',
		'number',
		'checkbox',
		'radio',
		'select',
		'file',
		'range',
		'tel',
		'url',
		'password',
		'date',
		'time',
		'color',
		'hidden',
	);

	/**
	 * Types that take a list of options (comma separated in the editor).
	 */
	const TYPES_WITH_OPTIONS = array( 'checkbox', 'radio', 'select' );

	/**
	 * Types that show a placeholder. For a select it is the first, empty option.
	 */
	const TYPES_WITH_PLACEHOLDER = array( 'text', 'email', 'textarea', 'number', 'tel', 'url', 'password', 'select' );

	/**
	 * The two forms that ship with the plugin.
	 *
	 * @return array
	 */
	public static function defaults(): array {
		return array(
			'form_a' => array(
				'name'   => 'Basic Contact Form',
				'fields' => array(
					self::field( 'user_name', 'Your Name', 'text', true, 'name' ),
					self::field( 'form_a_email', 'Email Address', 'email', true, 'email' ),
					self::field( 'form_a_phone_number', 'Phone No', 'text', true, 'phone number' ),
					self::field( 'form_a_message', 'Message', 'textarea', true, 'message' ),
				),
			),
			'form_b' => array(
				'name'   => 'Detailed Quote Form',
				'fields' => array(
					self::field( 'fullname', 'Full Name', 'text', true, 'fullname' ),
					self::field( 'company', 'Company Name', 'text', false, 'company' ),
					self::field( 'email', 'Business Email', 'email', true, 'business email' ),
					self::field( 'quantity', 'Quantity Needed', 'number', true, '500' ),
					self::field( 'form_b_message', 'Message', 'textarea', true, 'message' ),
				),
			),
		);
	}

	/**
	 * All forms: the saved ones, or the defaults when nothing is saved yet.
	 *
	 * @return array
	 */
	public static function all(): array {
		$forms = \get_option( self::OPTION_FORMS, array() );

		return ( \is_array( $forms ) && !empty( $forms ) ) ? $forms : self::defaults();
	}

	/**
	 * One form, or null.
	 *
	 * @param string $form_id Form id.
	 * @return array|null
	 */
	public static function get( string $form_id ): ?array {
		$forms = self::all();

		return isset( $forms[ $form_id ] ) && \is_array( $forms[ $form_id ] ) ? $forms[ $form_id ] : null;
	}

	/**
	 * Form id => form name, for the select in the settings.
	 *
	 * @return array
	 */
	public static function choices(): array {
		$choices = array();

		foreach ( self::all() as $id => $form ) {
			$choices[ (string) $id ] = (string) ( $form['name'] ?? $id );
		}

		return $choices;
	}

	/**
	 * The form chosen in the settings. Falls back to the first form when the
	 * saved choice no longer exists (for example after deleting it).
	 *
	 * @return string
	 */
	public static function selected_id(): string {
		$selected = (string) \get_option( self::OPTION_SELECTED, 'form_a' );
		$forms    = self::all();

		if ( isset( $forms[ $selected ] ) ) {
			return $selected;
		}

		$keys = \array_keys( $forms );

		return (string) ( $keys[0] ?? 'form_a' );
	}

	/**
	 * The input fields of a form (without the <form> tag and the button).
	 *
	 * @param string $form_id Form id.
	 * @return string HTML, already escaped.
	 */
	public static function render_fields( string $form_id ): string {
		$form = self::get( $form_id );

		if ( null === $form ) {
			return '<p>' . \esc_html__( 'Form not found.', 'doit_get_quote' ) . '</p>';
		}

		$html = '';

		foreach ( (array) ( $form['fields'] ?? array() ) as $field ) {
			if ( \is_array( $field ) ) {
				$html .= self::render_field( $field );
			}
		}

		return $html;
	}

	/**
	 * One field.
	 *
	 * @param array $field Field definition.
	 * @return string HTML, already escaped.
	 */
	public static function render_field( array $field ): string {
		$raw_id = \sanitize_key( (string) ( $field['id'] ?? '' ) );

		if ( '' === $raw_id ) {
			return '';
		}

		$id          = \esc_attr( $raw_id );
		$label       = \esc_html( (string) ( $field['label'] ?? '' ) );
		$type        = self::clean_type( $field['type'] ?? 'text' );
		$required    = !empty( $field['required'] );
		$required_at = $required ? ' required' : '';
		$mark        = $required ? ' <span class="required">*</span>' : '';
		$placeholder = (string) ( $field['placeholder'] ?? '' );
		$options     = self::options( $field );

		if ( 'hidden' === $type ) {
			return '<input type="hidden" id="' . $id . '" name="' . $id . '" value="" />';
		}

		$html = '<p class="doitgq-field doitgq-field-' . \esc_attr( $type ) . '">';

		switch ( $type ) {
			case 'textarea':
				$html .= '<label for="' . $id . '">' . $label . $mark . '</label><br>';
				$html .= \sprintf(
					'<textarea id="%1$s" name="%1$s" class="regular-text" rows="%2$d" placeholder="%3$s"%4$s></textarea>',
					$id,
					\max( 1, (int) ( $field['rows'] ?? 5 ) ),
					\esc_attr( $placeholder ),
					$required_at
				);
				break;

			case 'select':
				$html .= '<label for="' . $id . '">' . $label . $mark . '</label><br>';
				$html .= '<select id="' . $id . '" name="' . $id . '" class="regular-text"' . $required_at . '>';
				$html .= '<option value="">' . \esc_html( $placeholder ) . '</option>';

				foreach ( $options as $option ) {
					$html .= '<option value="' . \esc_attr( $option ) . '">' . \esc_html( $option ) . '</option>';
				}

				$html .= '</select>';
				break;

			case 'checkbox':
				if ( \count( $options ) > 1 ) {
					$html .= '<span class="doitgq-group-label">' . $label . $mark . '</span><br>';

					foreach ( $options as $option ) {
						$html .= \sprintf(
							'<label class="doitgq-choice"><input type="checkbox" id="%1$s" name="%2$s[]" value="%3$s" /> %4$s</label> ',
							\esc_attr( $raw_id . '_' . \sanitize_key( $option ) ),
							$id,
							\esc_attr( $option ),
							\esc_html( $option )
						);
					}
				} else {
					$html .= \sprintf(
						'<label class="doitgq-choice"><input type="checkbox" id="%1$s" name="%1$s" value="1"%2$s /> %3$s%4$s</label>',
						$id,
						$required_at,
						$label,
						$mark
					);
				}
				break;

			case 'radio':
				$html .= '<span class="doitgq-group-label">' . $label . $mark . '</span><br>';

				foreach ( $options as $option ) {
					$html .= \sprintf(
						'<label class="doitgq-choice"><input type="radio" id="%1$s" name="%2$s" value="%3$s"%4$s /> %5$s</label> ',
						\esc_attr( $raw_id . '_' . \sanitize_key( $option ) ),
						$id,
						\esc_attr( $option ),
						$required_at,
						\esc_html( $option )
					);
				}
				break;

			case 'file':
				$html  .= '<label for="' . $id . '">' . $label . $mark . '</label><br>';
				$accept = !empty( $field['accept'] ) ? ' accept="' . \esc_attr( (string) $field['accept'] ) . '"' : '';
				$html  .= '<input type="file" id="' . $id . '" name="' . $id . '"' . $accept . $required_at . ' />';
				break;

			case 'number':
			case 'range':
				$html .= '<label for="' . $id . '">' . $label . $mark . '</label><br>';
				$attrs = '';

				foreach ( array( 'min', 'max', 'step' ) as $attr ) {
					if ( isset( $field[ $attr ] ) && \is_numeric( $field[ $attr ] ) ) {
						$attrs .= ' ' . $attr . '="' . \esc_attr( (string) $field[ $attr ] ) . '"';
					}
				}

				$html .= \sprintf(
					'<input type="%1$s" id="%2$s" name="%2$s"%3$s%4$s%5$s%6$s />',
					\esc_attr( $type ),
					$id,
					'number' === $type ? ' class="regular-text"' : '',
					'' !== $placeholder && 'number' === $type ? ' placeholder="' . \esc_attr( $placeholder ) . '"' : '',
					$attrs,
					$required_at
				);
				break;

			default:
				$html .= '<label for="' . $id . '">' . $label . $mark . '</label><br>';
				$html .= \sprintf(
					'<input type="%1$s" id="%2$s" name="%2$s" class="regular-text" placeholder="%3$s"%4$s />',
					\esc_attr( $type ),
					$id,
					\esc_attr( $placeholder ),
					$required_at
				);
				break;
		}

		return $html . '</p>';
	}

	/**
	 * A disabled copy of a form for the settings page.
	 *
	 * @param string $form_id Form id.
	 * @return string HTML, already escaped.
	 */
	public static function render_preview( string $form_id ): string {
		$form = self::get( $form_id );

		if ( null === $form ) {
			return '<p>' . \esc_html__( 'Form not found.', 'doit_get_quote' ) . '</p>';
		}

		$html  = '<div class="doitgq-form-preview doitgq-preview-' . \esc_attr( $form_id ) . '">';
		$html .= '<h3>' . \esc_html( (string) ( $form['name'] ?? '' ) ) . '</h3>';

		foreach ( (array) ( $form['fields'] ?? array() ) as $field ) {
			if ( !\is_array( $field ) ) {
				continue;
			}

			$label       = (string) ( $field['label'] ?? '' );
			$type        = self::clean_type( $field['type'] ?? 'text' );
			$placeholder = !empty( $field['placeholder'] ) ? (string) $field['placeholder'] : $label;
			$options     = self::options( $field );

			if ( 'hidden' === $type ) {
				continue;
			}

			$html .= '<p><label>' . \esc_html( $label ) . '</label><br>';

			switch ( $type ) {
				case 'textarea':
					$html .= '<textarea placeholder="' . \esc_attr( $placeholder ) . '" disabled class="regular-text" rows="4"></textarea>';
					break;

				case 'select':
					$html .= '<select disabled class="regular-text"><option>' . \esc_html( $placeholder ) . '</option></select>';
					break;

				case 'checkbox':
				case 'radio':
					if ( empty( $options ) ) {
						$options = array( 'checkbox' === $type ? $placeholder : \__( 'Option', 'doit_get_quote' ) );
					}

					foreach ( $options as $option ) {
						$html .= '<label><input type="' . \esc_attr( $type ) . '" disabled /> ' . \esc_html( $option ) . '</label> ';
					}
					break;

				case 'file':
				case 'range':
					$html .= '<input type="' . \esc_attr( $type ) . '" disabled />';
					break;

				case 'color':
					$html .= '<input type="color" value="#2271b1" disabled />';
					break;

				default:
					$html .= '<input type="' . \esc_attr( $type ) . '" placeholder="' . \esc_attr( $placeholder ) . '" disabled class="regular-text" />';
					break;
			}

			$html .= '</p>';
		}

		$html .= '<button type="button" class="button" disabled>' . \esc_html__( 'Request Quote', 'doit_get_quote' ) . '</button>';

		return $html . '</div>';
	}

	/**
	 * A field type the plugin knows, or 'text'.
	 *
	 * @param mixed $type Type from a stored field.
	 * @return string
	 */
	public static function clean_type( $type ): string {
		$type = \is_string( $type ) ? $type : 'text';

		return \in_array( $type, self::TYPES, true ) ? $type : 'text';
	}

	/**
	 * The options of a field as a clean list of strings.
	 *
	 * Accepts the stored array or a comma separated string.
	 *
	 * @param array $field Field definition.
	 * @return array
	 */
	private static function options( array $field ): array {
		$options = $field['options'] ?? array();

		if ( \is_string( $options ) ) {
			$options = \explode( ',', $options );
		}

		$clean = array();

		foreach ( (array) $options as $option ) {
			$option = \trim( (string) $option );

			if ( '' !== $option ) {
				$clean[] = $option;
			}
		}

		return $clean;
	}

	/**
	 * Build a field definition for the defaults.
	 *
	 * @param string $id          Field id.
	 * @param string $label       Label.
	 * @param string $type        Type.
	 * @param bool   $required    Required.
	 * @param string $placeholder Placeholder.
	 * @return array
	 */
	private static function field( string $id, string $label, string $type, bool $required, string $placeholder ): array {
		return array(
			'id'          => $id,
			'label'       => $label,
			'type'        => $type,
			'required'    => $required,
			'placeholder' => $placeholder,
			'options'     => array(),
			'always_on'   => true,
		);
	}
}