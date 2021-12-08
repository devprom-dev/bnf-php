<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF;

use PHPUnit\Framework\TestCase;
use LogicException;
use Taco\BNF\Combinators\Sequence;
use Taco\BNF\Combinators\Pattern;
use Taco\BNF\Combinators\Variants;
use Taco\BNF\Combinators\Match;


class UtilsTest extends TestCase
{


	function testScanPatternIllegalPattern()
	{
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('The pattern \'~[a-z]*~\' corresponds to an empty string.');
		$src = '{abc{def}efg{{xyz}hch}} zdf';
		$comb = $this->createMock(Combinator::class);
		Utils::scanPattern($comb, ['~[a-z]*~'], $src, 0);
	}



	/**
	 * @dataProvider dataScanPattern
	 */
	function testScanPattern($src, $offset, $expected)
	{
		$comb = $this->createMock(Combinator::class);
		if (is_bool($expected)) {
			$this->assertFalse($expected, Utils::scanPattern($comb, ['~[a-z]+~'], $src, $offset));
		}
		else {
			$token = Utils::scanPattern($comb, ['~[a-z]+~'], $src, $offset);
			$this->assertEquals(new Token($comb, $expected[0], $expected[1], $expected[2]), $token);
		}
	}



	function dataScanPattern()
	{
		$src = '{abc{def}efg{{xyz}hch}} zdf';
		return [
			[$src, 0, False],
			[$src, 1, ["abc", 1, 4]],
		];
	}



	function testScanPattern_2()
	{
		$comb = $this->createMock(Combinator::class);
		$token = Utils::scanPattern($comb, ['~[0-9]+~'], "abcds123", 3, False);
		$this->assertEquals(new Token($comb, '123', 5, 8), $token);
	}



	/**
	 * @dataProvider dataLookupBlock
	 */
	function testLookupBlock($src, $offset, $expected)
	{
		$this->assertEquals($expected, Utils::lookupBlock('{', '}', $src, $offset));
	}



	function dataLookupBlock()
	{
		return [
			['', 0, [False, False]],
			['abc', 0, [False, False]],
			['abc}', 0, [False, False]],
			['{abc}', 0, [0, 4]],
			['{abc', 0, [0, False]],
			['{abc{def}}', 0, [0, 9]],
			['{abc{def}}', 1, [4, 8]],
			['{abc{def}}', 2, [4, 8]],
			['{abc{def}}', 3, [4, 8]],
			['{abc{def}}', 4, [4, 8]],
			['{abc{def}}', 5, [False, False]],
			['{abc{def}}', 500, [False, False]],
			['{abc{def}efg}', 0, [0, 12]],
			['{abc{def}efg{hch}}', 0, [0, 17]],
			['{abc{def}efg{{xyz}hch}} zdf', 0, [0, 22]],
		];
	}



	function testFlatting_1()
	{
		$src = [];
		$this->assertSame([], Utils::flatting($src));
	}



	function testFlatting_2()
	{
		$src = [new Token(new Match(Null, ['A']), 'A', 0, 1)];
		$this->assertSame($src, Utils::flatting($src));
	}



	function testFlatting()
	{
		$src = [new Token(new Sequence(Null, [
			new Match(Null, ['<'], False),
			new Match(Null, ['A']),
			new Match(Null, ['>'], False),
			]), [new Token(new Match(Null, ['A']), 'A', 1, 2)], 1, 3)];
		$this->assertEquals([
			new Token(new Match(Null, ['A']), 'A', 1, 3)
			]
			, Utils::flatting($src));
	}
}
