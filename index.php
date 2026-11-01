<?php

include_once 'src/settings.php';
include_once 'src/implementer.php';
include_once 'src/input.php';


// ====================================================================

// Dados
$settings = [];
$className = '';
$checked = false;


// ====================================================================

//* Ler configurações iniciais
loadSettings(__DIR__, $settings);

//* Inserir nome da classe
while (! $checked)
{
	echo 'Input class name: ';

	$className = trim(fgets(STDIN));
	$checked = checkClassFiles($className, $settings);
}

//* Aplicar implementação

?>