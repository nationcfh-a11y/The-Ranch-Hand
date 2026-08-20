<?php
/**
 * Tiny, dependency-free PDF writer for simple text documents.
 *
 * Purpose-built for the Hand profile sheet: a titled document with bold
 * headings, "Label: value" lines, clickable links, a grouped checklist, and a
 * wrapped free-text note. Uses the two built-in fonts Helvetica and
 * Helvetica-Bold (nothing to embed), US-Letter pages, automatic pagination.
 *
 * Not a general PDF library. Coordinates are PDF points (72 per inch), origin
 * bottom-left. Link click-rectangles and value indents are estimated from an
 * average glyph width (no font metrics), which is plenty for click targets.
 *
 * @package The_Ranch_Hand
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TRH_Simple_PDF {

	private $page_w = 612;
	private $page_h = 792;
	private $left   = 60;
	private $right  = 60;
	private $top    = 72;
	private $bottom = 60;

	private $y;
	private $buf        = '';
	private $pages      = array();
	private $page_index = 0;
	private $links      = array(); // each: page, rect [x1,y1,x2,y2], url

	/** Helvetica glyph widths (per 1000 em) for ASCII 32-126; default 556. */
	private static $hv = array(
		32 => 278, 33 => 278, 34 => 355, 35 => 556, 36 => 556, 37 => 889, 38 => 667, 39 => 191,
		40 => 333, 41 => 333, 42 => 389, 43 => 584, 44 => 278, 45 => 333, 46 => 278, 47 => 278,
		48 => 556, 49 => 556, 50 => 556, 51 => 556, 52 => 556, 53 => 556, 54 => 556, 55 => 556,
		56 => 556, 57 => 556, 58 => 278, 59 => 278, 60 => 584, 61 => 584, 62 => 584, 63 => 556,
		64 => 1015, 65 => 667, 66 => 667, 67 => 722, 68 => 722, 69 => 667, 70 => 611, 71 => 778,
		72 => 722, 73 => 278, 74 => 500, 75 => 667, 76 => 556, 77 => 833, 78 => 722, 79 => 778,
		80 => 667, 81 => 778, 82 => 722, 83 => 667, 84 => 611, 85 => 722, 86 => 667, 87 => 944,
		88 => 667, 89 => 667, 90 => 611, 91 => 278, 92 => 278, 93 => 278, 94 => 469, 95 => 556,
		96 => 333, 97 => 556, 98 => 556, 99 => 500, 100 => 556, 101 => 556, 102 => 278, 103 => 556,
		104 => 556, 105 => 222, 106 => 222, 107 => 500, 108 => 222, 109 => 833, 110 => 556, 111 => 556,
		112 => 556, 113 => 556, 114 => 333, 115 => 500, 116 => 278, 117 => 556, 118 => 500, 119 => 722,
		120 => 500, 121 => 500, 122 => 500, 123 => 334, 124 => 260, 125 => 334, 126 => 584,
	);

	public function __construct() {
		$this->y = $this->page_h - $this->top;
	}

	/** Usable text width. */
	private function usable() {
		return $this->page_w - $this->left - $this->right;
	}

	/** WinAnsi bytes of a UTF-8 string (used for both width and drawing). */
	private function to_winansi( $text ) {
		$text = (string) $text;
		if ( function_exists( 'iconv' ) ) {
			$converted = @iconv( 'UTF-8', 'CP1252//IGNORE', $text );
			if ( false !== $converted ) {
				$text = $converted;
			}
		} else {
			$text = preg_replace( '/[^\x20-\x7E]/', '', $text );
		}
		return $text;
	}

	/** Rendered width of a string at a font size, from Helvetica glyph metrics. */
	private function width_of( $text, $size ) {
		$text = $this->to_winansi( $text );
		$w    = 0;
		$len  = strlen( $text );
		for ( $i = 0; $i < $len; $i++ ) {
			$c  = ord( $text[ $i ] );
			$w += isset( self::$hv[ $c ] ) ? self::$hv[ $c ] : 556;
		}
		return $w * $size / 1000;
	}

	/** Start a new page if the next block would cross the bottom margin. */
	private function ensure_space( $need ) {
		if ( $this->y - $need < $this->bottom ) {
			$this->pages[] = $this->buf;
			$this->buf     = '';
			$this->y       = $this->page_h - $this->top;
			$this->page_index++;
		}
	}

	/** Append text at an absolute position without moving the cursor. */
	private function draw_at( $text, $size, $x, $y, $bold = false, $color = null ) {
		$font = $bold ? 'F2' : 'F1';
		if ( $color ) {
			$this->buf .= sprintf( "%.3f %.3f %.3f rg\n", $color[0], $color[1], $color[2] );
		}
		$this->buf .= sprintf(
			"BT /%s %d Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n",
			$font,
			$size,
			$x,
			$y,
			$this->escape( $text )
		);
		if ( $color ) {
			$this->buf .= "0 0 0 rg\n";
		}
	}

	/** Advance the cursor and draw one line; returns the baseline used. */
	private function line( $text, $size, $advance, $bold = false, $x = null ) {
		$this->ensure_space( $advance );
		$this->y -= $advance;
		$this->draw_at( $text, $size, null === $x ? $this->left : $x, $this->y, $bold );
		return $this->y;
	}

	/* ---- Public building blocks ---- */

	public function title( $text ) {
		$this->line( $text, 18, 26, true );
	}

	public function meta( $text ) {
		$this->line( $text, 10, 13 );
	}

	public function name( $text ) {
		$this->space( 8 );
		$this->line( $text, 13, 18, true );
	}

	public function section( $text ) {
		$this->space( 10 );
		$this->line( $text, 12, 18, true );
	}

	public function field( $label, $value ) {
		$this->line( $label . ': ' . $value, 11, 15 );
	}

	/** "Label: display" where display is a blue clickable link to $url. */
	public function field_link( $label, $url, $display ) {
		$prefix = $label . ': ';
		$this->ensure_space( 15 );
		$this->y -= 15;
		$this->draw_at( $prefix, 11, $this->left, $this->y );
		$x = $this->left + $this->width_of( $prefix, 11 ) + 2;
		$this->draw_at( $display, 11, $x, $this->y, false, array( 0, 0, 0.8 ) );
		$this->links[] = array(
			'page' => $this->page_index,
			'rect' => array( $x, $this->y - 2, $x + $this->width_of( $display, 11 ), $this->y + 9 ),
			'url'  => $url,
		);
	}

	public function group( $text ) {
		$this->space( 6 );
		$this->line( $text, 11.5, 16, true );
	}

	public function space( $height ) {
		$this->ensure_space( $height );
		$this->y -= $height;
	}

	/**
	 * Wrapped body text at 11pt: honors the author's line breaks, wraps long
	 * lines on word boundaries, and hard-breaks any single word wider than the
	 * column. $indent shifts the whole block right of the left margin.
	 */
	public function body( $text, $indent = 0 ) {
		$max = max( 10, (int) floor( ( $this->usable() - $indent ) / ( 11 * 0.5 ) ) );
		$text = str_replace( array( "\r\n", "\r" ), "\n", (string) $text );
		foreach ( explode( "\n", $text ) as $raw ) {
			$raw = rtrim( $raw );
			if ( '' === $raw ) {
				$this->space( 8 );
				continue;
			}
			$line = '';
			foreach ( preg_split( '/\s+/', $raw ) as $word ) {
				// Hard-break a word that can never fit (e.g. a long URL).
				while ( strlen( $word ) > $max ) {
					if ( '' !== $line ) {
						$this->line( $line, 11, 15, false, $this->left + $indent );
						$line = '';
					}
					$this->line( substr( $word, 0, $max ), 11, 15, false, $this->left + $indent );
					$word = substr( $word, $max );
				}
				$candidate = ( '' === $line ) ? $word : $line . ' ' . $word;
				if ( strlen( $candidate ) > $max && '' !== $line ) {
					$this->line( $line, 11, 15, false, $this->left + $indent );
					$line = $word;
				} else {
					$line = $candidate;
				}
			}
			if ( '' !== $line ) {
				$this->line( $line, 11, 15, false, $this->left + $indent );
			}
		}
	}

	/** Down-convert to WinAnsi and escape PDF string metacharacters. */
	private function escape( $text ) {
		$text = $this->to_winansi( $text );
		return str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $text );
	}

	/** Serialize all objects (with link annotations) and a byte-accurate xref. */
	public function output() {
		$this->pages[] = $this->buf;
		$page_count    = count( $this->pages );

		$obj  = array();
		$next = 5; // 1 Catalog, 2 Pages, 3 Font F1, 4 Font F2.
		$obj[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
		$obj[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

		$page_refs = array();
		for ( $i = 0; $i < $page_count; $i++ ) {
			$content_num = $next++;
			$stream      = $this->pages[ $i ];
			$obj[ $content_num ] = '<< /Length ' . strlen( $stream ) . " >>\nstream\n" . $stream . "\nendstream";

			$annot_refs = array();
			foreach ( $this->links as $link ) {
				if ( $link['page'] !== $i ) {
					continue;
				}
				$a = $next++;
				$r = $link['rect'];
				$obj[ $a ] = sprintf(
					'<< /Type /Annot /Subtype /Link /Border [0 0 0] /Rect [%.2f %.2f %.2f %.2f] /A << /S /URI /URI (%s) >> >>',
					$r[0],
					$r[1],
					$r[2],
					$r[3],
					$this->escape( $link['url'] )
				);
				$annot_refs[] = $a . ' 0 R';
			}

			$page_num = $next++;
			$annots   = $annot_refs ? ' /Annots [ ' . implode( ' ', $annot_refs ) . ' ]' : '';
			$obj[ $page_num ] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $this->page_w . ' ' . $this->page_h
				. '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $content_num . ' 0 R' . $annots . ' >>';
			$page_refs[] = $page_num . ' 0 R';
		}

		$obj[1] = '<< /Type /Catalog /Pages 2 0 R >>';
		$obj[2] = '<< /Type /Pages /Kids [ ' . implode( ' ', $page_refs ) . ' ] /Count ' . $page_count . ' >>';

		$max     = $next - 1;
		$out     = "%PDF-1.4\n";
		$offsets = array();
		for ( $num = 1; $num <= $max; $num++ ) {
			$offsets[ $num ] = strlen( $out );
			$out            .= $num . " 0 obj\n" . $obj[ $num ] . "\nendobj\n";
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
