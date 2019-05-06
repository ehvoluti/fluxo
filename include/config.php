<?php

session_start();

$config = array(
	'host'      => 'localhost',
	'banco'     => 'hugo',
	'usuario'   => 'postgres',
	'senha'     => '@Matrix12',
	'port'	    => '5432'
);

require_once('banco.php');
require_once('user.php');
require_once('utils.php');

conectar();
