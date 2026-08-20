<?php
/**
 * Tiny, dependency-free PDF writer for simple text documents.
 *
 * Purpose-built for one job: laying out a titled, grouped checklist (the Hand
 * experience sheet) as a clean multi-page PDF, with no Composer package or
 * binary. Standard Helvetica (one of the 14 built-in PDF fonts, so nothing to
 * embed) with WinAnsi encoding, US-Letter pages, automatic pagination.
 *
 * Not a general PDF library: text only, left-aligned, no wrapping (the caller
 * keeps lines short). Coordinates are PDF points (72 per inch), origin bottom-left.
 *
 * @package The_Ranch_Hand
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TRH_Simple_PDF {

	/** Page + margin geometry, in points. US Letter. */
	private $page_w = 612;
	private $page_h = 792;
	private $left   = 60;
	private $top    = 72;
	private $bottom = 60;

	/** Current baseline Y, current page's content stream, finished pages. */
	private $y;
	private $buf   = '';
	private $pages = array();

	public function __construct() {
		$this->y = $this->page_h - $this->top;
	}

	/** Start a new page when the next block would cross the bottom margin. */
	private function ensure_space( $need ) {
		if ( $this->y - $need < $this->bottom ) {
			$this->pages[] = $this->buf;
			$this->buf     = '';
			$this->y       = $this->page_h - $this->top;
		}
	}

	/** Draw one line of text and advance the baseline. */
	private function draw( $text, $size, $x, $advance ) {
		$this->ensure_space( $advance );
		$this->y -= $advance;
		$this->buf .= sprintf(
			"BT /F1 %d Tf 1 0 0 1 %d %d Tm (%s) Tj ET\n",
			$size,
			$x,
			$this->y,
			$this->escape( $text )
		);
	}

	public function title( $text ) {
		$this->draw( $text, 20, $this->left, 28 );
	}

	public function heading( $text ) {
		$this->space( 8 );
		$this->draw( $text, 13, $this->left, 20 );
	}

	public function item( $text ) {
		$this->draw( '-  ' . $text, 11, $this->left + 14, 15 );
	}

	public function paragraph( $text ) {
		$this->draw( $text, 11, $this->left, 16 );
	}

	public function space( $height ) {
		$this->ensure_space( $height );
		$this->y -= $height;
	}

	/**
	 * Draw free-form text at body size, wrapping long lines on word boundaries
	 * and honoring the author's own line breaks. Conservative character-count
	 * wrapping (no font metrics needed) that comfortably fits the text column.
	 */
	public function body( $text, $max_chars = 95 ) {
		$text  = str_replace( array( "\r\n", "\r" ), "\n", (string) $text );
		$lines = explode( "\n", $text );
		foreach ( $lines as $raw ) {
			$raw = rtrim( $raw );
			if ( '' === $raw ) {
				$this->space( 8 ); // blank line = paragraph gap
				continue;
			}
			$line = '';
			foreach ( preg_split( '/\s+/', $raw ) as $word ) {
				$candidate = ( '' === $line ) ? $word : $line . ' ' . $word;
				if ( strlen( $candidate ) > $max_chars && '' !== $line ) {
					$this->draw( $line, 11, $this->left, 15 );
					$line = $word;
				} else {
					$line = $candidate;
				}
			}
			if ( '' !== $line ) {
				$this->draw( $line, 11, $this->left, 15 );
			}
		}
	}

	/** Down-convert to WinAnsi and escape the three PDF string metacharacters. */
	private function escape( $text ) {
		$text = (string) $text;
		if ( function_exists( 'iconv' ) ) {
			$converted = @iconv( 'UTF-8', 'CP1252//IGNORE', $text );
			if ( false !== $converted ) {
				$text = $converted;
			}
		} else {
			$text = preg_replace( '/[^\x20-\x7E]/', '', $text );
		}
		return str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $text );
	}

	/** Serialize every object with a byte-accurate xref table; return PDF bytes. */
	public function output() {
		$this->pages[] = $this->buf; // flush the page in progress

		$objs      = array();
		$objs[1]   = '<< /Type /Catalog /Pages 2 0 R >>';
		$num_pages = count( $this->pages );

		$kids = array();
		for ( $i = 0; $i < $num_pages; $i++ ) {
			$kids[] = ( 4 + $i * 2 ) . ' 0 R';
		}
		$objs[2] = '<< /Type /Pages /Kids [ ' . implode( ' ', $kids ) . ' ] /Count ' . $num_pages . ' >>';
		$objs[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';

		for ( $i = 0; $i < $num_pages; $i++ ) {
			$page_num    = 4 + $i * 2;
			$content_num = 5 + $i * 2;
			$objs[ $page_num ] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $this->page_w . ' ' . $this->page_h
				. '] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . $content_num . ' 0 R >>';
			$stream            = $this->pages[ $i ];
			$objs[ $content_num ] = '<< /Length ' . strlen( $stream ) . " >>\nstream\n" . $stream . "\nendstream";
		}

		$max = 3 + $num_pages * 2;
		$out = "%PDF-1.4\n";
		$offsets = array();
		for ( $num = 1; $num <= $max; $num++ ) {
			$offsets[ $num ] = strlen( $out );
			$out            .= $num . " 0 obj\n" . $objs[ $num ] . "\nendobj\n";
		}

		$xref_pos = strlen( $out );
		$out     .= "xref\n0 " . ( $max + 1 ) . "\n";
		$out     .= "0000000000 65535 f \n";
		for ( $num = 1; $num <= $max; $num++ ) {
			$out .= sprintf( "%010d 00000 n \n", $offsets[ $num ] );
		}
		$out .= "trailer\n<< /Size " . ( $max + 1 ) . " /Root 1 0 R >>\nstartxref\n" . $xref_pos . "\n%%EOF";

		return $out;
	}
}
