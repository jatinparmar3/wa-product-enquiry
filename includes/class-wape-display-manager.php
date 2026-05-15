<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages button display logic to prevent duplicate rendering
 * Handles shortcode vs default display priority
 */
class WAPE_Display_Manager
{
    private static $shortcode_used_in_content = array();
    private static $page_button_rendered = false;

    public static function init()
    {
        // Track if shortcode is used in the current content
        add_filter('the_content', array(__CLASS__, 'track_shortcode_usage'), 5);
    }

    /**
     * Track if wa_product_button shortcode is used in content
     * Run early (priority 5) to check before rendering
     */
    public static function track_shortcode_usage($content)
    {
        $post_id = get_the_ID();
        
        // Check if shortcode exists in content
        if (has_shortcode($content, 'wa_product_button') || has_shortcode($content, 'wa_order_button')) {
            self::$shortcode_used_in_content[$post_id] = true;
        } else {
            self::$shortcode_used_in_content[$post_id] = false;
        }

        return $content;
    }

    /**
     * Check if shortcode is already used on this post
     */
    public static function has_shortcode_in_content($post_id = null)
    {
        if ($post_id === null) {
            $post_id = get_the_ID();
        }

        return isset(self::$shortcode_used_in_content[$post_id]) && self::$shortcode_used_in_content[$post_id];
    }

    /**
     * Check if button was already rendered on this page
     */
    public static function has_button_rendered()
    {
        return self::$page_button_rendered;
    }

    /**
     * Mark button as rendered on this page
     */
    public static function set_button_rendered()
    {
        self::$page_button_rendered = true;
    }

    /**
     * Reset rendering state (for testing purposes)
     */
    public static function reset_state()
    {
        self::$shortcode_used_in_content = array();
        self::$page_button_rendered = false;
    }
}
