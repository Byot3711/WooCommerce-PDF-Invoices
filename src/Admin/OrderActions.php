<?php
/**
 * Admin integration: order list column, bulk actions and the order meta box.
 *
 * @package WooPdfInvoice
 */

declare( strict_types=1 );

namespace WooPdfInvoice\Admin;

use WC_Order;
use WooPdfInvoice\Core\Download;
use WooPdfInvoice\Invoice\Invoice;
use WooPdfInvoice\Invoice\InvoiceRepository;

/**
 * Class OrderActions
 */
final class OrderActions {

	/**
	 * Order list column key.
	 *
	 * @var string
	 */
	private const COLUMN = 'wpi_invoice';

	/**
	 * Meta box id.
	 *
	 * @var string
	 */
	private const META_BOX = 'wpi_invoice_metabox';

	/**
	 * Meta box nonce action.
	 *
	 * @var string
	 */
	private const NONCE = 'wpi_invoice_metabox';

	/**
	 * Bulk action id.
	 *
	 * @var string
	 */
	private const BULK_ACTION = 'wpi_generate_invoices';

	/**
	 * Invoice repository.
	 *
	 * @var InvoiceRepository
	 */
	private InvoiceRepository $repository;

	/**
	 * OrderActions constructor.
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
		// Order list column (legacy posts storage + HPOS).
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_column' ) );
		add_filter( 'woocommerce_shop_order_list_table_columns', array( $this, 'add_column' ) );

		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_column_legacy' ), 10, 2 );
		add_action( 'woocommerce_shop_order_list_table_custom_column', array( $this, 'render_column_hpos' ), 10, 2 );

		// Bulk actions (works for both storage engines).
		add_filter( 'bulk_actions-edit-shop_order', array( $this, 'bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_bulk' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'bulk_notice' ) );

		// Order edit meta box.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_meta_box' ), 10, 2 );

		// AJAX delete.
		add_action( 'wp_ajax_wpi_delete_invoice', array( $this, 'ajax_delete' ) );
	}

	/**
	 * Adds the invoice column.
	 *
	 * @param array $columns Existing columns.
	 *
	 * @return array
	 */
	public function add_column( array $columns ): array {
		$offset = array_search( 'order_status', array_keys( $columns ), true );

		$new = array(
			self::COLUMN => __( 'Invoice', 'woo-pdf-invoice' ),
		);

		if ( false !== $offset ) {
			$columns = array_merge(
				array_slice( $columns, 0, $offset + 1, true ),
				$new,
				array_slice( $columns, $offset + 1, null, true )
			);
		} else {
			$columns += $new;
		}

		return $columns;
	}

	/**
	 * Renders the column for legacy posts storage.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Order (post) ID.
	 *
	 * @return void
	 */
	public function render_column_legacy( string $column, int $post_id ): void {
		if ( self::COLUMN !== $column ) {
			return;
		}

		$order = wc_get_order( $post_id );

		if ( $order ) {
			$this->column_html( $order );
		}
	}

	/**
	 * Renders the column for HPOS.
	 *
	 * @param string   $column Column key.
	 * @param WC_Order $order  Order.
	 *
	 * @return void
	 */
	public function render_column_hpos( string $column, WC_Order $order ): void {
		if ( self::COLUMN === $column ) {
			$this->column_html( $order );
		}
	}

	/**
	 * Outputs the column cell content.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return void
	 */
	private function column_html( WC_Order $order ): void {
		$invoice = $this->repository->find( $order );

		if ( $invoice ) {
			printf(
				'<a href="%1$s" target="_blank" rel="noopener">%2$s</a><br><small>%3$s</small>',
				esc_url( Download::url( $order->get_id() ) ),
				esc_html( $invoice->number() ),
				esc_html( $this->format_date( $invoice->date() ) )
			);
		} else {
			printf(
				'<a href="%1$s" target="_blank" rel="noopener" class="button button-small">%2$s</a>',
				esc_url( Download::url( $order->get_id() ) ),
				esc_html__( 'Generate', 'woo-pdf-invoice' )
			);
		}
	}

	/**
	 * Registers the bulk action.
	 *
	 * @param array $actions Existing actions.
	 *
	 * @return array
	 */
	public function bulk_actions( array $actions ): array {
		$actions[ self::BULK_ACTION ] = __( 'Generate invoices', 'woo-pdf-invoice' );

		return $actions;
	}

	/**
	 * Handles the bulk action.
	 *
	 * @param string $redirect Redirect URL.
	 * @param string $action   Action key.
	 * @param array  $ids      Selected order IDs.
	 *
	 * @return string
	 */
	public function handle_bulk( string $redirect, string $action, array $ids ): string {
		if ( self::BULK_ACTION !== $action ) {
			return $redirect;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return $redirect;
		}

		$count = 0;

		foreach ( $ids as $id ) {
			$order = wc_get_order( absint( $id ) );

			if ( $order ) {
				$this->repository->get_or_create( $order );
				$count++;
			}
		}

		return add_query_arg( 'wpi_generated', $count, $redirect );
	}

