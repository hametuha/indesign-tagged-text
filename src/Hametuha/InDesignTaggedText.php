<?php

namespace Hametuha;


class InDesignTaggedText {
	
	protected $line_code = "\n";
	
	protected $char_code = '';
	
	protected $os        = '';
	
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
		if ( ! in_array( $os, [ 'MAC', 'WIN' ] ) ) {
			throw new \Exception( 'OS should be WIN or MAC' );
		}
		$this->os = $os;
		if ( ! in_array( $char_code, $this->allowed_codes ) ) {
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
	 * @param string $file File path.
	 *
	 * @throws \Exception
	 * @return string
	 */
	public function convert( $file ) {
		if ( ! file_exists( $file ) ) {
			throw new \Exception( sprintf( '%s doesn\'t exist.' ) );
		}
		$content = file_get_contents( $file );
		if ( ! $content ) {
			throw new \Exception( 'Failed to load file.' );
		}
		$lines = preg_split( '#\r?\n#u', $content );
		$this->lines = $this->parse( $lines );
		return $content;
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
				'/^# /u' => 'Heading',
				'/^#\* /u' => 'CenterHeading',
				'/^> /u' => 'Quote',
				'/^>\| /u' => 'RightAligned',
				'/^<A> /u' => 'AA',
				'/^\{I\} /' => 'Interviewer',
				'/^>\* /u' => 'CenterAligned',
				'/^<\| /u' => 'Interviewee',
			] as $regexp => $style_name ) {
				if ( preg_match( $regexp, $line ) ) {
					$line = preg_replace( $regexp, "<ParaStyle:{$style_name}>", $line );
					break;
				}
			}
			// Change strong.
			foreach ( [
				'#\*\*([^*]+)\*\*#u',
			] as $regexp ) {
				$line = preg_replace( $regexp, '<CharStyle:Emphasis>$1<CharStyle:>', $line );
			}
			// Add kenten
			foreach ( [
				'#__([^_]+)__#u',
				'#《《([^》]+)》》#u',
			] as $regexp ) {
				$line = preg_replace( $regexp, '<cKentenKind:1>$1<cKentenKind:>', $line );
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
				'(.)《([^》]+)》',
			] as $index => $regexp ) {
				$line = preg_replace_callback( '#' . $regexp . '#u', function( $match ) {
					if ( '\\' === $match[1] ) {
						$replaced = mb_substr( $match[0], 1, mb_strlen( $match[0] ) - 1 );
						return $replaced;
					} else {
						$replaced = $match[1];
						
						return sprintf( '<cMojiRuby:0><cRuby:1><cRubyString:%s>%s<cMojiRuby:><cRuby:><cRubyString:>', $match[ 2 ], $replaced );
					}
				}, $line );
			}
			// Remove backslash
			$line = str_replace( '\\《', '《', $line );
			// If no paragraph styles are set, add no paragraph style.
			if ( 0 !== strpos( $line,  '<ParaStyle' ) ) {
				$line = '<ParaStyle:Normal>' . $line;
			}
			$converted[] = $line;
		}
		return $converted;
	}
	
	/**
	 * Save converted contents.
	 *
	 * @param string $target
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function save( $target ) {
		$lines = $this->add_header( $this->lines );
		$content = implode( $this->line_code, $lines );
		$content = $this->convert_encoding( $content, $this->char_code );
		if ( ! file_put_contents( $target, $content ) ) {
			throw new \Exception( 'Failed to save file.' );
		}
		return true;
	}
	
	/**
	 * Add tagged text header.
	 *
	 * @param  array $lines
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
	 * @param string $encoding
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
	 * @param string $string
	 * @param string $char_code
	 * @return string
	 */
	public function convert_encoding( $string, $char_code ) {
		if ( ! ( $new_encoding = $this->convert_to( $char_code ) ) ) {
			return $string;
		}
		return mb_convert_encoding( $string, $new_encoding, 'utf-8' );
	}
}
