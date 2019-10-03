<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Cidade extends Cadastro{
	function __construct($key = NULL){
		parent::__construct();
		$this->newrelation("cidade","uf","estado","uf");
		$this->table = "cidade";
		$this->primarykey = "codcidade";
		$this->setcodcidade($key);
		if($this->fields[$this->primarykey] != NULL){
			$this->searchbyobject();
		}
	}

	function getcodcidade(){
		return strtoupper($this->fields["codcidade"]);
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getcodoficial(){
		return $this->fields["codoficial"];
	}

	function getuf(){
		return $this->fields["uf"];
	}

	function setcodcidade($value){
		$this->fields["codcidade"] = value_numeric($value);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value,65);
	}

	function setcodoficial($value){
		$this->fields["codoficial"] = value_numeric($value);
	}

	function setuf($value){
		$this->fields["uf"] = value_string($value,2);
	}
}
?>