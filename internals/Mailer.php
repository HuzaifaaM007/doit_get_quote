<?php

/**
 * DOIT_Get_Quote
 *
 * @package   DOIT_Get_Quote
 * @license   GPL 2.0+
 */

namespace DOIT_Get_Quote\Internals;

use DOIT_Get_Quote\Engine\Base;

/**
 * Sends the quote emails: the admin notification and the reply with the PDF.
 *
 * SMTP is applied only to the emails sent through this class, never to the
 * other emails of the site (orders, password resets, ...).
 */
class Mailer extends Base {

	/**
	 * Send the "new quote request" notification to the store admin.
	 *
	 * Expected keys in $quote: name, email, phone, company, message,
	 * fields_text, product_text, cart_items (WooCommerce cart items) and
	 * is_html (bool).
	 *
	 * Placeholders available in the email templates: {name} {email} {phone}
	 * {company} {message} {submitted_fields_text} {store_name} and, depending
	 * on the template, {cart_items} {total_quantity} {product_details}.
	 *
	 * @param array $quote Quote data.
	 * @return bool True when wp_mail accepted the message.
	 */
	public function send_admin_notification( array $quote ): bool {
		$quote = \wp_parse_args(
			$quote,
			array(
				'name'         => '',
				'email'        => '',
				'phone'        => '',
				'company'      => '',
				'message'      => '',
				'fields_text'  => '',
				'product_text' => '',
				'cart_items'   => array(),
				'is_html'      => false,
			)
		);

		$is_html = (bool) $quote['is_html'];
		$name    = \sanitize_text_field( (string) $quote['name'] );
		$email   = \sanitize_email( (string) $quote['email'] );
		$company = \sanitize_text_field( (string) $quote['company'] );

		$vars = array(
			'name'                  => $name,
			'email'                 => $email,
			'phone'                 => \sanitize_text_field( (string) $quote['phone'] ),
			'company'               => $company,
			'message'               => (string) $quote['message'],
			'submitted_fields_text' => (string) $quote['fields_text'],
			'store_name'            => $this->store_name(),
		);

		if ( !empty( $quote['cart_items'] ) ) {
			$cart                   = $this->format_cart_items( (array) $quote['cart_items'] );
			$vars['cart_items']     = $cart['text'];
			$vars['total_quantity'] = $cart['quantity'];
			$template               = $this->get_template(
				'doitgq_email_template_for_cart_quote',
				$this->default_cart_template( '' !== $company )
			);
		} else {
			$vars['product_details'] = (string) $quote['product_text'];
			$template                = $this->get_template(
				'doitgq_email_template_for_product_quote',
				$this->default_product_template( '' !== $company )
			);
		}

		$body    = $this->render_template( $template, $vars, $is_html );
		$subject = \sanitize_text_field( (string) \get_option( 'doitgq_email_subject', '' ) );

		if ( '' === $subject ) {
			$subject = \__( 'Product Quotation', 'doit_get_quote' );
		}

		$headers = $this->build_headers( $is_html );

		if ( \is_email( $email ) ) {
			$headers[] = 'Reply-To: ' . ( '' !== $name ? $name . ' <' . $email . '>' : $email );
		}

		return $this->send( $this->admin_recipient(), $subject, $body, $headers );
	}

