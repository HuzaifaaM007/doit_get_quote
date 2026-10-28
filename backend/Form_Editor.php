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
 * The "Forms" section of the settings tab: list, create, edit and delete
 * the quote forms.
 *
 * The screen is printed by Settings_Tab (section=forms). The buttons submit to
 * admin-post.php, handled by save() and delete().
 *
 * The AJAX submit handler finds the customer's data by the field type and id,
 * so a form is only accepted when it can be understood:
 *  - an Email field is the customer's email,
 *  - a Text field whose id contains "name" is the customer's name,
 *  - a Text field with "phone" or "company" in the id fills those values,
 *  - a Textarea with "message" in the id is the message.
 */
class Form_Editor extends Base {

	const SAVE_ACTION   = 'doitgq_save_form';
	const DELETE_ACTION = 'doitgq_delete_form';
	const NONCE_FIELD   = 'doitgq_nonce';

	/**
	 * Names the submit handler already uses for something else.
	 */
	const RESERVED_IDS = array( 'action', 'nonce', 'cart_count', 'product_id', 'is_cart', 'is_product', '_wpnonce', 'new' );

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() { // phpcs:ignore
		parent::initialize();

		\add_action( 'admin_post_' . self::SAVE_ACTION, array( $this, 'save' ) );
		\add_action( 'admin_post_' . self::DELETE_ACTION, array( $this, 'delete' ) );
	}

	/**
	 * URL of the forms section.
	 *
	 * @param array $args Extra query arguments, for example array( 'edit' => 'form_a' ).
	 * @return string
	 */
	public static function url( array $args = array() ): string {
		return \add_query_arg(
			$args,
			\admin_url( 'admin.php?page=wc-settings&tab=' . Settings_Tab::TAB . '&section=forms' )
		);
	}

	/**
	 * Print the list of forms, or the editor when ?edit= is set.
	 *
	 * @return void
	 */
	public function render() {
		// The editor has its own buttons; hide WooCommerce's "Save changes".
		$GLOBALS['hide_save_button'] = true; // phpcs:ignore WordPress.WP.GlobalVariablesOverride

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only picks the screen.
		$edit = isset( $_GET['edit'] ) ? \sanitize_key( \wp_unslash( $_GET['edit'] ) ) : '';

		if ( '' !== $edit ) {
			$is_new = ( 'new' === $edit );
			$form   = $is_new ? null : Forms::get( $edit );

			if ( $is_new || null !== $form ) {
				$data = array(
					'is_new'            => $is_new,
					'form_id'           => $is_new ? '' : $edit,
					'name'              => $is_new ? '' : (string) ( $form['name'] ?? '' ),
					'fields'            => $is_new ? $this->starter_fields() : \array_values( (array) ( $form['fields'] ?? array() ) ),
					'option_types'      => Forms::TYPES_WITH_OPTIONS,
					'placeholder_types' => Forms::TYPES_WITH_PLACEHOLDER,
					'reserved_ids'      => self::RESERVED_IDS,
				);

				include __DIR__ . '/views/forms-editor.php';

				return;
			}
		}

		$data = array(
			'forms'    => Forms::all(),
			'selected' => Forms::selected_id(),
		);

		include __DIR__ . '/views/forms-list.php';
	}

	/**
	 * Save a form (admin-post.php?action=doitgq_save_form).
	 *
	 * @return void
	 */
	public function save() {
		if ( !\current_user_can( 'manage_woocommerce' ) ) {
			\wp_die( \esc_html__( 'Not allowed.', 'doit_get_quote' ), 403 );
		}

		\check_admin_referer( self::SAVE_ACTION, self::NONCE_FIELD );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
		$data    = \wp_unslash( $_POST );
		$is_new  = isset( $data['is_new'] ) && '1' === $data['is_new'];
		$form_id = \sanitize_key( (string) ( $data[ $is_new ? 'form_slug' : 'form_id' ] ?? '' ) );
		$name    = \sanitize_text_field( (string) ( $data['form_name'] ?? '' ) );
		$forms   = Forms::all();
		$back    = self::url( array( 'edit' => $is_new ? 'new' : $form_id ) );

		if ( '' === $form_id || '' === $name ) {
			$this->finish( 'error', \__( 'A form needs a name and an ID.', 'doit_get_quote' ), $back );
		}

		if ( \in_array( $form_id, self::RESERVED_IDS, true ) ) {
			$this->finish( 'error', \__( 'This form ID is reserved. Use another one.', 'doit_get_quote' ), $back );
		}

		if ( $is_new && isset( $forms[ $form_id ] ) ) {
			$this->finish( 'error', \__( 'A form with this ID already exists.', 'doit_get_quote' ), $back );
		}

		if ( !$is_new && !isset( $forms[ $form_id ] ) ) {
			$this->finish( 'error', \__( 'Form not found.', 'doit_get_quote' ), self::url() );
		}

		list( $fields, $error ) = $this->clean_fields( (array) ( $data['fields'] ?? array() ) );

		if ( '' !== $error ) {
			$this->finish( 'error', $error, $back );
		}

		$forms[ $form_id ] = array(
			'name'   => $name,
			'fields' => $fields,
		);

		\update_option( Forms::OPTION_FORMS, $forms );

		$this->finish( 'success', \__( 'Form saved.', 'doit_get_quote' ), self::url( array( 'edit' => $form_id ) ) );
	}

