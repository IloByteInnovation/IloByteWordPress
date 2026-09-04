=== IloByte Widgets — Bookings & Store ===
Contributors: ilobyte
Tags: booking, appointments, store, ecommerce, nigeria
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed your IloByte Pro appointment booking and mini store on any WordPress page with a shortcode.

== Description ==

If your business runs on [IloByte Pro](https://www.ilobyte.com), this plugin puts your live booking calendar and store on your existing WordPress site. A booking made on your website creates the same appointment, invoice and payment records as a walk-in at your counter — no double entry, no stock drift.

* `[ilobyte_booking]` — appointment picker: service, day, time, customer details. Deposits are collected through your own payment gateway.
* `[ilobyte_shop]` — product grid with cart and checkout. Orders are priced by your workspace and paid through your own gateway.

**An IloByte Pro workspace is required.** The widgets are served by your workspace; this plugin embeds them and ships no code from external servers.

== Installation ==

1. Install and activate the plugin.
2. In your IloByte Pro workspace, open Settings → Apps & Devices → Website widgets and generate a widget key.
3. In WordPress, open Settings → IloByte Widgets and paste your workspace address, widget key and slug.
4. Add `[ilobyte_booking]` or `[ilobyte_shop]` to any page or post.

== Frequently Asked Questions ==

= Do I need an IloByte account? =
Yes. The widgets are a window into your IloByte Pro workspace — bookings and orders land there.

= Is the widget key safe to publish? =
Yes. It is a publishable key: it can only do what any visitor to your booking page could do, and your workspace can restrict it to your own domains.

= Where does payment happen? =
On your own payment gateway's page, opened in a new tab — never inside the embedded frame.

== Privacy ==

The embedded widgets are served by your IloByte Pro workspace. Details a visitor enters (name, phone, email, booking or order contents) are sent to your workspace to create the booking or order, under your business's own privacy policy.

== Changelog ==

= 1.0.0 =
* First release: booking and shop shortcodes, settings page, iframe auto-resize.
