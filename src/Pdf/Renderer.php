<?php
/**
 * Builds the invoice HTML from an order + invoice, then feeds a template.
 *
 * @package WooPdfInvoice
 */

declare( strict_types=1 );

namespace WooPdfInvoice\Pdf;

use WC_Order;
use WooPdfInvoice\Admin\Settings;
use WooPdfInvoice\Invoice\Invoice;

/**
 * Class Renderer
 */
final class Renderer {

	/**
	 * Template name (without .php).
	 *
	 * @var string
	 */
	private const TEMPLATE = 'invoice';

	/**
	 * Renders the invoice HTML.
	 *
	 * @param WC_Order $order   Order.
	 * @param Invoice  $invoice Invoice.
	 *
	 * @return string
	 */
	public function render( WC_Order $order, Invoice $invoice ): string {
		$settings = Settings::get();
		$currency = $order->get_currency();

		$data = array(
			'invoice_number'     => $invoice->number(),
			'invoice_date'       => $this->format_date( $invoice->date() ),
			'due_date'           => $this->due_date( $invoice->date() ),
			'order_number'       => (string) $order->get_order_number(),
			'order_date'         => $this->format_date( $this->order_date( $order ) ),
			'payment_method'     => (string) $order->get_payment_method_title(),
			'company'            => $this->company( $settings ),
			'billing'            => $this->address_block(
				array(
					'name'      => $this->full_name( $order, 'billing' ),
					'company'   => (string) $order->get_billing_company(),
					'address_1' => (string) $order->get_billing_address_1(),
					'address_2' => (string) $order->get_billing_address_2(),
					'city'      => (string) $order->get_billing_city(),
					'postcode'  => (string) $order->get_billing_postcode(),
					'country'   => $this->country_name( (string) $order->get_billing_country() ),
					'email'     => (string) $order->get_billing_email(),
					'phone'     => (string) $order->get_billing_phone(),
				)
			),
			'shipping'           => $this->address_block(
				array(
					'name'      => $this->full_name( $order, 'shipping' ),
					'company'   => (string) $order->get_shipping_company(),
					'address_1' => (string) $order->get_shipping_address_1(),
					'address_2' => (string) $order->get_shipping_address_2(),
					'city'      => (string) $order->get_shipping_city(),
					'postcode'  => (string) $order->get_shipping_postcode(),
					'country'   => $this->country_name( (string) $order->get_shipping_country() ),
					'email'     => '',
					'phone'     => '',
				)
			),
			'items'              => $this->items( $order ),
			'totals'             => $this->totals( $order, $currency ),
			'tax_label'          => (string) ( $settings['tax_label'] ?? __( 'VAT', 'woo-pdf-invoice' ) ),
			'prices_include_tax' => (bool) $order->get_prices_include_tax(),
			'total_tax'          => (float) $order->get_total_tax(),
			'tax_amount'         => $this->format_amount( (float) $order->get_total_tax(), $currency ),
			'currency'           => $currency,
			'currency_symbol'    => $this->currency_symbol( $currency ),
			'footer'             => wp_kses_post( (string) ( $settings['invoice_footer'] ?? '' ) ),
			'logo_data_uri'      => $this->logo_data_uri( (int) ( $settings['company_logo'] ?? 0 ) ),
		);

		return $this->load_template( $data );
	}

