<?php
include_once 'impl/reader.php';


// =====================================================================

function prepareInputFiles(object &$data) : void
{
	$files = $data->settings['inputs'];

	$data->input = (object) [];

	foreach ($files as $k => $file)
	{
		$data->input->$k = file_get_contents($file);
	}
}


function run(object &$data) : void
{
	$data->params = (object) [];
	readApi($data->params, $data->input->api);

	var_dump($data->params->methods);
}

?>