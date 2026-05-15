<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Integrates with page builders: Elementor, Divi, Gutenberg
 */
class WAPE_Page_Builder_Integration
{
    public static function init()
    {
        // Elementor integration
        add_action('elementor/elements/categories_registered', array(__CLASS__, 'register_elementor_category'));
        add_action('elementor/widgets/widgets_registered', array(__CLASS__, 'register_elementor_widget'));

        // Divi integration (Visual Builder)
        add_action('divi_extensions_init', array(__CLASS__, 'register_divi_module'));

        // Gutenberg integration
        add_action('init', array(__CLASS__, 'register_gutenberg_block'));
    }

    /**
     * Register Elementor widget category
     */
    public static function register_elementor_category()
    {
        if (!did_action('elementor/loaded')) {
            return;
        }

        \Elementor\Plugin::instance()->elements_manager->add_category(
            'wape_elements',
            array(
                'title' => esc_html__('WhatsApp Notify', 'wa-product-enquiry'),
                'icon' => 'fa fa-whatsapp',
            )
        );
    }

    /**
     * Register Elementor widget
     */
    public static function register_elementor_widget()
    {
        if (!did_action('elementor/loaded')) {
            return;
        }

        require_once WAPE_PLUGIN_DIR . 'includes/elementor/class-wape-elementor-widget.php';

        \Elementor\Plugin::instance()->widgets_manager->register(new WAPE_Elementor_Widget());
    }

    /**
     * Register Divi module/extension
     */
    public static function register_divi_module()
    {
        if (function_exists('et_builder_add_main_module')) {
            require_once WAPE_PLUGIN_DIR . 'includes/divi/class-wape-divi-module.php';
        }
    }

    /**
     * Register Gutenberg block
     */
    public static function register_gutenberg_block()
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        wp_register_script(
            'wape-gutenberg-block',
            WAPE_PLUGIN_URL . 'assets/js/gutenberg-block.js',
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor'),
            WAPE_VERSION
        );

        wp_localize_script('wape-gutenberg-block', 'wapeGutenbergData', array(
            'pluginUrl' => WAPE_PLUGIN_URL,
            'settings' => WAPE_Settings::get_settings(),
        ));

        register_block_type('wape/whatsapp-button', array(
            'editor_script' => 'wape-gutenberg-block',
            'render_callback' => array(__CLASS__, 'render_gutenberg_block'),
            'attributes' => array(
                'postId' => array(
                    'type' => 'number',
                    'default' => 0,
                ),
                'buttonText' => array(
                    'type' => 'string',
                    'default' => '',
                ),
                'buttonStyle' => array(
                    'type' => 'string',
                    'default' => 'default',
                ),
                'alignment' => array(
                    'type' => 'string',
                    'default' => 'left',
                ),
            ),
        ));
    }

    /**
     * Render Gutenberg block
     */
    public static function render_gutenberg_block($attributes)
    {
        $post_id = !empty($attributes['postId']) ? absint($attributes['postId']) : get_the_ID();

        if (!$post_id) {
            return '';
        }

        $settings = WAPE_Settings::get_settings();

        return WAPE_Button_Render::build_button_html($post_id, $settings, array(
            'button_text' => !empty($attributes['buttonText']) ? sanitize_text_field($attributes['buttonText']) : '',
            'style' => !empty($attributes['buttonStyle']) ? sanitize_key($attributes['buttonStyle']) : 'default',
            'alignment' => !empty($attributes['alignment']) ? sanitize_key($attributes['alignment']) : 'left',
            'source' => 'gutenberg_block',
        ));
    }

    /**
     * Check if Elementor is active
     */
    public static function is_elementor_active()
    {
        return did_action('elementor/loaded');
    }

    /**
     * Check if Divi is active
     */
    public static function is_divi_active()
    {
        return function_exists('et_builder_add_main_module');
    }

    /**
     * Check if Gutenberg is active
     */
    public static function is_gutenberg_active()
    {
        return function_exists('register_block_type');
    }

    /**
     * Get active page builders
     */
    public static function get_active_builders()
    {
        $builders = array();

        if (self::is_elementor_active()) {
            $builders[] = 'elementor';
        }

        if (self::is_divi_active()) {
            $builders[] = 'divi';
        }

        if (self::is_gutenberg_active()) {
            $builders[] = 'gutenberg';
        }

        return $builders;
    }
}
