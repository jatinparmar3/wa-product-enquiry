<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles message scheduling - delayed/scheduled WhatsApp message sending
 */
class WAPE_Message_Scheduler
{
    public static function init()
    {
        add_action('init', array(__CLASS__, 'register_schedule_event'));
        add_action('wape_send_scheduled_message', array(__CLASS__, 'process_scheduled_messages'));
    }

    /**
     * Register WordPress cron event
     */
    public static function register_schedule_event()
    {
        if (!wp_next_scheduled('wape_send_scheduled_message')) {
            wp_schedule_event(time(), 'hourly', 'wape_send_scheduled_message');
        }
    }

    /**
     * Get scheduled messages for a post
     */
    public static function get_scheduled_messages($post_id)
    {
        return get_post_meta($post_id, '_wape_scheduled_messages', true) ?: array();
    }

    /**
     * Schedule a message to be sent at a specific time
     */
    public static function schedule_message($post_id, $phone, $message, $scheduled_time)
    {
        $messages = self::get_scheduled_messages($post_id);
        
        $message_id = wp_generate_uuid4();
        $messages[$message_id] = array(
            'phone' => WAPE_Settings::sanitize_whatsapp_number($phone),
            'message' => $message,
            'scheduled_time' => strtotime($scheduled_time),
            'created_at' => current_time('timestamp'),
            'status' => 'pending', // pending, sent, failed
        );

        update_post_meta($post_id, '_wape_scheduled_messages', $messages);

        return $message_id;
    }

    /**
     * Process all due scheduled messages
     */
    public static function process_scheduled_messages()
    {
        $current_time = current_time('timestamp');

        // Get all posts with scheduled messages
        $args = array(
            'meta_key' => '_wape_scheduled_messages',
            'posts_per_page' => -1,
            'fields' => 'ids',
        );

        $posts = get_posts($args);

        foreach ($posts as $post_id) {
            $messages = self::get_scheduled_messages($post_id);

            foreach ($messages as $message_id => &$msg) {
                if ($msg['status'] === 'pending' && $msg['scheduled_time'] <= $current_time) {
                    // Build WhatsApp URL
                    $wa_url = WAPE_Message_Builder::build_whatsapp_url($msg['phone'], $msg['message']);

                    if (!empty($wa_url)) {
                        // Log the scheduled message sending
                        self::log_scheduled_message($post_id, $message_id, $msg, $wa_url);
                        $msg['status'] = 'sent';
                    } else {
                        $msg['status'] = 'failed';
                    }
                }
            }

            update_post_meta($post_id, '_wape_scheduled_messages', $messages);
        }
    }

    /**
     * Log scheduled message activity
     */
    private static function log_scheduled_message($post_id, $message_id, $message_data, $wa_url)
    {
        $log = array(
            'post_id' => $post_id,
            'message_id' => $message_id,
            'phone' => $message_data['phone'],
            'sent_at' => current_time('timestamp'),
            'wa_url' => $wa_url,
        );

        // Store log
        add_post_meta($post_id, '_wape_message_log', $log);
    }

    /**
     * Cancel a scheduled message
     */
    public static function cancel_scheduled_message($post_id, $message_id)
    {
        $messages = self::get_scheduled_messages($post_id);

        if (isset($messages[$message_id])) {
            unset($messages[$message_id]);
            update_post_meta($post_id, '_wape_scheduled_messages', $messages);
            return true;
        }

        return false;
    }

    /**
     * Get message scheduling status
     */
    public static function get_message_status($post_id, $message_id)
    {
        $messages = self::get_scheduled_messages($post_id);

        if (isset($messages[$message_id])) {
            return $messages[$message_id]['status'];
        }

        return null;
    }
}