	/**
	 * Loads the (overridable) template and renders it with the given data.
	 *
	 * @param array $data Template data.
	 *
	 * @return string
	 */
	private function load_template( array $data ): string {
		$file = $this->locate_template();

		if ( ! is_readable( $file ) ) {
			return '';
		}

		extract( $data, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		ob_start();
		include $file;

		return (string) ob_get_clean();
	}

	/**
	 * Resolves the template file, allowing theme overrides.
	 *
	 * @return string
	 */
	private function locate_template(): string {
		$theme_file = get_stylesheet_directory() . '/woo-pdf-invoice/' . self::TEMPLATE . '.php';

		return is_readable( $theme_file ) ? $theme_file : WPI_PATH . 'templates/' . self::TEMPLATE . '.php';
	}

	/**
	 * Maps order line items into template-friendly arrays.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function items( WC_Order $order ): array {
		$currency = $order->get_currency();
		$rows     = array();

		foreach ( $order->get_items() as $item ) {
			if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
				continue;
			}

			$qty   = max( 1, (int) $item->get_quantity() );
			$total = (float) $item->get_total();

			$rows[] = array(
				'name'       => (string) $item->get_name(),
				'sku'        => $this->sku( $item ),
				'quantity'   => $qty,
				'unit_price' => $this->format_amount( $total / $qty, $currency ),
				'total'      => $this->format_amount( $total, $currency ),
			);
		}

		return $rows;
	}

	/**
	 * Builds the totals table.
	 *
	 * @param WC_Order $order    Order.
	 * @param string   $currency Currency code.
	 *
	 * @return array<int, array{label: string, value: string, highlight?: bool}>
	 */
	private function totals( WC_Order $order, string $currency ): array {
		$totals = array();

		$subtotal = (float) $order->get_subtotal();
		$discount = (float) $order->get_discount_total();
		$shipping = (float) $order->get_shipping_total();
		$tax      = (float) $order->get_total_tax();
		$total    = (float) $order->get_total();

		$totals[] = array(
			'label' => __( 'Subtotal', 'woo-pdf-invoice' ),
			'value' => $this->format_amount( $subtotal, $currency ),
		);

		if ( $discount > 0 ) {
			$totals[] = array(
				'label' => __( 'Discount', 'woo-pdf-invoice' ),
				'value' => '-' . $this->format_amount( $discount, $currency ),
			);
		}

		if ( $shipping > 0 ) {
			$totals[] = array(
				'label' => __( 'Shipping', 'woo-pdf-invoice' ),
				'value' => $this->format_amount( $shipping, $currency ),
			);
		}

		if ( $tax > 0 && ! $order->get_prices_include_tax() ) {
			$totals[] = array(
				'label' => (string) ( Settings::get()['tax_label'] ?? __( 'VAT', 'woo-pdf-invoice' ) ),
				'value' => $this->format_amount( $tax, $currency ),
			);
		}

		$totals[] = array(
			'label'     => __( 'Total', 'woo-pdf-invoice' ),
			'value'     => $this->format_amount( $total, $currency ),
			'highlight' => true,
		);

		return $totals;
	}

	/**
	 * Maps company settings into a display array.
	 *
	 * @param array $settings Settings.
	 *
	 * @return array<string, string>
	 */
	private function company( array $settings ): array {
		return array(
			'name'      => (string) ( $settings['company_name'] ?? '' ),
			'address'   => (string) ( $settings['company_address'] ?? '' ),
			'city'      => (string) ( $settings['company_city'] ?? '' ),
			'postcode'  => (string) ( $settings['company_postcode'] ?? '' ),
			'country'   => (string) ( $settings['company_country'] ?? '' ),
			'tax_id'    => (string) ( $settings['company_tax_id'] ?? '' ),
			'reg_no'    => (string) ( $settings['company_reg_no'] ?? '' ),
			'email'     => (string) ( $settings['company_email'] ?? '' ),
			'phone'     => (string) ( $settings['company_phone'] ?? '' ),
			'bank'      => (string) ( $settings['company_bank'] ?? '' ),
			'iban'      => (string) ( $settings['company_iban'] ?? '' ),
		);
	}

	/**
	 * Builds a multi-line address string.
	 *
	 * @param array $fields Address fields.
	 *
	 * @return string Escaped HTML with <br> line breaks.
	 */
	private function address_block( array $fields ): string {
		$lines = array();

		$map = array(
			'name',
			'company',
			'address_1',
			'address_2',
			'city',
			'postcode',
			'country',
			'email',
			'phone',
		);

		foreach ( $map as $key ) {
			$value = trim( (string) ( $fields[ $key ] ?? '' ) );

			if ( '' !== $value ) {
				$lines[] = $value;
			}
		}

		return implode( '<br>', array_map( 'esc_html', $lines ) );
	}

	/**
	 * Returns the full name for a billing/shipping address.
	 *
	 * @param WC_Order $order Order.
	 * @param string   $type  "billing" or "shipping".
	 *
	 * @return string
	 */
	private function full_name( WC_Order $order, string $type ): string {
		if ( 'billing' === $type ) {
			return trim( (string) $order->get_billing_first_name() . ' ' . (string) $order->get_billing_last_name() );
		}

		return trim( (string) $order->get_shipping_first_name() . ' ' . (string) $order->get_shipping_last_name() );
	}

	/**
	 * Resolves the SKU for an order item.
	 *
	 * @param \WC_Order_Item $item Order item.
	 *
	 * @return string
	 */
	private function sku( \WC_Order_Item $item ): string {
		$product = $item->get_product();

		if ( $product && method_exists( $product, 'get_sku' ) ) {
			return (string) $product->get_sku();
		}

		return '';
	}

	/**
	 * Formats an amount using the store's currency settings.
	 *
	 * @param float  $amount   Amount.
	 * @param string $currency Currency code.
	 *
	 * @return string
	 */
	private function format_amount( float $amount, string $currency ): string {
		$symbol = $this->currency_symbol( $currency );

		return $symbol . ' ' . number_format(
			$amount,
			wc_get_price_decimals(),
			wc_get_price_decimal_separator(),
			wc_get_price_thousand_separator()
		);
	}

	/**
	 * Returns a decoded currency symbol.
	 *
	 * @param string $currency Currency code.
	 *
	 * @return string
	 */
	private function currency_symbol( string $currency ): string {
		return html_entity_decode( get_woocommerce_currency_symbol( $currency ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Formats a MySQL date for display.
	 *
	 * @param string $date MySQL date.
	 *
	 * @return string
	 */
	private function format_date( string $date ): string {
		$timestamp = strtotime( $date );

		if ( false === $timestamp ) {
			return '';
		}

		return date_i18n( get_option( 'date_format' ), $timestamp );
	}

	/**
	 * Computes the due date from the invoice date.
	 *
	 * @param string $date Invoice date (MySQL).
	 *
	 * @return string
	 */
	private function due_date( string $date ): string {
		$days      = max( 0, (int) ( Settings::get()['invoice_due_days'] ?? 0 ) );
		$timestamp = strtotime( $date );

		if ( false === $timestamp ) {
			return '';
		}

		return date_i18n( get_option( 'date_format' ), $timestamp + ( $days * DAY_IN_SECONDS ) );
	}

	/**
	 * Returns the order creation date as a MySQL string.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return string
	 */
	private function order_date( WC_Order $order ): string {
		$created = $order->get_date_created();

		return $created ? $created->date( 'Y-m-d H:i:s' ) : current_time( 'mysql' );
	}

	/**
	 * Converts a country code into its localized name.
	 *
	 * @param string $code ISO 3166-1 alpha-2 code.
	 *
	 * @return string
	 */
	private function country_name( string $code ): string {
		if ( '' === $code || ! function_exists( 'WC' ) || ! isset( WC()->countries ) ) {
			return '';
		}

		return (string) WC()->countries->countries[ $code ] ?? $code;
	}

	/**
	 * Builds a base64 data URI for the company logo.
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return string
	 */
	private function logo_data_uri( int $attachment_id ): string {
		if ( $attachment_id <= 0 ) {
			return '';
		}

		$path = get_attached_file( $attachment_id );

		if ( ! $path || ! is_readable( $path ) ) {
			return '';
		}

		$mime = (string) wp_get_image_mime( $path );

		if ( '' === $mime || 0 !== strpos( $mime, 'image/' ) || 'image/svg+xml' === $mime ) {
			return '';
		}

		$bytes = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( false === $bytes ) {
			return '';
		}

		return 'data:' . $mime . ';base64,' . base64_encode( $bytes );
	}
}
