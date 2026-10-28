<?php

/**
 * One row of the form editor table. Also used, with the index __INDEX__, as
 * the template for the "Add field" button.
 *
 * @var int|string $index Row index.
 * @var array      $field Field definition (empty for a new row).
 *
 * @package DOIT_Get_Quote
 */

if ( !\defined( 'ABSPATH' ) ) {
	exit;
}

$f_id          = (string) ( $field['id'] ?? '' );
$f_label       = (string) ( $field['label'] ?? '' );
$f_type        = \DOIT_Get_Quote\Internals\Forms::clean_type( $field['type'] ?? 'text' );
$f_required    = !empty( $field['required'] );
$f_placeholder = (string) ( $field['placeholder'] ?? '' );
$f_options     = $field['options'] ?? array();
$f_options     = \is_array( $f_options ) ? \implode( ', ', $f_options ) : (string) $f_options;
$f_name        = 'fields[' . $index . ']';
$f_has_ph      = \in_array( $f_type, \DOIT_Get_Quote\Internals\Forms::TYPES_WITH_PLACEHOLDER, true );
$f_has_opts    = \in_array( $f_type, \DOIT_Get_Quote\Internals\Forms::TYPES_WITH_OPTIONS, true );
?>
<tr>
	<td><input type="text" name="<?php echo \esc_attr( $f_name ); ?>[id]" value="<?php echo \esc_attr( $f_id ); ?>" pattern="[A-Za-z0-9_\-]+" required></td>
	<td><input type="text" name="<?php echo \esc_attr( $f_name ); ?>[label]" value="<?php echo \esc_attr( $f_label ); ?>" required></td>
	<td>
		<select name="<?php echo \esc_attr( $f_name ); ?>[type]">
			<?php foreach ( \DOIT_Get_Quote\Internals\Forms::TYPES as $type ) : ?>
				<option value="<?php echo \esc_attr( $type ); ?>"<?php echo $type === $f_type ? ' selected' : ''; ?>><?php echo \esc_html( \ucfirst( $type ) ); ?></option>
			<?php endforeach; ?>
		</select>
	</td>
	<td><input type="checkbox" name="<?php echo \esc_attr( $f_name ); ?>[required]" value="1"<?php echo $f_required ? ' checked' : ''; ?>></td>
	<td><input type="text" name="<?php echo \esc_attr( $f_name ); ?>[placeholder]" value="<?php echo \esc_attr( $f_placeholder ); ?>"<?php echo $f_has_ph ? '' : ' disabled'; ?>></td>
	<td><input type="text" name="<?php echo \esc_attr( $f_name ); ?>[options]" value="<?php echo \esc_attr( $f_options ); ?>" placeholder="<?php \esc_attr_e( 'Comma-separated', 'doit_get_quote' ); ?>"<?php echo $f_has_opts ? '' : ' disabled'; ?>></td>
	<td><button type="button" class="button doitgq-remove-field"><?php \esc_html_e( 'Remove', 'doit_get_quote' ); ?></button></td>
</tr>