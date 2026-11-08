<?php
const EXT_H = '.h';
const EXT_CPP = '.cpp';
const EXT_WV = '.wv';


function checkClassFiles(string $name, array &$settings) : bool
{
	makeInputPaths($settings, $name);
	$inputs = $settings['inputs'];

	if (! checkFiles($inputs))
	{
		return false;
	}

	$answer = '';

	echo "API file:\t\t\t{$inputs['api']}" . PHP_EOL;

	if (isset($inputs['keywords']))
	{
		echo "Keyword list file: \t\t{$inputs['keywords']}" . PHP_EOL;
	}

	echo "Object Header file:\t\t{$inputs['obj']['header']}" . PHP_EOL;
	echo "Object Implementation file:\t{$inputs['obj']['impl']}" . PHP_EOL;
	echo "Lib Header file:\t\t{$inputs['lib']['header']}" . PHP_EOL;
	echo "Lib Implementation file:\t{$inputs['lib']['impl']}" . PHP_EOL;
	echo "Weaver file:\t\t\t{$inputs['wv']}" . PHP_EOL;
	echo "Builder Header file:\t\t{$inputs['builder']['header']}" . PHP_EOL;
	echo "Builder Implementation file:\t{$inputs['builder']['impl']}" . PHP_EOL . PHP_EOL;

	while (! in_array($answer, [ 'y', 'n' ]))
	{
		echo 'Is that OK? (Y/N) ';
		$answer = strtolower(trim(fgets(STDIN)));
	}

	return $answer === 'y';
}


function makeInputPaths(array &$settings, string $name) : void
{
	[
		'base'		=> $base,
		'api'		=> $api,
		'keywords'	=> $keywords,
		'obj'		=> $obj,
		'lib'		=> $lib,
		'wv'		=> $wv,
		'builder'	=> $builder,
	] = $settings['paths'];

	$base = realpath($base) . DIRECTORY_SEPARATOR;
	$settings['inputs']['api'] = $base . $api . $name . EXT_H;

	$keywordsFile = $base . $keywords . 'keywords' . EXT_H;

	if (is_file($keywordsFile))
	{
		$settings['inputs']['keywords'] = $keywordsFile;
	}

	foreach ([ 'obj' => $obj, 'lib' => $lib ] as $g => $group)
	{
		$filename = (isset($group['prefix']) ? $group['prefix'] : null) . $name;
		$settings['inputs'][$g] = [
			'header'	=> $base . $group['header'] . $name . EXT_H,
			'impl' 		=> $base . $group['impl'] . $filename . EXT_CPP,
		];
	}

	$package = ! empty($settings['package']) ? "{$settings['package']}/" : '';
	$builderFile = $base . $builder . $settings['package'] . 'Builder';

	$settings['inputs']['wv'] = $base . $wv . $package . $name . EXT_WV;
	$settings['inputs']['builder'] = [
		'header'	=> $builderFile . EXT_H,
		'impl'		=> $builderFile . EXT_CPP,
	];
}


function logInvalidFile(string $path) : void
{
	echo "The file $path does not exist" . PHP_EOL;
}


function checkFiles(array $paths) : bool
{
	[
		'api'		=> $api,
		'obj'		=> $obj,
		'lib'		=> $lib,
		'wv'		=> $wv,
		'builder'	=> $builder,
	] = $paths;

	foreach ([ $api, ...$obj, ...$lib, $wv, ...$builder ] as $path)
	{
		if (! is_file($path))
		{
			logInvalidFile($path);
			return false;
		}
	}

	return true;
}

?>