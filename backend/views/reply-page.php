<?php

/**
 * Reply to a quote request: the form behind the "Reply" row action.
 *
 * @var array $data Provided by DOIT_Get_Quote\Backend\Reply_Page::render().
 *
 * @package DOIT_Get_Quote
 */

if ( !\defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap doitgq-reply">
	<h1><?php \esc_html_e( 'Reply to Quote', 'doit_get_quote' ); ?></h1>

	<?php if ( '' !== $data['pdf_file'] ) : ?>
		<p>
			<?php \esc_html_e( 'Last PDF:', 'doit_get_quote' ); ?>
			<strong><?php echo \esc_html( $data['pdf_file'] ); ?></strong>
			(<?php echo \esc_html( \wp_date( 'd M Y H:i', $data['pdf_time'] ) ); ?>)
		</p>
	<?php endif; ?>

	<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" id="doitgq-reply-form">
		<input type="hidden" name="action" value="<?php echo \esc_attr( \DOIT_Get_Quote\Backend\Pdf_Handler::ACTION ); ?>">
		<input type="hidden" name="quote-post-id" value="<?php echo \esc_attr( $data['post_id'] ); ?>">
		<?php \wp_nonce_field( \DOIT_Get_Quote\Backend\Pdf_Handler::NONCE_ACTION ); ?>

		<p>
			<label><?php \esc_html_e( 'Type', 'doit_get_quote' ); ?>
				<select name="type">
					<option value="invoice"><?php \esc_html_e( 'Invoice', 'doit_get_quote' ); ?></option>
					<option value="quotation"><?php \esc_html_e( 'Quotation', 'doit_get_quote' ); ?></option>
				</select>
			</label>
			<label><?php \esc_html_e( 'Phone', 'doit_get_quote' ); ?>
				<input type="text" name="number" value="<?php echo \esc_attr( $data['phone'] ); ?>">
			</label>
			<label><?php \esc_html_e( 'Client', 'doit_get_quote' ); ?>
				<input type="text" name="client_name" value="<?php echo \esc_attr( $data['name'] ); ?>">
			</label>
		</p>

		<table class="widefat striped doitgq-items">
			<thead>
				<tr>
					<th><?php \esc_html_e( 'Item', 'doit_get_quote' ); ?></th>
					<th><?php \esc_html_e( 'Description', 'doit_get_quote' ); ?></th>
					<th><?php \esc_html_e( 'Qty', 'doit_get_quote' ); ?></th>
					<th><?php \esc_html_e( 'Unit price', 'doit_get_quote' ); ?></th>
					<th><?php \esc_html_e( 'Disc %', 'doit_get_quote' ); ?></th>
					<th><?php \esc_html_e( 'Line total', 'doit_get_quote' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody id="doitgq-rows">
				<?php foreach ( $data['items'] as $i => $item ) : ?>
					<tr>
						<td><input type="text" name="items[<?php echo (int) $i; ?>][name]" value="<?php echo \esc_attr( $item['name'] ?? '' ); ?>"></td>
						<td><input type="text" name="items[<?php echo (int) $i; ?>][desc]" value="<?php echo \esc_attr( \wp_strip_all_tags( (string) ( $item['desc'] ?? '' ) ) ); ?>"></td>
						<td><input type="number" name="items[<?php echo (int) $i; ?>][qty]" value="<?php echo \esc_attr( $item['qty'] ?? 1 ); ?>" min="0" step="any" class="doitgq-qty"></td>
						<td><input type="number" name="items[<?php echo (int) $i; ?>][price]" value="<?php echo \esc_attr( $item['price'] ?? 0 ); ?>" min="0" step="any" class="doitgq-price"></td>
						<td><input type="number" name="items[<?php echo (int) $i; ?>][discount]" value="0" min="0" max="100" step="any" class="doitgq-disc"></td>
						<td class="doitgq-line">0.00</td>
						<td><button type="button" class="button doitgq-remove">&times;</button></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p><button type="button" class="button" id="doitgq-add"><?php \esc_html_e( '+ Add item', 'doit_get_quote' ); ?></button></p>

		<p>
			<label><?php \esc_html_e( 'Overall discount %', 'doit_get_quote' ); ?>
				<input type="number" name="discount" id="doitgq-discount" value="0" min="0" max="100" step="any" class="doitgq-small">
			</label>
			<label><?php \esc_html_e( 'Tax %', 'doit_get_quote' ); ?>
				<input type="number" name="tax_rate" id="doitgq-tax" value="<?php echo \esc_attr( $data['tax_rate'] ); ?>" min="0" step="any" class="doitgq-small">
			</label>
		</p>

		<p class="doitgq-totals">
			<?php \esc_html_e( 'Subtotal:', 'doit_get_quote' ); ?> <strong id="doitgq-subtotal">0.00</strong> &nbsp;|&nbsp;
			<?php \esc_html_e( 'Discount:', 'doit_get_quote' ); ?> <strong id="doitgq-discamt">0.00</strong> &nbsp;|&nbsp;
			<?php \esc_html_e( 'Tax:', 'doit_get_quote' ); ?> <strong id="doitgq-taxamt">0.00</strong> &nbsp;|&nbsp;
			<?php \esc_html_e( 'Total:', 'doit_get_quote' ); ?> <strong id="doitgq-total">0.00</strong>
		</p>

		<p>
			<button type="submit" name="output" value="html" formtarget="_blank" class="button"><?php \esc_html_e( 'Preview HTML', 'doit_get_quote' ); ?></button>
			<button type="submit" name="output" value="save_pdf" class="button button-primary"><?php \esc_html_e( 'Save', 'doit_get_quote' ); ?></button>
			<button type="submit" name="output" value="download_pdf" class="button button-primary"><?php \esc_html_e( 'Download', 'doit_get_quote' ); ?></button>
			<button type="submit" name="output" value="save_reply" class="button button-primary"><?php \esc_html_e( 'Save & Reply', 'doit_get_quote' ); ?></button>
		</p>
	</form>
</div>