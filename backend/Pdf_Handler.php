<?php

/**
 * DOIT_Get_Quote
 *
 * @package   DOIT_Get_Quote
 * @license   GPL 2.0+
 */

namespace DOIT_Get_Quote\Backend;

use DOIT_Get_Quote\Engine\Base;
use DOIT_Get_Quote\Internals\Mailer;
use DOIT_Get_Quote\Internals\Pdf_Generator;

/**
 * Handles the Reply page form (admin-post.php?action=doitgq_quote_pdf).
 *
 * The form's "output" button decides what happens:
 * html (preview), save_pdf, download_pdf or save_reply (save and email).
 */
class Pdf_Handler extends Base
{

	const ACTION       = 'doitgq_quote_pdf';
	const NONCE_ACTION = 'doitgq_quote_reply_pdf';

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize()
	{ // phpcs:ignore
		parent::initialize();

		\add_action('admin_post_' . self::ACTION, array($this, 'handle'));
	}

	/**
	 * Handle the form submission.
	 *
	 * @return void
	 */
	public function handle()
	{
		if (!\current_user_can('manage_options')) {
			\wp_die(\esc_html__('Not allowed.', 'doit_get_quote'), 403);
		}

		\check_admin_referer(self::NONCE_ACTION);

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified above.
		$post_id = isset($_POST['quote-post-id']) ? \absint($_POST['quote-post-id']) : 0;
		$output  = isset($_POST['output']) ? \sanitize_key(\wp_unslash($_POST['output'])) : 'save_pdf';
		// phpcs:enable

		$post = $post_id ? \get_post($post_id) : null;

		if (!$post || 'quote_request' !== $post->post_type) {
			\wp_die(\esc_html__('Invalid request.', 'doit_get_quote'), 403);
		}

		if (!\in_array($output, array('html', 'save_pdf', 'download_pdf', 'save_reply'), true)) {
			$output = 'save_pdf';
		}

		$generator = new Pdf_Generator();
		$doc       = $this->build_doc($post_id, $generator);
		error_log('printing the doc array:' . print_r($doc, true));
		$html      = $generator->render_html($doc);
		error_log($html);

		while (\ob_get_level()) {
			\ob_end_clean();
		}

		if ('html' === $output) {
			\header('Content-Type: text/html; charset=utf-8');
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside the template.
			exit;
		}

		$to = (string) \get_post_meta($post_id, '_customer_email', true);

		if ('save_reply' === $output && !\is_email($to)) {
			$this->finish('error', \__('This request has no valid customer email.', 'doit_get_quote'));
		}

		try {
			$file = $generator->generate($doc, $html);
		} catch (\Throwable $e) {
			\error_log('DOITGQ: PDF generation failed: ' . $e->getMessage()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			$this->finish('error', \__('Could not generate the PDF. Check the debug log.', 'doit_get_quote'));
		}

		if ('download_pdf' === $output) {
			$this->download($file);
		}

		if ('save_reply' === $output) {
			$this->reply($doc, $to, $file, $post_id);
		}

		$this->finish(
			'success',
			\sprintf(
				/* translators: %s: PDF file name */
				\__('PDF saved as %s', 'doit_get_quote'),
				$file['file_name']
			)
		);
	}

	/**
	 * Build the document from the default data plus what was typed in the form.
	 *
	 * @param int           $post_id   quote_request ID.
	 * @param Pdf_Generator $generator Generator, for the default data.
	 * @return array
	 */
	private function build_doc(int $post_id, Pdf_Generator $generator): array
	{
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified in handle().
		$data = \wp_unslash($_POST);
		// phpcs:enable

		$type = (isset($data['type']) && 'quotation' === $data['type']) ? 'quotation' : 'invoice';
		$doc  = $generator->default_doc($post_id, $type);

		// The form's "number" field is the client's phone number.
		if (!empty($data['number'])) {
			$doc['client']['phone'] = \sanitize_text_field($data['number']);
		}

		if (!empty($data['client_name'])) {
			$doc['client']['name'] = \sanitize_text_field($data['client_name']);
		}

		$doc['tax_rate'] = \max(0, (float) ($data['tax_rate'] ?? 0));
		$doc['discount'] = \min(100, \max(0, (float) ($data['discount'] ?? 0)));

		$items = array();

		foreach ((array) ($data['items'] ?? array()) as $row) {
			if (!\is_array($row)) {
				continue;
			}

			$name = \sanitize_text_field($row['name'] ?? '');

			if ('' === $name) {
				continue;
			}

			$items[] = array(
				'name'     => $name,
				'desc'     => \sanitize_text_field($row['desc'] ?? ''),
				'qty'      => \max(0, (float) ($row['qty'] ?? 0)),
				'price'    => \max(0, (float) ($row['price'] ?? 0)),
				'discount' => \min(100, \max(0, (float) ($row['discount'] ?? 0))),
			);
		}

		$doc['items'] = $items;

		return $doc;
	}

	/**
	 * Email the PDF to the customer and record the reply time.
	 *
	 * @param array  $doc     Document data.
	 * @param string $to      Customer email.
	 * @param array  $file    Result of Pdf_Generator::generate().
	 * @param int    $post_id quote_request ID.
	 * @return void
	 */
	private function reply(array $doc, string $to, array $file, int $post_id)
	{
		$sent = (new Mailer())->send_quote_reply($doc, $to, $file['file_path']);

		if (!$sent) {
			$this->finish(
				'error',
				\sprintf(
					/* translators: %s: PDF file name */
					\__('PDF saved as %s, but the email could not be sent.', 'doit_get_quote'),
					$file['file_name']
				)
			);
		}

		\update_post_meta($post_id, '_quote_replied_at', \time());

		$this->finish(
			'success',
			\sprintf(
				/* translators: 1: PDF file name, 2: customer email */
				\__('Sent %1$s to %2$s', 'doit_get_quote'),
				$file['file_name'],
				$to
			)
		);
	}

	/**
	 * Stream the saved PDF to the browser.
	 *
	 * @param array $file Result of Pdf_Generator::generate().
	 * @return void
	 */
	private function download(array $file)
	{
		\header('Content-Type: application/pdf');
		\header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
		\header('Content-Length: ' . \filesize($file['file_path']));
		\readfile($file['file_path']); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	/**
	 * Queue a notice and go back to the page the form was sent from.
	 *
	 * @param string $type    'success' or 'error'.
	 * @param string $message Notice text.
	 * @return void
	 */
	private function finish(string $type, string $message)
	{
		Admin_Notice::add($type, $message);

		$redirect = \wp_get_referer() ? \wp_get_referer() : \admin_url('edit.php?post_type=quote_request');

		\wp_safe_redirect($redirect);
		exit;
	}
}
