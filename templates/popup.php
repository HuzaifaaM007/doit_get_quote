<?php

/**
 * The quote popup.
 *
 * @var string $form_id Id of the form selected in the settings.
 *
 * The script enables is_cart, or is_product and product_id, depending on which
 * button was clicked. Disabled inputs are not sent.
 *
 * @package DOIT_Get_Quote
 */

if ( !\defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="doitgq-popup-overlay" class="doitgq-popup-overlay" role="dialog" aria-modal="true" aria-labelledby="doitgq-popup-title" hidden>
	<div id="doitgq-popup-box" class="doitgq-popup-box">
		<button type="button" id="doitgq-close-popup" class="doitgq-close-popup" aria-label="<?php \esc_attr_e( 'Close', 'doit_get_quote' ); ?>">&times;</button>
		<h2 id="doitgq-popup-title"><?php \esc_html_e( 'Request a Quote', 'doit_get_quote' ); ?></h2>

		<form id="doitgq-form">
			<input type="hidden" name="is_cart" value="" disabled>
			<input type="hidden" name="is_product" value="" disabled>
			<input type="hidden" name="product_id" value="" disabled>

			<?php // Honeypot: hidden from people, filled in by simple bots. ?>
			<div class="doitgq-hp" aria-hidden="true">
				<label><?php \esc_html_e( 'Leave this field empty', 'doit_get_quote' ); ?>
					<input type="text" name="doitgq_website" value="" tabindex="-1" autocomplete="off">
				</label>
			</div>

			<?php echo \DOIT_Get_Quote\Internals\Forms::render_fields( $form_id ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in Forms. ?>

			<p>
				<button type="submit" class="button" id="doitgq-submit-btn"><?php \esc_html_e( 'Request Quote', 'doit_get_quote' ); ?></button>
			</p>
		</form>

		<div id="doitgq-form-response" class="doitgq-form-response" role="status" aria-live="polite"></div>
	</div>
</div>