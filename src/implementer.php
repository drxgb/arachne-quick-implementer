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

	writeObjHeader($files['obj']['header'], $params, $settings, $obj->header);
	writeLibHeader($files['lib']['header'], $params, $settings, $lib->header);
	writeObjImpl($files['obj']['impl'], $className, $params, $settings, $obj->impl);
	writeLibImpl($files['lib']['impl'], $className, $params, $settings, $lib->impl);
	writeWvImpl($files['wv'], $params, $settings, $wv);
	// TODO: Escrever no construtor de classes Weaver
}

?>