	/**
	 * Delete a form (admin-post.php?action=doitgq_delete_form).
	 *
	 * @return void
	 */
	public function delete() {
		if ( !\current_user_can( 'manage_woocommerce' ) ) {
			\wp_die( \esc_html__( 'Not allowed.', 'doit_get_quote' ), 403 );
		}

		\check_admin_referer( self::DELETE_ACTION );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified above.
		$form_id = isset( $_GET['form_id'] ) ? \sanitize_key( \wp_unslash( $_GET['form_id'] ) ) : '';
		$forms   = Forms::all();

		if ( !isset( $forms[ $form_id ] ) ) {
			$this->finish( 'error', \__( 'Form not found.', 'doit_get_quote' ), self::url() );
		}

		if ( \count( $forms ) <= 1 ) {
			$this->finish( 'error', \__( 'You cannot delete the only form. Create another one first.', 'doit_get_quote' ), self::url() );
		}

		unset( $forms[ $form_id ] );
		\update_option( Forms::OPTION_FORMS, $forms );

		$this->finish( 'success', \__( 'Form deleted.', 'doit_get_quote' ), self::url() );
	}

	/**
	 * Clean and check the fields sent by the editor.
	 *
	 * Rows without an id or a label are ignored.
	 *
	 * @param array $raw Raw fields from $_POST.
	 * @return array array( cleaned fields, error message ). The message is empty when all is well.
	 */
	private function clean_fields( array $raw ): array {
		$clean     = array();
		$seen      = array();
		$has_email = false;
		$has_name  = false;

		foreach ( $raw as $row ) {
			if ( !\is_array( $row ) ) {
				continue;
			}

			$id    = \sanitize_key( (string) ( $row['id'] ?? '' ) );
			$label = \sanitize_text_field( (string) ( $row['label'] ?? '' ) );

			if ( '' === $id || '' === $label ) {
				continue;
			}

			if ( \in_array( $id, self::RESERVED_IDS, true ) ) {
				/* translators: %s: field id */
				return array( array(), \sprintf( \__( 'The field ID "%s" is reserved. Use another one.', 'doit_get_quote' ), $id ) );
			}

			if ( isset( $seen[ $id ] ) ) {
				/* translators: %s: field id */
				return array( array(), \sprintf( \__( 'The field ID "%s" is used twice.', 'doit_get_quote' ), $id ) );
			}

			$seen[ $id ] = true;

			$type    = Forms::clean_type( $row['type'] ?? 'text' );
			$options = array();

			if ( \in_array( $type, Forms::TYPES_WITH_OPTIONS, true ) && !empty( $row['options'] ) ) {
				foreach ( \explode( ',', (string) $row['options'] ) as $option ) {
					$option = \sanitize_text_field( \trim( $option ) );

					if ( '' !== $option ) {
						$options[] = $option;
					}
				}
			}

			$clean[] = array(
				'id'          => $id,
				'label'       => $label,
				'type'        => $type,
				'required'    => !empty( $row['required'] ),
				'placeholder' => \in_array( $type, Forms::TYPES_WITH_PLACEHOLDER, true ) ? \sanitize_text_field( (string) ( $row['placeholder'] ?? '' ) ) : '',
				'options'     => $options,
				'always_on'   => true,
			);

			$has_email = $has_email || 'email' === $type;
			$has_name  = $has_name || ( 'text' === $type && false !== \strpos( $id, 'name' ) );
		}

		if ( empty( $clean ) ) {
			return array( array(), \__( 'Add at least one field.', 'doit_get_quote' ) );
		}

		if ( !$has_email ) {
			return array( array(), \__( 'The form needs an Email field.', 'doit_get_quote' ) );
		}

		if ( !$has_name ) {
			return array( array(), \__( 'The form needs a Text field whose ID contains "name", for example user_name.', 'doit_get_quote' ) );
		}

		return array( $clean, '' );
	}

	/**
	 * The two fields every form needs, so a new form starts valid.
	 *
	 * @return array
	 */
	private function starter_fields(): array {
		return array(
			array(
				'id'          => 'user_name',
				'label'       => 'Your Name',
				'type'        => 'text',
				'required'    => true,
				'placeholder' => 'name',
			),
			array(
				'id'          => 'user_email',
				'label'       => 'Email Address',
				'type'        => 'email',
				'required'    => true,
				'placeholder' => 'email',
			),
		);
	}

	/**
	 * Queue a notice and go to a page.
	 *
	 * @param string $type    'success' or 'error'.
	 * @param string $message Notice text.
	 * @param string $url     Where to go.
	 * @return void
	 */
	private function finish( string $type, string $message, string $url ) {
		Admin_Notice::add( $type, $message );

		\wp_safe_redirect( $url );
		exit;
	}
}