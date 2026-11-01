<?php
// Constantes
const SETTINGS_FILE = "settings.json";


// ===========================================================
// 		Protótipos
// ===========================================================

function loadSettings(string $path, array &$settings) : void
{
	$file = file_get_contents($path . DIRECTORY_SEPARATOR . SETTINGS_FILE);
	$settings = json_decode($file, true);

	if (isset($settings['eol']))
	{
		$settings['eol'] = convertEOL($settings['eol']);
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