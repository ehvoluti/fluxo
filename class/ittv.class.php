<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ItTV extends Cadastro{
	function __construct($codtv = NULL,$codproduto = NULL){
		parent::__construct();
		$this->table = "ittv";
		$this->primarykey = "codittv";
		$this->setcodtv($codtv);
		$this->setcodproduto($codproduto);
		if($this->getcodittv() != NULL){
			$this->searchbyobject();
		}
	}

	function getcodittv(){
		return $this->fields["codittv"];
	}

	function getcodtv(){
		return $this->fields["codtv"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getdivisao(){
		return $this->fields["divisao"];
	}

	function setcodittv($value){
		$this->fields["codittv"] = value_numeric($value);
	}

	function setcodtv($value){
		$this->fields["codtv"] = value_numeric($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setdivisao($value){
		$this->fields["divisao"] = value_numeric($value);
	}
}