<?php

namespace Hametuha;


/**
 * Convert markup to InDesign tagged text.
 *
 */
class InDesignTaggedText {

	protected $line_code = "\n";

	protected $char_code = '';

	protected $os = '';

	protected $allowed_codes = [ 'UNICODE', 'SJIS', 'ASCII' ];

	protected $lines = [];

	/**
	 * InDesignTaggedText constructor.
	 *
	 * @param string $os        WIN or MAC
	 * @param string $char_code UNIOCDE, SJIS, ASCII
	 *
	 * @throws \Exception
	 */
	public function __construct( $os, $char_code ) {
		$os        = strtoupper( $os );
		$char_code = strtoupper( $char_code );
		if ( ! in_array( $os, [ 'MAC', 'WIN' ], true ) ) {
			throw new \Exception( 'OS should be WIN or MAC' );
		}
		$this->os = $os;
		if ( ! in_array( $char_code, $this->allowed_codes, true ) ) {
			throw new \Exception( sprintf( '$char_code should be in %s.', implode( ', ', $this->allowed_codes ) ) );
		}
		$this->char_code = $char_code;
		if ( 'MAC' === $this->os ) {
			$this->line_code = "\r";
		} else {
			$this->line_code = "\r\n";
		}
	}

	/**
	 * Convert file.
	 *
	 * @param string $file     File path.
	 * @param bool   $markdown If true, convert markdonw.
	 *
	 * @throws \Exception
	 * @return string[]
	 */
	public function convert( $file, $markdown = false ) {
		if ( ! file_exists( $file ) ) {
			throw new \Exception( sprintf( '%s doesn\'t exist.', $file ) );
		}
		$content = file_get_contents( $file );
		if ( ! $content ) {
			throw new \Exception( 'Failed to load file.' );
		}
		return $this->convert_from_string( $content, $markdown );
	}

	/**
	 * Convert lines from string.
	 *
	 * @param string $text     Text to convert.
	 * @param bool   $markdown If true, convert markdonw.
	 * @return string[]
	 */
	public function convert_from_string( $text, $markdown = false ) {
		if ( $markdown ) {
			$text = $this->markdown_format( $text );
		}
		$lines       = preg_split( '#\r?\n#u', $text );
		$this->lines = $this->parse( $lines );
		return $this->lines;
	}

	/**
	 * Parse all lines.
	 *
	 * @param array $lines
	 *
	 * @return array
	 */
	protected function parse( $lines ) {
		$converted = [];
		foreach ( $lines as $line ) {
			// Set paragraph style to remove space
			if ( preg_match( '#^　[^　]#u', $line ) ) {
				$line = preg_replace( '#^　#u', '', $line );
			}
			// Paragraph styles.
			foreach ( [
				'/^(#{1,6}) /u' => 'Heading',
				'/^#\* /u'      => 'CenterHeading',
				'/^> /u'        => 'Quote',
				'/^>\| /u'      => 'RightAligned',
				'/^<A> /u'      => 'AA',
				'/^\{I\} /'     => 'Interviewer',
				'/^>\* /u'      => 'CenterAligned',
				'/^<\| /u'      => 'Interviewee',
				'/^- /u'        => 'UnorderedList',
				'/^\d+. /u'     => 'OrderedList',
			] as $regexp => $style_name ) {
				if ( preg_match( $regexp, $line ) ) {
					switch ( $style_name ) {
						case 'Heading':
							$line = preg_replace_callback( $regexp, function( $matches ) {
								return sprintf( '<ParaStyle:Heading%d>', strlen( $matches[1] ) );
							}, $line );
							break 2;
						default:
							$line = preg_replace( $regexp, "<ParaStyle:{$style_name}>", $line );
							break 2;
					}
				}
			}
			// Change strong.
			foreach ( [
				'#\*\*([^*]+)\*\*#u',
			] as $regexp ) {
				$line = preg_replace( $regexp, '<CharStyle:Emphasis>$1<CharStyle:>', $line );
			}
			// Add Kenten.
			foreach ( [
				'#__([^_]+)__#u',
				'#《《([^》]+)》》#u',
			] as $regexp ) {
				$line = preg_replace( $regexp, '<CharStyle:Sesami>$1<CharStyle:>', $line );
			}
			// Double dash
			foreach ( [
				'#—{2}#u', // EM dash.
				'#―{2}#u', // Double horizontal bar.
			] as $regexp ) {
				// Dash will be horizontal bar(U+2015).
				$line = preg_replace( $regexp, '<CharStyle:Dash>―<CharStyle:>', $line );
			}
			// Warichu
			foreach ( [
				'#〔〔([^〕]+)〕〕#u',
			] as $regexp ) {
				$line = preg_replace( $regexp, '〔<cWarichu:1>$1<cWarichu:>〕', $line );
			}
			// Set TCY
			$line = preg_replace_callback( '#<tcy>(.*?)</tcy>#u', function( $match ) {
				$length = mb_strlen( $match[1] );
				if ( 5 >= $length ) {
					return sprintf( '<cTCY:1><cTCYNumDigits:%2$d>%1$s<cTCY:><cTCYNumDigits:%2$d>', $match[1], $length );
				} else {
					// No TCY.
					return $match[1];
				}
			}, $line );
			// Set ruby.
			foreach ( [
				'｜([^《]+)《([^》]+)》',
				'\|([^<]+)<([^>]+)>',
			] as $index => $regexp ) {
				$line = preg_replace_callback( '#' . $regexp . '#u', function( $match ) {
					if ( '\\' === $match[1] ) {
						$replaced = mb_substr( $match[0], 1, mb_strlen( $match[0] ) - 1 );
						return $replaced;
					} else {
						$replaced = $match[1];

						return sprintf( '<cMojiRuby:0><cRuby:1><cRubyString:%s>%s<cMojiRuby:><cRuby:><cRubyString:>', $match[2], $replaced );
					}
				}, $line );
			}
			// Remove backslash
			$line = str_replace( '\\《', '《', $line );
			// Sup and Sub.
			foreach ( [
				'#<sup>([^<]+)</sup>#u' => 'Sup',
				'#<sub>([^<]*)</sub>#u' => 'Sub',
			] as $regexp => $style ) {
				$line = preg_replace( $regexp, '<CharStyle:' . $style . '>$1<CharStyle:>', $line );
			}

			// Footnote.
			foreach ( [
				'#［［([^］]+)］］#u',
			] as $regexp ) {
				$line = preg_replace( $regexp, '<FootnoteStart:>$1<FootnoteEnd:>', $line );
			}

			// If no paragraph styles are set, add no paragraph style.
			if ( 0 !== strpos( $line, '<ParaStyle' ) ) {
				$line = '<ParaStyle:Normal>' . $line;
			}
			$converted[] = $line;
		}
		return $converted;
	}

