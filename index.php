<?php
include_once 'src/settings.php';
include_once 'src/implementer.php';
include_once 'src/input.php';

$settings = [];
$data = (object) [];
$className = '';
$checked = false;

// Ler configurações iniciais
loadSettings(__DIR__, $settings);

// Inserir nome da classe
while (! $checked)
{
	echo 'Input class name: ';
	$className = trim(fgets(STDIN));
	$checked = checkClassFiles($className, $settings);
}

// Preparar dados
$data->settings = $settings;
$data->className = $className;

// Aplicar implementação
prepareInputFiles($data);
run($data);
?>