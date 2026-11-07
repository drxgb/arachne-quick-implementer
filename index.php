<?php
include_once 'src/settings.php';
include_once 'src/implementer.php';
include_once 'src/input.php';

$settings = [];
$data = (object) [];
$className = '';
$package = '';
$pos = false;
$checked = false;
$classLoaded = isset($argv[1]);

// Ler configurações iniciais
loadSettings(__DIR__, $settings);

// Carregar argumentos
$pos = strrpos($argv[1], '/');

if ($classLoaded && $pos !== false)
{
	$package = substr($argv[1], 0, $pos);
	$className = substr($argv[1], $pos + 1);
}

// Escolher nome do pacote da classe
if (! empty($package))
{
	echo "Package name: {$package}" . PHP_EOL;
}
else
{
	echo 'Input package name (empty for root): ';
	$package = trim(fgets(STDIN));
}

$settings['package'] = $package;


// Inserir nome da classe
while (! $checked)
{
	if ($classLoaded)
	{
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