<?php
include_once 'impl/reader.php';
include_once 'impl/writer.php';


// =====================================================================

function prepareInputFiles(object &$data) : void
{
	$files = $data->settings['inputs'];
	$protoFiles = array_filter($files, fn (array|string $f) : bool => is_string($f));
	$cppFiles = array_filter($files, fn (array|string $f) : bool => is_array($f));

	$data->input = (object) [];

	foreach ($protoFiles as $f => $file)
	{
		$data->input->$f = file_get_contents($file);
	}
	foreach ($cppFiles as $f => $subfiles)
	{
		$data->input->$f = (object) [];

		foreach ($subfiles as $s => $subfile)
		{
			$data->input->$f->$s = file_get_contents($subfile);
		}
	}

	$data->settings['keywords'] = makeKeywordList($data->input->keywords, $data->settings['eol']);
}


function makeKeywordList(string $content, string $eol = "\n") : array
{
	$keywords = [];
	$lines = array_map(
		function (string $line) : array
		{
			$pattern = '/constexpr const char\*\s+([_A-Z]+)\s*=\s*\"([_A-Za-z0-9]+)\";/m';
			$result = preg_match_all($pattern, $line, $matches);

			if ($result === false)
			{
				return [];
			}

			return [
				'name'	=> $matches[1][0],
				'key'	=> $matches[2][0],
			];

		},
		array_filter(
			explode($eol, $content),
			fn (string $l) : bool => str_starts_with(ltrim($l), 'constexpr const char*'))
	);

	foreach ($lines as $line)
	{
		[ 'key' => $key, 'name' => $name ] = $line;
		$keywords[$key] = $name;
	}

	return $keywords;
}


function read(object &$data) : void
{
	$data->params = (object) [];
	readApi($data->params, $data->input->api);
}


function write(object $data) : void
{
	$files = $data->settings['inputs'];
	$className = $data->className;
	$params = $data->params;
	$settings = $data->settings;
	$obj = $data->input->obj;
	$lib = $data->input->lib;
	$wv = $data->input->wv;
	$builder = $data->input->builder;

	writeObjHeader($files['obj']['header'], $params, $settings, $obj->header);
	writeLibHeader($files['lib']['header'], $params, $settings, $lib->header);
	writeObjImpl($files['obj']['impl'], $className, $params, $settings, $obj->impl);
	writeLibImpl($files['lib']['impl'], $className, $params, $settings, $lib->impl);
	writeWvImpl($files['wv'], $params, $settings, $wv);
	writeBuilderHeader($files['builder']['header'], $className, $settings, $builder->header);
	writeBuilderImpl($files['builder']['impl'], $className, $settings, $builder->impl, $wv);
}

?>