# IloByte Widgets for WordPress

Embed your [IloByte Pro](https://www.ilobyte.com) appointment booking and mini
store on any WordPress page. A booking made on your website creates the same
appointment, invoice and payment records as a walk-in at your counter — no
double entry, no stock drift.

## Shortcodes

| Shortcode | Renders |
|---|---|
| `[ilobyte_booking]` | Appointment picker: service → day → time → details. Deposits collected through your own payment gateway. |
| `[ilobyte_shop]` | Product grid with cart and checkout. Orders are priced by your workspace and paid through your own gateway. |

## Setup

1. In your IloByte Pro workspace: **Settings → Apps & Devices → Website
   widgets** → *Generate widget key*.
2. In WordPress: **Settings → IloByte Widgets** → paste your workspace address
   (e.g. `https://yourbusiness.ilobyte.com`), the `pk_…` widget key, and your
   workspace slug.
3. Add a shortcode to any page or post.

The widget key is **publishable** — it can only do what any visitor to your
booking page could do, and your workspace can restrict it to your own domains
(Website widgets → *Allowed websites*).

## Design notes

- The plugin renders the widget **iframe directly from PHP** and ships its own
  small resize listener — it loads no executable code from external servers,
  which keeps it comfortably inside the WordPress.org plugin guidelines.
- Payment always opens on the gateway's own page in a new tab, never inside
  the embedded frame.
- The embedded pages are served by the business's own IloByte workspace and
  report their height via `postMessage` (`{type: 'ilobyte:resize', height}`).

## Development

The plugin is the thin layer; the widgets themselves live in the IloByte Pro
platform. To package for distribution:

```sh
zip -r ilobyte-widgets.zip ilobyte-widgets -x "*.DS_Store"
```

## License

GPL-2.0-or-later — see `ilobyte-widgets/readme.txt` and the license header in
`ilobyte-widgets/ilobyte-widgets.php`. https://www.gnu.org/licenses/gpl-2.0.html
