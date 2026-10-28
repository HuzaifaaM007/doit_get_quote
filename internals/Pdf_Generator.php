<?php

/**
 * DOIT_Get_Quote
 *
 * @package   DOIT_Get_Quote
 * @license   GPL 2.0+
 */

namespace DOIT_Get_Quote\Internals;

use DOIT_Get_Quote\Engine\Base;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * Builds the quotation / invoice document and turns it into a PDF.
 *
 * This class knows nothing about $_POST, redirects or emails. The request
 * handling lives in Backend\Pdf_Handler.
 */
class Pdf_Generator extends Base {

	/**
	 * Folder (inside wp-content) where the PDFs are stored.
	 */
	const DIR_NAME = 'doitgq-quote-files';

	/**
	 * Default document data for a quote request.
	 *
	 * The caller fills in 'items', 'tax_rate', 'discount' and any client
	 * overrides from the form.
	 *
	 * @param int    $post_id quote_request post ID.
	 * @param string $type    'invoice' or 'quotation'.
	 * @return array
	 */
	public function default_doc( int $post_id, string $type ): array {
		$is_invoice = ( 'invoice' === $type );

		$email = (string) \get_post_meta( $post_id, '_customer_email', true );
		$name  = (string) \get_post_meta( $post_id, '_customer_name', true );
		$phone = (string) \get_post_meta( $post_id, '_customer_phone', true );

		// Older requests may not have the name saved yet.
		if ( '' === $name && '' !== $email ) {
			$user = \get_user_by( 'email', $email );
			$name = $user ? (string) $user->first_name : '';
		}

		$sender = (string) \get_option( 'doitgq_sender_mail', '' );

		return array(
			'type'        => $type,
			'number'      => ( $is_invoice ? 'INV-' : 'QT-' ) . $post_id,
			'post_id'     => $post_id,
			'reference'   => 'REQ-' . $post_id,
			'date'        => \wp_date( 'd M Y' ),
			'due_date'    => \wp_date( 'd M Y', \strtotime( '+14 days' ) ),
			'valid_until' => \wp_date( 'd M Y', \strtotime( '+30 days' ) ),
			'currency'    => \function_exists( 'get_woocommerce_currency' ) ? \get_woocommerce_currency() : 'USD',
			'tax_rate'    => 0,
			'discount'    => 0,
			'company'     => array(
				'name'    => \wp_specialchars_decode( (string) \get_bloginfo( 'name' ), ENT_QUOTES ),
				'address' => \trim(
					\get_option( 'woocommerce_store_address', '' ) . "\n" .
					\get_option( 'woocommerce_store_city', '' ) . ' ' .
					\get_option( 'woocommerce_store_postcode', '' )
				),
				'email'   => \is_email( $sender ) ? $sender : (string) \get_option( 'admin_email' ),
				'phone'   => '',
			),
			'client'      => array(
				'name'    => $name,
				'address' => '',
				'email'   => $email,
				'phone'   => $phone,
			),
			'items'       => array(),
			'notes'       => $is_invoice
				? \__( 'Payment due within 14 days.', 'doit_get_quote' )
				: \__( 'This quotation is valid for 30 days.', 'doit_get_quote' ),
		);
	}

	/**
	 * Render the document template to an HTML string.
	 *
	 * The template (templates/pdf-document.php) receives $doc and escapes
	 * everything it prints.
	 *
	 * @param array $doc Document data.
	 * @return string
	 */
	public function render_html( array $doc ): string {
		$template = \dirname( __DIR__ ) . '/templates/pdf-document.php';

		if ( !\is_readable( $template ) ) {
			return '';
		}

		\ob_start();
		include $template;

		return (string) \ob_get_clean();
	}

	/**
	 * Create the PDF with mPDF and save it in the protected folder.
	 *
	 * Also stores the file name and the generation time in the quote's post
	 * meta (_quote_pdf_file, _quote_pdf_generated_at).
	 *
	 * @param array  $doc  Document data.
	 * @param string $html HTML from render_html().
	 * @return array{file_name: string, file_path: string}
	 * @throws \Exception When mPDF fails.
	 */
	public function generate( array $doc, string $html ): array {
		$dir  = $this->files_dir();
		$temp = $dir . '/tmp';
		\wp_mkdir_p( $temp );

		$mpdf = new Mpdf(
			array(
				'mode'             => 'utf-8',
				'format'           => 'A4',
				'margin_top'       => 15,
				'margin_bottom'    => 20,
				'margin_left'      => 14,
				'margin_right'     => 14,
				'tempDir'          => $temp,
				'default_font'     => 'dejavusans',
				'autoScriptToLang' => true, // Urdu / Arabic fallback.
				'autoLangToFont'   => true,
			)
		);

		$number = (string) ( $doc['number'] ?? 'document' );
		$name   = (string) ( $doc['company']['name'] ?? '' );

		$mpdf->SetTitle( $number );
		$mpdf->SetAuthor( $name );
		$mpdf->SetHTMLFooter(
			'<div style="text-align:center;font-size:8px;color:#777;">'
			. \esc_html( $name ) . ' &middot; Page {PAGENO} of {nb}</div>'
		);

		$mpdf->WriteHTML( $html );

		$file_name = \sanitize_file_name( $number . '-' . \wp_date( 'Ymd-His' ) . '.pdf' );
		$file_path = $dir . '/' . $file_name;

		$mpdf->Output( $file_path, Destination::FILE );

		if ( !empty( $doc['post_id'] ) ) {
			\update_post_meta( $doc['post_id'], '_quote_pdf_file', $file_name );
			\update_post_meta( $doc['post_id'], '_quote_pdf_generated_at', \time() );
		}

		return array(
			'file_name' => $file_name,
			'file_path' => $file_path,
		);
	}

	/**
	 * Absolute path of the PDF folder. Created and protected on first use.
	 *
	 * @return string
	 */
	public function files_dir(): string {
		$dir = \trailingslashit( \WP_CONTENT_DIR ) . self::DIR_NAME;

		if ( !\file_exists( $dir ) ) {
			\wp_mkdir_p( $dir );
		}

		$this->protect_dir( $dir );

		return $dir;
	}

	/**
	 * Block direct web access to the PDFs.
	 *
	 * The .htaccess works on Apache (XAMPP). On Nginx the folder has to be
	 * blocked in the server config instead.
	 *
	 * @param string $dir Folder path.
	 * @return void
	 */
	private function protect_dir( string $dir ): void {
		$htaccess = $dir . '/.htaccess';
		$index    = $dir . '/index.php';

		if ( !\file_exists( $htaccess ) ) {
			\file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions
				$htaccess,
				"<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n"
			);
		}

		if ( !\file_exists( $index ) ) {
			\file_put_contents( $index, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}
}