	/**
	 * Email the quotation / invoice PDF to the customer.
	 *
	 * The caller is responsible for generating the PDF and for saving the
	 * "replied at" post meta once this returns true.
	 *
	 * @param array  $doc       Document data (type, number, client => name).
	 * @param string $to        Customer email address.
	 * @param string $file_path Absolute path of the PDF to attach.
	 * @return bool True when wp_mail accepted the message.
	 */
	public function send_quote_reply( array $doc, string $to, string $file_path ): bool {
		if ( !\is_email( $to ) ) {
			return false;
		}

		$label  = ( 'invoice' === ( $doc['type'] ?? '' ) ) ? 'invoice' : 'quotation';
		$store  = $this->store_name();
		$number = \sanitize_text_field( (string) ( $doc['number'] ?? '' ) );
		$name   = !empty( $doc['client']['name'] ) ? \sanitize_text_field( (string) $doc['client']['name'] ) : 'Customer';

		$subject = \sprintf(
			/* translators: 1: document type (invoice or quotation), 2: document number, 3: store name */
			\__( 'Your %1$s %2$s from %3$s', 'doit_get_quote' ),
			$label,
			$number,
			$store
		);

		$body = \sprintf(
			/* translators: 1: customer name, 2: document type, 3: document number, 4: store name */
			\__( "Hello %1\$s,\n\nThank you for your interest. Please find your %2\$s %3\$s attached.\n\nIf you have any questions, just reply to this email.\n\nRegards,\n%4\$s", 'doit_get_quote' ),
			$name,
			$label,
			$number,
			$store
		);

		$headers   = $this->build_headers( false );
		$headers[] = 'Reply-To: ' . $this->admin_recipient();

		$attachments = \file_exists( $file_path ) ? array( $file_path ) : array();

		return $this->send( $to, $subject, $body, $headers, $attachments );
	}

	/**
	 * Configure PHPMailer to use the SMTP account saved in the settings.
	 *
	 * Hooked to phpmailer_init only while one of our emails is being sent.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance.
	 * @return void
	 */
	public function configure_smtp( $phpmailer ) {
		$sender = (string) \get_option( 'doitgq_sender_mail', '' );
		$host   = \trim( (string) \get_option( 'doitgq_mailing_service', '' ) );

		$phpmailer->isSMTP();
		$phpmailer->Host       = '' !== $host ? $host : 'smtp.gmail.com';
		$phpmailer->SMTPAuth   = true;
		$phpmailer->Port       = 587;
		$phpmailer->Username   = (string) \get_option( 'doitgq_mailer_username', '' );
		$phpmailer->Password   = (string) \get_option( 'doitgq_mailer_password', '' );
		$phpmailer->SMTPSecure = 'tls';

		if ( \is_email( $sender ) ) {
			$phpmailer->From     = $sender;
			$phpmailer->FromName = $this->store_name();
		}
	}

