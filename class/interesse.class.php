<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Interesse extends Cadastro{
	function __construct($codinteresse = NULL){
		parent::__construct();
		$this->table = "interesse";
		$this->primarykey = "codinteresse";
		$this->setcodinteresse($codinteresse);
		if($this->getcodinteresse() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodinteresse(){
		return $this->fields["codinteresse"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function setcodinteresse($value){
		$this->fields["codinteresse"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}
}
?>