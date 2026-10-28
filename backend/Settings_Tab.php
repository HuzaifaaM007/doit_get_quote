<?php

/**
 * DOIT_Get_Quote
 *
 * @package   DOIT_Get_Quote
 * @license   GPL 2.0+
 */

namespace DOIT_Get_Quote\Backend;

use DOIT_Get_Quote\Engine\Base;
use DOIT_Get_Quote\Internals\Forms;

/**
 * The "Get a Quote" tab in WooCommerce > Settings.
 *
 * Two sections: General Settings (WooCommerce settings API, so WooCommerce
 * checks the nonce and capability and cleans the values) and Forms (the form
 * editor, see Form_Editor).
 *
 * Options saved here (all prefixed doitgq_): location_product_list,
 * location_product_details, location_cart, form_type, mailing_service,
 * mailer_username, mailer_password, sender_mail, admin_email, email_subject,
 * email_template_for_cart_quote, email_template_for_product_quote.
 */
class Settings_Tab extends Base {

	const TAB = 'doitgq_settings';

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() { // phpcs:ignore
		parent::initialize();

		\add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_tab' ), 50 );
		\add_action( 'woocommerce_settings_tabs_' . self::TAB, array( $this, 'render' ) );
		\add_action( 'woocommerce_update_options_' . self::TAB, array( $this, 'save' ) );
		\add_action( 'woocommerce_admin_field_doitgq_preview', array( $this, 'render_preview_row' ) );
		\add_filter(
			'plugin_action_links_' . \plugin_basename( \dirname( __DIR__ ) . '/doit_get_quote.php' ),
			array( $this, 'add_action_link' )
		);
	}

	/**
	 * Add the tab.
	 *
	 * @param array $tabs Existing tabs.
	 * @return array
	 */
	public function add_tab( $tabs ) {
		$tabs[ self::TAB ] = \__( 'Get a Quote', 'doit_get_quote' );

		return $tabs;
	}

