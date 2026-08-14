<?php
/**
 * dompdf wrapper: renders HTML into PDF bytes.
 *
 * @package WooPdfInvoice
 */

declare( strict_types=1 );

namespace WooPdfInvoice\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;
use WooPdfInvoice\Admin\Settings;

/**
 * Class Engine
 */
final class Engine {

	/**
	 * Supported paper sizes.
	 *
	 * @var string[]
	 */
	public const PAPER_SIZES = array( 'A4', 'A5', 'Letter', 'Legal' );

	/**
	 * Supported orientations.
	 *
	 * @var string[]
	 */
	public const ORIENTATIONS = array( 'portrait', 'landscape' );

	/**
	 * Renders HTML to a PDF document.
	 *
	 * @param string      $html        HTML source.
	 * @param string|null $paper       Paper size override.
	 * @param string|null $orientation Orientation override.
	 *
	 * @return string PDF binary content.
	 *
	 * @throws \RuntimeException When dompdf is unavailable.
	 */
	public function generate( string $html, ?string $paper = null, ?string $orientation = null ): string {
		if ( ! class_exists( Dompdf::class ) ) {
			throw new \RuntimeException(
				esc_html__( 'PDF engine is missing. Run `composer install` inside the plugin folder.', 'woo-pdf-invoice' )
			);
		}

		$settings = Settings::get();

		$paper       = $paper ?? ( $settings['pdf_paper_size'] ?? 'A4' );
		$orientation = $orientation ?? ( $settings['pdf_orientation'] ?? 'portrait' );

		$options = new Options();
		$options->set( 'isRemoteEnabled', true );
		$options->set( 'isHtml5ParserEnabled', true );
		$options->set( 'defaultFont', 'DejaVu Sans' );
		$options->set( 'defaultMediaType', 'print' );
		$options->set( 'dpi', 96 );

		$dompdf = new Dompdf( $options );
		$dompdf->loadHtml( $this->normalize( $html ) );
		$dompdf->setPaper( $paper, $orientation );
		$dompdf->render();

		return (string) $dompdf->output();
	}

	/**
	 * Light HTML normalization before rendering.
	 *
	 * @param string $html HTML source.
	 *
	 * @return string
	 */
	private function normalize( string $html ): string {
		// dompdf struggles with self-closing void tags written as XML.
		return (string) preg_replace( '/<br\s*\/?>/i', '<br>', $html );
	}
}
