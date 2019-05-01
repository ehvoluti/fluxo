<?php

session_start();

$config = array(
	'host'      => 'localhost',
	'banco'     => 'hugo2',
	'usuario'   => 'postgres',
	'senha'     => 'matrix',
	'port'	    => '5432'
);

require_once('banco.php');
require_once('user.php');
require_once('utils.php');

conectar();
