<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Regiao extends Cadastro{
	function __construct($key = NULL){
		parent::__construct();
		$this->table = "regiao";
		$this->primarykey = "codregiao";
		$this->setcodregiao($key);
		if($this->fields[$this->primarykey] != NULL){
			$this->searchbyobject();
		}
	}

	function getcodregiao(){
		return $this->fields["codregiao"];
	}

	function getnome(){
		return $this->fields["nome"];
	}
	
	function getcep(){
		return $this->fields["cep"];
	}

	function setcodregiao($value){
		$this->fields["codregiao"] = value_numeric($value);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value,40);
	}

	function setcep($value){
		$this->fields["cep"] = value_string($value,9);
	}
}
?>