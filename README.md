# Sequential PDF Invoices

Generate sequential PDF invoices from orders, attach them to customer emails, and let customers download them from My Account. Compatible with WooCommerce.

## Features

- Sequential invoice numbers with configurable prefix, suffix and zero-padding
- On-the-fly PDF generation (A4 / A5 / Letter / Legal, portrait or landscape)
- Attach invoices to processing, completed, on-hold and customer-invoice emails
- Download from My Account (order list + order details)
- Admin order list column, bulk generate action and order meta box
- Company block: logo, address, CUI, trade register, bank / IBAN, footer
- Custom tax label and correct VAT for tax-inclusive or tax-exclusive stores
- Compatible with High-Performance Order Storage (HPOS)
- Theme override: copy `templates/invoice.php` to `yourtheme/woo-pdf-invoice/`

## Requirements

- WordPress 6.2+
- WooCommerce 6.0+
- PHP 7.4+ with `ext-dom` and `ext-mbstring`

dompdf 3.1.6 is shipped in `vendor/`. No Composer step is required to install the plugin.

## Installation

1. Download the repository as a ZIP, or clone it into `wp-content/plugins/woo-pdf-invoice`.
2. Make sure WooCommerce is active.
3. Activate **Sequential PDF Invoices** from the Plugins screen.
4. Go to **WooCommerce → PDF Invoices** and fill in company details and numbering.

## Configuration

| Setting | Default | Notes |
| --- | --- | --- |
| Company name, address, city, postcode, country | empty | Printed in the invoice header |
| Tax ID / CUI, trade register, email, phone | empty | Optional legal block |
| Bank / IBAN | empty | Optional payment details |
| Logo | none | Media library attachment |
| Invoice prefix / suffix / padding | `F-` / empty / `4` | Example: `F-0001` |
| Due days | `15` | Used for the due date |
| Tax label | `VAT` | Shown next to the tax total |
| Paper size / orientation | A4 / portrait | Passed to dompdf |
| Attach to emails | processing, completed | Also supports on-hold and customer invoice |
| Footer | empty | Printed at the bottom of the PDF |

The invoice counter is stored separately from settings. Changing the prefix or padding does not reset the sequence, and numbers are never reused.

## Usage

1. After an order exists, generate the invoice from the order list or the order edit screen.
2. The customer gets an **Invoice (PDF)** link in My Account once the invoice exists.
3. If email attachment is enabled, the same PDF is attached to the selected WooCommerce emails.

Admins can generate, download or delete an invoice from the order meta box. Bulk generate is available from the orders list.

## Customizing the template

Copy:

```
templates/invoice.php
```

to:

```
your-theme/woo-pdf-invoice/invoice.php
```

The plugin loads the theme file first. Styles must stay inline because the HTML is rendered by dompdf.

## Folder structure

```
woo-pdf-invoice/
├── woo-pdf-invoice.php
├── uninstall.php
├── composer.json
├── readme.txt
├── assets/
│   ├── css/admin.css
│   └── js/admin.js
├── languages/
├── src/
│   ├── Plugin.php
│   ├── Installer.php
│   ├── Admin/
│   ├── Core/
│   ├── Email/
│   ├── Frontend/
│   ├── Invoice/
│   └── Pdf/
├── templates/
│   └── invoice.php
└── vendor/
```

## License

GPL-2.0-or-later, same as WordPress and WooCommerce.
