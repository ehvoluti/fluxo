<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ClienteEstab extends Cadastro{

	function __construct($codestabelec = NULL, $codcliente = NULL){
		parent::__construct();
		$this->table = "clienteestab";
		$this->primarykey = array("codestabelec", "codcliente");
		$this->setcodestabelec($codestabelec);
		$this->setcodcliente($codcliente);
		if(!is_null($this->getcodestabelec()) && !is_null($this->getcodcliente())){
			$this->searchbyobject();
		}
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodcliente(){
		return $this->fields["codcliente"];
	}

	function getsincpdv(){
		return $this->fields["sincpdv"];
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodcliente($value){
		$this->fields["codcliente"] = value_numeric($value);
	}

	function setsincpdv($value){
		$this->fields["sincpdv"] = value_string($value, 1);
	}

}