<?php

namespace DOIT_Get_Quote\Backend;

use DOIT_Get_Quote\Engine\Base;

class Meta_Box extends Base
{

    const NONCE_ACTION = 'doitgq_save_meta';
    const NONCE_FIELD = 'doitgq_meta_box';

    /**
     * Initialize the class.
     *
     * @return void|bool
     */
    public function initialize()
    {
        parent::initialize();

        \add_action('add_meta_boxes_quote_request', array($this, 'add_meta_box'));
        \add_action('save_post_quote_request', array($this, 'save'));
    }

    /**
     * Register the meta box.
     *
     * @return void
     */
    public function add_meta_box(): void
    {
        \add_meta_box(
            'doitgq_quote_request_details',
            \__('Quote Request Details', 'doit_get_quote'),
            array($this, 'render'),
            'quote_request',
            'normal',
            'high'
        );
    }

    /**
     * Render the meta box.
     *
     * @param \WP_Post $post Current post.
     * @return void
     */
    public function render(\WP_Post $post): void
    {
        \wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);
        $email    = \get_post_meta($post->ID, '_customer_email', true);
        $products = \get_post_meta($post->ID, '_quote_products', true);
?>
        <table class="form-table">
            <tr>
                <th><label for="doitgq_customer_email"><?php \esc_html_e('Customer Email', 'doit_get_quote'); ?></label></th>
                <td>
                    <input type="email" name="customer_email" id="doitgq_customer_email"
                        value="<?php echo \esc_attr($email); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="doitgq_quote_products"><?php \esc_html_e('Quote Products', 'doit_get_quote'); ?></label></th>
                <td>
                    <textarea id="doitgq_quote_products" rows="10" class="large-text code" readonly><?php echo \esc_textarea(\wp_json_encode($products, JSON_PRETTY_PRINT)); ?></textarea>
                </td>
            </tr>
        </table>
<?php
    }

    /**
     * Save the meta box data.
     *
     * @param int $post_id Post ID.
     * @return void
     */
    public function save($post_id)
    {
        if (!isset($_POST[self::NONCE_FIELD])) {
            return;
        }

        $nonce = \sanitize_text_field(\wp_unslash($_POST[self::NONCE_FIELD]));

        if (!\wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return;
        }

        if (\defined('DOING_AUTOSAVE') && \DOING_AUTOSAVE) {
            return;
        }

        if (!\current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['customer_email'])) {
            \update_post_meta(
                $post_id,
                '_customer_email',
                \sanitize_email(\wp_unslash($_POST['customer_email']))
            );
        }
    }
}
