<?php
/**
 * Customer-facing invoice downloads.
 *
 * @package WooPdfInvoice
 */

declare( strict_types=1 );

namespace WooPdfInvoice\Frontend;

use WC_Order;
use WooPdfInvoice\Core\Download;
use WooPdfInvoice\Invoice\InvoiceRepository;

/**
 * Class MyAccount
 */
final class MyAccount {

	/**
	 * Invoice repository.
	 *
	 * @var InvoiceRepository
	 */
	private InvoiceRepository $repository;

	/**
	 * MyAccount constructor.
	 *
	 * @param InvoiceRepository $repository Repository.
	 */
	public function __construct( InvoiceRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'order_actions' ), 10, 2 );
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'order_details' ) );
	}

	/**
	 * Adds a download action to the customer orders list.
	 *
	 * @param array    $actions Existing actions.
	 * @param WC_Order $order   Order.
	 *
	 * @return array
	 */
	public function order_actions( array $actions, WC_Order $order ): array {
		if ( ! $this->repository->find( $order ) ) {
			return $actions;
		}

		$actions['wpi_invoice'] = array(
			'url'  => Download::url( $order->get_id() ),
			'name' => __( 'Invoice (PDF)', 'sequential-pdf-invoices' ),
		);

		return $actions;
	}

	/**
	 * Renders a download button on the single order screen.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return void
	 */
	public function order_details( WC_Order $order ): void {
		if ( ! $this->repository->find( $order ) ) {
			return;
		}

		printf(
			'<p class="wpi-order-download"><a class="button" href="%1$s" target="_blank" rel="noopener">%2$s</a></p>',
			esc_url( Download::url( $order->get_id() ) ),
			esc_html__( 'Download invoice (PDF)', 'sequential-pdf-invoices' )
		);
	}
}
