# WA Product Enquiry

WA Product Enquiry is a WordPress plugin that adds WhatsApp enquiry buttons to WooCommerce products and selected custom post type single pages. It also supports order-related WhatsApp quick links for store owners and customers.

## Repository Tags

`WordPress`, `WooCommerce`, `WhatsApp`, `Product Enquiry`, `Order Notifications`, `Custom Post Types`, `WhatsApp Button`, `WP Plugin`

## Short Description

Add a WhatsApp enquiry button to product pages, build pre-filled enquiry messages automatically, and generate WhatsApp quick links for WooCommerce order updates.

## Features

- Shows a WhatsApp button on WooCommerce single product pages.
- Shows a WhatsApp button on selected custom post type single pages.
- Builds dynamic enquiry messages with product/post details.
- Supports two shortcodes: `wa_product_button` and `wa_order_button`.
- Adds WhatsApp quick-link notes for WooCommerce orders.
- Generates customer WhatsApp status links when order status changes.
- Lets you customize button text and message templates.

## Installation

1. Upload the plugin folder to `wp-content/plugins/`.
2. Activate the plugin from the WordPress admin dashboard.
3. Open `WP Admin -> WhatsApp Notify`.
4. Configure your WhatsApp number and display options.
5. Save the settings.

## Setup

- Enter the admin WhatsApp number in digits-only format, including country code.
- Enable display on WooCommerce single product pages if needed.
- Enable display on selected custom post type single pages if needed.
- Select the custom post types you want to support.
- Choose which product fields should appear in the enquiry message.
- Customize the message templates for products and WooCommerce orders.

## Shortcodes

- `[wa_product_button]` - Displays a WhatsApp enquiry button for product-style content.
- `[wa_order_button]` - Displays a WhatsApp order button for supported order-related use cases.

## Message Placeholders

### Post and Product Placeholders

- `{title}`
- `{price}`
- `{category}`
- `{sku}`
- `{excerpt}`
- `{url}`
- `{image}`
- `{custom_fields}`
- `{post_type}`
- `{site_name}`

### WooCommerce Owner Template Placeholders

- `{order_id}`
- `{customer_name}`
- `{customer_phone}`
- `{order_total}`
- `{items}`
- `{admin_order_url}`

### WooCommerce Customer Template Placeholders

- `{order_id}`
- `{customer_name}`
- `{order_status}`
- `{order_total}`
- `{order_url}`

## How It Works

- The plugin creates WhatsApp `wa.me` deep links.
- Messages are pre-filled, but the actual message is sent only after the user opens WhatsApp and confirms it.
- Order-related links and notes are stored in WooCommerce order meta and order notes.

## Requirements

- WordPress
- WooCommerce for product and order features
- A valid WhatsApp number with country code

## Example Use Case

Use this plugin on an online store to let customers quickly ask about a product, request pricing, or get order updates through WhatsApp.

## Notes

- This plugin does not send messages directly from the server.
- For fully automated WhatsApp message delivery, you would need a WhatsApp Business API integration.
