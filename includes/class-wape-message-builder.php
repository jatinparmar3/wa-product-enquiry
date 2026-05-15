<?php

if (!defined('ABSPATH')) {
    exit;
}

class WAPE_Message_Builder
{
    public static function build_whatsapp_url($phone, $message)
    {
        $phone = WAPE_Settings::sanitize_whatsapp_number($phone);

        if (empty($phone) || empty($message)) {
            return '';
        }

        return 'https://wa.me/' . rawurlencode($phone) . '?text=' . rawurlencode($message);
    }

    public static function build_post_message($post_id, $settings = null, $override = array())
    {
        $settings = is_array($settings) ? $settings : WAPE_Settings::get_settings();
        $context = self::get_post_context($post_id, $settings, $override);

        $template = !empty($settings['message_template']) ? $settings['message_template'] : WAPE_Settings::get_defaults()['message_template'];

        return self::replace_placeholders($template, $context);
    }

    public static function get_post_context($post_id, $settings, $override = array())
    {
        $post = get_post($post_id);

        if (!$post) {
            return array();
        }

        $fields = isset($settings['fields']) && is_array($settings['fields']) ? $settings['fields'] : array();

        $title = in_array('title', $fields, true) ? get_the_title($post_id) : '';
        $price = in_array('price', $fields, true) ? self::get_post_price($post_id, $post->post_type) : '';
        $category = in_array('category', $fields, true) ? self::get_post_categories($post_id, $post->post_type) : '';
        $sku = in_array('sku', $fields, true) ? self::get_post_sku($post_id, $post->post_type) : '';
        $excerpt = in_array('excerpt', $fields, true) ? self::get_post_excerpt($post) : '';
        $image = in_array('image', $fields, true) ? get_the_post_thumbnail_url($post_id, 'full') : '';
        $url = in_array('url', $fields, true) ? get_permalink($post_id) : '';

        $custom_fields = self::get_custom_fields_string($post_id, $settings);

        $context = array(
            'title' => $title,
            'price' => $price,
            'category' => $category,
            'sku' => $sku,
            'excerpt' => $excerpt,
            'url' => $url,
            'image' => $image,
            'custom_fields' => $custom_fields,
            'post_type' => $post->post_type,
            'site_name' => get_bloginfo('name'),
            'quantity' => isset($override['quantity']) ? (string) $override['quantity'] : '',
            'variation' => isset($override['variation']) ? (string) $override['variation'] : '',
        );

        // Allow filtering of context for extensibility
        return apply_filters('wape_post_context', $context, $post_id);
    }

    public static function build_order_message($order, $template, $extra = array())
    {
        if (!is_object($order) || !method_exists($order, 'get_items')) {
            return '';
        }

        $items = array();
        foreach ($order->get_items() as $item) {
            $items[] = sprintf('%s x %s', $item->get_name(), (string) $item->get_quantity());
        }

        $context = array(
            'order_id' => (string) $order->get_id(),
            'customer_name' => trim($order->get_formatted_billing_full_name()),
            'customer_phone' => WAPE_Settings::sanitize_whatsapp_number((string) $order->get_billing_phone()),
            'order_total' => wp_strip_all_tags((string) $order->get_formatted_order_total()),
            'items' => implode("\n", $items),
            'admin_order_url' => admin_url('post.php?post=' . $order->get_id() . '&action=edit'),
            'order_status' => wc_get_order_status_name($order->get_status()),
            'order_url' => $order->get_view_order_url(),
        );

        $context = wp_parse_args($extra, $context);

        return self::replace_placeholders($template, $context);
    }

    private static function replace_placeholders($template, $context)
    {
        $replacements = array();

        foreach ($context as $key => $value) {
            $clean = is_scalar($value) ? (string) $value : '';

            // Strip HTML tags but preserve line breaks, then decode HTML entities
            $clean = wp_strip_all_tags($clean);
            $charset = get_bloginfo('charset') ?: 'UTF-8';
            $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, $charset);

            // Normalize CRLF to LF
            $clean = str_replace("\r\n", "\n", $clean);

            $replacements['{' . $key . '}'] = $clean;
        }

        $message = strtr((string) $template, $replacements);
        $message = self::normalize_message_lines($message);
        $message = preg_replace('/\n{3,}/', "\n\n", $message);

