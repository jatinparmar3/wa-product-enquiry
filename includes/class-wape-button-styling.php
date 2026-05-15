<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages button styling, positioning, and device-specific display
 */
class WAPE_Button_Styling
{
    // Available button styles
    private static $button_styles = array(
        'default' => 'Default (Rounded)',
        'flat' => 'Flat Design',
        'gradient' => 'Gradient',
        'outline' => 'Outline',
        'icon-only' => 'Icon Only',
        'floating' => 'Floating Button',
    );

    // Available positions
    private static $button_positions = array(
        'before' => 'Before Content',
        'after' => 'After Content',
        'top-left' => 'Top Left Corner (Fixed)',
        'top-right' => 'Top Right Corner (Fixed)',
        'bottom-left' => 'Bottom Left Corner (Fixed)',
        'bottom-right' => 'Bottom Right Corner (Fixed)',
        'inline' => 'Inline with Product (WooCommerce)',
    );

    /**
     * Get available button styles
     */
    public static function get_button_styles()
    {
        return apply_filters('wape_button_styles', self::$button_styles);
    }

    /**
     * Get available positions
     */
    public static function get_button_positions()
    {
        return apply_filters('wape_button_positions', self::$button_positions);
    }

    /**
     * Get button style settings
     */
    public static function get_button_style_settings()
    {
        return get_option('wape_button_style_settings', self::get_style_defaults());
    }

    /**
     * Get default style settings
     */
    public static function get_style_defaults()
    {
        return array(
            'style' => 'default',
            'position' => 'after',
            'primary_color' => '#25D366', // WhatsApp green
            'text_color' => '#FFFFFF',
            'hover_color' => '#20BA5A',
            'border_radius' => '8',
            'font_size' => '14',
            'padding' => '12,20',
            'icon_size' => '20',
            'show_on_mobile' => 'yes',
            'show_on_desktop' => 'yes',
            'mobile_breakpoint' => '768',
            'animation' => 'none', // none, pulse, bounce, shake
            'shadow' => 'yes',
            'dark_mode_enabled' => 'yes',
            'dark_mode_primary' => '#128C7E',
            'dark_mode_text' => '#FFFFFF',
        );
    }

    /**
     * Get CSS classes based on device and settings
     */
    public static function get_button_classes($post_id = null)
    {
        $settings = self::get_button_style_settings();
        $classes = array('wape-btn');

        // Add style class
        $classes[] = 'wape-btn--' . sanitize_html_class($settings['style']);

        // Add animation class if set
        if (!empty($settings['animation']) && $settings['animation'] !== 'none') {
            $classes[] = 'wape-animate--' . sanitize_html_class($settings['animation']);
        }

        // Add shadow class if enabled
        if ($settings['shadow'] === 'yes') {
            $classes[] = 'wape-btn--shadow';
        }

        // Add dark mode detection
        if ($settings['dark_mode_enabled'] === 'yes') {
            $classes[] = 'wape-btn--dark-aware';
        }

        return implode(' ', apply_filters('wape_button_classes', $classes, $post_id));
    }

    /**
     * Get wrapper classes based on position and settings
     */
    public static function get_wrapper_classes()
    {
        $settings = self::get_button_style_settings();
        $classes = array('wape-button-wrap');

        // Add position class
        $classes[] = 'wape-position--' . sanitize_html_class($settings['position']);

        // Add device visibility classes
        if ($settings['show_on_mobile'] !== 'yes') {
            $classes[] = 'wape-hide-mobile';
        }

        if ($settings['show_on_desktop'] !== 'yes') {
            $classes[] = 'wape-hide-desktop';
        }

        return implode(' ', apply_filters('wape_wrapper_classes', $classes));
    }

    /**
     * Generate inline CSS for button styling
     */
    public static function get_inline_styles()
    {
        $settings = self::get_button_style_settings();
        $is_dark_mode = self::is_dark_mode_enabled();

        $primary_color = $is_dark_mode ? $settings['dark_mode_primary'] : $settings['primary_color'];
        $text_color = $is_dark_mode ? $settings['dark_mode_text'] : $settings['text_color'];
        $hover_color = $settings['hover_color'];

        $css = array(
            '--wape-primary-color' => $primary_color,
            '--wape-text-color' => $text_color,
            '--wape-hover-color' => $hover_color,
            '--wape-border-radius' => intval($settings['border_radius']) . 'px',
            '--wape-font-size' => intval($settings['font_size']) . 'px',
            '--wape-padding' => str_replace(',', 'px ', $settings['padding']) . 'px',
            '--wape-icon-size' => intval($settings['icon_size']) . 'px',
            '--wape-mobile-breakpoint' => intval($settings['mobile_breakpoint']) . 'px',
        );

        $styles = '';
        foreach ($css as $key => $value) {
            $styles .= $key . ': ' . esc_attr($value) . '; ';
        }

        return $styles;
    }

    /**
     * Check if dark mode is enabled/active
     */
    public static function is_dark_mode_enabled()
    {
        $settings = self::get_button_style_settings();

        if ($settings['dark_mode_enabled'] !== 'yes') {
            return false;
        }

        // Check if user prefers dark mode (CSS media query via JS detection)
        // This would be handled on the frontend with JS
        return apply_filters('wape_is_dark_mode', false);
    }

    /**
     * Check if button should show on current device
     */
    public static function should_show_on_device()
    {
        $settings = self::get_button_style_settings();
        $is_mobile = wp_is_mobile();

        if ($is_mobile && $settings['show_on_mobile'] !== 'yes') {
            return false;
        }

        if (!$is_mobile && $settings['show_on_desktop'] !== 'yes') {
            return false;
        }

        return true;
    }

    /**
     * Save button style settings
     */
    public static function save_style_settings($settings)
    {
        // Validate and sanitize
        $validated = self::validate_style_settings($settings);
        update_option('wape_button_style_settings', $validated);

        return true;
    }

    /**
     * Validate style settings
     */
    private static function validate_style_settings($settings)
    {
        $defaults = self::get_style_defaults();
        $validated = array();

        foreach ($defaults as $key => $default_value) {
            if (isset($settings[$key])) {
                $value = $settings[$key];

                switch ($key) {
                    case 'style':
                    case 'position':
                    case 'animation':
                        $validated[$key] = in_array($value, array_keys(array_merge(self::$button_styles, self::$button_positions))) ? $value : $default_value;
                        break;

                    case 'primary_color':
                    case 'text_color':
                    case 'hover_color':
                    case 'dark_mode_primary':
                    case 'dark_mode_text':
                        $validated[$key] = sanitize_hex_color($value) ?: $default_value;
                        break;

                    case 'border_radius':
                    case 'font_size':
                    case 'icon_size':
                    case 'mobile_breakpoint':
                        $validated[$key] = absint($value) ?: $default_value;
                        break;

                    case 'padding':
                        $validated[$key] = sanitize_text_field($value);
                        break;

                    case 'show_on_mobile':
                    case 'show_on_desktop':
                    case 'shadow':
                    case 'dark_mode_enabled':
                        $validated[$key] = in_array($value, array('yes', 'no')) ? $value : $default_value;
                        break;

                    default:
                        $validated[$key] = $value;
                }
            } else {
                $validated[$key] = $default_value;
            }
        }

        return $validated;
    }
}
