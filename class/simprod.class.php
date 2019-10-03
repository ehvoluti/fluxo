<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class SimProd extends Cadastro{
	function __construct($key = NULL){
		parent::__construct();
		$this->table = "simprod";
		$this->primarykey = "codsimilar";
		$this->setcodsimilar($key);
		if($this->fields[$this->primarykey] != NULL){
			$this->searchbyobject();
		}
	}

	function getcodsimilar(){
		return $this->fields["codsimilar"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getimpdescricao(){
		return $this->fields["impdescricao"];
	}

	function setcodsimilar($value){
		$this->fields["codsimilar"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,40);
	}

	function setimpdescricao($value){
		$this->fields["impdescricao"] = value_string($value,1);
	}
}
?>