        return trim((string) $message);
    }

    private static function normalize_message_lines($message)
    {
        // Normalize all line endings to LF
        $message = str_replace(array("\r\n", "\r"), "\n", (string) $message);

        // Labels that must always start on their own line
        $label_list = array('Title:', 'Price:', 'Category:', 'SKU:', 'Link:', 'Page:', 'Image:', 'Sale:', 'Regular:', 'Description:', 'Quantity:');

        // Build a regex pattern from the labels (escaped for regex)
        $escaped = array();
        foreach ($label_list as $lbl) {
            $escaped[] = preg_quote($lbl, '/');
        }
        $pattern = '(' . implode('|', $escaped) . ')';

        // Split the message by labels, keeping the delimiters
        $parts = preg_split('/' . $pattern . '/i', $message, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if (!is_array($parts) || empty($parts)) {
            return $message;
        }

        // Rebuild: ensure each label starts on a new line
        $result = '';
        $count = count($parts);
        for ($i = 0; $i < $count; $i++) {
            $part = $parts[$i];

            // Check if this part is a label
            $is_label = false;
            foreach ($label_list as $lbl) {
                if (strcasecmp(trim($part), $lbl) === 0) {
                    $is_label = true;
                    break;
                }
            }

            if ($is_label) {
                // Trim any trailing whitespace/newlines from what came before
                $result = rtrim($result);
                // Add newline before the label (unless result is empty or already ends with newline)
                if ($result !== '' && substr($result, -1) !== "\n") {
                    $result .= "\n";
                }
                $result .= $part;
                // If there is a next part (the value), append it with a space if needed
                if (isset($parts[$i + 1])) {
                    $value = $parts[$i + 1];
                    // If value doesn't start with a space, add one
                    if ($value !== '' && $value[0] !== ' ') {
                        $result .= ' ';
                    }
                    $result .= rtrim($value);
                    $i++; // Skip the value part since we already added it
                }
            } else {
                $result .= $part;
            }
        }

        return $result;
    }

    private static function get_post_price($post_id, $post_type)
    {
        if ($post_type === 'product' && function_exists('wc_get_product')) {
            $product = wc_get_product($post_id);
            if ($product) {
                // If product is on sale, return sale and regular prices on separate lines
                if (method_exists($product, 'is_on_sale') && $product->is_on_sale()) {
                    $sale = '';
                    $regular = '';

                    if (method_exists($product, 'get_sale_price')) {
                        $sale_raw = $product->get_sale_price();
                        if ($sale_raw !== '' && function_exists('wc_price')) {
                            $sale = wc_price(wc_get_price_to_display($product, array('price' => $sale_raw)));
                        } elseif ($sale_raw !== '') {
                            $sale = (string) $sale_raw;
                        }
                    }

                    if (method_exists($product, 'get_regular_price')) {
                        $regular_raw = $product->get_regular_price();
                        if ($regular_raw !== '' && function_exists('wc_price')) {
                            $regular = wc_price(wc_get_price_to_display($product, array('price' => $regular_raw)));
                        } elseif ($regular_raw !== '') {
                            $regular = (string) $regular_raw;
                        }
                    }

                    $lines = array();
                    if ($sale !== '') {
                        $lines[] = 'Sale: ' . wp_strip_all_tags($sale);
                    }
                    if ($regular !== '') {
                        $lines[] = 'Regular: ' . wp_strip_all_tags($regular);
                    }

                    if (!empty($lines)) {
                        return implode("\n", $lines);
                    }
                }

                // Default: return formatted price HTML stripped of tags
                return wp_strip_all_tags((string) $product->get_price_html());
            }
        }

        $raw_price = get_post_meta($post_id, 'price', true);
        if ($raw_price === '') {
            $raw_price = get_post_meta($post_id, '_price', true);
        }

        if ($raw_price !== '' && function_exists('wc_price')) {
            // If raw meta contains numeric value, format using wc_price
            if (is_numeric($raw_price)) {
                return wc_price((float) $raw_price);
            }
        }

        return (string) $raw_price;
    }

    private static function get_post_sku($post_id, $post_type)
    {
        if ($post_type === 'product' && function_exists('wc_get_product')) {
            $product = wc_get_product($post_id);
            if ($product) {
                return (string) $product->get_sku();
            }
        }

        return (string) get_post_meta($post_id, 'sku', true);
    }

    private static function get_post_excerpt($post)
    {
        if (!empty($post->post_excerpt)) {
            return wp_strip_all_tags($post->post_excerpt);
        }

        return wp_trim_words(wp_strip_all_tags($post->post_content), 22);
    }

    private static function get_post_categories($post_id, $post_type)
    {
        $taxonomies = array();

        if ($post_type === 'product') {
            $taxonomies[] = 'product_cat';
        }

        $taxonomies[] = 'category';

        $names = array();

        foreach (array_unique($taxonomies) as $taxonomy) {
            if (!taxonomy_exists($taxonomy)) {
                continue;
            }

            $terms = get_the_terms($post_id, $taxonomy);
            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            foreach ($terms as $term) {
                $names[] = $term->name;
            }
        }

        return implode(', ', array_unique($names));
    }

    private static function get_custom_fields_string($post_id, $settings)
    {
        $keys_raw = isset($settings['custom_meta_keys']) ? (string) $settings['custom_meta_keys'] : '';
        if ($keys_raw === '') {
            return '';
        }

        $keys = array_filter(array_map('trim', explode(',', $keys_raw)));
        if (empty($keys)) {
            return '';
        }

        $lines = array();
        foreach ($keys as $key) {
            $meta_key = sanitize_key($key);
            if ($meta_key === '') {
                continue;
            }

            $value = get_post_meta($post_id, $meta_key, true);
            if ($value === '' || $value === null) {
                continue;
            }

            if (is_array($value)) {
                $value = implode(', ', array_map('wp_strip_all_tags', $value));
            }

            $label = ucwords(str_replace(array('-', '_'), ' ', $meta_key));
            $lines[] = $label . ': ' . wp_strip_all_tags((string) $value);
        }

        if (empty($lines)) {
            return '';
        }

        return implode("\n", $lines) . "\n";
    }
}
