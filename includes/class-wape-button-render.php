<?php

if (!defined('ABSPATH')) {
    exit;
}

class WAPE_Button_Render
{
    public static function init()
    {
        add_shortcode('wa_product_button', array(__CLASS__, 'shortcode'));
        add_shortcode('wa_order_button', array(__CLASS__, 'shortcode'));

        add_action('wp', array(__CLASS__, 'register_auto_hooks'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'maybe_enqueue_assets'));
    }

    public static function register_auto_hooks()
    {
        $settings = WAPE_Settings::get_settings();

        if ($settings['enable_woo_single'] === 'yes' && function_exists('is_product')) {
            add_action('woocommerce_single_product_summary', array(__CLASS__, 'render_woocommerce_button'), 35);
        }

        if ($settings['enable_cpt_single'] === 'yes') {
            add_filter('the_content', array(__CLASS__, 'append_button_to_cpt_content'));
        }
    }

    public static function maybe_enqueue_assets()
    {
        if (is_admin()) {
            return;
        }

        if (is_singular()) {
            wp_enqueue_style('wape-style');
            wp_enqueue_script('wape-script');
        }
    }

    public static function shortcode($atts = array())
    {
        $atts = shortcode_atts(
            array(
                'post_id' => 0,
                'button_text' => '',
            ),
            $atts,
            'wa_product_button'
        );

        $post_id = absint($atts['post_id']);
        if ($post_id < 1) {
            $post_id = get_the_ID();
        }

        if (!$post_id) {
            return '';
        }

        $settings = WAPE_Settings::get_settings();

        return self::build_button_html($post_id, $settings, array(
            'button_text' => sanitize_text_field($atts['button_text']),
            'source' => 'shortcode',
        ));
    }

    public static function render_woocommerce_button()
    {
        if (!is_product()) {
            return;
        }

        // Don't render if shortcode is already used on this page
        if (WAPE_Display_Manager::has_shortcode_in_content() || WAPE_Display_Manager::has_button_rendered()) {
            return;
        }

        // Check device visibility
        if (!WAPE_Button_Styling::should_show_on_device()) {
            return;
        }

        $post_id = get_the_ID();
        $settings = WAPE_Settings::get_settings();

        echo self::build_button_html($post_id, $settings, array('source' => 'woo_single'));
        WAPE_Display_Manager::set_button_rendered();
    }

    public static function append_button_to_cpt_content($content)
    {
        if (!is_singular() || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        // Don't render if shortcode is already used or button already rendered
        if (WAPE_Display_Manager::has_shortcode_in_content() || WAPE_Display_Manager::has_button_rendered()) {
            return $content;
        }

        // Check device visibility
        if (!WAPE_Button_Styling::should_show_on_device()) {
            return $content;
        }

        $post_id = get_the_ID();
        $post_type = get_post_type($post_id);
        $settings = WAPE_Settings::get_settings();

        if ($post_type === 'product') {
            return $content;
        }

        $selected_cpts = isset($settings['selected_cpts']) ? (array) $settings['selected_cpts'] : array();

        if (!in_array($post_type, $selected_cpts, true)) {
            return $content;
        }

        $button_html = self::build_button_html($post_id, $settings, array('source' => 'cpt_single'));
        if ($button_html === '') {
            return $content;
        }

        WAPE_Display_Manager::set_button_rendered();
        return $content . $button_html;
    }

    public static function build_button_html($post_id, $settings, $args = array())
    {
        $phone = isset($settings['admin_number']) ? WAPE_Settings::sanitize_whatsapp_number($settings['admin_number']) : '';
        if ($phone === '') {
            return '';
        }

        $message = WAPE_Message_Builder::build_post_message($post_id, $settings);
        $wa_url = WAPE_Message_Builder::build_whatsapp_url($phone, $message);

        if ($wa_url === '') {
            return '';
        }

        $button_text = !empty($args['button_text']) ? $args['button_text'] : $settings['button_text'];
        if ($button_text === '') {
            $button_text = __('Order on WhatsApp', 'wa-product-enquiry');
        }

        wp_enqueue_style('wape-style');
        wp_enqueue_script('wape-script');

        $data_attrs = array(
            'data-wape-dynamic' => '1',
            'data-wape-source' => isset($args['source']) ? sanitize_key($args['source']) : 'unknown',
            'data-post-id' => (string) absint($post_id),
        );

        $attr_html = '';
        foreach ($data_attrs as $key => $value) {
            $attr_html .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        
        $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="none" aria-hidden="true" focusable="false" style="display:inline-block;vertical-align:middle;"><path fill="#ffffff" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982 .998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>';

        // Get button styling classes and styles
        $button_classes = WAPE_Button_Styling::get_button_classes($post_id);
        $wrapper_classes = WAPE_Button_Styling::get_wrapper_classes();
        $inline_styles = WAPE_Button_Styling::get_inline_styles();

        return '<div class="' . esc_attr($wrapper_classes) . '" style="' . esc_attr($inline_styles) . '">'
            . '<a class="' . esc_attr($button_classes) . '" href="' . esc_attr($wa_url) . '" target="_blank" rel="noopener"' . $attr_html . '>'
            . '<span class="wape-btn-icon">' . $icon_svg . '</span>'
            . '<span class="wape-btn-label">' . esc_html($button_text) . '</span>'
            . '</a>'
            . '</div>';
    }
}
