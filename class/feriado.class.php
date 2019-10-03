<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Feriado extends Cadastro{

	function __construct($mes = NULL, $dia = NULL){
		parent::__construct();
		$this->table = "feriado";
		$this->primarykey = array("mes", "dia");
		$this->setmes($mes);
		$this->setdia($dia);
		if($this->getmes() != NULL && $this->getdia() != NULL){
			$this->searchbyobject();
		}
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getmes(){
		return $this->fields["mes"];
	}

	function getdia(){
		return $this->fields["dia"];
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value, 30);
	}

	function setmes($value){
		$this->fields["mes"] = value_numeric($value);
	}

	function setdia($value){
		$this->fields["dia"] = value_numeric($value);
	}

}