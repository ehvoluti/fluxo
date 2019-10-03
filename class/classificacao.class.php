<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Classificacao extends Cadastro{
	function __construct($key = NULL){
		parent::__construct();
		$this->table = "classificacao";
		$this->primarykey = "codclassif";
		$this->setcodclassif($key);
		if($this->fields[$this->primarykey] != NULL){
			$this->searchbyobject();
		}
	}

	function getcodclassif(){
		return $this->fields["codclassif"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getpcontas(){
		return $this->fields["pcontas"];
	}

	function getcodgrcta(){
		return $this->fields["codgrcta"];
	}

	function gettipo(){
		return $this->fields["tipo"];
	}

	function setcodclassif($value){
		$this->fields["codclassif"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function setpcontas($value){
		$this->fields["pcontas"] = value_string($value,1);
	}

	function setcodgrcta($value){
		$this->fields["codgrcta"] = value_numeric($value);
	}

	function settipo($value){
		$this->fields["tipo"] = value_string($value,1);
	}
}
?>