<?php

/**
 * Create or edit one quote form.
 *
 * The settings screen is already inside WooCommerce's own <form>, so there is
 * no second form here: the Save button posts everything to admin-post.php with
 * formaction. The nonce field has its own name because WooCommerce prints a
 * field called _wpnonce in the same form.
 *
 * @var array $data Provided by DOIT_Get_Quote\Backend\Form_Editor::render().
 *
 * @package DOIT_Get_Quote
 */

if ( !\defined( 'ABSPATH' ) ) {
	exit;
}

$doitgq_forms_class = '\DOIT_Get_Quote\Backend\Form_Editor';
?>
<div id="doitgq-form-editor">
	<h3><?php echo $data['is_new'] ? \esc_html__( 'Add New Form', 'doit_get_quote' ) : \esc_html__( 'Edit Form', 'doit_get_quote' ); ?></h3>

	<input type="hidden" name="action" value="<?php echo \esc_attr( $doitgq_forms_class::SAVE_ACTION ); ?>">
	<input type="hidden" name="is_new" value="<?php echo $data['is_new'] ? '1' : '0'; ?>">
	<input type="hidden" name="form_id" value="<?php echo \esc_attr( $data['form_id'] ); ?>">
	<?php \wp_nonce_field( $doitgq_forms_class::SAVE_ACTION, $doitgq_forms_class::NONCE_FIELD, false ); ?>

	<table class="form-table">
		<tr>
			<th><label for="doitgq-form-name"><?php \esc_html_e( 'Form name', 'doit_get_quote' ); ?></label></th>
			<td><input type="text" name="form_name" id="doitgq-form-name" value="<?php echo \esc_attr( $data['name'] ); ?>" class="regular-text" required></td>
		</tr>
		<?php if ( $data['is_new'] ) : ?>
			<tr>
				<th><label for="doitgq-form-slug"><?php \esc_html_e( 'Form ID', 'doit_get_quote' ); ?></label></th>
				<td>
					<input type="text" name="form_slug" id="doitgq-form-slug" class="regular-text" pattern="[a-z0-9_]+" placeholder="form_c" required>
					<p class="description"><?php \esc_html_e( 'Lowercase letters, numbers and underscores. It cannot be changed later.', 'doit_get_quote' ); ?></p>
				</td>
			</tr>
		<?php endif; ?>
	</table>

	<h3><?php \esc_html_e( 'Fields', 'doit_get_quote' ); ?></h3>

	<p class="description">
		<?php \esc_html_e( 'The form needs an Email field (the customer\'s email) and a Text field whose ID contains "name" (the customer\'s name). A Text field with "phone" or "company" in its ID fills those values, and a Textarea with "message" in its ID is the message. Rows without an ID or a label are ignored.', 'doit_get_quote' ); ?>
	</p>

	<table class="widefat doitgq-fields-table" id="doitgq-fields-table"
		data-option-types="<?php echo \esc_attr( \implode( ',', $data['option_types'] ) ); ?>"
		data-placeholder-types="<?php echo \esc_attr( \implode( ',', $data['placeholder_types'] ) ); ?>"
		data-reserved-ids="<?php echo \esc_attr( \implode( ',', $data['reserved_ids'] ) ); ?>">
		<thead>
			<tr>
				<th><?php \esc_html_e( 'Field ID', 'doit_get_quote' ); ?></th>
				<th><?php \esc_html_e( 'Label', 'doit_get_quote' ); ?></th>
				<th><?php \esc_html_e( 'Type', 'doit_get_quote' ); ?></th>
				<th><?php \esc_html_e( 'Required', 'doit_get_quote' ); ?></th>
				<th><?php \esc_html_e( 'Placeholder', 'doit_get_quote' ); ?></th>
				<th><?php \esc_html_e( 'Options', 'doit_get_quote' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody id="doitgq-fields-body">
			<?php
			foreach ( $data['fields'] as $index => $field ) {
				include __DIR__ . '/form-field-row.php';
			}
			?>
		</tbody>
	</table>

	<template id="doitgq-field-row-template">
		<?php
		$index = '__INDEX__';
		$field = array();
		include __DIR__ . '/form-field-row.php';
		?>
	</template>

	<p><button type="button" class="button" id="doitgq-add-field"><?php \esc_html_e( '+ Add field', 'doit_get_quote' ); ?></button></p>

	<p id="doitgq-editor-error" class="doitgq-editor-error" role="alert" hidden></p>

	<p>
		<button type="submit" class="button button-primary" id="doitgq-save-form"
			formaction="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" formmethod="post"><?php \esc_html_e( 'Save form', 'doit_get_quote' ); ?></button>
		<a href="<?php echo \esc_url( $doitgq_forms_class::url() ); ?>" class="button"><?php \esc_html_e( 'Back to the list', 'doit_get_quote' ); ?></a>
	</p>
</div>