<?php
include 'constants.php';


function readApi(object &$params, string $content) : void
{
	$params->api = (object) [];
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
				$params->methods[$a][$c][] = [
					'signature'	=> trim(substr($content, $begin, $end - $begin)),
					'property'	=> findDocParameter($content, 'property', $begin),
				];
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
		$begin = $offset;

		while ($begin > 0)
		{
			--$begin;
			$ch = $content[$begin];

			if (in_array($ch, [ ':', K_EOL, K_OPEN_BRACKET ]))
			{
				break;
			}
		}

		if ($begin > 0)
		{
			$doc = trim(substr($content, $begin, $offset - $begin), " \t\r\n");

			if (! empty($doc))
			{
				$begin = stripos($doc, "@$key");

				if ($begin !== false)
				{
					$begin += strlen("@$key ");
					$end = strpos($doc, "\n", $begin);
					$param = trim(substr($doc, $begin, $end - $begin), " \t\r\n");
				}
			}
		}
	}

	return $param;
}

?>