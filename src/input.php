<?php
const EXT_H = '.h';
const EXT_CPP = '.cpp';


function checkClassFiles(string $name, array &$settings) : bool
{
	makeInputPaths($settings, $name);

	[
		'api'			=> $api,
		'header'		=> $header,
		'impl'			=> $impl,
	] = $settings['inputs'];

	if (! is_file($api))
	{
		logInvalidFile($api);
		return false;
	}
	if (! is_file($header))
	{
		logInvalidFile($header);
		return false;
	}
	if (! is_file($impl))
	{
		logInvalidFile($impl);
		return false;
	}

	$answer = '';

	echo "API file:\t\t$api" . PHP_EOL;
	echo "Header file:\t\t$header" . PHP_EOL;
	echo "Implementation file:\t$impl" . PHP_EOL . PHP_EOL;

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
		'base'			=> $base,
		'api'			=> $api,
		'header'		=> $header,
		'impl'			=> $impl,
	] = $settings['paths'];

	$settings['inputs'] = [
		'api'		=> $base . $api . $name . EXT_H,
		'header'	=> $base . $header . $name . EXT_H,
		'impl' 		=> $base . $impl . $name . EXT_CPP,
	];
}


function logInvalidFile(string $path) : void
{
	echo "The file $path does not exist" . PHP_EOL;
}

?>