	/**
	 * Save converted contents.
	 *
	 * @param string $target         Export target.
	 * @param bool   $convert_encode If true, convert encodieng.
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function save( $target, $convert_encode = true ) {
		$content = $this->export( $convert_encode );
		if ( ! file_put_contents( $target, $content ) ) {
			throw new \Exception( 'Failed to save file.' );
		}
		return true;
	}

	/**
	 * Export text.
	 *
	 * @param bool $convert_encode If true, convert encoding.
	 * @param bool $format_markdown Default false.
	 * @return string
	 */
	public function export( $convert_encode = false ) {
		if ( $convert_encode ) {
			$lines = $this->add_header( $this->lines );
		} else {
			$lines = $this->lines;
		}
		$content = implode( $this->line_code, $lines );
		// Convert forced line break(強制改行)
		// todo: Windows accespts lf?
		$content = str_replace( '<000A>', "\n", $content );
		if ( $convert_encode ) {
			$content = $this->convert_encoding( $content, $this->char_code );
		}
		return $content;
	}

	/**
	 * Fix markdown line ending.
	 *
	 * 1. Convert double space to single line break.
	 * 2. Remove 2 space at the end of line.
	 * 3. Clear nbsp
	 *
	 * @param string $text Contents.
	 * @return string
	 */
	public function markdown_format( $text ) {
		// Fix double space.
		$text = preg_replace( "/(\r\n|\r|\n)(\r\n|\r|\n)/u", '$1', $text );
		// Remove 2 space.
		$text = preg_replace( '/ {2}$/mu', '', $text );
		// Remove single nbsp.
		$text = preg_replace( '/(\r\n|\r|\n)&nbsp;(\r\n|\r|\n)/u', '$1$2', $text );
		return $text;
	}

	/**
	 * Add tagged text header.
	 *
	 * @param string[] $lines Lines.
	 * @return array
	 */
	public function add_header( $lines ) {
		return array_merge( [
			sprintf( '<%s-%s>', $this->char_code, $this->os ),
		], $lines );
	}

	/**
	 * Convert encoding string.
	 *
	 * @see http://kstation2.blog10.fc2.com/blog-entry-314.html
	 * @param string $encoding Encoding name.
	 *
	 * @return string
	 */
	protected function convert_to( $encoding ) {
		switch ( $encoding ) {
			case 'SJIS':
				return 'sjis-win';
				break;
			case 'UNICODE':
				return 'UTF-16BE';
				break;
			case 'ASCII':
			default:
				return '';
				break;
		}
	}

	/**
	 * Convert lines to output string.
	 *
	 * @param string $string    String to convert.
	 * @param string $char_code Target char code.
	 * @return string
	 */
	public function convert_encoding( $string, $char_code ) {
		$new_encoding = $this->convert_to( $char_code );
		if ( ! $new_encoding ) {
			return $string;
		}
		return mb_convert_encoding( $string, $new_encoding, 'utf-8' );
	}
}
