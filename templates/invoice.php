<?php
/**
 * Invoice template.
 *
 * Rendered server-side and fed to dompdf, so all styles are inlined.
 *
 * Variables injected by WooPdfInvoice\Pdf\Renderer::render():
 *   string $invoice_number, $invoice_date, $due_date, $order_number,
 *          $order_date, $payment_method, $billing, $shipping,
 *          $tax_label, $tax_amount, $currency, $currency_symbol,
 *          $footer, $logo_data_uri
 *   array  $company, $items, $totals
 *   bool   $prices_include_tax
 *   float  $total_tax
 *
 * @package WooPdfInvoice
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lang        = str_replace( '_', '-', (string) get_locale() );
$seller_name = '' !== trim( (string) $company['name'] ) ? $company['name'] : get_bloginfo( 'name' );
$city_line   = trim( $company['postcode'] . ' ' . $company['city'] );
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( $lang ); ?>">
<head>
	<meta charset="utf-8" />
	<title><?php echo esc_html( $invoice_number ); ?></title>
	<style>
		@page { margin: 16mm 14mm; }
		* { box-sizing: border-box; }
		body {
			font-family: 'DejaVu Sans', sans-serif;
			font-size: 10.5px;
			line-height: 1.45;
			color: #1a1a1a;
			margin: 0;
		}
		table { width: 100%; border-collapse: collapse; }
		td { vertical-align: top; }

		.invoice-header { margin-bottom: 18px; }
		.invoice-brand { font-size: 20px; font-weight: bold; color: #111; }
		.invoice-logo { margin-bottom: 8px; }
		.invoice-logo img { max-width: 200px; max-height: 90px; }
		.invoice-title {
			text-align: right;
			font-size: 26px;
			font-weight: bold;
			color: #0b3d66;
		}
		.invoice-number { font-size: 15px; color: #333; }

		.meta-table td { padding: 2px 0; }
		.meta-table .label { color: #666; padding-right: 14px; white-space: nowrap; }
		.company-block { font-size: 10px; color: #333; }

		.addresses { margin-top: 18px; }
		.addresses td { width: 50%; }
		.address-box {
			border: 1px solid #e2e2e2;
			border-radius: 4px;
			padding: 10px 12px;
			min-height: 70px;
		}
		.address-box h3 {
			margin: 0 0 6px;
			font-size: 10.5px;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			color: #0b3d66;
		}

		.items { margin-top: 20px; }
		.items th, .items td { border: 1px solid #ddd; padding: 7px 9px; text-align: left; }
		.items thead th {
			background: #0b3d66;
			color: #ffffff;
			font-size: 9.5px;
			text-transform: uppercase;
		}
		.items tbody tr:nth-child(even) td { background: #f7f9fb; }
		.num { text-align: right; white-space: nowrap; }

		.totals-wrap { margin-top: 12px; }
		.totals { width: 46%; margin-left: auto; }
		.totals td { padding: 4px 8px; }
		.totals .label { color: #666; }
		.totals .value { text-align: right; white-space: nowrap; }
		.totals .grand td {
			border-top: 2px solid #0b3d66;
			font-size: 13px;
			font-weight: bold;
		}

		.tax-note { margin-top: 10px; color: #555; font-size: 9.5px; }
		.payment-details { margin-top: 16px; font-size: 9.5px; color: #333; }
		.footer {
			margin-top: 24px;
			padding-top: 10px;
			border-top: 1px solid #e2e2e2;
			font-size: 9px;
			color: #777;
		}
	</style>
</head>
<body>

	<table class="invoice-header">
		<tr>
			<td class="invoice-brand">
				<?php if ( $logo_data_uri ) : ?>
					<div class="invoice-logo"><img src="<?php echo esc_attr( $logo_data_uri ); ?>" alt="" /></div>
				<?php endif; ?>
				<div><?php echo esc_html( $seller_name ); ?></div>
			</td>
			<td class="invoice-title">
				<?php echo esc_html__( 'INVOICE', 'woo-pdf-invoice' ); ?><br />
				<span class="invoice-number"><?php echo esc_html( $invoice_number ); ?></span>
			</td>
		</tr>
	</table>

	<table class="invoice-meta">
		<tr>
			<td>
				<div class="company-block">
					<?php if ( '' !== $company['address'] ) : ?><?php echo esc_html( $company['address'] ); ?><br /><?php endif; ?>
					<?php if ( '' !== $city_line ) : ?><?php echo esc_html( $city_line ); ?><br /><?php endif; ?>
					<?php if ( '' !== $company['country'] ) : ?><?php echo esc_html( $company['country'] ); ?><br /><?php endif; ?>
					<?php if ( '' !== $company['tax_id'] ) : ?>
						<?php echo esc_html__( 'VAT / CUI', 'woo-pdf-invoice' ); ?>: <?php echo esc_html( $company['tax_id'] ); ?><br />
					<?php endif; ?>
					<?php if ( '' !== $company['reg_no'] ) : ?>
						<?php echo esc_html__( 'Reg. no.', 'woo-pdf-invoice' ); ?>: <?php echo esc_html( $company['reg_no'] ); ?><br />
					<?php endif; ?>
					<?php if ( '' !== $company['email'] ) : ?><?php echo esc_html( $company['email'] ); ?><br /><?php endif; ?>
					<?php if ( '' !== $company['phone'] ) : ?><?php echo esc_html( $company['phone'] ); ?><?php endif; ?>
				</div>
			</td>
			<td>
				<table class="meta-table">
					<tr>
						<td class="label"><?php echo esc_html__( 'Issue date', 'woo-pdf-invoice' ); ?></td>
						<td><?php echo esc_html( $invoice_date ); ?></td>
					</tr>
					<?php if ( $due_date ) : ?>
						<tr>
							<td class="label"><?php echo esc_html__( 'Due date', 'woo-pdf-invoice' ); ?></td>
							<td><?php echo esc_html( $due_date ); ?></td>
						</tr>
					<?php endif; ?>
					<tr>
						<td class="label"><?php echo esc_html__( 'Order', 'woo-pdf-invoice' ); ?></td>
						<td><?php echo esc_html( $order_number ); ?></td>
					</tr>
					<tr>
						<td class="label"><?php echo esc_html__( 'Order date', 'woo-pdf-invoice' ); ?></td>
						<td><?php echo esc_html( $order_date ); ?></td>
					</tr>
					<?php if ( $payment_method ) : ?>
						<tr>
							<td class="label"><?php echo esc_html__( 'Payment method', 'woo-pdf-invoice' ); ?></td>
							<td><?php echo esc_html( $payment_method ); ?></td>
						</tr>
					<?php endif; ?>
				</table>
			</td>
		</tr>
	</table>

	<table class="addresses">
		<tr>
			<td>
				<div class="address-box">
					<h3><?php echo esc_html__( 'Bill to', 'woo-pdf-invoice' ); ?></h3>
					<?php echo $billing; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</td>
			<td>
				<div class="address-box">
					<h3><?php echo esc_html__( 'Ship to', 'woo-pdf-invoice' ); ?></h3>
					<?php echo $shipping; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</td>
		</tr>
	</table>

	<table class="items">
		<thead>
			<tr>
				<th><?php echo esc_html__( 'Product', 'woo-pdf-invoice' ); ?></th>
				<th><?php echo esc_html__( 'SKU', 'woo-pdf-invoice' ); ?></th>
				<th class="num"><?php echo esc_html__( 'Qty', 'woo-pdf-invoice' ); ?></th>
				<th class="num"><?php echo esc_html__( 'Unit price', 'woo-pdf-invoice' ); ?></th>
				<th class="num"><?php echo esc_html__( 'Amount', 'woo-pdf-invoice' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $items as $item ) : ?>
				<tr>
					<td><?php echo esc_html( $item['name'] ); ?></td>
					<td><?php echo esc_html( $item['sku'] ); ?></td>
					<td class="num"><?php echo esc_html( (string) $item['quantity'] ); ?></td>
					<td class="num"><?php echo esc_html( $item['unit_price'] ); ?></td>
					<td class="num"><?php echo esc_html( $item['total'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<div class="totals-wrap">
		<table class="totals">
			<?php foreach ( $totals as $row ) : ?>
				<tr class="<?php echo ! empty( $row['highlight'] ) ? 'grand' : ''; ?>">
					<td class="label"><?php echo esc_html( $row['label'] ); ?></td>
					<td class="value"><?php echo esc_html( $row['value'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		</table>
	</div>

	<?php if ( $prices_include_tax && $total_tax > 0 ) : ?>
		<p class="tax-note">
			<?php
			printf(
				/* translators: %1$s: tax label, %2$s: formatted tax amount */
				esc_html__( 'Prices include %1$s: %2$s', 'woo-pdf-invoice' ),
				esc_html( $tax_label ),
				esc_html( $tax_amount )
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( '' !== $company['bank'] || '' !== $company['iban'] ) : ?>
		<div class="payment-details">
			<strong><?php echo esc_html__( 'Payment details', 'woo-pdf-invoice' ); ?></strong><br />
			<?php if ( '' !== $company['bank'] ) : ?>
				<?php echo esc_html__( 'Bank', 'woo-pdf-invoice' ); ?>: <?php echo esc_html( $company['bank'] ); ?><br />
			<?php endif; ?>
			<?php if ( '' !== $company['iban'] ) : ?>
				<?php echo esc_html__( 'IBAN', 'woo-pdf-invoice' ); ?>: <?php echo esc_html( $company['iban'] ); ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( $footer ) : ?>
		<div class="footer"><?php echo $footer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	<?php endif; ?>

</body>
</html>
