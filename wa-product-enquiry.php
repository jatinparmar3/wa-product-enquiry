<?php
/**
 * Plugin Name: WA Product Enquiry
 * Plugin URI: https://example.com/
 * Description: Adds a WhatsApp enquiry button to WooCommerce products and selected CPT single pages with auto-built product details and optional order notification links.
 * Version: 1.0.0
 * Author: Jatin Parmar
 * Text Domain: wa-product-enquiry
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WAPE_VERSION', '1.0.0');
define('WAPE_PLUGIN_FILE', __FILE__);
define('WAPE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WAPE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WAPE_OPTION_KEY', 'wape_settings');

require_once WAPE_PLUGIN_DIR . 'includes/class-wape-settings.php';
require_once WAPE_PLUGIN_DIR . 'includes/class-wape-message-builder.php';
require_once WAPE_PLUGIN_DIR . 'includes/class-wape-button-render.php';
require_once WAPE_PLUGIN_DIR . 'includes/class-wape-hooks.php';
require_once WAPE_PLUGIN_DIR . 'includes/class-wape-plugin.php';
require_once WAPE_PLUGIN_DIR . 'includes/class-wape-display-manager.php';
require_once WAPE_PLUGIN_DIR . 'includes/class-wape-message-scheduler.php';
require_once WAPE_PLUGIN_DIR . 'includes/class-wape-button-styling.php';
require_once WAPE_PLUGIN_DIR . 'includes/class-wape-product-variants.php';
require_once WAPE_PLUGIN_DIR . 'includes/class-wape-page-builder-integration.php';
require_once WAPE_PLUGIN_DIR . 'includes/class-wape-template-editor.php';

WAPE_Plugin::init();
