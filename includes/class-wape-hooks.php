<?php

if (!defined('ABSPATH')) {
    exit;
}

class WAPE_Hooks
{
    public static function init()
    {
        add_action('init', array(__CLASS__, 'register_woocommerce_hooks'));
    }

    public static function register_woocommerce_hooks()
    {
        if (!class_exists('WooCommerce')) {
            return;
        }

        add_action('woocommerce_new_order', array(__CLASS__, 'add_owner_order_whatsapp_note'));
        add_action('woocommerce_order_status_changed', array(__CLASS__, 'prepare_customer_status_whatsapp_link'), 10, 4);

        add_action('woocommerce_admin_order_data_after_order_details', array(__CLASS__, 'render_admin_order_links'));
        add_action('woocommerce_thankyou', array(__CLASS__, 'render_customer_whatsapp_button'));
        add_action('woocommerce_view_order', array(__CLASS__, 'render_customer_whatsapp_button'));
    }

    public static function add_owner_order_whatsapp_note($order_id)
    {
        $settings = WAPE_Settings::get_settings();

        if ($settings['enable_woo_owner_note'] !== 'yes') {
            return;
        }

        $phone = WAPE_Settings::sanitize_whatsapp_number($settings['admin_number']);
        if ($phone === '') {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $template = !empty($settings['woo_owner_template']) ? $settings['woo_owner_template'] : WAPE_Settings::get_defaults()['woo_owner_template'];
        $message = WAPE_Message_Builder::build_order_message($order, $template);
        $url = WAPE_Message_Builder::build_whatsapp_url($phone, $message);

        if ($url === '') {
            return;
        }

        update_post_meta($order_id, '_wape_owner_whatsapp_url', esc_url_raw($url));

        $order->add_order_note(
            sprintf(
                /* translators: %s: WhatsApp URL */
                __('Owner WhatsApp quick link: %s', 'wa-product-enquiry'),
                $url
            )
        );
    }

    public static function prepare_customer_status_whatsapp_link($order_id, $old_status, $new_status, $order)
    {
        $settings = WAPE_Settings::get_settings();

        if ($settings['enable_woo_customer_note'] !== 'yes') {
            return;
        }

        if (!is_object($order) || !method_exists($order, 'get_id')) {
            $order = wc_get_order($order_id);
            if (!$order) {
                return;
            }
        }

        $customer_phone = WAPE_Settings::sanitize_whatsapp_number($order->get_billing_phone());
        if ($customer_phone === '') {
            return;
        }

        $template = !empty($settings['woo_customer_template']) ? $settings['woo_customer_template'] : WAPE_Settings::get_defaults()['woo_customer_template'];

        $message = WAPE_Message_Builder::build_order_message($order, $template, array(
            'order_status' => wc_get_order_status_name($new_status),
        ));

        $url = WAPE_Message_Builder::build_whatsapp_url($customer_phone, $message);
        if ($url === '') {
            return;
        }

        update_post_meta($order_id, '_wape_customer_whatsapp_url', esc_url_raw($url));

        $order->add_order_note(
            sprintf(
                /* translators: %s: WhatsApp URL */
                __('Customer WhatsApp status link: %s', 'wa-product-enquiry'),
                $url
            )
        );
    }

    public static function render_admin_order_links($order)
    {
        if (!is_object($order) || !method_exists($order, 'get_id')) {
            return;
        }

        $owner_url = get_post_meta($order->get_id(), '_wape_owner_whatsapp_url', true);
        $customer_url = get_post_meta($order->get_id(), '_wape_customer_whatsapp_url', true);

        if (!$owner_url && !$customer_url) {
            return;
        }

        echo '<div class="wape-order-links" style="margin-top:12px;">';
        echo '<strong>' . esc_html__('WhatsApp Quick Links', 'wa-product-enquiry') . '</strong><br />';

        if ($owner_url) {
            echo '<a class="button" style="margin-top:8px;margin-right:8px;" href="' . esc_url($owner_url) . '" target="_blank" rel="noopener">' . esc_html__('Open Owner Message', 'wa-product-enquiry') . '</a>';
        }

        if ($customer_url) {
            echo '<a class="button" style="margin-top:8px;" href="' . esc_url($customer_url) . '" target="_blank" rel="noopener">' . esc_html__('Open Customer Message', 'wa-product-enquiry') . '</a>';
        }

        echo '</div>';
    }

    public static function render_customer_whatsapp_button($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $url = get_post_meta($order->get_id(), '_wape_customer_whatsapp_url', true);
        if (!$url) {
            return;
        }

        echo '<div class="wape-thankyou-wrap" style="margin-top:16px;">';
        echo '<a class="wape-btn" href="' . esc_url($url) . '" target="_blank" rel="noopener">';
        echo '<span class="wape-btn-label">' . esc_html__('Chat on WhatsApp for Order Updates', 'wa-product-enquiry') . '</span>';
        echo '</a>';
        echo '</div>';
    }
}
