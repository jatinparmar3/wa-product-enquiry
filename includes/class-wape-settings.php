<?php

if (!defined('ABSPATH')) {
    exit;
}

class WAPE_Settings
{
    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'register_admin_menu'));
    }

    public static function get_defaults()
    {
        return array(
            'admin_number' => '',
            'button_text' => __('Order on WhatsApp', 'wa-product-enquiry'),
            'enable_woo_single' => 'yes',
            'enable_cpt_single' => 'yes',
            'selected_cpts' => array(),
            'fields' => array('title', 'price', 'category', 'sku', 'image', 'url'),
            'custom_meta_keys' => '',
            'message_template' => "Hello, I want to enquire about this item.\n-------------------------\nTitle: {title}\nPrice: {price}\nCategory: {category}\nSKU: {sku}\n{custom_fields}Link: {url}\nImage: {image}\n-------------------------\nPlease share availability and best price. 🙏",
            'enable_woo_owner_note' => 'yes',
            'enable_woo_customer_note' => 'yes',
            'woo_owner_template' => "*New WooCommerce Order Received*\n\n*Order ID:* #{order_id}\n*Customer:* {customer_name}\n*Phone:* {customer_phone}\n*Total:* {order_total}\n\n*Items:*\n{items}\n\n*Admin Order Link:* {admin_order_url}",
            'woo_customer_template' => "Hi {customer_name} 👋\n\n*Your order has been updated*\n\n*Order ID:* #{order_id}\n*Status:* {order_status}\n*Total:* {order_total}\n\nTrack your order here:\n{order_url}",
        );
    }

    public static function get_settings()
    {
        $saved = get_option(WAPE_OPTION_KEY, array());
        $settings = wp_parse_args(is_array($saved) ? $saved : array(), self::get_defaults());

        if (!is_array($settings['selected_cpts'])) {
            $settings['selected_cpts'] = array();
        }

        if (!is_array($settings['fields'])) {
            $settings['fields'] = self::get_defaults()['fields'];
        }

        return $settings;
    }

    public static function register_admin_menu()
    {
        add_menu_page(
            __('WhatsApp Notify', 'wa-product-enquiry'),
            __('WhatsApp Notify', 'wa-product-enquiry'),
            'manage_options',
            'wape-settings',
            array(__CLASS__, 'render_settings_page'),
            'dashicons-whatsapp',
            56
        );
    }

    public static function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['wape_save_settings'])) {
            self::save_settings();
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved.', 'wa-product-enquiry') . '</p></div>';
        }

        $settings = self::get_settings();
        $post_types = self::get_selectable_cpts();
        $field_options = self::get_field_options();
        ?>
        <div class="wrap wape-admin-wrap">
            <h1><?php esc_html_e('WA Product Enquiry', 'wa-product-enquiry'); ?></h1>
            <p><?php esc_html_e('Configure WhatsApp button behavior for WooCommerce and custom post type single pages.', 'wa-product-enquiry'); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field('wape_save_settings_nonce', 'wape_nonce'); ?>

                <div class="wape-card">
                    <h2><?php esc_html_e('General', 'wa-product-enquiry'); ?></h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="wape_admin_number"><?php esc_html_e('Admin WhatsApp Number', 'wa-product-enquiry'); ?></label></th>
                            <td>
                                <input type="text" id="wape_admin_number" name="admin_number" class="regular-text" value="<?php echo esc_attr($settings['admin_number']); ?>" />
                                <p class="description"><?php esc_html_e('Use country code and digits only. Example: 919876543210', 'wa-product-enquiry'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="wape_button_text"><?php esc_html_e('Button Text', 'wa-product-enquiry'); ?></label></th>
                            <td>
                                <input type="text" id="wape_button_text" name="button_text" class="regular-text" value="<?php echo esc_attr($settings['button_text']); ?>" />
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="wape-card">
                    <h2><?php esc_html_e('Display Rules', 'wa-product-enquiry'); ?></h2>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="enable_woo_single" value="yes" <?php checked($settings['enable_woo_single'], 'yes'); ?> />
                            <?php esc_html_e('Show button on WooCommerce single product page', 'wa-product-enquiry'); ?>
                        </label>
                        <br />
                        <label>
                            <input type="checkbox" name="enable_cpt_single" value="yes" <?php checked($settings['enable_cpt_single'], 'yes'); ?> />
                            <?php esc_html_e('Show button on selected CPT single pages', 'wa-product-enquiry'); ?>
                        </label>
                    </fieldset>

                    <h3><?php esc_html_e('Select CPT Types', 'wa-product-enquiry'); ?></h3>
                    <fieldset class="wape-cpt-grid">
                        <?php foreach ($post_types as $post_type => $label) : ?>
                            <label>
                                <input
                                    type="checkbox"
                                    name="selected_cpts[]"
                                    value="<?php echo esc_attr($post_type); ?>"
                                    <?php checked(in_array($post_type, $settings['selected_cpts'], true)); ?>
                                />
                                <?php echo esc_html($label . ' (' . $post_type . ')'); ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                </div>

                <div class="wape-card">
                    <h2><?php esc_html_e('Message Builder', 'wa-product-enquiry'); ?></h2>
                    <h3><?php esc_html_e('Include Fields', 'wa-product-enquiry'); ?></h3>
                    <fieldset class="wape-field-grid">
                        <?php foreach ($field_options as $key => $label) : ?>
                            <label>
                                <input
                                    type="checkbox"
                                    name="fields[]"
                                    value="<?php echo esc_attr($key); ?>"
                                    <?php checked(in_array($key, $settings['fields'], true)); ?>
                                />
                                <?php echo esc_html($label); ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                    <p>
                        <label for="wape_custom_meta_keys"><strong><?php esc_html_e('Custom Meta Keys', 'wa-product-enquiry'); ?></strong></label><br />
                        <input type="text" id="wape_custom_meta_keys" name="custom_meta_keys" class="regular-text" value="<?php echo esc_attr($settings['custom_meta_keys']); ?>" />
                        <span class="description"><?php esc_html_e('Comma separated keys. Example: weight,pack_size,origin', 'wa-product-enquiry'); ?></span>
                    </p>
                    <p>
                        <label for="wape_message_template"><strong><?php esc_html_e('Message Template', 'wa-product-enquiry'); ?></strong></label>
                    </p>
                    <textarea id="wape_message_template" name="message_template" rows="8" class="large-text code"><?php echo esc_textarea($settings['message_template']); ?></textarea>
                    <p class="description">
                        <?php esc_html_e('Available placeholders: {title}, {price}, {category}, {sku}, {excerpt}, {url}, {image}, {custom_fields}, {post_type}, {site_name}', 'wa-product-enquiry'); ?>
                    </p>
                </div>

                <div class="wape-card">
                    <h2><?php esc_html_e('WooCommerce Notifications', 'wa-product-enquiry'); ?></h2>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="enable_woo_owner_note" value="yes" <?php checked($settings['enable_woo_owner_note'], 'yes'); ?> />
                            <?php esc_html_e('Add owner WhatsApp quick-link note on new order', 'wa-product-enquiry'); ?>
                        </label>
                        <br />
                        <label>
                            <input type="checkbox" name="enable_woo_customer_note" value="yes" <?php checked($settings['enable_woo_customer_note'], 'yes'); ?> />
                            <?php esc_html_e('Generate customer WhatsApp status link on order status change', 'wa-product-enquiry'); ?>
                        </label>
                    </fieldset>

                    <p>
                        <label for="wape_woo_owner_template"><strong><?php esc_html_e('Owner Order Message Template', 'wa-product-enquiry'); ?></strong></label>
                    </p>
                    <textarea id="wape_woo_owner_template" name="woo_owner_template" rows="6" class="large-text code"><?php echo esc_textarea($settings['woo_owner_template']); ?></textarea>
                    <p class="description">
                        <?php esc_html_e('Placeholders: {order_id}, {customer_name}, {customer_phone}, {order_total}, {items}, {admin_order_url}', 'wa-product-enquiry'); ?>
                    </p>

                    <p>
                        <label for="wape_woo_customer_template"><strong><?php esc_html_e('Customer Status Message Template', 'wa-product-enquiry'); ?></strong></label>
                    </p>
                    <textarea id="wape_woo_customer_template" name="woo_customer_template" rows="6" class="large-text code"><?php echo esc_textarea($settings['woo_customer_template']); ?></textarea>
                    <p class="description">
                        <?php esc_html_e('Placeholders: {order_id}, {customer_name}, {order_status}, {order_total}, {order_url}', 'wa-product-enquiry'); ?>
                    </p>
                </div>

                <p>
                    <button type="submit" name="wape_save_settings" class="button button-primary button-large"><?php esc_html_e('Save Settings', 'wa-product-enquiry'); ?></button>
                </p>
            </form>
        </div>
        <?php
    }

    public static function save_settings()
    {
        if (!isset($_POST['wape_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wape_nonce'])), 'wape_save_settings_nonce')) {
            return;
        }

        $defaults = self::get_defaults();
        $sanitized = array();

        $sanitized['admin_number'] = self::sanitize_whatsapp_number(isset($_POST['admin_number']) ? wp_unslash($_POST['admin_number']) : '');
        $sanitized['button_text'] = sanitize_text_field(isset($_POST['button_text']) ? wp_unslash($_POST['button_text']) : $defaults['button_text']);

        $sanitized['enable_woo_single'] = isset($_POST['enable_woo_single']) ? 'yes' : 'no';
        $sanitized['enable_cpt_single'] = isset($_POST['enable_cpt_single']) ? 'yes' : 'no';

        $available_cpts = array_keys(self::get_selectable_cpts());
        $selected_cpts = isset($_POST['selected_cpts']) ? (array) wp_unslash($_POST['selected_cpts']) : array();
        $selected_cpts = array_map('sanitize_key', $selected_cpts);
        $sanitized['selected_cpts'] = array_values(array_intersect($selected_cpts, $available_cpts));

        $field_options = array_keys(self::get_field_options());
        $fields = isset($_POST['fields']) ? (array) wp_unslash($_POST['fields']) : $defaults['fields'];
        $fields = array_map('sanitize_key', $fields);
        $sanitized['fields'] = array_values(array_intersect($fields, $field_options));

        $sanitized['custom_meta_keys'] = sanitize_text_field(isset($_POST['custom_meta_keys']) ? wp_unslash($_POST['custom_meta_keys']) : '');
        $sanitized['message_template'] = sanitize_textarea_field(isset($_POST['message_template']) ? wp_unslash($_POST['message_template']) : $defaults['message_template']);

        $sanitized['enable_woo_owner_note'] = isset($_POST['enable_woo_owner_note']) ? 'yes' : 'no';
        $sanitized['enable_woo_customer_note'] = isset($_POST['enable_woo_customer_note']) ? 'yes' : 'no';

        $sanitized['woo_owner_template'] = sanitize_textarea_field(isset($_POST['woo_owner_template']) ? wp_unslash($_POST['woo_owner_template']) : $defaults['woo_owner_template']);
        $sanitized['woo_customer_template'] = sanitize_textarea_field(isset($_POST['woo_customer_template']) ? wp_unslash($_POST['woo_customer_template']) : $defaults['woo_customer_template']);

        update_option(WAPE_OPTION_KEY, wp_parse_args($sanitized, $defaults));
    }

    public static function sanitize_whatsapp_number($raw)
    {
        return preg_replace('/\D+/', '', (string) $raw);
    }

    public static function get_selectable_cpts()
    {
        $post_types = get_post_types(
            array(
                'public' => true,
                '_builtin' => false,
            ),
            'objects'
        );

        $output = array();

        foreach ($post_types as $post_type => $object) {
            if ($post_type === 'attachment') {
                continue;
            }

            $output[$post_type] = isset($object->labels->singular_name) ? $object->labels->singular_name : $post_type;
        }

        return $output;
    }

    public static function get_field_options()
    {
        return array(
            'title' => __('Title', 'wa-product-enquiry'),
            'price' => __('Price', 'wa-product-enquiry'),
            'category' => __('Category', 'wa-product-enquiry'),
            'sku' => __('SKU', 'wa-product-enquiry'),
            'excerpt' => __('Short Description', 'wa-product-enquiry'),
            'image' => __('Image URL', 'wa-product-enquiry'),
            'url' => __('Page URL', 'wa-product-enquiry'),
        );
    }
}
