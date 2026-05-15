<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Elementor Widget for WhatsApp Button
 */
class WAPE_Elementor_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'wape_whatsapp_button';
    }

    public function get_title() {
        return esc_html__('WhatsApp Button', 'wa-product-enquiry');
    }

    public function get_icon() {
        return 'eicon-share-elv';
    }

    public function get_categories() {
        return array('wape_elements');
    }

    public function get_keywords() {
        return array('whatsapp', 'button', 'enquiry', 'contact');
    }

    protected function register_controls() {
        // Content Tab
        $this->start_controls_section(
            'content_section',
            array(
                'label' => esc_html__('Content', 'wa-product-enquiry'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'button_text',
            array(
                'label' => esc_html__('Button Text', 'wa-product-enquiry'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => esc_html__('Order on WhatsApp', 'wa-product-enquiry'),
                'default' => esc_html__('Order on WhatsApp', 'wa-product-enquiry'),
            )
        );

        $this->add_control(
            'button_style',
            array(
                'label' => esc_html__('Button Style', 'wa-product-enquiry'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'default',
                'options' => array(
                    'default' => esc_html__('Default (Rounded)', 'wa-product-enquiry'),
                    'flat' => esc_html__('Flat Design', 'wa-product-enquiry'),
                    'gradient' => esc_html__('Gradient', 'wa-product-enquiry'),
                    'outline' => esc_html__('Outline', 'wa-product-enquiry'),
                    'icon-only' => esc_html__('Icon Only', 'wa-product-enquiry'),
                    'floating' => esc_html__('Floating', 'wa-product-enquiry'),
                ),
            )
        );

        $this->add_control(
            'alignment',
            array(
                'label' => esc_html__('Alignment', 'wa-product-enquiry'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => array(
                    'left' => array(
                        'title' => esc_html__('Left', 'wa-product-enquiry'),
                        'icon' => 'eicon-text-align-left',
                    ),
                    'center' => array(
                        'title' => esc_html__('Center', 'wa-product-enquiry'),
                        'icon' => 'eicon-text-align-center',
                    ),
                    'right' => array(
                        'title' => esc_html__('Right', 'wa-product-enquiry'),
                        'icon' => 'eicon-text-align-right',
                    ),
                ),
                'default' => 'left',
            )
        );

        $this->end_controls_section();

        // Style Tab
        $this->start_controls_section(
            'style_section',
            array(
                'label' => esc_html__('Style', 'wa-product-enquiry'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'button_color',
            array(
                'label' => esc_html__('Button Color', 'wa-product-enquiry'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#25D366',
                'selectors' => array(
                    '{{WRAPPER}} .wape-btn' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'text_color',
            array(
                'label' => esc_html__('Text Color', 'wa-product-enquiry'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#FFFFFF',
                'selectors' => array(
                    '{{WRAPPER}} .wape-btn' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $post_id = get_the_ID();

        if (!$post_id) {
            echo '<p>' . esc_html__('Please use this widget on a single post/page.', 'wa-product-enquiry') . '</p>';
            return;
        }

        $wape_settings = WAPE_Settings::get_settings();

        echo wp_kses_post(WAPE_Button_Render::build_button_html($post_id, $wape_settings, array(
            'button_text' => sanitize_text_field($settings['button_text']),
            'style' => sanitize_key($settings['button_style']),
            'source' => 'elementor_widget',
        )));
    }

    protected function render_plain_content() {
        echo wp_kses_post($this->get_settings_for_display()['button_text']);
    }
}
