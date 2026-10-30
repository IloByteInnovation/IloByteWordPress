=== IloByte Widgets — Bookings & Store ===
Contributors: ilobyte
Tags: booking, appointments, store, ecommerce, nigeria
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed your IloByte Pro booking calendar, mini store and live service menu on any WordPress page. Prices always match your workspace.

== Description ==

If your business runs on [IloByte Pro](https://www.ilobyte.com), this plugin puts your live booking calendar and store on your existing WordPress site. A booking made on your website creates the same appointment, invoice and payment records as a walk-in at your counter — no double entry, no stock drift.

* `[ilobyte_booking]` — appointment picker: service, day, time, customer details. Deposits are collected through your own payment gateway.
* `[ilobyte_shop]` — product grid with cart and checkout. Orders are priced by your workspace and paid through your own gateway.
* `[ilobyte_menu]` — your live service menu, grouped by category, with a "Book" button per service.
* `[ilobyte_price]` and `[ilobyte_duration]` — one service's live price or duration, anywhere in your text.

**An IloByte Pro workspace is required.** The widgets are served by your workspace; this plugin embeds them and ships no code from external servers.

= Live prices and durations =

IloByte Pro is the single source of truth for your services, prices, durations and availability. WordPress only displays them — nothing is edited here, and no secret key is stored. Change a price in IloByte Pro and every page that shows it updates within 10 minutes.

**Open the calendar on one service**

* `[ilobyte_booking service="ITM-0002"]` — the booking calendar opens on the service with item code ITM-0002.
* Without a `service` attribute, the calendar reads `?service=ITM-0002` from the page address, so a "Book" button anywhere can link to `/book-a-session/?service=ITM-0002`.

**Show a price or duration inside your own text**

* `[ilobyte_price service="ITM-0002"]` → ₦30,000
* `[ilobyte_duration service="ITM-0002"]` → 60 Minutes (90 → 1 Hour 30 Minutes, 120 → 2 Hours)
* `[ilobyte_duration service="ITM-0002" format="short"]` → 60 min

Both work inside other markup, including page-builder HTML blocks that run shortcodes. An unknown code shows nothing to visitors; administrators see a hint in the page source.

**Show the whole menu**

`[ilobyte_menu book_url="/book-a-session/"]` lists every bookable service by category: name, duration, price and a one-line description, with a "Book" link to `/book-a-session/?service=CODE`. Options:

* `category="Massages"` — only this category (several: `category="Massages, Facials"`).
* `book_url="/book-a-session/"` — add a Book link per service.
* `book_label="Book now"` — change the link text.
* `images="yes"` — show a thumbnail per service.
* `headings="no"` — hide the category headings.

The menu inherits your theme's fonts and colours.

== Installation ==

1. Install and activate the plugin.
2. In your IloByte Pro workspace, open Settings → Apps & Devices → Website widgets and generate a widget key.
3. In WordPress, open Settings → IloByte Widgets and paste your workspace address, widget key and slug.
4. Add `[ilobyte_booking]`, `[ilobyte_shop]` or `[ilobyte_menu]` to any page or post.

== Frequently Asked Questions ==

= Do I need an IloByte account? =
Yes. The widgets are a window into your IloByte Pro workspace — bookings and orders land there.

= Is the widget key safe to publish? =
Yes. It is a publishable key: it can only do what any visitor to your booking page could do, and your workspace can restrict it to your own domains.

= Where does payment happen? =
On your own payment gateway's page, opened in a new tab — never inside the embedded frame.

= Where do I change a price? =
In IloByte Pro — the website updates within 10 minutes, or press Refresh now under Settings → IloByte Widgets. Prices, durations and service names cannot be edited from WordPress.

= What if IloByte Pro cannot be reached? =
The plugin keeps the last good copy of your service menu and shows it until IloByte Pro answers again. If there has never been a successful sync, visitors see nothing where a price or menu would be, and administrators see a short notice.

= Where do I find a service's item code? =
On the item in your IloByte Pro workspace (for example ITM-0002).

== Privacy ==

The embedded widgets are served by your IloByte Pro workspace. Details a visitor enters (name, phone, email, booking or order contents) are sent to your workspace to create the booking or order, under your business's own privacy policy.

For `[ilobyte_price]`, `[ilobyte_duration]` and `[ilobyte_menu]`, your WordPress server calls api.ilobyte.com to read your public service menu. No visitor data is sent — only your publishable widget key and workspace slug.

== External services ==

This plugin connects to the IloByte Pro API (https://api.ilobyte.com), operated by IloByte Innovation, to read your workspace's public service menu: service names, descriptions, images, categories, durations and prices.

* When: from your WordPress server, when a page using `[ilobyte_price]`, `[ilobyte_duration]` or `[ilobyte_menu]` is viewed and the cached copy is older than 10 minutes, or when an administrator presses Refresh now.
* What is sent: your publishable widget key and workspace slug, from Settings → IloByte Widgets. Nothing about your visitors is sent.

The booking and store widgets are pages of your IloByte Pro workspace (https://yourbusiness.ilobyte.com) shown in a frame; they load in the visitor's browser.

IloByte terms of service: https://www.ilobyte.com/terms
IloByte privacy policy: https://www.ilobyte.com/privacy

== Changelog ==

= 1.1.0 =
* New: live prices and durations from IloByte Pro — `[ilobyte_price]`, `[ilobyte_duration]` and the `[ilobyte_menu]` service menu. IloByte Pro stays the single source of truth; nothing is edited in WordPress.
* New: `[ilobyte_booking service="ITM-0002"]` opens the calendar on one service; a `?service=` link to the booking page does the same.
* New: "Live prices" box under Settings → IloByte Widgets with the last sync time, service count and a Refresh now button.
* The service menu is cached for 10 minutes, and the last good copy is shown if IloByte Pro cannot be reached.
* Deleting the plugin now removes its settings and cached menu.

= 1.0.1 =
* The widget iframes now carry descriptive accessibility titles ("Appointment booking", "Online store") instead of a "Powered by" credit. No visitor-facing attribution remains.

= 1.0.0 =
* First release: booking and shop shortcodes, settings page, iframe auto-resize.
