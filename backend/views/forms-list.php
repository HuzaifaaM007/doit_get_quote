<?php

/**
 * The list of quote forms (WooCommerce > Settings > Get a Quote > Forms).
 *
 * @var array $data Provided by DOIT_Get_Quote\Backend\Form_Editor::render().
 *
 * @package DOIT_Get_Quote
 */

if ( !\defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h3><?php \esc_html_e( 'Quote Forms', 'doit_get_quote' ); ?></h3>
<p><?php \esc_html_e( 'Choose which form customers see in General Settings.', 'doit_get_quote' ); ?></p>

<table class="widefat striped doitgq-forms-list">
	<thead>
		<tr>
			<th><?php \esc_html_e( 'Form ID', 'doit_get_quote' ); ?></th>
			<th><?php \esc_html_e( 'Name', 'doit_get_quote' ); ?></th>
			<th><?php \esc_html_e( 'Fields', 'doit_get_quote' ); ?></th>
			<th><?php \esc_html_e( 'Actions', 'doit_get_quote' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $data['forms'] as $form_id => $form ) : ?>
			<tr>
				<td><?php echo \esc_html( $form_id ); ?></td>
				<td>
					<?php echo \esc_html( (string) ( $form['name'] ?? '' ) ); ?>
					<?php if ( (string) $form_id === $data['selected'] ) : ?>
						<strong>(<?php \esc_html_e( 'in use', 'doit_get_quote' ); ?>)</strong>
					<?php endif; ?>
				</td>
				<td><?php echo (int) \count( (array) ( $form['fields'] ?? array() ) ); ?></td>
				<td>
					<a href="<?php echo \esc_url( \DOIT_Get_Quote\Backend\Form_Editor::url( array( 'edit' => $form_id ) ) ); ?>"><?php \esc_html_e( 'Edit', 'doit_get_quote' ); ?></a> |
					<a href="<?php echo \esc_url( \wp_nonce_url( \admin_url( 'admin-post.php?action=' . \DOIT_Get_Quote\Backend\Form_Editor::DELETE_ACTION . '&form_id=' . \rawurlencode( (string) $form_id ) ), \DOIT_Get_Quote\Backend\Form_Editor::DELETE_ACTION ) ); ?>" class="doitgq-delete-form"><?php \esc_html_e( 'Delete', 'doit_get_quote' ); ?></a>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<p>
	<a href="<?php echo \esc_url( \DOIT_Get_Quote\Backend\Form_Editor::url( array( 'edit' => 'new' ) ) ); ?>" class="button button-primary"><?php \esc_html_e( 'Add New Form', 'doit_get_quote' ); ?></a>
</p>