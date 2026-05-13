<?php

if (!defined('ABSPATH')) {
    exit;
}

class WAPE_Plugin
{
    public static function init()
    {
        add_action('plugins_loaded', array(__CLASS__, 'bootstrap'));
    }

    public static function bootstrap()
    {
        load_plugin_textdomain('wa-product-enquiry', false, dirname(plugin_basename(WAPE_PLUGIN_FILE)) . '/languages');

        if (class_exists('WAPE_Settings')) {
            WAPE_Settings::init();
        }

        if (class_exists('WAPE_Button_Render')) {
            WAPE_Button_Render::init();
        }

        if (class_exists('WAPE_Hooks')) {
            WAPE_Hooks::init();
        }

        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_public_assets'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
    }

    public static function enqueue_public_assets()
    {
        wp_register_style(
            'wape-style',
            WAPE_PLUGIN_URL . 'assets/css/style.css',
            array(),
            WAPE_VERSION
        );

        wp_register_script(
            'wape-script',
            WAPE_PLUGIN_URL . 'assets/js/script.js',
            array(),
            WAPE_VERSION,
            true
        );
    }

    public static function enqueue_admin_assets($hook)
    {
        if (strpos($hook, 'wape-settings') === false) {
            return;
        }

        wp_enqueue_style('wape-style');
    }
}
