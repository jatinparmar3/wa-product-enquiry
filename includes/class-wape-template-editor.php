<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Visual/Drag-and-Drop Template Editor
 * Allows admins to build messages without coding
 */
class WAPE_Template_Editor
{
    public static function init()
    {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_editor_assets'));
        add_action('wp_ajax_wape_save_template', array(__CLASS__, 'ajax_save_template'));
        add_action('wp_ajax_nopriv_wape_save_template', array(__CLASS__, 'ajax_save_template'));
        add_action('wp_ajax_wape_load_template', array(__CLASS__, 'ajax_load_template'));
        add_action('wp_ajax_nopriv_wape_load_template', array(__CLASS__, 'ajax_load_template'));
        add_action('wp_ajax_wape_preview_template', array(__CLASS__, 'ajax_preview_template'));
        add_action('wp_ajax_nopriv_wape_preview_template', array(__CLASS__, 'ajax_preview_template'));
        add_action('wp_ajax_wape_get_placeholders', array(__CLASS__, 'ajax_get_placeholders'));
        add_action('wp_ajax_nopriv_wape_get_placeholders', array(__CLASS__, 'ajax_get_placeholders'));
    }

    /**
     * Get available template components
     */
    public static function get_template_components()
    {
        return array(
            'text' => array(
                'label' => __('Text', 'wa-product-enquiry'),
                'icon' => 'dashicons-edit',
                'category' => 'basic',
                'editable' => true,
            ),
            'product_title' => array(
                'label' => __('Product Title', 'wa-product-enquiry'),
                'icon' => 'dashicons-text',
                'category' => 'product',
                'placeholder' => '{title}',
            ),
            'product_price' => array(
                'label' => __('Product Price', 'wa-product-enquiry'),
                'icon' => 'dashicons-tag',
                'category' => 'product',
                'placeholder' => '{price}',
            ),
            'product_sku' => array(
                'label' => __('Product SKU', 'wa-product-enquiry'),
                'icon' => 'dashicons-barcode',
                'category' => 'product',
                'placeholder' => '{sku}',
            ),
            'product_category' => array(
                'label' => __('Product Category', 'wa-product-enquiry'),
                'icon' => 'dashicons-list-view',
                'category' => 'product',
                'placeholder' => '{category}',
            ),
            'product_image' => array(
                'label' => __('Product Image URL', 'wa-product-enquiry'),
                'icon' => 'dashicons-format-image',
                'category' => 'product',
                'placeholder' => '{image}',
            ),
            'product_url' => array(
                'label' => __('Product URL', 'wa-product-enquiry'),
                'icon' => 'dashicons-link',
                'category' => 'product',
                'placeholder' => '{url}',
            ),
            'order_id' => array(
                'label' => __('Order ID', 'wa-product-enquiry'),
                'icon' => 'dashicons-tag',
                'category' => 'order',
                'placeholder' => '{order_id}',
            ),
            'order_status' => array(
                'label' => __('Order Status', 'wa-product-enquiry'),
                'icon' => 'dashicons-info',
                'category' => 'order',
                'placeholder' => '{order_status}',
            ),
            'customer_name' => array(
                'label' => __('Customer Name', 'wa-product-enquiry'),
                'icon' => 'dashicons-admin-users',
                'category' => 'order',
                'placeholder' => '{customer_name}',
            ),
            'order_total' => array(
                'label' => __('Order Total', 'wa-product-enquiry'),
                'icon' => 'dashicons-money-alt',
                'category' => 'order',
                'placeholder' => '{order_total}',
            ),
            'line_break' => array(
                'label' => __('Line Break', 'wa-product-enquiry'),
                'icon' => 'dashicons-editor-break',
                'category' => 'basic',
            ),
            'separator' => array(
                'label' => __('Separator', 'wa-product-enquiry'),
                'icon' => 'dashicons-minus',
                'category' => 'basic',
            ),
        );
    }

    /**
     * Get template components by category
     */
    public static function get_components_by_category($category = null)
    {
        $components = self::get_template_components();

        if ($category) {
            $components = array_filter($components, function($item) use ($category) {
                return isset($item['category']) && $item['category'] === $category;
            });
        }

        return $components;
    }

    /**
     * Build template from component structure
     */
    public static function build_template_from_components($components)
    {
        $template = '';

        foreach ($components as $component) {
            $type = $component['type'] ?? '';

            switch ($type) {
                case 'text':
                    $template .= $component['content'] ?? '';
                    break;

                case 'line_break':
                    $template .= "\n";
                    break;

                case 'separator':
                    $template .= "\n-------------------------\n";
                    break;

                default:
                    if (isset($component['placeholder'])) {
                        $template .= $component['placeholder'];
                    }
            }

            $template .= "\n";
        }

        return trim($template);
    }

