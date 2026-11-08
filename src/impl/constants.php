<?php
const K_CLASS = 'class';

const K_OPEN_BRACKET = '{';
const K_CLOSE_BRACKET = '}';

const K_PUBLIC = 'public:';
const K_PRIVATE = 'private: ';
const K_PROTECTED = 'protected:';

const K_VIRTUAL = 'virtual';
const K_INLINE = 'inline';
const K_STATIC = 'static';
const K_CONST = 'const';
const K_UNSIGNED = 'unsigned';

const K_RETURN = 'return';
const K_NULLPTR = 'nullptr';

const K_ZERO = '= 0;';
const K_EOL = ';';
const K_COMMENT = '//';
const K_PTR = '*';

const T_VOID = 'void';

const WV_PREFIX = '@';
const WV_COMMENT = '#';
const WV_FUNCTION = 'function';

const TYPES = [
	'bool'		=> 'false',
	'char'		=> '\0',
	'double'	=> '0.0',
	'float'		=> '0.0f',
	'int'		=> '0',
	'long'		=> '0',
	'short'		=> '0',
];

const PRIMITIVE_TYPES = [
	'bool'			=> 'Boolean',
	'byte'			=> 'Byte',
	'char'			=> 'Character',
	'double'		=> 'Double',
	'float'			=> 'Float',
	'int'			=> 'Integer',
	'short'			=> 'Short',
	'string'		=> 'String',
];
?>