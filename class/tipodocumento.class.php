<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class TipoDocumento extends Cadastro{

	function __construct($key = NULL){
		parent::__construct();
		$this->table = "tipodocumento";
		$this->primarykey = "codtpdocto";
		$this->setcodtpdocto($key);
		if($this->getcodtpdocto() != NULL){
			$this->searchbyobject();
		}
	}

	function save(){
		$object = objectbytable("tipodocumento", $this->getcodtpdocto(), $this->con);
		return parent::save($object);
	}

	function getcodtpdocto(){
		return $this->fields["codtpdocto"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function gettipo(){
		return $this->fields["tipo"];
	}

	function getgerarblocok(){
		return $this->fields["gerarblocok"];
	}

	function setcodtpdocto($value){
		$this->fields["codtpdocto"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value, 40);
	}

	function settipo($value){
		$this->fields["tipo"] = value_check($value, array("E", "F", "S"));
	}

	function setgerarblocok($value){
		$this->fields["gerarblocok"] = value_string($value, 1);
	}
}