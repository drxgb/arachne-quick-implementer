<?php

function writeObjHeader(string $filename, object $params, array $settings, string $content) : void
{
	$eol = $settings['eol'];

	foreach ($params->methods as $access => $categories)
	{
		$replace = '';
		$begin = strrpos($content, "$access:");

		if ($begin !== false && count($categories) > 0)
		{
			$begin += strlen($access) + 1;
			$replace .= $eol;

			foreach ($categories as $category => $methods)
			{
				$members = [
					'inline'	=> [],
					'prototype'	=> [],
				];

				if (! empty($category))
				{
					$replace .= "\t\t" . K_COMMENT . ' ';
					$replace .= replaceCategoryName($category, $settings['replacements']);
					$replace .= $eol;
				}

				foreach ($methods as $method)
				{
					[
						'signature'	=> $signature,
						'property'	=> $property,
					] = $method;

					if (! empty($method['inline']))
					{
						$members['inline'][] = $method;
					}
					else
					{
						$members['prototype'][] = $signature;
					}
				}

				foreach ($members['prototype'] as $method)
				{
					$replace .= "\t\t" . $method . K_EOL .$eol;
				}
				if (count($members['prototype']) > 0 && count($members['inline']) > 0)
				{
					$replace .= $eol;
				}
				foreach ($members['inline'] as $method)
				{
					[
						'signature'	=> $signature,
						'property'	=> $property,
					] = $method;

					$replace .= "\t\t" . K_INLINE . ' ' . $signature . $eol;
					$replace .= "\t\t" . K_OPEN_BRACKET . $eol;

					if (isset($method['getter']))
					{
						$replace .= "\t\t\t";
						$replace .= sprintf($settings['bodies']['getter'], $property);
						$replace .= $eol;
					}
					else if (isset($method['setter']))
					{
						$param = $method['param'] ?? $property;
						$replace .= "\t\t\t";
						$replace .= sprintf($settings['bodies']['setter'], $property, $param);
						$replace .= $eol;
					}
					else
					{
						$replace .= "\t\t\t" . makeFuncBody($signature) . $eol;
					}

					$replace .= "\t\t" . K_CLOSE_BRACKET . $eol . $eol;
				}
			}

			$content = substr_replace($content, $replace, $begin, 0);
		}
	}

	writeFile($filename, $content);
}


function writeLibHeader(string $filename, object $params, array $settings, string $content) : void
{
	$eol = $settings['eol'];
	$replace = '';

	foreach ($params->methods as $categories)
	{
		foreach ($categories as $category => $methods)
		{
			if (! empty($category))
			{
				$replace .= $eol . $eol;
				$replace .= "\t\t" . K_COMMENT . ' ';
				$replace .= replaceCategoryName($category, $settings['replacements']);
			}

			foreach ($methods as $method)
			{
				[ 'signature' => $signature ] = $method;
				$name = getMethodName($signature);

				if ($name !== null)
				{
					$replace .= $eol;
					$replace .= "\t\t" . K_STATIC . ' ';
					$replace .= sprintf($settings['lib']['signature'], $name);
				}
			}
		}
	}

	$mark = $settings['lib']['mark'];
	$offset = strpos($content, $mark);
	$offset += strlen($mark);
	$content = substr_replace($content, $replace, $offset, 0);

	writeFile($filename, $content);
}


function writeObjImpl(string $filename, string $className, object $params, array $settings, string $content) : void
{
	$eol = $settings['eol'];

	if (count($params->methods) > 0)
	{
		$content .= $eol;
	}

	foreach ($params->methods as $categories)
	{
		$replace = '';

		foreach ($categories as $methods)
		{
			foreach ($methods as $method)
			{
				[ 'signature' => $signature ] = $method;

				if (empty($method['inline']))
				{
					$replace .= makeImplMethodSignature($className, $signature, $eol);
				}
			}
		}

		$content .= $replace;
	}

	writeFile($filename, $content);
}


function writeLibImpl(string $filename, string $className, object $params, array $settings, string $content) : void
{
	$eol = $settings['eol'];
	$line = makeCommentLine() . $eol;

	if (count($params->methods) > 0)
	{
		$content .= $eol;
	}

	foreach ($params->methods as $categories)
	{
		$replace = '';

		foreach ($categories as $category => $methods)
		{
			$replace .= $eol . $line;
			$replace .= K_COMMENT . " \t\t";
			$replace .= replaceCategoryName($category, $settings['replacements']) . $eol;
			$replace .= $line;

			foreach ($methods as $method)
			{
				[ 'signature' => $signature ] = $method;
				$name = sprintf($settings['lib']['signature'], getMethodName($signature));
				$replace .= makeImplMethodSignature($className, $name, $eol);
			}

			$replace .= $eol;
		}

		$content .= $replace;
	}

	writeFile($filename, $content);
}


