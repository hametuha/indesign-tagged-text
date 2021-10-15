<?php

use Hametuha\InDesignTaggedText;

/**
 * Test utility functions.
 *
 */
class UtilityTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Check test case.
	 */
	public function test_converter() {
		$converter = new InDesignTaggedText( 'MAC', 'UNICODE' );
		$txt = <<<TXT
## 春はあけのぼ
ようよう白くなりゆく|山際<やまぎは>
少し__あかりて__
TXT;
		$converter->convert_from_string( $txt );
		$converted = $converter->export();
		$expected  = <<<TXT
<UNICODE-MAC>\r<ParaStyle:Heading2>春はあけのぼ\r<ParaStyle:Normal>ようよう白くなりゆく<cMojiRuby:0><cRuby:1><cRubyString:やまぎは>山際<cMojiRuby:><cRuby:><cRubyString:>\r<ParaStyle:Normal>少し<CharStyle:Sesami>あかりて<CharStyle:>
TXT;
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
}
