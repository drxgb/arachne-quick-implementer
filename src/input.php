<?php
const EXT_H = '.h';
const EXT_CPP = '.cpp';


function checkClassFiles(string $name, array &$settings) : bool
{
	makeInputPaths($settings, $name);
	$inputs = $settings['inputs'];

	if (! checkFiles($inputs))
	{
		return false;
	}

	$answer = '';

	echo "API file:\t\t{$inputs['api']}" . PHP_EOL;
	echo "Object Header file:\t\t{$inputs['obj']['header']}" . PHP_EOL;
	echo "Object Implementation file:\t{$inputs['obj']['impl']}" . PHP_EOL;
	echo "Lib Header file:\t\t{$inputs['lib']['header']}" . PHP_EOL;
	echo "Lib Implementation file:\t{$inputs['lib']['impl']}" . PHP_EOL . PHP_EOL;

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
		'base'	=> $base,
		'api'	=> $api,
		'obj'	=> $obj,
		'lib'	=> $lib,
	] = $settings['paths'];

	$settings['inputs']['api'] = $base . $api . $name . EXT_H;

	foreach ([ 'obj' => $obj, 'lib' => $lib ] as $g => $group)
	{
		$filename = (isset($group['prefix']) ? $group['prefix'] : null) . $name;
		$settings['inputs'][$g] = [
			'header'	=> $base . $group['header'] . $name . EXT_H,
			'impl' 		=> $base . $group['impl'] . $filename . EXT_CPP,
		];
	}
}


function logInvalidFile(string $path) : void
{
	echo "The file $path does not exist" . PHP_EOL;
}


function checkFiles(array $paths) : bool
{
	[
		'api'	=> $api,
		'obj'	=> $obj,
		'lib'	=> $lib,
	] = $paths;

	foreach ([ $api, ...$obj, ...$lib ] as $path)
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