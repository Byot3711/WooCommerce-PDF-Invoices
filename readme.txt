=== WooCommerce PDF Invoices ===
Contributors: woopdfinvoice
Tags: woocommerce, invoice, pdf, billing, factura
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 7.4
WC requires at least: 6.0
WC tested up to: 9.5
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate professional, sequential PDF invoices for WooCommerce orders, attach them to customer emails and let customers download them from their account.

== Description ==

WooCommerce PDF Invoices (Facturare PDF) generates clean, professional PDF invoices directly from your WooCommerce orders.

Features:

* Sequential invoice numbering with configurable prefix, suffix and zero-padding.
* Automatic PDF generation via dompdf (A4/A5/Letter/Legal, portrait or landscape).
* Attach the invoice PDF to the processing, completed, on-hold or customer-invoice emails.
* Customer download buttons in "My Account" orders list and order details.
* Secure access: nonce-protected links for admins/customers plus optional token-based guest links.
* Admin order-list column, bulk "Generate invoices" action and an order meta box.
* Company details block: logo, address, tax ID (CUI), trade register no., bank/IBAN, footer.
* Custom tax label and correct VAT display for tax-inclusive and tax-exclusive stores.
* Fully compatible with WooCommerce High-Performance Order Storage (HPOS).
* Theme-overridable invoice template (copy `templates/invoice.php` to `yourtheme/woo-pdf-invoice/`).

== Installation ==

1. Upload the `woo-pdf-invoice` folder to `/wp-content/plugins/`, or install it via the Plugins screen.
2. Make sure WooCommerce is active.
3. Activate the plugin.
4. Go to WooCommerce → PDF Invoices and fill in your company details and numbering preferences.

The plugin ships with its Composer dependencies (dompdf) inside the `vendor/` folder, so no build step is required.

== Frequently Asked Questions ==

= Does it work with High-Performance Order Storage (HPOS)? =

Yes. All order data is read and written through the WooCommerce CRUD API, so both classic post storage and HPOS are supported.

= How do customers download their invoice? =

Once an invoice is generated, a "Invoice (PDF)" link appears on the customer's order list and a download button on the order details page. Admins can also generate and download from the order list or edit screen.

= Can I customize the invoice layout? =

Yes. Copy `templates/invoice.php` to `yourtheme/woo-pdf-invoice/invoice.php` and edit it. The plugin automatically uses the theme override.

= How are invoices numbered? =

Numbers are assigned sequentially and never reused. The counter is stored independently of settings, so changing the format does not reset the sequence.

== Changelog ==

= 1.0.0 =
* Initial release.
