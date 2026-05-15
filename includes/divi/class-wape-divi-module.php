<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Divi Module for WhatsApp Button
 */
class WAPE_Divi_Module extends ET_Builder_Module {

    public $slug = 'et_pb_wape_whatsapp_button';
    public $vb_support = 'on';

    protected $module_credits = array(
        'module_uri' => 'https://example.com/',
        'author' => 'WA Product Enquiry',
        'author_uri' => 'https://example.com/',
    );

    public function init() {
        $this->name = esc_html__('WhatsApp Button', 'wa-product-enquiry');
        $this->icon_path = dirname(__FILE__) . '/icon.svg';
    }

    public function get_settings_modal_toggles() {
        return array(
            'general' => array(
                'toggles' => array(
                    'content' => array(
                        'title' => esc_html__('Content', 'wa-product-enquiry'),
                        'priority' => 10,
                    ),
                    'style' => array(
                        'title' => esc_html__('Design', 'wa-product-enquiry'),
                        'priority' => 20,
                    ),
                ),
            ),
        );
    }

    public static function get_module_fields() {
        return array(
            'button_text' => array(
                'label' => esc_html__('Button Text', 'wa-product-enquiry'),
                'description' => esc_html__('Enter the button text', 'wa-product-enquiry'),
                'toggle_slug' => 'content',
                'type' => 'text',
                'default' => esc_html__('Order on WhatsApp', 'wa-product-enquiry'),
            ),
            'button_style' => array(
                'label' => esc_html__('Button Style', 'wa-product-enquiry'),
                'description' => esc_html__('Choose button style', 'wa-product-enquiry'),
                'toggle_slug' => 'style',
                'type' => 'select',
                'default' => 'default',
                'options' => array(
                    'default' => esc_html__('Default (Rounded)', 'wa-product-enquiry'),
                    'flat' => esc_html__('Flat Design', 'wa-product-enquiry'),
                    'gradient' => esc_html__('Gradient', 'wa-product-enquiry'),
                    'outline' => esc_html__('Outline', 'wa-product-enquiry'),
                    'icon-only' => esc_html__('Icon Only', 'wa-product-enquiry'),
                    'floating' => esc_html__('Floating', 'wa-product-enquiry'),
                ),
            ),
            'alignment' => array(
                'label' => esc_html__('Alignment', 'wa-product-enquiry'),
                'description' => esc_html__('Align the button', 'wa-product-enquiry'),
                'toggle_slug' => 'content',
                'type' => 'text_align',
                'default' => 'left',
            ),
            'button_color' => array(
                'label' => esc_html__('Button Color', 'wa-product-enquiry'),
                'description' => esc_html__('Choose button color', 'wa-product-enquiry'),
                'toggle_slug' => 'style',
                'type' => 'color',
                'default' => '#25D366',
            ),
        );
    }

    public function render($attrs, $content, $render_slug) {
        $post_id = get_the_ID();

        if (!$post_id) {
            return '<p>' . esc_html__('Please use this module on a single post/page.', 'wa-product-enquiry') . '</p>';
        }

        $wape_settings = WAPE_Settings::get_settings();

        $button_html = WAPE_Button_Render::build_button_html($post_id, $wape_settings, array(
            'button_text' => sanitize_text_field($this->props['button_text']),
            'style' => sanitize_key($this->props['button_style']),
            'source' => 'divi_module',
        ));

        return $button_html;
    }
}

// Register the module
new WAPE_Divi_Module();
