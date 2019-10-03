<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class EstabelecimentoIEST extends Cadastro{
	function __construct($codestabelec = NULL,$codadminist = NULL){
		parent::__construct();
		$this->table = "estabelecimentoiest";
		$this->primarykey = "codestabeleciest";
		$this->setcodestabelec($codestabelec);
		if($this->getcodestabelec() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodestabeleciest(){
		return $this->fields["codestabeleciest"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getuf(){
		return $this->fields["uf"];
	}

	function getiest(){
		return $this->fields["iest"];
	}

	function setcodestabeleciest($value){
		$this->fields["codestabeleciest"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setuf($value){
		$this->fields["uf"] = value_string($value, 2);
	}

	function setiest($value){
		$this->fields["iest"] = value_string($value, 20);
	}
}

