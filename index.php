<?php
include_once 'src/settings.php';
include_once 'src/implementer.php';
include_once 'src/input.php';

$settings = [];
$data = (object) [];
$className = '';
$checked = false;
$classLoaded = isset($argv[1]);

// Ler configurações iniciais
loadSettings(__DIR__, $settings);

// Inserir nome da classe
while (! $checked)
{
	if ($classLoaded)
	{
		$className = $argv[1];
		echo "Class name: {$className}" . PHP_EOL . PHP_EOL;
	}
	else
	{
		echo 'Input class name: ';
		$className = trim(fgets(STDIN));
	}

	if (! empty($className))
	{
		$checked = checkClassFiles($className, $settings);

		if ($classLoaded && ! $checked)
		{
			exit(0);
		}
	}
}

// Preparar dados
$data->settings = $settings;
$data->className = $className;

// Aplicar implementação
prepareInputFiles($data);
read($data);
write($data);
?>