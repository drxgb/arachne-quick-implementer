<?php
include_once 'impl/reader.php';
include_once 'impl/writer.php';


// =====================================================================

function prepareInputFiles(object &$data) : void
{
	$files = $data->settings['inputs'];

	$data->input = (object) [];
	$data->input->api = file_get_contents($files['api']);

	foreach (array_slice($files, 1) as $f => $subfiles)
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

	writeObjHeader($files['obj']['header'], $params, $settings, $obj->header);
	writeLibHeader($files['lib']['header'], $params, $settings, $lib->header);
	writeObjImpl($files['obj']['impl'], $className, $params, $settings, $obj->impl);
	writeLibImpl($files['lib']['impl'], $className, $params, $settings, $lib->impl);
}

?>