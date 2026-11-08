<?php
include_once 'constants.php';


function writeObjHeader(string $filename, object $params, array $settings, string &$content) : void
{
	$eol = $settings['eol'];

	foreach ($params->methods as $access => $categories)
	{
		$replace = '';
		$begin = strrpos($content, "$access:");

		if ($begin !== false && count($categories) > 0)
		{
			$begin += strlen($access) + 1;

			foreach ($categories as $category => $methods)
			{
				$methods = array_filter(
					$methods,
					fn (array $m) : bool => ! str_contains($content, $m['signature'])
				);

				if (count($methods))
				{
					$replace .= $eol;
				}
				else
				{
					continue;
				}

				$members = [
					'inline'	=> [],
					'prototype'	=> [],
				];

				makeCategoryComment($content, $replace, $category, $settings, $eol);

				foreach ($methods as $method)
				{
					[ 'signature' => $signature ] = $method;

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
						'doc'		=> $doc,
					] = $method;

					$replace .= "\t\t" . K_INLINE . ' ' . $signature . $eol;
					$replace .= "\t\t" . K_OPEN_BRACKET . $eol;

					if (isset($method['getter']))
					{
						$replace .= "\t\t\t";
						$replace .= sprintf($settings['bodies']['getter'], $doc['property']);
						$replace .= $eol;
					}
					else if (isset($method['setter']))
					{
						$param = $method['param'] ?? $doc['property'];
						$replace .= "\t\t\t";
						$replace .= sprintf($settings['bodies']['setter'], $doc['property'], $param);
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


function writeLibHeader(string $filename, object $params, array $settings, string &$content) : void
{
	$eol = $settings['eol'];
	$replace = '';

	foreach ($params->methods as $categories)
	{
		foreach ($categories as $category => $methods)
		{
			$methods = array_filter(
				$methods,
				fn (array $m) : bool => ! str_contains($content, $m['name'])
			);

			if (count($methods))
			{
				$replace .= $eol;
			}
			else
			{
				continue;
			}

			makeCategoryComment($content, $replace, $category, $settings, $eol);

			foreach ($methods as $method)
			{
				[ 'name' => $name ] = $method;

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


function writeBuilderHeader(string $filename, string $className, array $settings, string &$content) : void
{
	$eol = $settings['eol'];
	$end = strrpos($content, '};');

	if ($end !== false)
	{
		$signature = sprintf($settings['builder']['proto'], $className);

		if (! str_contains($content, $signature))
		{
			$begin = findPos($content, [ K_EOL, ':' ], $end, -1);
			$replace = $eol . "\t\t";
			$replace .= $signature;

			$content = substr_replace($content, $replace, $begin + 1, 0);
			writeFile($filename, $content);
		}
	}
}


function writeObjImpl(string $filename, string $className, object $params, array $settings, string &$content) : void
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
					$implSignature = makeImplMethodSignature($className, $signature, $eol);

					if (! str_contains($content, $implSignature))
					{
						$replace .= $implSignature;
					}
				}
			}
		}

		$content .= $replace;
	}

	writeFile($filename, $content);
}


function writeLibImpl(string $filename, string $className, object $params, array $settings, string &$content) : void
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
			$comment = $eol . $line;
			$comment .= K_COMMENT . " \t\t";
			$comment .= replaceCategoryName($category, $settings['replacements']) . $eol;
			$comment .= $line;

			$count = 0;

			if (! str_contains($content, $comment))
			{
				$replace .= $comment;
			}

			foreach ($methods as $method)
			{
				[ 'name' => $name ] = $method;
				$name = sprintf($settings['lib']['signature'], $name);
				$implSignature = makeImplMethodSignature($className, $name, $eol);

				if (! str_contains($content, $implSignature))
				{
					$replace .= $implSignature;
					$count++;
				}
			}

			if ($count > 0)
			{
				$replace .= $eol;
			}
		}

		$content .= $replace;
	}

	writeFile($filename, $content);
}


function writeWvImpl(string $filename, object $params, array $settings, string &$content) : void
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


function writeBuilderImpl(string $filename, string $className, array $settings, string &$content, string $wvContent) : void
{
	$eol = $settings['eol'];
	$builderClass = $settings['package'] . 'Builder';
	$signature = sprintf($settings['builder']['signature'], $builderClass, $className);

	if (! str_contains($content, $signature))
	{
		// Adicionar include
		$replace = '';
		$begin = strrpos($content, '#include "');
		$end = strpos($content, $eol, $begin);
		$include = sprintf($settings['builder']['include'], $className);

		$replace .= $eol;
		$replace .= $include;
		$content = substr_replace($content, $replace, $end, 0);

		// Adicionar chamada do método em onBuild()
		$end = strpos($content, 'return 0;');
		$begin = findPos($content, ';', $end, -1);

		$replace = $eol;
		$replace .= sprintf("\tbuild%s();", $className);
		$content = substr_replace($content, $replace, $begin + 1, 0);

		// Montar a classe
		$replace = $eol;
		$replace .= $signature . $eol;
		$replace .= K_OPEN_BRACKET . $eol;

		$lines = explode($eol, $wvContent);

		foreach ($lines as $line)
		{
			$line = trim($line);

			if (empty($line) || in_array($line, [ K_OPEN_BRACKET, K_CLOSE_BRACKET, WV_PREFIX ]))
			{
				continue;
			}

			if (str_starts_with($line, WV_COMMENT))
			{
				$commentLine = makeCommentLine(18);
				$category = K_COMMENT . " \t\t" . trim(substr($line, 1));

				$replace .= "\t" . $commentLine . $eol;
				$replace .= "\t" . $category . $eol;
				$replace .= "\t" . $commentLine . $eol . $eol;
			}
			else
			{
				$line = str_replace(K_EOL, '', $line);
				$words = [];
				$statement = [];
				$begin = 0;
				$replace .= "\t" . K_COMMENT . ' ' . $line . $eol;

				while ($begin < strlen($line))
				{
					$endChar = ' ';

					for ($end = $begin; $end < strlen($line) && $line[$end] !== $endChar; ++$end)
					{
						if ($line[$end] === '(')
						{
							$endChar = ')';
						}
					}

					$word = trim(substr($line, $begin, $end + 1 - $begin));

					if (! empty($word))
					{
						$words[] = $word;
					}

					$begin = $end + 1;
				}

				if (str_contains($line, K_CLASS))
				{
					while (! empty($words))
					{
						$word = array_shift($words);

						switch ($word)
						{
							case 'final':
							case 'abstract':
							{
								$statement['rules'][] = '.set' . ucfirst($word) . '()';
								break;
							}

							case 'class':
							{
								$statement['class'] = findNameConst($settings, array_shift($words));
								break;
							}

							case 'extends':
							{
								$type = findNameConst($settings, array_shift($words));
								$statement['inherits'][] = ".extend({$type})";
								break;
							}
						}
					}

					$replace .= "\t" . "createClass({$statement['class']})" . $eol;

					foreach ($statement['rules'] ?? [] as $rule)
					{
						$replace .= "\t\t" . $rule . $eol;
					}
					foreach ($statement['inherits'] ?? [] as $inherit)
					{
						$replace .= "\t\t" . $inherit . $eol;
					}

					$replace .= "\t\t.end();" . $eol;
				}
				else if (str_contains($line, WV_FUNCTION))
				{
					while (! empty($words))
					{
						$word = array_shift($words);

						switch ($word)
						{
							case 'private':
							case 'protected':
							{
								$word = strtoupper($word);
								$statement['access'] = ".setAccessModifier({$word})";
								break;
							}

							case 'abstract':
							case 'const':
							case 'static':
							case 'final':
							{
								$statement['rules'][] = '.set' . ucfirst($word) . '()';
								break;
							}

							case 'public':
							case 'native':
							case 'function':
							case ':':
							{
								break;
							}

							default:
							{
								if (isset($statement['name']))
								{
									$returnType = replaceWeaverType($word);

									if ($returnType === 'void' || in_array($returnType, PRIMITIVE_TYPES))
									{
										$returnType = strtoupper($returnType);
										$statement['method'] = '.buildMethod(';
										$statement['method'] .= "Weaver::VTYPE_{$returnType}, ";
										$statement['method'] .= "&{$className}::{$statement['name']}";
										$statement['method'] .= ')';
									}
									else
									{
										$statement['return'] = '.returnTypeName(';
										$statement['return'] .= findNameConst($settings, $returnType);
										$statement['return'] .= ')';
										$statement['method'] = '.buildMethod(';
										$statement['method'] .= 'Weaver::VTYPE_';
										$statement['method'] .= isEnumType($settings, $returnType) ? 'ENUM_TYPE' : 'INSTANCE';
										$statement['method'] .= ', ';
										$statement['method'] .= "&{$className}::{$statement['name']}";
										$statement['method'] .= ')';
									}
								}
								else
								{
									$begin = strpos($word, '(');
									$name = substr($word, 0, $begin);
									$rawParams = str_replace([ '(', ')' ], '', substr($word, $begin));
									$params = empty($rawParams) ? [] : explode(',', trim($rawParams));

									foreach ($params as $param)
									{
										$parts = explode(' ', trim($param));
										$const = false;
										$type = null;
										$var = null;
										$default = null;

										while (! empty($parts))
										{
											$part = array_shift($parts);

											if ($part === K_CONST)
											{
												$const = true;
											}
											else if ($part === '=')
											{
												$default = array_shift($parts);
											}
											else if (is_null($type))
											{
												$type = replaceWeaverType($part);
											}
											else
											{
												$var = findNameConst($settings, $part);
											}
										}

										$paramResult = '.addParam(';
										$paramResult .= $var . ', ';

										if (isPrimitiveType($type))
										{
											$paramResult .= 'Weaver::VTYPE_' . strtoupper($type);
										}
										else
										{
											$paramResult .= 'Weaver::VTYPE_';
											$paramResult .= isEnumType($settings, $type) ? 'ENUM_TYPE' : 'INSTANCE';
											$paramResult .= ', ' . $settings['keywords'][$type];
										}

										if ($default)
										{
											if ($default === 'null')
											{
												$paramResult .= ", new Weaver::Null";
											}
											else if (preg_match('/\"*.\"/i', $default))
											{
												$paramResult .= ", new Weaver::String({$default})";
											}
											else if (preg_match('/\'*.\'/i', $default))
											{
												$paramResult .= ", new Weaver::Character({$default})";
											}
											else if (preg_match('/\d*\.\d+f/i', $default))
											{
												$paramResult .= ", new Weaver::Float({$default})";
											}
											else if (preg_match('/\d*\.\d+/i', $default))
											{
												$paramResult .= ", new Weaver::Double({$default})";
											}
											else if (preg_match('/\d+/i', $default))
											{
												$paramResult .= ", new Weaver::{$type}({$default})";
											}
											else
											{
												$paramResult .= ", new Weaver::Instance";
											}
										}
										if ($const)
										{
											$paramResult .= ', true';
										}

										$paramResult .= ')';
										$statement['params'][] = $paramResult;
									}

									$statement['name'] = $name;
									$statement['begin'] = '.begin(';
									$statement['begin'] .= findNameConst($settings, $name);
									$statement['begin'] .= ')';
								}
							}
						}
					}

					$replace .= "\tmemberBuilder()" . $eol;
					$replace .= "\t\t{$statement['begin']}" . $eol;

					foreach ($statement['rules'] ?? [] as $rule)
					{
						$replace .= "\t\t" . $rule . $eol;
					}
					foreach ($statement['params'] ?? [] as $param)
					{
						$replace .= "\t\t" . $param . $eol;
					}
					if (isset($statement['return']))
					{
						$replace .= "\t\t{$statement['return']}" . $eol;
					}

					$replace .= "\t\t{$statement['method']}" . $eol;
					$replace .= "\t\t.end();" . $eol;
				}

				$replace .= $eol;
			}
		}

		$replace .= "\tprototypeBuilder().end();" . $eol;
		$replace .= K_CLOSE_BRACKET . $eol;

		$content .= $replace;
		writeFile($filename, $content);
	}
}


function findNameConst(array $settings, string $name) : string
{
	if (isset($settings['keywords']))
	{
		$keywords = $settings['keywords'];

		if (isset($keywords[$name]))
		{
			return $keywords[$name];
		}
	}

	return "\"{$name}\"";
}


function isPrimitiveType(string $type) : bool
{
	return $type === 'void' || in_array($type, PRIMITIVE_TYPES);
}


function isEnumType(array $settings, string $type) : bool
{
	if (isset($settings['keywords']))
	{
		if (! empty($type) && in_array($type, $settings['keywords']));
		{
			$key = $settings['keywords'][$type];
			return str_starts_with($key, 'E_');
		}
	}

	return false;
}


function replaceWeaverType(string $type) : string
{
	if (isset(PRIMITIVE_TYPES[$type]))
	{
		return PRIMITIVE_TYPES[$type];
	}

	return $type;
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

	return str_replace(K_PTR, '', $type);
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


function makeCategoryComment(
	string $content,
	string &$replace,
	string $category,
	array $settings,
	string $eol
) : void
{
	if (! empty($category))
	{
		$comment = "\t\t" . K_COMMENT . ' ';
		$comment .= replaceCategoryName($category, $settings['replacements']);

		if (! str_contains($content, $comment))
		{
			$replace .= $comment . $eol;
		}
	}
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