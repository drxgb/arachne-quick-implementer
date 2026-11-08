<?php
// Constantes
const SETTINGS_FILE = "settings.json";
const SETTINGS_TEST = "settings.json.test";


// ===========================================================
// 		Protótipos
// ===========================================================

function loadSettings(string $path, array &$settings, bool $test = false) : void
{
	$filename = $path . DIRECTORY_SEPARATOR;
	$filename .= $test ? SETTINGS_TEST : SETTINGS_FILE;
	$file = file_get_contents($filename);
	$settings = json_decode($file, true);

	if (isset($settings['eol']))
	{
		$settings['eol'] = convertEOL($settings['eol']);
	}
	if (isset($settings['paths']))
	{
		$settings['paths']['base'] = $path . DIRECTORY_SEPARATOR . $settings['paths']['base'];
	}
}


function convertEOL(string $eol) : string
{
	switch ($eol)
	{
		case 'CR':		return "\r";
		case 'CRLF':	return "\r\n";
		default:		return "\n";
	}
}
?>