	/**
	 * Shows the bulk action notice.
	 *
	 * @return void
	 */
	public function bulk_notice(): void {
		$count = isset( $_GET['wpi_generated'] ) ? absint( $_GET['wpi_generated'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $count < 1 ) {
			return;
		}

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %d: number of invoices */
					_n( '%d invoice generated.', '%d invoices generated.', $count, 'woo-pdf-invoice' ),
					$count
				)
			)
		);
	}

	/**
	 * Registers the meta box on both classic and HPOS order screens.
	 *
	 * @return void
	 */
	public function add_meta_box(): void {
		add_meta_box(
			self::META_BOX,
			__( 'PDF Invoice', 'woo-pdf-invoice' ),
			array( $this, 'render_meta_box' ),
			array( 'shop_order', 'woocommerce_page_wc-orders' ),
			'side',
			'high'
		);
	}

	/**
	 * Renders the meta box.
	 *
	 * @param mixed $post Post or order object.
	 *
	 * @return void
	 */
	public function render_meta_box( $post ): void {
		$order = wc_get_order( $this->order_id_from_post( $post ) );

		if ( ! $order ) {
			echo '<p>' . esc_html__( 'Order not found.', 'woo-pdf-invoice' ) . '</p>';

			return;
		}

		$invoice = $this->repository->find( $order );

		wp_nonce_field( self::NONCE, 'wpi_invoice_nonce' );

		echo '<div class="wpi-metabox">';

		if ( $invoice ) {
			printf(
				'<p><strong>%1$s</strong><br><span class="wpi-invoice-date">%2$s</span></p>',
				esc_html( $invoice->number() ),
				esc_html( $this->format_date( $invoice->date() ) )
			);
		} else {
			echo '<p class="wpi-no-invoice">' . esc_html__( 'No invoice generated yet.', 'woo-pdf-invoice' ) . '</p>';
		}

		printf(
			'<label for="wpi_invoice_number">%s</label>',
			esc_html__( 'Invoice number', 'woo-pdf-invoice' )
		);

		printf(
			'<input type="text" id="wpi_invoice_number" name="wpi_invoice_number" value="%s" class="widefat" />',
			esc_attr( $invoice ? $invoice->number() : '' )
		);

		echo '<p class="wpi-metabox-actions">';

		printf(
			'<a href="%1$s" target="_blank" rel="noopener" class="button">%2$s</a> ',
			esc_url( Download::url( $order->get_id() ) ),
			esc_html__( 'Download PDF', 'woo-pdf-invoice' )
		);

		if ( $invoice ) {
			printf(
				'<button type="button" class="button-link-delete wpi-delete-invoice" data-order="%d" data-nonce="%s">%s</button>',
				esc_attr( (string) $order->get_id() ),
				esc_attr( wp_create_nonce( 'wpi_delete_' . $order->get_id() ) ),
				esc_html__( 'Delete invoice', 'woo-pdf-invoice' )
			);
		}

		echo '</p>';
		echo '<p class="description">' . esc_html__( 'Saving the order stores the number above.', 'woo-pdf-invoice' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Persists the manually entered invoice number on order save.
	 *
	 * @param int|string $order_id Order ID.
	 * @param mixed      $post     Post or order object.
	 *
	 * @return void
	 */
	public function save_meta_box( $order_id, $post = null ): void {
		$nonce = isset( $_POST['wpi_invoice_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wpi_invoice_nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( '' === $nonce || ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$order = wc_get_order( absint( $order_id ) );

		if ( ! $order ) {
			return;
		}

		$number = isset( $_POST['wpi_invoice_number'] ) ? sanitize_text_field( wp_unslash( $_POST['wpi_invoice_number'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$this->repository->set_number( $order, $number );
	}

	/**
	 * Deletes the invoice via AJAX.
	 *
	 * @return void
	 */
	public function ajax_delete(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'woo-pdf-invoice' ) ), 403 );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$nonce    = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! $order_id || ! wp_verify_nonce( $nonce, 'wpi_delete_' . $order_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'woo-pdf-invoice' ) ), 400 );
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found.', 'woo-pdf-invoice' ) ), 404 );
		}

		$this->repository->delete( $order );

		wp_send_json_success();
	}

	/**
	 * Resolves an order ID from a meta box callback argument.
	 *
	 * @param mixed $post Post or order object.
	 *
	 * @return int
	 */
	private function order_id_from_post( $post ): int {
		if ( $post instanceof WC_Order ) {
			return $post->get_id();
		}

		if ( is_object( $post ) && isset( $post->ID ) ) {
			return (int) $post->ID;
		}

		if ( is_numeric( $post ) ) {
			return (int) $post;
		}

		return 0;
	}

	/**
	 * Formats a MySQL date for the admin UI.
	 *
	 * @param string $date MySQL date.
	 *
	 * @return string
	 */
	private function format_date( string $date ): string {
		$timestamp = strtotime( $date );

		return false === $timestamp ? '' : date_i18n( get_option( 'date_format' ), $timestamp );
	}
}
