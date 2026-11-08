<?php
include 'constants.php';


function readApi(object &$params, string $content) : void
{
	$params->methods = [];

	$accesses = [
		'public'	=> K_PUBLIC,
		'private'	=> K_PRIVATE,
		'protected'	=> K_PROTECTED,
	];

	$offset = strpos($content, K_CLASS);
	$offset = strpos($content, K_OPEN_BRACKET, $offset);
	$cbVirtual = strlen(K_VIRTUAL);
	$begin = $offset;

	foreach ($accesses as $a => $access)
	{
		$begin = strrpos($content, $access);
		$categoryPos = fetchCategoryPositions($content, $begin);
		$pos = reset($categoryPos);

		while ($begin !== false)
		{
			$begin = strpos($content, K_VIRTUAL, $begin);

			if ($begin !== false)
			{
				$begin += $cbVirtual;
				$end = strpos($content, K_ZERO, $begin);
				[ 'end' => $e ] = $pos;

				if ($begin > $e)
				{
					$pos = next($categoryPos);
				}

				[ 'category' => $c ] = $pos;
				$ck = strtolower($c);

				$method = [];
				$method['signature'] = trim(substr($content, $begin, $end - $begin));
				$method['doc']['property'] = findDocParameter($content, 'property', $begin);
				$method['doc']['param'] = findDocParameter($content, 'param', $begin);

				makeMethodStructure($method);

				if ($ck === 'getters')
				{
					$method['getter'] = true;
					$method['inline'] = $method['doc']['property'] !== null;
				}
				else if ($ck === 'setters')
				{
					$method['setter'] = true;
					$method['inline'] = $method['doc']['property'] !== null;
				}

				$params->methods[$a][$c][] = $method;
			}
		}
	}
}


function fetchCategoryPositions(string $content, int $offset) : array
{
	$keys = [ 'getters', 'setters', 'predicates', 'native' ];
	$begin = $offset;
	$end = $offset;
	$line = null;
	$categories = [
		[ 'category' => null, 'end' => 0 ],
	];

	while ($begin !== false)
	{
		$begin = strpos($content, K_COMMENT, $end);

		if ($begin !== false)
		{
			$begin += 2;
			$end = strpos($content, "\n", $begin);
			$line = trim(substr($content, $begin, $end - $begin), " \t\r\n");

			if (in_array(strtolower($line), $keys))
			{
				$categories[] = [
					'category'	=> $line,
					'end'		=> $begin,
				];
			}
		}
	}

	for ($i = 0; $i < count($categories) - 1; ++$i)
	{
		$categories[$i]['end'] = $categories[$i + 1]['end'];
	}

	$categories[array_key_last($categories)]['end'] = strlen($content);
	usort(
		$categories,
		fn (array $a, array $b) : int => $a['end'] < $b['end'] ? -1 : 1
	);

	return $categories;
}


function findDocParameter(string $content, string $key, int $offset) : ?string
{
	$param = null;

	if ($offset > 0)
	{
		$begin = findPos($content, [ ':', K_EOL, K_OPEN_BRACKET ], $offset, -1);

		if ($begin > 0)
		{
			$doc = trim(substr($content, $begin, $offset - $begin), " \t\r\n");

			if (! empty($doc))
			{
				$begin = stripos($doc, "@$key");

				if ($begin !== false)
				{
					$begin += strlen("@$key ");
					$end = findPos($doc, [ ' ', "\t", "\r", "\n" ], $begin);
					$param = trim(substr($doc, $begin, $end - $begin), " \t\r\n");
				}
			}
		}
	}

	return $param;
}


function makeMethodStructure(array &$method) : void
{
	if (isset($method['signature']))
	{
		$params = [];
		$name = '';
		$type = '';

		$signature = $method['signature'];
		$begin = strrpos($signature, '(');
		$end = strpos($signature, ')', $begin);
		$rawParams = explode(',', str_replace([ '(', ')' ], '', substr($signature, $begin, $end - $begin)));

		foreach ($rawParams as $param)
		{
			$param = trim($param);
			$pos = strrpos($param, ' ');
			$pos = $pos === false ? 0 : $pos;
			$params[] = [
				'type'	=> trim(substr($param, 0, $pos)),
				'name'	=> trim(substr($param, $pos + 1)),
			];
		}

		$end = $begin;
		$begin = findPos($signature, ' ', $end, -1);
		$name = trim(substr($signature, $begin, $end - $begin));

		$end = $begin;
		$begin = findPos($signature, ' ', $end - 1, -1);
		$type = trim(substr($signature, $begin, $end - $begin));

		$method += compact('type', 'name', 'params');
	}
}


function findPos(string $content, array|string $query, int $offset, int $step = 1) : int
{
	$pos = $offset;
	$len = strlen($content);

	if (is_string($query))
	{
		$query = [ $query ];
	}

	while ($pos > 0 && $pos < $len)
	{
		$ch = $content[$pos];

		if (in_array($ch, $query))
		{
			break;
		}

		$pos += $step;
	}

	return $pos;
}

?>