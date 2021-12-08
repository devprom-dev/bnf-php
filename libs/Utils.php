<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF;

use Taco\BNF\Combinators\Sequence;
use LogicException;


class Utils
{

	/**
	 * @param array<string> $patterns
	 * @param string $src
	 * @param int $offset
	 * @param bool $checkStart
	 * @return Token|False
	 */
	static function scanPattern(Combinator $type, array $patterns, $src, $offset, $checkStart = True)
	{
		foreach ($patterns as $pattern) {
			if (preg_match($pattern, $src, $out, PREG_OFFSET_CAPTURE, $offset) && self::checkStartOffset($out[0][1], $offset, $checkStart)) {
				if (count($out) == 1) {
					if (strlen($out[0][0]) == 0) {
						throw new LogicException("The pattern '$pattern' corresponds to an empty string.");
					}
					return new Token($type, $out[0][0], $out[0][1], $out[0][1] + strlen($out[0][0]));
				}
				elseif (count($out) == 2) {
					if (strlen($out[1][0]) == 0) {
						throw new LogicException("The pattern '$pattern' corresponds to an empty string.");
					}
					return new Token($type, $out[1][0], $out[1][1], $out[1][1] + strlen($out[1][0]));
				}
				else {
					throw new LogicException("A pattern with multiple parentheses is not supported: '$pattern'.");
				}
			}
		}
		return False;
	}



	/**
	 * @param array<string, Combinator> $bank
	 * @return array<string, Combinator>
	 */
	static function addToBank(array $bank, Combinator $node)
	{
		if ($node->getName()) {
			$bank[$node->getName()] = $node;
		}
		return $bank;
	}



	/**
	 * @param array<Combinator> $xs
	 * @param int $offset
	 * @return array<string, int>
	 */
	static function buildExpected(array $xs, $offset)
	{
		$ret = [];
		foreach ($xs as $x) {
			if ($x->getName()) {
				$ret[$x->getName()] = $offset;
			}
		}
		return $ret;
	}



	/**
	 * @param array<Token> $res
	 * @return array<Token>
	 */
	static function filterCapture(array $res)
	{
		$ret = [];
		foreach ($res as $x) {
			if ($x->isCapture()) {
				$ret[] = $x;
			}
		}
		return $ret;
	}



	/**
	 * @param array<Token> $src
	 * @return array<Token>
	 */
	static function flatting(array $src)
	{
		$ret = [];
		foreach ($src as $x) {
			switch (True) {
				case $x->type instanceof Sequence && is_array($x->content) && count($x->content) === 1 && empty($x->getName()) && reset($x->content) instanceof Token:
					$item = reset($x->content);
					$item->start = $x->start;
					$item->end = $x->end;
					$ret[] = $item;
					break;
				/* To zdrcávání není úplně domyšlené...
				case $x->type instanceof Sequence && count($x->content) === 1:
					$item = reset($x->content);
					if (empty($item->getName())) {
						$item->type = $item->type->setName($x->getName());
						$item->start = $x->start;
						$item->end = $x->end;
					}
					$ret[] = $item;
					break;//*/
				default:
					$ret[] = $x;
			}
		}
		return $ret;
	}



	/**
	 * @param int $start
	 * @param int $end
	 * @param string $src
	 * @param int $offset
	 * @return array<int|false>
	 */
	static function lookupBlock($start, $end, $src, $offset)
	{
		if ($offset >= strlen($src)) {
			return [False, False];
		}
		$startIndex = strpos($src, $start, $offset);
		$endIndex = strpos($src, $end, $offset);

		if ($startIndex !== False) {
			$endIndex = self::lookupEndIndex($start, $end, $src, $startIndex + 1);
			return [$startIndex, $endIndex];
		}
		return [False, False];
	}



	/**
	 * @param int $start
	 * @param int $end
	 * @param string $src
	 * @param int $offset
	 * @return int<0, max>|false
	 */
	private static function lookupEndIndex($start, $end, $src, $offset)
	{
		$startIndex = strpos($src, $start, $offset);
		$endIndex = strpos($src, $end, $offset);
		if ($startIndex !== False && $startIndex < $endIndex) {
			if ($endIndex = self::lookupEndIndex($start, $end, $src, $startIndex + 1)) {
				$endIndex = self::lookupEndIndex($start, $end, $src, $endIndex + 1);
			}
			return $endIndex;
		}
		return $endIndex;
	}



	/**
	 * @param mixed $val
	 * @param mixed $expected
	 * @param bool $checkStart
	 * @return bool
	 */
	private static function checkStartOffset($val, $expected, $checkStart)
	{
		if ( ! $checkStart) {
			return True;
		}
		return $val === $expected;
	}

}