function writeWvImpl(string $filename, object $params, array $settings, string $content) : void
{
	$eol = $settings['eol'];
	$wvSignature = $settings['wv']['signature'];
	$replace = K_OPEN_BRACKET;

	foreach ($params->methods as $access => $categories)
	{
		foreach ($categories as $category => $methods)
		{
			$replace .= $eol . "\t" . WV_COMMENT . ' ';
			$replace .= replaceCategoryName($category, $settings['replacements']);
			$replace .= $eol;

			foreach ($methods as $method)
			{
				[
					'type'		=> $type,
					'name'		=> $name,
					'params'	=> $params,
				] = $method;

				$replace .= "\t";
				$replace .= sprintf(
					$wvSignature,
					$access,
					$name,
					stringfyParams($params),
					replaceTypeName($type)
				);
				$replace .= $eol;
			}
		}
	}

	$replace .= K_CLOSE_BRACKET . $eol;
	$offset = strpos($content, K_OPEN_BRACKET);
	$content = substr_replace($content, $replace, $offset);

	writeFile($filename, $content);
}


function replaceCategoryName(string $key, array $replacements) : string
{
	$k = strtolower($key);

	if (isset($replacements[$k]))
	{
		return $replacements[$k];
	}

	return $key;
}


function replaceTypeName(string $type) : string
{
	if (str_contains($type, 'char*'))
	{
		$pos = strrpos($type, 'char*');
		$type = substr_replace($type, 'string', $pos);
	}
	if (str_contains($type, 'uint8_t'))
	{
		$pos = strrpos($type, 'uint8_t');
		$type = substr_replace($type, 'byte', $pos);
	}

	return $type;
}


function stringfyParams(array $params) : string
{
	$result = '';
	$count = count($params);

	for ($i = 0; $i < $count; ++$i)
	{
		$param = $params[$i];
		$result .= replaceTypeName($param['type']);
		$result .= ' ';
		$result .= $param['name'];

		if ($i < $count - 1)
		{
			$result .= ', ';
		}
	}

	return trim($result);
}


function writeFile(string $filename, string $content) : void
{
	if (strlen($content) > 0)
	{
		file_put_contents($filename, $content);
		echo "Written: {$filename}" . PHP_EOL;
	}
}


function makeFuncBody(string $signature) : string
{
	$result = '';
	$offset = 0;

	foreach ([ K_CONST, K_UNSIGNED ] as $keyword)
	{
		if (substr($signature, $offset, strlen($keyword)) === $keyword)
		{
			$offset += strlen($keyword) + 1;
		}
	}

	$end = strpos($signature, ' ', $offset);
	$type = trim(substr($signature, $offset, $end - $offset));

	if ($type === T_VOID)
	{
		$result = K_COMMENT . ' ';
	}
	else
	{
		$result = K_RETURN . ' ';

		if (str_ends_with($type, K_PTR) || ! array_key_exists($type, TYPES))
		{
			$result .= K_NULLPTR;
		}
		else
		{
			$result .= TYPES[$type];
		}

		$result .= K_EOL;
	}

	return $result;
}


function getMethodName(string $signature) : ?string
{
	$end = strrpos($signature, '(');

	if ($end !== false)
	{
		$offset = findPos($signature, ' ', $end, -1);

		if ($offset >= 0)
		{
			return trim(substr($signature, $offset, $end - $offset));
		}
	}

	return null;
}


function makeImplMethodSignature(string $className, string $signature, string $eol = "\n") : string
{
	$result = $eol;
	$offset = strrpos($signature, '(');
	$offset = findPos($signature, ' ', $offset, -1) + 1;
	$signature = substr_replace($signature, "{$className}::", $offset, 0);

	$result .= $signature . $eol;
	$result .= K_OPEN_BRACKET . $eol;
	$result .= "\t" . makeFuncBody($signature) . $eol;
	$result .= K_CLOSE_BRACKET . $eol;

	return $result;
}


function makeCommentLine(int $count = 32) : string
{
	return K_COMMENT . ' ' . implode('', array_fill(0, $count, '=-')) . '=';
}

?>