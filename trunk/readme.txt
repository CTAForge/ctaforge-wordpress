=== CTAForge ===
Contributors:      ctaforge
Tags:              email marketing, newsletter, signup form, contact sync, WooCommerce
Requires at least: 6.0
Tested up to:      6.7
Requires PHP:      8.0
Stable tag:        1.0.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Connect your WordPress site to CTAForge — embed signup forms, sync contacts and track email engagement in real time.

== Description ==

**CTAForge** is a multi-tenant email marketing platform built for modern businesses. This official plugin connects your WordPress site to CTAForge so you can:

* 📋 **Embed signup forms** anywhere using the `[ctaforge_form]` shortcode or the native Gutenberg block
* 🔄 **Auto-sync new users** — every new WordPress registration can be subscribed to a CTAForge list automatically
* 🛒 **WooCommerce integration** — sync customers and purchase events as contacts and custom field data
* 📊 **Real-time analytics** — track opens, clicks and conversions directly from your CTAForge dashboard

= Shortcode Usage =

Basic form:
`[ctaforge_form list_id="your-list-uuid"]`

With name fields and custom button:
`[ctaforge_form list_id="uuid" fields="first_name,last_name" button="Sign me up!" title="Get the newsletter"]`

= Attributes =

* `list_id` — UUID of the CTAForge list (defaults to the list configured in Settings)
* `title` — Form heading text
* `description` — Optional subtitle
* `button` — Submit button label
* `placeholder` — Email input placeholder
* `fields` — Comma-separated extra fields: `first_name`, `last_name`
* `success` — Message displayed on successful submission
* `error` — Message displayed on failure
* `class` — Extra CSS classes applied to the form wrapper

= Gutenberg Block =

The **CTAForge Signup Form** block is available in the Widgets category of the block editor. All attributes above are configurable via the block sidebar.

= WooCommerce =

When WooCommerce is active, CTAForge automatically:

1. Subscribes customers to your default list at checkout
2. Tags them as `woocommerce-customer`
3. Tags completed orders as `woocommerce-purchased`
4. Tags refunded customers as `woocommerce-refunded`

This allows you to segment your list and send targeted campaigns to buyers.

= Requirements =

* A [CTAForge account](https://ctaforge.com) (free plan available)
* PHP 8.0 or higher
* WordPress 6.0 or higher

== Installation ==

1. Upload the `ctaforge` folder to `/wp-content/plugins/`
2. Activate the plugin in **Plugins → Installed Plugins**
3. Go to **Settings → CTAForge** and enter your API key
4. Add `[ctaforge_form]` to any page, post or widget

== Frequently Asked Questions ==

= Where do I find my API key? =

Log in to your CTAForge account → Settings → API Keys → Create new key.

= Can I use multiple forms on the same page? =

Yes — each `[ctaforge_form]` shortcode renders an independent form. Use different `list_id` attributes to subscribe to different lists.

= Does the plugin work with page builders? =

Yes. The shortcode works in any builder that supports WordPress shortcodes (Elementor, Beaver Builder, Divi, etc.). A dedicated Elementor widget is on the roadmap.

= Is WooCommerce integration required? =

No. WooCommerce features are only loaded when WooCommerce is active.

= Does it work with multisite? =

Yes — each subsite can have its own API key and default list configured independently.

== Screenshots ==

1. Signup form on the front end
2. CTAForge Settings page in wp-admin
3. Gutenberg block with sidebar controls
4. WooCommerce order sync in action

== Changelog ==

= 1.0.0 =
* Initial release
* [ctaforge_form] shortcode
* Gutenberg block
* WordPress user sync
* WooCommerce order sync (customers, completed, refunded)
* Admin settings page with connection test

== Upgrade Notice ==

= 1.0.0 =
Initial release — no upgrade steps required.
