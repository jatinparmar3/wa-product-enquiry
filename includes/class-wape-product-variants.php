<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles WooCommerce product variations support
 * Includes selected variant details in enquiry messages
 */
class WAPE_Product_Variants
{
    public static function init()
    {
        add_filter('wape_post_context', array(__CLASS__, 'add_variant_to_context'), 10, 2);
        add_action('wp_footer', array(__CLASS__, 'enqueue_variant_script'));
    }

    /**
     * Add variant information to message context
     */
    public static function add_variant_to_context($context, $post_id)
    {
        if (get_post_type($post_id) !== 'product') {
            return $context;
        }

        $product = wc_get_product($post_id);

        if (!$product || !$product->is_type('variable')) {
            return $context;
        }

        // Variant data will be populated from frontend via JavaScript
        // This allows real-time variant selection before message building
        $context['variant_id'] = '';
        $context['variant_attributes'] = '';
        $context['variant_price'] = '';
        $context['variant_sku'] = '';

        return $context;
    }

    /**
     * Get all variant options for a product
     */
    public static function get_product_variants($product_id)
    {
        $product = wc_get_product($product_id);

        if (!$product || !$product->is_type('variable')) {
            return array();
        }

        $variants = array();

        foreach ($product->get_available_variations() as $variation_data) {
            $variation = wc_get_product($variation_data['variation_id']);

            if (!$variation) {
                continue;
            }

            $attributes = array();
            foreach ($variation->get_attributes() as $attr_name => $attr_value) {
                $attributes[$attr_name] = $attr_value;
            }

            $variants[$variation_data['variation_id']] = array(
                'variation_id' => $variation_data['variation_id'],
                'attributes' => $attributes,
                'price' => $variation->get_price(),
                'sku' => $variation->get_sku(),
                'stock' => $variation->get_stock_quantity(),
                'in_stock' => $variation->is_in_stock(),
                'image_id' => $variation_data['image_id'],
                'image_url' => wp_get_attachment_image_url($variation_data['image_id'], 'full'),
            );
        }

        return $variants;
    }

    /**
     * Build variant string for message
     */
    public static function build_variant_string($variant_data)
    {
        if (empty($variant_data)) {
            return '';
        }

        $variant_string = '';

        if (!empty($variant_data['attributes'])) {
            foreach ($variant_data['attributes'] as $attr_name => $attr_value) {
                $label = ucwords(str_replace(array('pa_', '_'), array('', ' '), $attr_name));
                $variant_string .= $label . ': ' . ucfirst($attr_value) . "\n";
            }
        }

        return trim($variant_string);
    }

    /**
     * Enqueue JavaScript for variant handling
     */
    public static function enqueue_variant_script()
    {
        if (!is_product()) {
            return;
        }

        $product_id = get_the_ID();
        $product = wc_get_product($product_id);

        if (!$product || !$product->is_type('variable')) {
            return;
        }

        $variants = self::get_product_variants($product_id);

        if (empty($variants)) {
            return;
        }

        wp_localize_script('wape-script', 'wapeVariantData', array(
            'productId' => $product_id,
            'variants' => $variants,
            'nonce' => wp_create_nonce('wape_variant_nonce'),
        ));
    }

    /**
     * AJAX handler to get selected variant data
     */
    public static function ajax_get_variant_data()
    {
        check_ajax_referer('wape_variant_nonce', 'nonce');

        $product_id = absint($_POST['product_id']);
        $variant_id = absint($_POST['variant_id']);

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error('Invalid product');
        }

        $variant = wc_get_product($variant_id);
        if (!$variant) {
            wp_send_json_error('Invalid variant');
        }

        $attributes = array();
        foreach ($variant->get_attributes() as $attr_name => $attr_value) {
            $attributes[$attr_name] = $attr_value;
        }

        wp_send_json_success(array(
            'variant_id' => $variant_id,
            'attributes' => $attributes,
            'price' => $variant->get_price(),
            'formatted_price' => wc_price($variant->get_price()),
            'sku' => $variant->get_sku(),
            'stock' => $variant->get_stock_quantity(),
            'in_stock' => $variant->is_in_stock(),
        ));
    }

    /**
     * Update message with selected variant data
     */
    public static function build_variant_message_override($variant_id)
    {
        $variant = wc_get_product($variant_id);

        if (!$variant) {
            return array();
        }

        $attributes = array();
        foreach ($variant->get_attributes() as $attr_name => $attr_value) {
            $attributes[$attr_name] = $attr_value;
        }

        $attr_string = '';
        foreach ($attributes as $name => $value) {
            $attr_string .= ucwords(str_replace(array('pa_', '_'), array('', ' '), $name)) . ': ' . ucfirst($value) . ', ';
        }
        $attr_string = rtrim($attr_string, ', ');

        return array(
            'variant_id' => $variant_id,
            'variant_attributes' => $attr_string,
            'variant_price' => $variant->get_price(),
            'variant_sku' => $variant->get_sku(),
        );
    }
}
