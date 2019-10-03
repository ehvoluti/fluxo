<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Moeda extends Cadastro{
	function __construct($key = NULL){
		parent::__construct();
		$this->table = "moeda";
		$this->primarykey = "codmoeda";
		$this->setcodmoeda($key);
		if($this->fields[$this->primarykey] != NULL){
			$this->searchbyobject();
		}
	}
	
	function getcodmoeda(){
		return $this->fields["codmoeda"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getsimbolo(){
		return $this->fields["simbolo"];
	}

	function getcodoficial(){
		return $this->fields["codoficial"];
	}

	function setcodmoeda($value){
		$this->fields["codmoeda"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,30);
	}
	
	function setsimbolo($value){
		$this->fields["simbolo"] = value_string($value,5);
	}
	
	function setcodoficial($value){
		$this->fields["codoficial"] = value_string($value,5);
	}
}
?>