	/**
	 * Send through wp_mail, using SMTP only for this call.
	 *
	 * @param string $to          Recipient.
	 * @param string $subject     Subject.
	 * @param string $body        Body.
	 * @param array  $headers     Headers.
	 * @param array  $attachments Absolute file paths.
	 * @return bool
	 */
	private function send( string $to, string $subject, string $body, array $headers, array $attachments = array() ): bool {
		$use_smtp = $this->smtp_is_configured();

		if ( $use_smtp ) {
			\add_action( 'phpmailer_init', array( $this, 'configure_smtp' ) );
		}

		$sent = \wp_mail( $to, $subject, $body, $headers, $attachments );

		if ( $use_smtp ) {
			\remove_action( 'phpmailer_init', array( $this, 'configure_smtp' ) );
		}

		if ( !$sent ) {
			global $phpmailer;

			$info = ( isset( $phpmailer ) && \is_object( $phpmailer ) ) ? $phpmailer->ErrorInfo : 'unknown error';
			\error_log( 'DOITGQ: email failed: ' . $info ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		}

		return (bool) $sent;
	}

	/**
	 * SMTP is used only when both the username and the password are saved.
	 *
	 * @return bool
	 */
	private function smtp_is_configured(): bool {
		return '' !== \trim( (string) \get_option( 'doitgq_mailer_username', '' ) )
			&& '' !== \trim( (string) \get_option( 'doitgq_mailer_password', '' ) );
	}

	/**
	 * Headers shared by every quote email.
	 *
	 * @param bool $is_html Send as HTML.
	 * @return array
	 */
	private function build_headers( bool $is_html ): array {
		return array(
			'Content-Type: ' . ( $is_html ? 'text/html' : 'text/plain' ) . '; charset=UTF-8',
			'From: ' . $this->store_name() . ' <' . \get_option( 'admin_email' ) . '>',
		);
	}

	/**
	 * Replace the {placeholders}. In HTML mode the values are escaped,
	 * because they come from the customer.
	 *
	 * @param string $template Template with {placeholders}.
	 * @param array  $vars     Placeholder => value.
	 * @param bool   $is_html  HTML email.
	 * @return string
	 */
	private function render_template( string $template, array $vars, bool $is_html ): string {
		$search  = array();
		$replace = array();

		foreach ( $vars as $key => $value ) {
			$value     = (string) $value;
			$search[]  = '{' . $key . '}';
			$replace[] = $is_html ? \nl2br( \esc_html( $value ) ) : $value;
		}

		return \str_replace( $search, $replace, $template );
	}

	/**
	 * Build the text list of the cart items and the total quantity.
	 *
	 * @param array $cart_items WooCommerce cart items.
	 * @return array{text: string, quantity: int}
	 */
	private function format_cart_items( array $cart_items ): array {
		$text     = '';
		$quantity = 0;

		foreach ( $cart_items as $cart_item ) {
			if ( empty( $cart_item['data'] ) || !\is_object( $cart_item['data'] ) ) {
				continue;
			}

			$product   = $cart_item['data'];
			$item_qty  = (int) ( $cart_item['quantity'] ?? 0 );
			$quantity += $item_qty;

			$text .= \sprintf(
				"- %1\$s (Qty: %2\$d)\n  URL: %3\$s\n Admin Link: %4\$s\n\n",
				$product->get_name(),
				$item_qty,
				\get_permalink( $product->get_id() ),
				\admin_url( 'post.php?post=' . $product->get_id() . '&action=edit' )
			);
		}

		return array(
			'text'     => $text,
			'quantity' => $quantity,
		);
	}

	/**
	 * Template saved in the settings, or the default when empty.
	 *
	 * @param string $option  Option name.
	 * @param string $default Default template.
	 * @return string
	 */
	private function get_template( string $option, string $default ): string {
		$template = (string) \get_option( $option, '' );

		return '' === \trim( $template ) ? $default : $template;
	}

	/**
	 * Default template for a cart quote.
	 *
	 * @param bool $has_company Add the company line.
	 * @return string
	 */
	private function default_cart_template( bool $has_company ): string {
		return "New Quote Request Received\n\nA customer has requested a quote for their cart. Here are the details:\n\nCustomer Name: {name}\nCustomer Email: {email}\n"
			. ( $has_company ? "Company: {company}\n" : '' )
			. "Phone: {phone}\n\nRequested Items:\n{cart_items}\n\nTotal Quantity: {total_quantity}\n\nCustomer Message:\n{message}\n\n{submitted_fields_text}\n\nRegards,\n{store_name}";
	}

	/**
	 * Default template for a single product quote.
	 *
	 * @param bool $has_company Add the company line.
	 * @return string
	 */
	private function default_product_template( bool $has_company ): string {
		return "New Quote Request Received\n\nA customer has requested a quote for a product. Here are the details:\n\nCustomer Name: {name}\nCustomer Email: {email}\n"
			. ( $has_company ? "Company: {company}\n" : '' )
			. "Phone: {phone}\n\nRequested Product:\n{product_details}\n\nCustomer Message:\n{message}\n\nRegards,\n{store_name}";
	}

	/**
	 * Who receives the quote notifications.
	 *
	 * @return string
	 */
	private function admin_recipient(): string {
		$to = (string) \get_option( 'doitgq_admin_email', '' );

		return \is_email( $to ) ? $to : (string) \get_option( 'admin_email' );
	}

	/**
	 * Store name, decoded for use in headers and subjects.
	 *
	 * @return string
	 */
	private function store_name(): string {
		return \wp_specialchars_decode( (string) \get_bloginfo( 'name' ), ENT_QUOTES );
	}
}