    /**
     * Parse template back to components
     */
    public static function parse_template_to_components($template)
    {
        $components = array();
        $lines = explode("\n", $template);

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                $components[] = array(
                    'type' => 'line_break',
                );
            } elseif ($line === '-------------------------') {
                $components[] = array(
                    'type' => 'separator',
                );
            } elseif (preg_match('/\{([^}]+)\}/', $line, $matches)) {
                // Placeholder found
                $placeholder = $matches[0];
                $component_type = self::get_component_by_placeholder($placeholder);

                $components[] = array(
                    'type' => $component_type ?: 'text',
                    'placeholder' => $placeholder,
                    'content' => $line,
                );
            } else {
                $components[] = array(
                    'type' => 'text',
                    'content' => $line,
                );
            }
        }

        return $components;
    }

    /**
     * Get component type by placeholder
     */
    private static function get_component_by_placeholder($placeholder)
    {
        $components = self::get_template_components();

        foreach ($components as $key => $component) {
            if (isset($component['placeholder']) && $component['placeholder'] === $placeholder) {
                return $key;
            }
        }

        return null;
    }

    /**
     * AJAX: Save template
     */
    public static function ajax_save_template()
    {
        check_ajax_referer('wape_template_editor_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $template_name = sanitize_text_field($_POST['template_name'] ?? '');
        $template_content = sanitize_textarea_field($_POST['template_content'] ?? '');
        $template_type = sanitize_key($_POST['template_type'] ?? 'product');

        if (empty($template_name) || empty($template_content)) {
            wp_send_json_error('Template name and content required');
        }

        $templates = get_option('wape_custom_templates', array());
        $template_id = wp_generate_uuid4();

        $templates[$template_id] = array(
            'id' => $template_id,
            'name' => $template_name,
            'content' => $template_content,
            'type' => $template_type,
            'created_at' => current_time('timestamp'),
            'updated_at' => current_time('timestamp'),
        );

        update_option('wape_custom_templates', $templates);

        wp_send_json_success(array(
            'template_id' => $template_id,
            'message' => __('Template saved successfully', 'wa-product-enquiry'),
        ));
    }

    /**
     * AJAX: Load template
     */
    public static function ajax_load_template()
    {
        check_ajax_referer('wape_template_editor_nonce', 'nonce');

        $template_id = sanitize_key($_POST['template_id'] ?? '');

        if (empty($template_id)) {
            wp_send_json_error('Template ID required');
        }

        $templates = get_option('wape_custom_templates', array());

        if (!isset($templates[$template_id])) {
            wp_send_json_error('Template not found');
        }

        $template = $templates[$template_id];
        $components = self::parse_template_to_components($template['content']);

        wp_send_json_success(array(
            'template' => $template,
            'components' => $components,
        ));
    }

    /**
     * AJAX: Preview template
     */
    public static function ajax_preview_template()
    {
        check_ajax_referer('wape_template_editor_nonce', 'nonce');

        $template_content = sanitize_textarea_field($_POST['template_content'] ?? '');

        if (empty($template_content)) {
            wp_send_json_error('Template content required');
        }

        $preview_context = array(
            'title' => 'Sample Product Title',
            'price' => '$99.99',
            'sku' => 'SKU-12345',
            'category' => 'Electronics',
            'image' => 'https://via.placeholder.com/150',
            'url' => 'https://example.com/product',
            'order_id' => '12345',
            'order_status' => 'Processing',
            'customer_name' => 'John Doe',
            'order_total' => '$199.99',
            'custom_fields' => '',
        );

        $preview = WAPE_Message_Builder::replace_placeholders($template_content, $preview_context);

        wp_send_json_success(array(
            'preview' => $preview,
        ));
    }

    /**
     * AJAX: Get all placeholders
     */
    public static function ajax_get_placeholders()
    {
        check_ajax_referer('wape_template_editor_nonce', 'nonce');

        $type = sanitize_key($_POST['type'] ?? 'product');

        $placeholders = array();

        if ($type === 'product' || $type === 'all') {
            $placeholders['product'] = array(
                '{title}' => __('Product Title', 'wa-product-enquiry'),
                '{price}' => __('Product Price', 'wa-product-enquiry'),
                '{sku}' => __('Product SKU', 'wa-product-enquiry'),
                '{category}' => __('Product Category', 'wa-product-enquiry'),
                '{image}' => __('Product Image URL', 'wa-product-enquiry'),
                '{url}' => __('Product URL', 'wa-product-enquiry'),
                '{excerpt}' => __('Product Description', 'wa-product-enquiry'),
            );
        }

        if ($type === 'order' || $type === 'all') {
            $placeholders['order'] = array(
                '{order_id}' => __('Order ID', 'wa-product-enquiry'),
                '{order_status}' => __('Order Status', 'wa-product-enquiry'),
                '{customer_name}' => __('Customer Name', 'wa-product-enquiry'),
                '{customer_phone}' => __('Customer Phone', 'wa-product-enquiry'),
                '{order_total}' => __('Order Total', 'wa-product-enquiry'),
                '{items}' => __('Order Items', 'wa-product-enquiry'),
            );
        }

        wp_send_json_success($placeholders);
    }

    /**
     * Get all custom templates
     */
    public static function get_custom_templates($type = null)
    {
        $templates = get_option('wape_custom_templates', array());

        if ($type) {
            $templates = array_filter($templates, function($t) use ($type) {
                return isset($t['type']) && $t['type'] === $type;
            });
        }

        return $templates;
    }

    /**
     * Delete custom template
     */
    public static function delete_custom_template($template_id)
    {
        $templates = get_option('wape_custom_templates', array());

        if (isset($templates[$template_id])) {
            unset($templates[$template_id]);
            update_option('wape_custom_templates', $templates);
            return true;
        }

        return false;
    }

    /**
     * Enqueue editor assets
     */
    public static function enqueue_editor_assets($hook)
    {
        if (strpos($hook, 'wape-settings') === false) {
            return;
        }

        wp_enqueue_script(
            'wape-template-editor',
            WAPE_PLUGIN_URL . 'assets/js/template-editor.js',
            array('jquery', 'wp-util'),
            WAPE_VERSION
        );

        wp_localize_script('wape-template-editor', 'wapeTemplateEditor', array(
            'nonce' => wp_create_nonce('wape_template_editor_nonce'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'components' => self::get_template_components(),
        ));

        wp_enqueue_style(
            'wape-template-editor',
            WAPE_PLUGIN_URL . 'assets/css/template-editor.css',
            array(),
            WAPE_VERSION
        );
    }
}