	/**
	 * Add a "Settings" link on the Plugins page.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function add_action_link( $links ) {
		$url = \admin_url( 'admin.php?page=wc-settings&tab=' . self::TAB );

		\array_unshift( $links, '<a href="' . \esc_url( $url ) . '">' . \esc_html__( 'Settings', 'doit_get_quote' ) . '</a>' );

		return $links;
	}

	/**
	 * Print the tab.
	 *
	 * @return void
	 */
	public function render() {
		$sections = $this->sections();
		$current  = $this->current_section();

		echo '<h2>' . \esc_html__( 'Get a Quote', 'doit_get_quote' ) . '</h2>';

		if ( \count( $sections ) > 1 ) {
			$links = array();

			foreach ( $sections as $key => $title ) {
				$url     = \admin_url( 'admin.php?page=wc-settings&tab=' . self::TAB . '&section=' . $key );
				$links[] = '<a href="' . \esc_url( $url ) . '"' . ( $key === $current ? ' class="current"' : '' ) . '>' . \esc_html( $title ) . '</a>';
			}

			echo '<ul class="subsubsub">';
			echo '<li>' . \implode( ' | </li><li>', $links ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts.
			echo '</ul><br class="clear" />';
		}

		if ( 'general' === $current ) {
			\woocommerce_admin_fields( $this->get_settings() );
		} elseif ( 'forms' === $current ) {
			( new Form_Editor() )->render();
		}
	}

	/**
	 * Save the tab.
	 *
	 * @return void
	 */
	public function save() {
		if ( 'general' === $this->current_section() ) {
			\woocommerce_update_options( $this->get_settings() );
		}
	}

	/**
	 * Print the row of the custom 'doitgq_preview' field type.
	 *
	 * Every form gets a hidden row; the script shows the one that is selected.
	 *
	 * @param array $value Field definition.
	 * @return void
	 */
	public function render_preview_row( $value ) {
		$form_id  = (string) ( $value['form_id'] ?? '' );
		$selected = Forms::selected_id();
		?>
		<tr valign="top" class="doitgq-preview-row" data-form-id="<?php echo \esc_attr( $form_id ); ?>"<?php echo $form_id === $selected ? '' : ' style="display:none;"'; ?>>
			<th scope="row" class="titledesc"></th>
			<td class="forminp"><?php echo Forms::render_preview( $form_id ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in Forms. ?></td>
		</tr>
		<?php
	}

	/**
	 * Sections of the tab.
	 *
	 * @return array Section key => title.
	 */
	private function sections(): array {
		return array(
			'general' => \__( 'General Settings', 'doit_get_quote' ),
			'forms'   => \__( 'Forms', 'doit_get_quote' ),
		);
	}

	/**
	 * The section being viewed.
	 *
	 * @return string
	 */
	private function current_section(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only picks which section to show.
		$section = isset( $_GET['section'] ) ? \sanitize_key( \wp_unslash( $_GET['section'] ) ) : 'general';

		return isset( $this->sections()[ $section ] ) ? $section : 'general';
	}

	/**
	 * Field definitions for the WooCommerce settings API.
	 *
	 * @return array
	 */
	private function get_settings(): array {
		$settings = array(
			array(
				'title' => \__( 'Quote Button', 'doit_get_quote' ),
				'type'  => 'title',
				'id'    => 'doitgq_button_options',
			),
			array(
				'title'         => \__( 'Show the quote button on', 'doit_get_quote' ),
				'desc'          => \__( 'Shop and category pages', 'doit_get_quote' ),
				'id'            => 'doitgq_location_product_list',
				'type'          => 'checkbox',
				'default'       => 'no',
				'checkboxgroup' => 'start',
			),
			array(
				'desc'          => \__( 'Single product page', 'doit_get_quote' ),
				'id'            => 'doitgq_location_product_details',
				'type'          => 'checkbox',
				'default'       => 'no',
				'checkboxgroup' => '',
			),
			array(
				'desc'          => \__( 'Cart page', 'doit_get_quote' ),
				'id'            => 'doitgq_location_cart',
				'type'          => 'checkbox',
				'default'       => 'no',
				'checkboxgroup' => 'end',
			),
			array(
				'title'    => \__( 'Quote form', 'doit_get_quote' ),
				'desc_tip' => \__( 'The form customers fill in when they ask for a quote.', 'doit_get_quote' ),
				'id'       => 'doitgq_form_type',
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'default'  => 'form_a',
				'options'  => Forms::choices(),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'doitgq_button_options',
			),
			array(
				'title' => \__( 'Form Preview', 'doit_get_quote' ),
				'desc'  => \__( 'This is how the selected form will look.', 'doit_get_quote' ),
				'type'  => 'title',
				'id'    => 'doitgq_form_preview',
			),
		);

		foreach ( \array_keys( Forms::all() ) as $form_id ) {
			$settings[] = array(
				'type'    => 'doitgq_preview',
				'id'      => 'doitgq_preview_' . $form_id,
				'form_id' => (string) $form_id,
			);
		}

		return \array_merge(
			$settings,
			array(
				array(
					'type' => 'sectionend',
					'id'   => 'doitgq_form_preview',
				),
				array(
					'title' => \__( 'Email', 'doit_get_quote' ),
					'desc'  => \__( 'Leave the SMTP username and password empty to send with the default WordPress mailer. Placeholders: {name} {email} {phone} {company} {message} {submitted_fields_text} {store_name}, and {cart_items} {total_quantity} for the cart template or {product_details} for the product template.', 'doit_get_quote' ),
					'type'  => 'title',
					'id'    => 'doitgq_email_options',
				),
				array(
					'title'   => \__( 'SMTP host', 'doit_get_quote' ),
					'id'      => 'doitgq_mailing_service',
					'type'    => 'text',
					'default' => 'smtp.gmail.com',
				),
				array(
					'title' => \__( 'SMTP username', 'doit_get_quote' ),
					'id'    => 'doitgq_mailer_username',
					'type'  => 'text',
				),
				array(
					'title'    => \__( 'SMTP password', 'doit_get_quote' ),
					'desc_tip' => \__( 'For Gmail use an app password, not your account password.', 'doit_get_quote' ),
					'id'       => 'doitgq_mailer_password',
					'type'     => 'password',
				),
				array(
					'title' => \__( 'Sender email', 'doit_get_quote' ),
					'id'    => 'doitgq_sender_mail',
					'type'  => 'email',
				),
				array(
					'title'    => \__( 'Send quote requests to', 'doit_get_quote' ),
					'desc_tip' => \__( 'Leave empty to use the site admin email.', 'doit_get_quote' ),
					'id'       => 'doitgq_admin_email',
					'type'     => 'email',
				),
				array(
					'title'   => \__( 'Email subject', 'doit_get_quote' ),
					'id'      => 'doitgq_email_subject',
					'type'    => 'text',
					'default' => \__( 'Product Quotation', 'doit_get_quote' ),
				),
				array(
					'title'   => \__( 'Cart quote email', 'doit_get_quote' ),
					'id'      => 'doitgq_email_template_for_cart_quote',
					'type'    => 'textarea',
					'css'     => 'width:100%; height:240px;',
					'default' => $this->default_template( 'cart-quote' ),
				),
				array(
					'title'   => \__( 'Product quote email', 'doit_get_quote' ),
					'id'      => 'doitgq_email_template_for_product_quote',
					'type'    => 'textarea',
					'css'     => 'width:100%; height:240px;',
					'default' => $this->default_template( 'product-quote' ),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'doitgq_email_options',
				),
			)
		);
	}

	/**
	 * Default email template shown in the settings (templates/emails/*.html).
	 *
	 * @param string $name File name without extension.
	 * @return string
	 */
	private function default_template( string $name ): string {
		$path = \dirname( __DIR__ ) . '/templates/emails/' . $name . '.html';

		return \is_readable( $path ) ? (string) \file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
}