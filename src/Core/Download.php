<?php
/**
 * Secure PDF download endpoint.
 *
 * @package WooPdfInvoice
 */

declare( strict_types=1 );

namespace WooPdfInvoice\Core;

use WC_Order;
use WooPdfInvoice\Invoice\Invoice;
use WooPdfInvoice\Invoice\InvoiceRepository;
use WooPdfInvoice\Pdf\Engine;
use WooPdfInvoice\Pdf\Renderer;

/**
 * Class Download
 */
final class Download {

	/**
	 * Query argument carrying the order ID.
	 *
	 * @var string
	 */
	public const QUERY_VAR = 'wpi_invoice';

	/**
	 * Invoice repository.
	 *
	 * @var InvoiceRepository
	 */
	private InvoiceRepository $repository;

	/**
	 * HTML renderer.
	 *
	 * @var Renderer
	 */
	private Renderer $renderer;

	/**
	 * PDF engine.
	 *
	 * @var Engine
	 */
	private Engine $engine;

	/**
	 * Download constructor.
	 *
	 * @param InvoiceRepository $repository Repository.
	 * @param Renderer          $renderer   Renderer.
	 * @param Engine            $engine     PDF engine.
	 */
	public function __construct( InvoiceRepository $repository, Renderer $renderer, Engine $engine ) {
		$this->repository = $repository;
		$this->renderer   = $renderer;
		$this->engine     = $engine;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'template_redirect', array( $this, 'handle' ) );
	}

	/**
	 * Builds a download URL for an order.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return string
	 */
	public static function url( int $order_id ): string {
		return wp_nonce_url(
			home_url( '/?wpi_invoice=' . $order_id ),
			'wpi_download_' . $order_id
		);
	}

	/**
	 * Builds a guest download URL (token based).
	 *
	 * @param int    $order_id Order ID.
	 * @param string $token    Access token.
	 *
	 * @return string
	 */
	public static function guest_url( int $order_id, string $token ): string {
		return home_url( '/?wpi_invoice=' . $order_id . '&token=' . rawurlencode( $token ) );
	}

	/**
	 * Handles the download request.
	 *
	 * @return void
	 */
	public function handle(): void {
		$order_id = isset( $_GET[ self::QUERY_VAR ] ) ? absint( $_GET[ self::QUERY_VAR ] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 0 === $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			$this->deny( __( 'Order not found.', 'woo-pdf-invoice' ) );
		}

		if ( ! $this->is_authorized( $order ) ) {
			$this->deny( __( 'You are not allowed to view this invoice.', 'woo-pdf-invoice' ), 403 );
		}

		try {
			$invoice = $this->repository->get_or_create( $order );
			$html    = $this->renderer->render( $order, $invoice );
			$pdf     = $this->engine->generate( $html );
		} catch ( \Throwable $e ) {
			$this->deny(
				/* translators: %s: error message */
				sprintf( __( 'Invoice could not be generated: %s', 'woo-pdf-invoice' ), $e->getMessage() ),
				500
			);
		}

		$this->output( $pdf, $invoice, $order );
	}

	/**
	 * Checks whether the current request may download the invoice.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return bool
	 */
	private function is_authorized( WC_Order $order ): bool {
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		if ( '' !== $nonce && wp_verify_nonce( $nonce, 'wpi_download_' . $order->get_id() ) && $this->is_owner( $order ) ) {
			return true;
		}

		if ( '' !== $token ) {
			$stored = (string) $order->get_meta( InvoiceRepository::META_TOKEN );

			return '' !== $stored && hash_equals( $stored, $token );
		}

		return false;
	}

	/**
	 * Whether the current user owns the order.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return bool
	 */
	private function is_owner( WC_Order $order ): bool {
		$user_id = get_current_user_id();

		return $user_id > 0 && (int) $order->get_customer_id() === $user_id;
	}

	/**
	 * Outputs the PDF and exits.
	 *
	 * @param string   $pdf     PDF bytes.
	 * @param Invoice  $invoice Invoice.
	 * @param WC_Order $order   Order.
	 *
	 * @return void
	 */
	private function output( string $pdf, Invoice $invoice, WC_Order $order ): void {
		$filename = sanitize_file_name(
			sprintf( 'invoice-%s-%d.pdf', $invoice->number(), $order->get_id() )
		);

		nocache_headers();

		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: inline; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $pdf ) );

		echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Terminates the request with an error.
	 *
	 * @param string $message Message.
	 * @param int    $status  HTTP status code.
	 *
	 * @return never
	 */
	private function deny( string $message, int $status = 404 ): void {
		wp_die( esc_html( $message ), '', array( 'response' => $status ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
