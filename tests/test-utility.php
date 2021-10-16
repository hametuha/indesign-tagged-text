<?php

use Hametuha\InDesignTaggedText;

/**
 * Test utility functions.
 *
 */
class UtilityTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Get proper parser.
	 *
	 * @param string $os        Default "MAC"
	 * @param string $char_code Default "Unicode"
	 * @return InDesignTaggedText
	 */
	protected function converter( $os = 'MAC', $char_code = 'UNICODE' ) {
		return new InDesignTaggedText( $os, $char_code );
	}

	/**
	 * Check test case.
	 */
	public function test_converter() {
		$converter = $this->converter();
		$txt = <<<TXT
## 春はあけのぼ
ようよう白くなりゆく|山際<やまぎは>
少し__あかりて__
- 紫だちたる
- 雲のほそく
1. たなびきたる
102. 夏は夜
TXT;
		$converter->convert_from_string( $txt );
		$converted = $converter->export();
		$expected  = <<<TXT
<UNICODE-MAC>
<ParaStyle:Heading2>春はあけのぼ
<ParaStyle:Normal>ようよう白くなりゆく<cMojiRuby:0><cRuby:1><cRubyString:やまぎは>山際<cMojiRuby:><cRuby:><cRubyString:>
<ParaStyle:Normal>少し<CharStyle:Sesami>あかりて<CharStyle:>
<ParaStyle:UnorderedList>紫だちたる
<ParaStyle:UnorderedList>雲のほそく
<ParaStyle:OrderedList>たなびきたる
<ParaStyle:OrderedList>夏は夜
TXT;
		$expected = str_replace( "\n", "\r", $expected );
		$this->assertEquals( $expected, $converted );
	}

	/**
	 * Check invalid OS.
	 */
	public function test_invalid_os() {
		$this->expectException( Exception::class );
		$converter = new InDesignTaggedText( 'Ubuntu', 'UNICODE' );
	}

	/**
	 * Check invalid charcode.
	 */
	public function test_invalid_charcode() {
		$this->expectException( Exception::class );
		$converter = new InDesignTaggedText( 'MAC', 'UJIS' );
	}

	/**
	 * Check no file.
	 */
	public function test_non_existent_file() {
		$this->expectException( Exception::class );
		$converter = new InDesignTaggedText( 'MAC', 'UNICODE' );
		$converter->convert( 'tests/non-exitent.txt' );
	}

	/**
	 * Check markdown.
	 */
	public function test_markdown_format() {
		$mark_down = <<<TXT
これは一行目

これは二行目

これは三行目

四行目はスペースで改行
これは普通の改行になる。

一行空き。

&nbsp;

一行空くはず。
TXT;
		$mark_down = str_replace( "\n", "\r", $mark_down );
		$converted = $this->converter()->markdown_format( $mark_down );
		$expected = <<<TXT
これは一行目
これは二行目
これは三行目
四行目はスペースで改行
これは普通の改行になる。
一行空き。

一行空くはず。
TXT;
		$expected = str_replace( "\n", "\r", $expected );
		$this->assertEquals( $expected, $converted );
	}
}
